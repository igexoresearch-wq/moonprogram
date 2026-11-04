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
  'warning'=>'Moon-only impulsive transfer study. No docking command, proximity approach controls, or verified collision-safe terminal rendezvous.'];
}
