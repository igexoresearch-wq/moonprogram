<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
require_once __DIR__.'/nbody_gravity.php';
require_once __DIR__.'/verlet_kernel.php';
/** Midcourse correction references, computed from independent tracking state. */
function moonSimMidcourseCorrection(array $craft,array $velocity,array $moon,array $moonVelocity,
 float $moonMu,float $moonRadius,float $coastSeconds):array{
 if($moonMu<=0||$moonRadius<=0||$coastSeconds<=0)throw new InvalidArgumentException('Invalid midcourse geometry');
 $rel=moonSimRelativeVector($craft,$moon);
 $rv=moonSimRelativeVector($velocity,$moonVelocity);
 $predict=[];
 for($i=0;$i<3;$i++)$predict[$i]=$rel[$i]+$rv[$i]*$coastSeconds;
 $range=moonSimVectorMagnitude($predict);
 $relativeSpeed=moonSimVectorMagnitude($rv);
 $unit=$range>1?array_map(static fn($v)=>$v/$range,$predict):[0,0,0];
 // A zero-displacement ballistic correction would target the lunar center,
 // which is unsafe. Return the uncorrected track and lateral reference only.
 $lateral=moonSimVectorCross($rv,$rel);$m=moonSimVectorMagnitude($lateral);
 $safeDirection=$m>1e-5?array_map(static fn($v)=>$v/$m,$lateral):[0,0,0];
 return ['linearized_coast_seconds'=>$coastSeconds,
 'uncorrected_relative_range_m'=>$range,
 'relative_speed_m_s'=>$relativeSpeed,
 'lateral_unit_xyz'=>$safeDirection,
 'minimum_safe_lunar_range_m'=>$moonRadius+100000,
 'correction_required'=>$range>$moonRadius+100000,
 'warning'=>'Linearly extrapolated reference only. Requires full Earth/Moon propagator and verified correction targeting; do not burn from this alone.'];
}
/** Earth-centered Earth-return safety reference: whether the conic can avoid entry. */
function moonSimEarthReturnClassification(array $position,array $velocity,array $earth,
 float $earthMu,float $earthRadius):array{
 $orbit=moonSimOrbitalElements($position,$velocity,$earth,$earthMu);
 $peri=$orbit['periapsis_radius'];
 return ['earth_periapsis_altitude_m'=>$peri===null?null:$peri-$earthRadius,
 'potential_earth_entry'=>$peri!==null&&$peri<=$earthRadius+120000,
 'earth_bound'=>$orbit['specific_energy']<0,
 'note'=>'A free-return trajectory requires full Earth-Moon propagation and a valid lunar flyby; this conic alone does not establish one.'];
}

/** Coast a candidate correction through moving Earth-Moon gravity. No impulse
 * is applied to the simulator; delta-v is an optional hypothetical input. */
function moonSimMidcoursePropagatedPreview(
 array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $earthRadius,float $moonRadius,
 float $duration=345600.0,?array $deltaVelocity=null,float $step=60.0
):array{
 if($duration<=0||$duration>1209600||$step<=0||$earthMu<=0||$moonMu<=0)
  throw new InvalidArgumentException('Invalid coast integration parameters');
 if($deltaVelocity!==null){
  foreach($deltaVelocity as $v)if(!is_finite((float)$v))throw new InvalidArgumentException('Invalid correction vector');
  for($i=0;$i<3;$i++)$velocity[$i]+=(float)$deltaVelocity[$i];
 }
 $closest=INF;$at=0.0;$approachVelocity=null;$moonHit=false;$earthHit=false;
 $time=0.0;$passedMoon=false;$postMoonEarthPeriapsis=INF;
 while($time<$duration-1e-8){
  $h=min($step,$duration-$time);
  $a0=moonSimGravityAcceleration($craft,$earth,$moon,$earthMu,$moonMu);
  $m0=moonSimMoonAcceleration($moon,$earth,$earthMu,$moonMu);
  $next=moonSimVerletDrift($craft,$velocity,$a0,$h);
  $nextMoon=moonSimVerletDrift($moon,$moonVelocity,$m0,$h);
  $a1=moonSimGravityAcceleration($next,$earth,$nextMoon,$earthMu,$moonMu);
  $m1=moonSimMoonAcceleration($nextMoon,$earth,$earthMu,$moonMu);
  $velocity=moonSimVerletKick($velocity,$a0,$a1,$h);
  $moonVelocity=moonSimVerletKick($moonVelocity,$m0,$m1,$h);
  $craft=$next;$moon=$nextMoon;$time+=$h;
  $earthRange=moonSimVectorMagnitude(moonSimRelativeVector($craft,$earth));
  $moonRange=moonSimVectorMagnitude(moonSimRelativeVector($craft,$moon));
  if($moonRange<$closest){
   $closest=$moonRange;$at=$time;
   $approachVelocity=moonSimVectorMagnitude(moonSimRelativeVector($velocity,$moonVelocity));
  }elseif($time>$at+3600){$passedMoon=true;}
  if($passedMoon)$postMoonEarthPeriapsis=min($postMoonEarthPeriapsis,$earthRange);
  if($earthRange<=$earthRadius){$earthHit=true;break;}
  if($moonRange<=$moonRadius){$moonHit=true;break;}
 }
 $surface=($closest-$moonRadius)/1000.0;
 return [
  'predicted_closest_lunar_altitude_km'=>$surface,
  'closest_lunar_approach_time_s'=>$at,
  'relative_speed_at_approach_m_s'=>$approachVelocity,
  'earth_impact'=>$earthHit,'moon_impact'=>$moonHit,
  'post_lunar_earth_min_altitude_m'=>is_finite($postMoonEarthPeriapsis)?$postMoonEarthPeriapsis-$earthRadius:null,
  'free_return_observed'=>$passedMoon&&$earthHit&&!$moonHit,
  'horizon_seconds'=>$duration,
  'warning'=>'Numerical coast preview only. A free return needs demonstrated post-flyby Earth encounter, not an osculating conic or hypothetical correction alone.'
 ];
}
