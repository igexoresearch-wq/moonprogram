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
 $time=0.0;$passedMoon=false;$postMoonEarthPeriapsis=INF;$postMoonEarthClosestTime=null;$entry120kmTime=null;
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
  if($passedMoon){
    if($earthRange<$postMoonEarthPeriapsis){$postMoonEarthPeriapsis=$earthRange;$postMoonEarthClosestTime=$time;}
    if($entry120kmTime===null && $earthRange<=$earthRadius+120000.0)$entry120kmTime=$time;
  }
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
  'post_lunar_earth_closest_approach_time_s'=>$postMoonEarthClosestTime,
  'entry_interface_120km_time_s'=>$entry120kmTime,
  'free_return_observed'=>$passedMoon&&$earthHit&&!$moonHit,
  'horizon_seconds'=>$duration,
  'warning'=>'Numerical coast preview only. A free return needs demonstrated post-flyby Earth encounter, not an osculating conic or hypothetical correction alone.'
 ];
}

/** Solve bounded manual midcourse correction candidates with the same propagator
 * as the existing FIDO coast display. No simulated spacecraft state is changed. */
function moonSimMidcourseSearch(
 array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $earthRadius,float $moonRadius,
 float $maxDeltaV=35.0,float $horizon=604800.0
):array {
 if($maxDeltaV<=0||$maxDeltaV>300||!is_finite($maxDeltaV))throw new InvalidArgumentException('Invalid correction budget');
 $baseline=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonVelocity,
  $earthMu,$moonMu,$earthRadius,$moonRadius,$horizon,null,120.0);
 $relative=moonSimRelativeVector($velocity,$moonVelocity);
 $unit=moonSimVectorMagnitude($relative);
 if($unit<1e-8)throw new RuntimeException('Undefined relative velocity');
 $forward=array_map(static fn($x)=>$x/$unit,$relative);
 $radial=moonSimRelativeVector($moon,$craft);
 $mag=moonSimVectorMagnitude($radial);
 if($mag<1.0)throw new RuntimeException('Undefined lunar direction');
 $radial=array_map(static fn($x)=>$x/$mag,$radial);
 $normal=moonSimVectorCross($forward,$radial);
 $nm=moonSimVectorMagnitude($normal);
 if($nm<1e-8)throw new RuntimeException('Undefined correction plane');
 $normal=array_map(static fn($x)=>$x/$nm,$normal);
 $side=moonSimVectorCross($normal,$forward);
 $best=null;$evaluated=0;
 foreach([0.25,0.5,0.75,1.0] as $fraction){
  foreach([$forward,$radial,$normal,$side] as $axis){
   foreach([-1.0,1.0] as $sign){
    $dv=array_map(static fn($x)=>$x*$maxDeltaV*$fraction*$sign,$axis);
    $candidate=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonVelocity,
     $earthMu,$moonMu,$earthRadius,$moonRadius,$horizon,$dv,120.0);
    $evaluated++;
    if($candidate['earth_impact']||$candidate['moon_impact'])continue;
    $alt=$candidate['predicted_closest_lunar_altitude_km'];
    if($alt<100.0 || !is_finite($alt))continue;
    if($best===null||$alt<$best['predicted_closest_lunar_altitude_km']){
     $best=$candidate;$best['correction_delta_v_xyz']=$dv;
     $best['correction_magnitude_m_s']=$maxDeltaV*$fraction;
    }
   }
  }
 }
 return ['baseline'=>$baseline,'candidate'=>$best,
  'evaluated_candidates'=>$evaluated,
  'improvement_m'=>$best===null?null:1000.0*($baseline['predicted_closest_lunar_altitude_km']-$best['predicted_closest_lunar_altitude_km']),
  'candidate_is_better'=>$best!==null&&$best['predicted_closest_lunar_altitude_km']<$baseline['predicted_closest_lunar_altitude_km'],
  'warning'=>'Discrete impulsive correction study; no engine command, no verified free-return guarantee.'];
}

/** Ground-computer free-return screening: never label a flyby as free-return
 * unless post-lunar Earth approach appears inside the integrated time horizon.
 */
function moonSimFreeReturnSearch(
 array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $earthRadius,float $moonRadius,
 float $maxCorrection=35.0,float $horizon=1036800.0
):array{
 if($maxCorrection<=0||$maxCorrection>300)throw new InvalidArgumentException('Invalid correction limit');
 $base=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonVelocity,
  $earthMu,$moonMu,$earthRadius,$moonRadius,$horizon,null,120.0);
 $forward=moonSimRelativeVector($velocity,$moonVelocity);
 $speed=moonSimVectorMagnitude($forward);
 if($speed<1.0)throw new RuntimeException('Insufficient relative state for targeting');
 $forward=array_map(static fn($x)=>$x/$speed,$forward);
 $toward=moonSimRelativeVector($moon,$craft);
 $distance=moonSimVectorMagnitude($toward);
 if($distance<1.0)throw new RuntimeException('Undefined Moon sightline');
 $toward=array_map(static fn($x)=>$x/$distance,$toward);
 $normal=moonSimVectorCross($forward,$toward);
 $n=moonSimVectorMagnitude($normal);
 if($n<1e-8)throw new RuntimeException('Undefined candidate plane');
 $normal=array_map(static fn($x)=>$x/$n,$normal);
 $lateral=moonSimVectorCross($normal,$forward);
 $best=null;$tested=0;
 foreach([$forward,$toward,$lateral,$normal] as $axis){
  foreach([-1.0,1.0] as $sign){
   foreach([0.25,0.5,1.0] as $factor){
    $dv=array_map(static fn($x)=>$x*$maxCorrection*$sign*$factor,$axis);
    $preview=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonVelocity,
     $earthMu,$moonMu,$earthRadius,$moonRadius,$horizon,$dv,180.0);
    $tested++;
    if($preview['moon_impact']||$preview['predicted_closest_lunar_altitude_km']<100.0)continue;
    // Return corridor only requires an Earth approach after the lunar flyby,
    // not a forced surface impact or an artificially guaranteed reentry.
    $peri=$preview['post_lunar_earth_min_altitude_m'];
    if($peri===null||$peri<100000.0||$peri>2000000.0)continue;
    $error=abs($peri-120000.0);
    if($best===null||$error<$best['earth_return_corridor_error_m']){
      $best=$preview;
      $best['correction_delta_v_xyz']=$dv;
      $best['correction_magnitude_m_s']=$maxCorrection*$factor;
      $best['earth_return_corridor_error_m']=$error;
    }
   }
  }
 }
 return ['unmodified_track'=>$base,'return_candidate'=>$best,
 'searched_candidates'=>$tested,'return_corridor_found'=>$best!==null,
 'warning'=>'Finite-horizon numeric Earth-return corridor screening, not proof of safe reentry, capture or actual free-return. Verify under production physics and full flight timeline.'];
}
