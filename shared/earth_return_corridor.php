<?php
declare(strict_types=1);
require_once __DIR__.'/midcourse_tracking.php';
/** Numerical return corridor study with the existing coupled Earth/Moon kernel.
 * No burn is issued; the spacecraft is not directly repositioned. */
function moonSimReturnCorridorReference(array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $earthRadius,float $moonRadius,
 ?array $correction=null,float $horizon=1036800.0):array{
 $preview=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonVelocity,
  $earthMu,$moonMu,$earthRadius,$moonRadius,$horizon,$correction,120.0);
 $alt=$preview['post_lunar_earth_min_altitude_m'];
 return ['predicted_closest_earth_altitude_m'=>$alt,
  'estimated_earth_arrival_corridor'=>$alt!==null&&$alt>=65000&&$alt<=140000,
  'earth_surface_impact'=>$preview['earth_impact'],
  'lunar_surface_impact'=>$preview['moon_impact'],
  'corridor_too_high'=>$alt!==null&&$alt>140000,
  'corridor_too_low'=>$alt!==null&&$alt<65000,
  'numerical_preview'=>$preview,
  'warning'=>'Finite horizon point-mass gravitation: atmospheric heating, aerodynamic entry and recovery survival are not evaluated.'];
}
function moonSimReturnCourseSearch(array $craft,array $velocity,array $earth,array $moon,array $moonVelocity,
 float $earthMu,float $moonMu,float $earthRadius,float $moonRadius,float $maxDv=40.0):array{
 if($maxDv<=0||$maxDv>300)throw new InvalidArgumentException('Invalid return correction budget');
 $toEarth=moonSimRelativeVector($earth,$craft);
 $radial=moonSimVectorMagnitude($toEarth);
 if($radial<1)throw new RuntimeException('Invalid Earth tracking direction');
 $unit=array_map(static fn($x)=>$x/$radial,$toEarth);
 $vel=moonSimVectorMagnitude($velocity);
 if($vel<1)throw new RuntimeException('Invalid velocity direction');
 $flight=array_map(static fn($x)=>$x/$vel,$velocity);
 $normal=moonSimVectorCross($unit,$flight);$n=moonSimVectorMagnitude($normal);
 if($n<1e-9)throw new RuntimeException('Correction plane singular');
 $normal=array_map(static fn($x)=>$x/$n,$normal);
 $side=moonSimVectorCross($normal,$unit);
 $best=null;$evaluated=0;
 foreach([$unit,$flight,$normal,$side] as $axis)
 foreach([-1.0,1.0] as $sgn)
 foreach([0.25,0.5,1.0] as $factor){
  $dv=array_map(static fn($x)=>$x*$maxDv*$factor*$sgn,$axis);
  $p=moonSimReturnCorridorReference($craft,$velocity,$earth,$moon,$moonVelocity,
   $earthMu,$moonMu,$earthRadius,$moonRadius,$dv);
  $evaluated++;
  $alt=$p['predicted_closest_earth_altitude_m'];
  if($alt===null||$alt<65000||$alt>140000||$p['lunar_surface_impact'])continue;
  $err=abs($alt-100000);
  if($best===null||$err<$best['error_m'])$best=['delta_v_xyz'=>$dv,
     'delta_v_m_s'=>$maxDv*$factor,'error_m'=>$err,'return_preview'=>$p];
 }
 return ['candidate'=>$best,'evaluated'=>$evaluated,
  'warning'=>'Impulsive correction screening; candidate must be converted to finite SPS burn and manually confirmed; no entry survivability claim.'];
}
