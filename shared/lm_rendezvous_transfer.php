<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** Finite-horizon lunar rendezvous study with Moon gravity. Does not command burns. */
function moonSimRendezvousTransferSearch(array $lmP,array $lmV,array $cmP,array $cmV,
 array $moonP,array $moonV,float $mu,float $moonRadius,float $maxDv=120.0):array{
 if($mu<=0||$moonRadius<=0||$maxDv<=0)throw new InvalidArgumentException('Invalid transfer search');
 $lmR=moonSimRelativeVector($lmP,$moonP);$cmR=moonSimRelativeVector($cmP,$moonP);
 $lmVel=moonSimRelativeVector($lmV,$moonV);$cmVel=moonSimRelativeVector($cmV,$moonV);
 $r=moonSimVectorMagnitude($lmR);$rc=moonSimVectorMagnitude($cmR);
 if($r<=$moonRadius+10000||$rc<=$moonRadius+10000)throw new RuntimeException('Transfer requires safe lunar orbit');
 $radial=array_map(static fn($x)=>$x/$r,$lmR);
 $h=moonSimVectorCross($lmR,$lmVel);$hm=moonSimVectorMagnitude($h);
 if($hm<1e-7)throw new RuntimeException('Undefined transfer plane');
 $normal=array_map(static fn($x)=>$x/$hm,$h);
 $tangent=moonSimVectorCross($normal,$radial);
 $best=null;$sampled=0;
 foreach([0.0,0.25,0.5,1.0] as $fraction)
 foreach([-1.0,1.0] as $sign)
 foreach([$tangent,$normal,$radial] as $axis){
  $dv=[];for($j=0;$j<3;$j++)$dv[$j]=$sign*$fraction*$maxDv*$axis[$j];
  $p=$lmR;$v=$lmVel;for($j=0;$j<3;$j++)$v[$j]+=$dv[$j];
  $cp=$cmR;$cv=$cmVel;$closest=INF;$at=0.0;$contact=false;$closestRelativeSpeed=INF;
  $duration=18000.0;$step=10.0;
  for($time=0.0;$time<$duration;$time+=$step){
   $pr=moonSimVectorMagnitude($p);$cr=moonSimVectorMagnitude($cp);
   if(min($pr,$cr)<=$moonRadius){$contact=true;break;}
   $a=[];$b=[];
   for($j=0;$j<3;$j++){$a[$j]=-$mu*$p[$j]/($pr**3);$b[$j]=-$mu*$cp[$j]/($cr**3);}
   for($j=0;$j<3;$j++){
    $v[$j]+=$a[$j]*$step;$p[$j]+=$v[$j]*$step;
    $cv[$j]+=$b[$j]*$step;$cp[$j]+=$cv[$j]*$step;
   }
   $range=moonSimVectorMagnitude(moonSimRelativeVector($p,$cp));
   if($range<$closest){$closest=$range;$at=$time+$step;$closestRelativeSpeed=moonSimVectorMagnitude(moonSimRelativeVector($v,$cv));}
  }
  $sampled++;
  if($contact)continue;
  // Penalize hazardous high-speed intercepts; these are not dockable.
  $score=$closest+max(0.0,$closestRelativeSpeed-5.0)*10000.0;
  if($best===null||$score<$best['candidate_score']){
   $best=['impulsive_candidate_delta_v_xyz'=>$dv,'candidate_delta_v_m_s'=>$fraction*$maxDv,
    'closest_range_m'=>$closest,'closest_time_s'=>$at,
    'relative_speed_at_closest_m_s'=>$closestRelativeSpeed,
    'candidate_score'=>$score,
    'proximity_caution'=>$closest<10000.0&&$closestRelativeSpeed>5.0];
  }
 }
 return ['best'=>$best,'candidates_evaluated'=>$sampled,
  'candidate_usable_for_approach'=>$best!==null&&$best['closest_range_m']<=10000.0&&$best['relative_speed_at_closest_m_s']<=5.0,
  'correction_at_encounter_xyz'=>$best===null?null:(static function(array $b)use($lmVel,$cmVel):array{
    // Current velocity difference is not the later encounter velocity;
    // avoid presenting a fictitious docking-burn vector here.
    return ['available'=>false,'reason'=>'Recompute at the predicted encounter from propagated spacecraft states'];
  })($best),
  'warning'=>'Moon-only impulsive transfer study. No docking command, proximity approach controls, or verified collision-safe terminal rendezvous.'];
}

/** Finite APS burn candidate evaluation against a moving CSM in Moon gravity.
 * Local lunar frame, no actual velocity or spacecraft telemetry mutation.
 */
