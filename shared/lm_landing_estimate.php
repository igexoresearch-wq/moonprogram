<?php
/** Lunar surface touchdown/landing envelope estimated from physical telemetry. */
function moonSimLmLandingEstimate(float $altitude,float $radial,float $horizontal,float $mu,float $radius):array{
 if($radius<=0||$mu<=0||!is_finite($altitude)||!is_finite($radial)||!is_finite($horizontal))
  throw new InvalidArgumentException('Invalid landing telemetry');
 $gravity=$mu/(($radius+max(0.0,$altitude))**2);
 $ballisticTime=$radial<0&&$altitude>0
  ?(sqrt($radial*$radial+2*$gravity*$altitude)+$radial)/$gravity:null;
 $contact=$altitude<=0;
 $withinEnvelope=$contact?abs($radial)<=2.0&&$horizontal<=2.0:
   $altitude<100.0&&abs($radial)<=4.0&&$horizontal<=3.0;
 return ['altitude_m'=>$altitude,'vertical_velocity_m_s'=>$radial,
  'horizontal_velocity_m_s'=>$horizontal,'time_to_surface_unpowered_s'=>$ballisticTime,
  'touchdown_or_close_approach'=>$contact,'soft_landing_envelope'=>$withinEnvelope,
  'warning'=>'Ballistic time is informational only; powered descent remains controlled by pilot and physics.'];
}
