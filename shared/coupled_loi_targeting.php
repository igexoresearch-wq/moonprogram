<?php
declare(strict_types=1);
require_once __DIR__.'/nbody_gravity.php';
require_once __DIR__.'/verlet_kernel.php';
require_once __DIR__.'/orbital_mechanics.php';
/**
 * Coupled Earth-Moon, finite-duration SPS capture estimate.
 * Earth is fixed in the simulator reference frame. The Moon is propagated
 * using its existing gravitational kernel. No state mutation is performed.
 */
function moonSimCoupledLoi(
 array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $moonRadius,float $earthRadius,
 float $mass,float $fuel,float $thrust,float $isp,float $burnDuration,
 float $delay=0.0,float $step=2.0
):array{
 foreach([$earthMu,$moonMu,$moonRadius,$earthRadius,$mass,$fuel,$thrust,$isp,$burnDuration,$delay,$step] as $v)
  if(!is_finite($v))throw new InvalidArgumentException('Nonfinite LOI input');
 if($earthMu<=0||$moonMu<=0||$moonRadius<=0||$earthRadius<=0||$mass<=1||$fuel<0||
    $fuel>=$mass||$thrust<=0||$isp<=0||$burnDuration<=0||$delay<0||$delay>86400||$step<=0)
  throw new InvalidArgumentException('Invalid LOI parameters');
 $flow=$thrust/($isp*9.80665);
 if($flow*$burnDuration>$fuel)throw new RuntimeException('Insufficient SPS fuel');
 $end=$delay+$burnDuration;$t=0.0;$spent=0.0;
 $direction=null;$impact=false;
 while($t<$end-1e-8){
  $h=min($step,$end-$t);
  if($t<$delay)$h=min($h,$delay-$t);
  elseif($t<$end)$h=min($h,1.0);
  if($h<=1e-8)throw new RuntimeException('LOI timestep underflow');
  $burn=$t>=$delay-1e-8;
  if($burn&&$direction===null){
   $relVel=moonSimRelativeVector($velocity,$moonVelocity);
   $len=moonSimVectorMagnitude($relVel);
   if($len<1.0)throw new RuntimeException('LOI retrograde vector unavailable');
   $direction=array_map(static fn($x)=>-$x/$len,$relVel);
  }
  $a0=moonSimGravityAcceleration($craft,$earth,$moon,$earthMu,$moonMu);
  $m0=moonSimMoonAcceleration($moon,$earth,$earthMu,$moonMu);
  if($burn)for($i=0;$i<3;$i++)$a0[$i]+=$direction[$i]*$thrust/($mass-$spent);
  $next=moonSimVerletDrift($craft,$velocity,$a0,$h);
  $nextMoon=moonSimVerletDrift($moon,$moonVelocity,$m0,$h);
  if($burn)$spent+=$h*$flow;
  $a1=moonSimGravityAcceleration($next,$earth,$nextMoon,$earthMu,$moonMu);
  $m1=moonSimMoonAcceleration($nextMoon,$earth,$earthMu,$moonMu);
  if($burn)for($i=0;$i<3;$i++)$a1[$i]+=$direction[$i]*$thrust/($mass-$spent);
  $velocity=moonSimVerletKick($velocity,$a0,$a1,$h);
  $moonVelocity=moonSimVerletKick($moonVelocity,$m0,$m1,$h);
  $craft=$next;$moon=$nextMoon;$t+=$h;
  if(moonSimVectorMagnitude(moonSimRelativeVector($craft,$moon))<=$moonRadius||
     moonSimVectorMagnitude(moonSimRelativeVector($craft,$earth))<=$earthRadius){
   $impact=true;break;
  }
 }
 $rel=moonSimRelativeVector($craft,$moon);
 $vrel=moonSimRelativeVector($velocity,$moonVelocity);
 $el=moonSimOrbitalElements($rel,$vrel,[0,0,0],$moonMu);
 return [
  'ignition_delay_seconds'=>$delay,
  'burn_seconds'=>$burnDuration,'fuel_required_kg'=>$spent,
  'burn_direction_inertial_xyz'=>$direction,
  'impact_detected'=>$impact,
  'predicted_lunar_bound'=>$el['specific_energy']<0,
  'predicted_perilune_altitude_m'=>$el['periapsis_radius']===null?null:$el['periapsis_radius']-$moonRadius,
  'predicted_apolune_altitude_m'=>$el['apoapsis_radius']===null?null:$el['apoapsis_radius']-$moonRadius,
  'moon_relative_energy_j_kg'=>$el['specific_energy'],
  'warning'=>'Coupled numerical source calculation; orbital elements at cutoff are instantaneous and not long-term guaranteed. Verify before flight.'
 ];
}
function moonSimCoupledLoiSearch(
 array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $moonRadius,float $earthRadius,
 float $mass,float $fuel,float $thrust,float $isp,
 float $targetApolune=120000.0,float $minimumPerilune=80000.0
):array {
 if($targetApolune<$minimumPerilune||$minimumPerilune<10000)
  throw new InvalidArgumentException('Unsafe requested lunar orbit');
 $max=min(1000.0,$fuel*$isp*9.80665/$thrust);
 $best=null;$sampled=0;$rejected=0;
 foreach([0.0,60.0,120.0,240.0,360.0] as $delay){
  for($duration=10.0;$duration<=$max;$duration+=15.0){
   try{$candidate=moonSimCoupledLoi($craft,$velocity,$earth,$moon,$moonVelocity,
    $earthMu,$moonMu,$moonRadius,$earthRadius,$mass,$fuel,$thrust,$isp,$duration,$delay);}
   catch(RuntimeException $e){$rejected++;continue;}
   $sampled++;
   $apo=$candidate['predicted_apolune_altitude_m'];
   $peri=$candidate['predicted_perilune_altitude_m'];
   if($candidate['impact_detected']||!$candidate['predicted_lunar_bound']||
      $peri===null||$peri<$minimumPerilune||$apo===null)continue;
   $error=abs($apo-$targetApolune);
   if($best===null||$error<$best['apolune_error_m']){
    $best=$candidate;$best['apolune_error_m']=$error;
   }
  }
 }
 if($best===null)throw new RuntimeException('No fuel-feasible safe lunar capture in sampled burns');
 $best['target_apolune_altitude_m']=$targetApolune;
 $best['required_minimum_perilune_altitude_m']=$minimumPerilune;
 $best['candidates_sampled']=$sampled;$best['candidates_rejected']=$rejected;
 $best['within_target_20km']=$best['apolune_error_m']<20000.0;
 return $best;
}
