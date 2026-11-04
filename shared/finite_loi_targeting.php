<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
require_once __DIR__.'/verlet_kernel.php';
/**
 * Nominal fixed-attitude finite-thrust capture estimate in the instantaneous
 * Moon-relative frame. No ignition commands, and no guarantee of stable capture.
 */
function moonSimFiniteLoiEstimate(array $relativePosition,array $relativeVelocity,
 float $moonMu,float $moonRadius,float $stackMass,float $fuelMass,
 float $thrust,float $isp,float $duration,float $step=1.0):array {
 if($moonMu<=0||$moonRadius<=0||$stackMass<=0||$fuelMass<0||$fuelMass>=$stackMass||
    $thrust<=0||$isp<=0||$duration<=0||$step<=0)throw new InvalidArgumentException('Invalid LOI input');
 $before=moonSimOrbitalElements($relativePosition,$relativeVelocity,[0,0,0],$moonMu);
 $v=moonSimVectorMagnitude($relativeVelocity);if($v<1e-9)throw new RuntimeException('No retrograde heading');
 $direction=array_map(static fn($x)=>-$x/$v,$relativeVelocity);
 $flow=$thrust/($isp*9.80665);$spent=$duration*$flow;
 if($spent>$fuelMass)throw new RuntimeException('Insufficient LOI propellant');
 $pos=$relativePosition;$vel=$relativeVelocity;$mass=$stackMass;
 $time=0.0;
 while($time<$duration-1e-9){
  $dt=min($step,$duration-$time);
  $r=moonSimVectorMagnitude($pos);
  if($r<=$moonRadius)throw new RuntimeException('Lunar impact during LOI');
  $g=array_map(static fn($x)=>-$moonMu*$x/($r*$r*$r),$pos);
  $a=[];for($j=0;$j<3;$j++)$a[$j]=$g[$j]+$thrust*$direction[$j]/$mass;
  $next=moonSimVerletDrift($pos,$vel,$a,$dt);
  $mass-=$flow*$dt;
  $r2=moonSimVectorMagnitude($next);
  if($r2<=$moonRadius)throw new RuntimeException('Lunar impact during LOI');
  $g2=array_map(static fn($x)=>-$moonMu*$x/($r2*$r2*$r2),$next);
  $a2=[];for($j=0;$j<3;$j++)$a2[$j]=$g2[$j]+$thrust*$direction[$j]/$mass;
  $vel=moonSimVerletKick($vel,$a,$a2,$dt);$pos=$next;$time+=$dt;
 }
 $after=moonSimOrbitalElements($pos,$vel,[0,0,0],$moonMu);
 return ['burn_direction_moon_relative_xyz'=>$direction,
 'burn_duration_seconds'=>$duration,'fuel_used_kg'=>$spent,
 'initial_specific_energy'=>$before['specific_energy'],
 'final_specific_energy'=>$after['specific_energy'],
 'predicted_periapsis_altitude_m'=>$after['periapsis_radius']===null?null:$after['periapsis_radius']-$moonRadius,
 'predicted_apoapsis_altitude_m'=>$after['apoapsis_radius']===null?null:$after['apoapsis_radius']-$moonRadius,
 'predicted_lunar_bound'=>$after['specific_energy']<0,
 'warning'=>'Moon-only fixed-attitude finite-burn reference. Must refine with coupled Earth-Moon propagation before flight execution.'];
}

/**
 * Search finite SPS capture burns from the present Moon-relative state.
 * A viable candidate must finish bound, with a periapsis safely above lunar
 * terrain and an apoapsis compatible with the requested target.
 */
function moonSimFiniteLoiTarget(
 array $relativePosition,array $relativeVelocity,float $moonMu,float $moonRadius,
 float $stackMass,float $fuelMass,float $thrust,float $isp,
 float $desiredApoapsisAltitude=120000.0,float $safePeriapsisAltitude=80000.0
):array {
 if($desiredApoapsisAltitude<$safePeriapsisAltitude || $safePeriapsisAltitude<10000)
  throw new InvalidArgumentException('Unsafe lunar orbit target');
 $r=moonSimVectorMagnitude($relativePosition);
 if($r<=$moonRadius+$safePeriapsisAltitude)throw new RuntimeException('Too low for safe targeting');
 $v=moonSimVectorMagnitude($relativeVelocity);
 $radial=moonSimVectorDot($relativePosition,$relativeVelocity)/$r;
 if($radial>=0.0)throw new RuntimeException('Target LOI on inbound approach before perilune');
 $maxDuration=min(1200.0,0.95*$fuelMass*$isp*9.80665/$thrust);
 if($maxDuration<1.0)throw new RuntimeException('Insufficient fuel for a burn');
 $best=null;$tested=0;$rejected=0;
 for($t=5.0;$t<=$maxDuration;$t+=5.0){
  try{
   $p=moonSimFiniteLoiEstimate($relativePosition,$relativeVelocity,$moonMu,
     $moonRadius,$stackMass,$fuelMass,$thrust,$isp,$t,1.0);
  }catch(RuntimeException $ex){$rejected++;continue;}
  $tested++;
  if(!$p['predicted_lunar_bound'] || $p['predicted_periapsis_altitude_m']===null
    || $p['predicted_periapsis_altitude_m']<$safePeriapsisAltitude
    || $p['predicted_apoapsis_altitude_m']===null)continue;
  $err=abs($p['predicted_apoapsis_altitude_m']-$desiredApoapsisAltitude);
  if($best===null||$err<$best['apoapsis_target_error_m']){
   $best=$p;$best['apoapsis_target_error_m']=$err;
  }
 }
 if($best===null)throw new RuntimeException('No safe, bound lunar capture in tested finite-burn durations');
 $best['target_apoapsis_altitude_m']=$desiredApoapsisAltitude;
 $best['minimum_periapsis_altitude_m']=$safePeriapsisAltitude;
 $best['evaluated_candidates']=$tested;
 $best['rejected_candidates']=$rejected;
 $best['target_achieved_within_20km']=$best['apoapsis_target_error_m']<=20000.0;
 return $best;
}
