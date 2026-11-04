<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** State-dependent lunar braking and descent boundary conditions (SI). */
function moonSimPoweredDescentTargets(array $p,array $v,array $moonP,array $moonV,
 float $mu,float $radius,float $mass,float $fuel,float $thrust,float $isp):array {
 $r=moonSimRelativeVector($p,$moonP);$u=moonSimVectorMagnitude($r);
 if($mu<=0||$radius<=0||$u<=$radius||$mass<=0||$fuel<=0||$thrust<=0||$isp<=0)
  throw new InvalidArgumentException('Invalid descent state');
 $up=array_map(static fn($x)=>$x/$u,$r);
 $relative=moonSimRelativeVector($v,$moonV);
 $radial=moonSimVectorDot($relative,$up);
 $tangent=array_map(static fn($x,$n)=>$x-$radial*$n,$relative,$up);
 $horizontal=moonSimVectorMagnitude($tangent);
 $height=$u-$radius;$gravity=$mu/($u*$u);
 $maxAcc=$thrust/$mass;
 $netBrake=$maxAcc-$gravity;
 if($netBrake<=0)throw new RuntimeException('Descent thrust cannot oppose lunar gravity');
 $stopDistance=$horizontal*$horizontal/(2*$netBrake);
 $ve=$isp*9.80665;$maxDv=$ve*log($mass/($mass-$fuel));
 $desiredVelocity=max(0.0,$horizontal-0.5*$maxDv);
 $brakeDirection=$horizontal>1e-6?array_map(static fn($x)=>-$x/$horizontal,$tangent):[0.0,0.0,0.0];
 $safe=$height>15000 && $height<200000 && $stopDistance<$height*20 && $maxDv>$horizontal*0.9;
 return [
 'altitude_m'=>$height,'radial_speed_m_s'=>$radial,'horizontal_speed_m_s'=>$horizontal,
 'maximum_dv_m_s'=>$maxDv,'available_thrust_accel_m_s2'=>$maxAcc,
 'braking_distance_reference_m'=>$stopDistance,
 'retrograde_direction_xyz'=>$brakeDirection,
 'suggested_horizontal_speed_after_initial_braking_m_s'=>$desiredVelocity,
 'descent_initialization_feasible'=>$safe,
 'status'=>$safe?'PRELIMINARY BRAKING TARGET AVAILABLE':'INHIBIT: UNSAFE DESCENT INITIAL CONDITIONS',
 'warning'=>'Necessary-condition braking reference; not a landing trajectory or terrain clearance guarantee.'
 ];
}
