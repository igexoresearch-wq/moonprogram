<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** Moon-relative initial powered-descent targets; advisory until LM ascent/descent guidance is connected. */
function moonSimDescentInitialization(array $position,array $velocity,array $moonPosition,array $moonVelocity,
 float $moonMu,float $moonRadius,float $targetStartAltitude=15000.0):array {
 if($moonMu<=0||$moonRadius<=0||$targetStartAltitude<1000)throw new InvalidArgumentException('Invalid lunar descent settings');
 $r=moonSimRelativeVector($position,$moonPosition);
 $v=moonSimRelativeVector($velocity,$moonVelocity);
 $radius=moonSimVectorMagnitude($r);
 if($radius<=$moonRadius)throw new RuntimeException('LM is on or below lunar surface');
 $elements=moonSimOrbitalElements($r,$v,[0,0,0],$moonMu);
 if($elements['specific_energy']>=0||$elements['periapsis_radius']===null)throw new RuntimeException('Start descent from bound lunar orbit');
 $height=$radius-$moonRadius;
 $normal=array_map(static fn($x)=>$x/$radius,$r);
 $radial=moonSimVectorDot($normal,$v);
 $tangent=array_map(static fn($x,$u)=>$x-$radial*$u,$v,$normal);
 $speed=moonSimVectorMagnitude($tangent);
 if($speed<1)throw new RuntimeException('No horizontal orbital motion');
 $forward=array_map(static fn($x)=>-$x/$speed,$tangent);
 $targetRadius=$moonRadius+$targetStartAltitude;
 $descentA=($radius+$targetRadius)/2;
 $currentCircular=sqrt($moonMu/$radius);
 $transferSpeed=sqrt(max(0.0,$moonMu*(2/$radius-1/$descentA)));
 $retroDv=max(0.0,$currentCircular-$transferSpeed);
 return ['current_altitude_m'=>$height,
 'radial_speed_m_s'=>$radial,'horizontal_speed_m_s'=>$speed,
 'target_descent_perilune_altitude_m'=>$targetStartAltitude,
 'current_perilune_altitude_m'=>$elements['periapsis_radius']-$moonRadius,
 'suggested_retrograde_vector_xyz'=>$forward,
 'initial_braking_delta_v_m_s'=>$retroDv,
 'valid_descent_orbit'=>$height>=$targetStartAltitude+5000&&$height<200000&&abs($radial)<300,
 'status'=>'PRELIMINARY DESCENT ORBIT INITIALIZATION ONLY',
 'warning'=>'Two-body deorbit reference, not powered descent guidance or touchdown solution. Pilot must verify before action.'];
}