function moonSimFiniteLunarRendezvous(
 array $lmP,array $lmV,array $cmP,array $cmV,array $moonP,array $moonV,
 float $mu,float $moonRadius,float $wetMass,float $fuel,float $thrust,float $isp,
 array $burnDirection,float $burnSeconds,float $coastSeconds=18000.0,float $dt=5.0
):array{
 if($wetMass<=$fuel||$fuel<=0||$thrust<=0||$isp<=0||$mu<=0||$dt<=0||
   $burnSeconds<0||$coastSeconds<=0||$burnSeconds>$coastSeconds)
  throw new InvalidArgumentException('Invalid finite rendezvous inputs');
 $flow=$thrust/($isp*9.80665);
 if($flow*$burnSeconds>$fuel)throw new RuntimeException('Insufficient APS fuel for rendezvous');
 $d=moonSimVectorMagnitude($burnDirection);
 if($d<0.99||$d>1.01)throw new InvalidArgumentException('Unit burn direction required');
 $p=moonSimRelativeVector($lmP,$moonP);$v=moonSimRelativeVector($lmV,$moonV);
 $cp=moonSimRelativeVector($cmP,$moonP);$cv=moonSimRelativeVector($cmV,$moonV);
 $closest=INF;$closestRelativeSpeed=null;$at=0.0;$closestCorrection=null;
 for($t=0.0;$t<$coastSeconds-1e-9;){
  $h=min($dt,$coastSeconds-$t);
  if($t<$burnSeconds)$h=min($h,$burnSeconds-$t);
  $burn=$t<$burnSeconds-1e-9;
  $spent=$flow*$t;
  $r=moonSimVectorMagnitude($p);$rc=moonSimVectorMagnitude($cp);
  if($r<=$moonRadius+1000||$rc<=$moonRadius+1000)
   throw new RuntimeException('Unsafe low-lunar orbit or surface contact during rendezvous');
  $a=[];$ac=[];
  for($j=0;$j<3;$j++){
   $a[$j]=-$mu*$p[$j]/($r**3)+($burn?$thrust*$burnDirection[$j]/($wetMass-$spent):0);
   $ac[$j]=-$mu*$cp[$j]/($rc**3);
  }
  // Kick-drift-kick for both lunar trajectories; thrust on LM only.
  $half=[];$chalf=[];$np=[];$ncp=[];
  for($j=0;$j<3;$j++){
   $half[$j]=$v[$j]+$a[$j]*$h/2;
   $chalf[$j]=$cv[$j]+$ac[$j]*$h/2;
   $np[$j]=$p[$j]+$half[$j]*$h;
   $ncp[$j]=$cp[$j]+$chalf[$j]*$h;
  }
  $nr=moonSimVectorMagnitude($np);$nrc=moonSimVectorMagnitude($ncp);
  if(min($nr,$nrc)<=$moonRadius+1000)throw new RuntimeException('Unsafe projected lunar pass');
  for($j=0;$j<3;$j++){
   $endBurn=$burn?$thrust*$burnDirection[$j]/($wetMass-$flow*($t+$h)):0;
   $v[$j]=$half[$j]+(-$mu*$np[$j]/($nr**3)+$endBurn)*$h/2;
   $cv[$j]=$chalf[$j]-$mu*$ncp[$j]/($nrc**3)*$h/2;
  }
  $p=$np;$cp=$ncp;$t+=$h;
  $range=moonSimVectorMagnitude(moonSimRelativeVector($p,$cp));
  if($range<$closest){
   $closest=$range;$at=$t;
   $correction=moonSimRelativeVector($cv,$v);
   $closestCorrection=$correction;
   $closestRelativeSpeed=moonSimVectorMagnitude($correction);
  }
 }
 $used=$flow*$burnSeconds;
 return ['burn_seconds'=>$burnSeconds,'fuel_used_kg'=>$used,
  'initial_direction_xyz'=>$burnDirection,
  'closest_range_m'=>$closest,'encounter_time_s'=>$at,
  'relative_speed_at_encounter_m_s'=>$closestRelativeSpeed,
  'predicted_match_velocity_delta_v_xyz'=>$closestCorrection,
  'predicted_match_velocity_delta_v_m_s'=>$closestRelativeSpeed,
  'proximity_safe_reference'=>$closest<10000&&$closestRelativeSpeed<5,
  'warning'=>'Finite local-lunar APS study. Predicted velocity match is a second required maneuver, not applied automatically.'];
}
function moonSimFiniteRendezvousSearch(
 array $lmP,array $lmV,array $cmP,array $cmV,array $moonP,array $moonV,
 float $mu,float $moonRadius,float $wetMass,float $fuel,float $thrust,float $isp
):array{
 $reference=moonSimLunarRendezvousReference($lmP,$lmV,$cmP,$cmV,$moonP,$moonV,$mu,$moonRadius);
 $relative=moonSimRelativeVector($cmP,$lmP);
 $rv=moonSimRelativeVector($cmV,$lmV);
 $axes=[$relative,$rv,moonSimVectorCross($relative,$rv)];
 $best=null;$checked=0;$rejected=0;
 foreach($axes as $axis){
  $n=moonSimVectorMagnitude($axis);if($n<1)continue;
  foreach([-1,1] as $sign){
   $dir=array_map(static fn($x)=>$sign*$x/$n,$axis);
   foreach([5.0,10.0,20.0,40.0,80.0,120.0] as $seconds){
    try{$candidate=moonSimFiniteLunarRendezvous($lmP,$lmV,$cmP,$cmV,$moonP,$moonV,
       $mu,$moonRadius,$wetMass,$fuel,$thrust,$isp,$dir,$seconds);}
    catch(RuntimeException $e){$rejected++;continue;}
    $checked++;
    $cost=$candidate['closest_range_m']
      +max(0,$candidate['relative_speed_at_encounter_m_s']-3.0)*8000.0;
    if($best===null||$cost<$best['score']){
     $best=$candidate;$best['score']=$cost;
    }
   }
  }
 }
 return ['current_tracking'=>$reference,'best_finite_burn'=>$best,
  'candidates_evaluated'=>$checked,'candidates_rejected'=>$rejected,
  'warning'=>'Finite fuel-limited rendezvous reference. Actual rendezvous requires crew confirmation and further proximity corrections.'];
}
