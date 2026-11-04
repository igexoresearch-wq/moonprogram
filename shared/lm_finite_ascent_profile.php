<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/**
 * Finite, variable-mass Moon-centered APS launch study. No state mutation.
 * The solver searches commanded pitch-program shape, braking/turnover height
 * and actual engine cutoff time. Solutions are filtered on physical lunar
 * periapsis, apolune, propellant and contact rather than speed alone.
 */
function moonSimLunarAscentPropagate(
 array $position,array $velocity,array $moonPosition,array $moonVelocity,
 float $mu,float $radius,float $mass,float $fuel,float $thrust,float $isp,
 float $burnSeconds,float $turnoverAltitude,float $pitchBias=0.0,float $step=1.0
):array {
 if($mu<=0||$radius<=0||$mass<=0||$fuel<=0||$mass<=$fuel||
    $thrust<=0||$isp<=0||$burnSeconds<=0||$burnSeconds>1200||
    $turnoverAltitude<1000||$turnoverAltitude>150000||$step<=0)
  throw new InvalidArgumentException('Invalid lunar ascent profile');
 $r=moonSimRelativeVector($position,$moonPosition);
 $v=moonSimRelativeVector($velocity,$moonVelocity);
 $flow=$thrust/($isp*9.80665);
 if($flow*$burnSeconds>$fuel)throw new RuntimeException('APS profile exceeds available propellant');
 $cross=moonSimVectorCross($r,$v);
 if(moonSimVectorMagnitude($cross)<1){
  $cross=moonSimVectorCross($r,[0.0,0.0,1.0]);
  if(moonSimVectorMagnitude($cross)<1)$cross=moonSimVectorCross($r,[0.0,1.0,0.0]);
 }
 $n=moonSimVectorMagnitude($cross);
 $h=array_map(static fn($x)=>$x/$n,$cross);
 $spent=0.0;$time=0.0;$contact=false;
 $lastDirection=[0.0,0.0,0.0];$lastPitch=0.0;
 while($time<$burnSeconds-1e-9){
  $dt=min($step,$burnSeconds-$time);
  $rm=moonSimVectorMagnitude($r);
  if($rm<=$radius-0.5){$contact=true;break;}
  $up=array_map(static fn($x)=>$x/$rm,$r);
  $horizontal=moonSimVectorCross($h,$up);
  $hn=moonSimVectorMagnitude($horizontal);
  if($hn<1e-10)throw new RuntimeException('Undefined orbital heading');
  $horizontal=array_map(static fn($x)=>$x/$hn,$horizontal);
  $alt=max(0.0,$rm-$radius);
  $basePitch=75.0*(1.0-min(1.0,$alt/$turnoverAltitude));
  $pitch=max(-10.0,min(85.0,$basePitch+$pitchBias));
  $rad=deg2rad($pitch);
  $dir=[];
  for($i=0;$i<3;$i++)$dir[$i]=$up[$i]*sin($rad)+$horizontal[$i]*cos($rad);
  $m=$mass-$spent;
  $acc=[];
  for($i=0;$i<3;$i++)$acc[$i]=-$mu*$r[$i]/($rm**3)+$thrust*$dir[$i]/$m;
  // Symplectic kick-drift-kick; propellant mass is changed at midpoint.
  $half=[];$next=[];
  for($i=0;$i<3;$i++){
   $half[$i]=$v[$i]+$acc[$i]*$dt/2.0;
   $next[$i]=$r[$i]+$half[$i]*$dt;
  }
  $spent+=$flow*$dt;
  $nm=moonSimVectorMagnitude($next);
  if($nm<=$radius-0.5){$contact=true;break;}
  for($i=0;$i<3;$i++){
   $a1=-$mu*$next[$i]/($nm**3)+$thrust*$dir[$i]/($mass-$spent);
   $v[$i]=$half[$i]+$a1*$dt/2.0;
  }
  $r=$next;$time+=$dt;$lastDirection=$dir;$lastPitch=$pitch;
 }
 $orb=moonSimOrbitalElements($r,$v,[0.0,0.0,0.0],$mu);
 $peri=$orb['periapsis_radius'];
 $apo=$orb['apoapsis_radius'];
 return ['burn_duration_s'=>$time,'fuel_consumed_kg'=>$spent,
   'turnover_altitude_m'=>$turnoverAltitude,'pitch_bias_deg'=>$pitchBias,
   'last_pitch_deg'=>$lastPitch,'last_direction_xyz'=>$lastDirection,
   'final_position_moon_xyz'=>$r,'final_velocity_moon_xyz'=>$v,
   'final_perilune_altitude_m'=>$peri===null?null:$peri-$radius,
   'final_apolune_altitude_m'=>$apo===null?null:$apo-$radius,
   'final_specific_energy_j_kg'=>$orb['specific_energy'],
   'lunar_impact'=>$contact,
   'lunar_bound'=>$orb['specific_energy']<0];
}
function moonSimLunarAscentProfileSearch(
 array $position,array $velocity,array $moonPosition,array $moonVelocity,
 float $mu,float $radius,float $mass,float $fuel,float $thrust,float $isp,
 float $targetAltitude=100000.0
):array{
 if($targetAltitude<30000||$targetAltitude>300000)
  throw new InvalidArgumentException('Invalid target orbit');
 $maxTime=min(900.0,$fuel*$isp*9.80665/$thrust*0.98);
 $best=null;$scanned=0;$safe=0;
 foreach([20000.0,50000.0,80000.0,120000.0] as $turn){
  foreach([-10.0,0.0,10.0] as $bias){
   for($time=120.0;$time<=$maxTime;$time+=20.0){
    try{$candidate=moonSimLunarAscentPropagate($position,$velocity,$moonPosition,$moonVelocity,
      $mu,$radius,$mass,$fuel,$thrust,$isp,$time,$turn,$bias,1.0);}
    catch(RuntimeException $e){continue;}
    $scanned++;
    if($candidate['lunar_impact']||!$candidate['lunar_bound']||
       $candidate['final_perilune_altitude_m']===null||
       $candidate['final_apolune_altitude_m']===null||
       $candidate['final_perilune_altitude_m']<30000.0)continue;
    $safe++;
    $score=abs($candidate['final_perilune_altitude_m']-$targetAltitude)
      +abs($candidate['final_apolune_altitude_m']-$targetAltitude);
    if($best===null||$score<$best['orbit_target_error_m']){
     $best=$candidate;$best['orbit_target_error_m']=$score;
    }
   }
  }
 }
 if($best===null)throw new RuntimeException('No safe fuel-feasible APS ascent profile found');
 $best['profiles_scanned']=$scanned;$best['safe_profiles']=$safe;
 $best['target_altitude_m']=$targetAltitude;
 $best['orbit_tolerance_20km']=$best['orbit_target_error_m']<=40000.0;
 $best['note']='Moon-only finite APS target. Steering feedback and real engine state may diverge; review at each stage.';
 return $best;
}
