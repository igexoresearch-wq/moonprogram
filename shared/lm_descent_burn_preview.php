<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** Constant-mass seed only; engine mass flow accounted stepwise. Moon fixed for
 * this short powered descent forecast. No simulator state is changed. */
function moonSimDescentBurnPreview(array $position,array $velocity,array $moonPosition,array $moonVelocity,
 float $mu,float $moonRadius,float $mass,float $fuel,float $thrust,float $isp,
 float $duration,float $step=1.0):array {
 if($mu<=0||$moonRadius<=0||$mass<=0||$fuel<=0||$fuel>=$mass||$thrust<=0||$isp<=0||
    $duration<=0||$step<=0||$duration>1200)throw new InvalidArgumentException('Invalid finite descent burn');
 $flow=$thrust/($isp*9.80665);
 if($flow*$duration>$fuel)throw new RuntimeException('Descent burn requires more propellant than available');
 $r=moonSimRelativeVector($position,$moonPosition);$v=moonSimRelativeVector($velocity,$moonVelocity);
 $height0=moonSimVectorMagnitude($r)-$moonRadius;
 $spent=0.0;$elapsed=0.0;$contact=false;$direction=null;
 while($elapsed<$duration-1e-8){
  $dt=min($step,$duration-$elapsed);
  $radius=moonSimVectorMagnitude($r);
  if($radius<=$moonRadius){$contact=true;break;}
  $up=array_map(static fn($x)=>$x/$radius,$r);
  $radial=moonSimVectorDot($v,$up);
  $tangent=[];for($i=0;$i<3;$i++)$tangent[$i]=$v[$i]-$radial*$up[$i];
  $tmag=moonSimVectorMagnitude($tangent);
  // Predominantly retrograde braking with sufficient upward support.
  $direction=[];for($i=0;$i<3;$i++)$direction[$i]=$up[$i]*0.65-($tmag>0.01?$tangent[$i]/$tmag*0.76:0.0);
  $norm=moonSimVectorMagnitude($direction);
  for($i=0;$i<3;$i++)$direction[$i]/=$norm;
  $accel=$thrust/($mass-$spent);
  for($i=0;$i<3;$i++){
   $v[$i]+=(-$mu*$r[$i]/($radius**3)+$accel*$direction[$i])*$dt;
   $r[$i]+=$v[$i]*$dt;
  }
  $spent+=$flow*$dt;$elapsed+=$dt;
 }
 $finalRadius=moonSimVectorMagnitude($r);
 $up=array_map(static fn($x)=>$x/max($finalRadius,1),$r);
 $radial=moonSimVectorDot($v,$up);
 $speed=moonSimVectorMagnitude($v);
 $horizontal=sqrt(max(0.0,$speed*$speed-$radial*$radial));
 return ['initial_altitude_m'=>$height0,'burn_seconds_elapsed'=>$elapsed,
 'ending_altitude_m'=>$finalRadius-$moonRadius,'ending_radial_speed_m_s'=>$radial,
 'ending_horizontal_speed_m_s'=>$horizontal,'fuel_used_kg'=>$spent,
 'lunar_contact_during_burn'=>$contact,
 'candidate_burn_direction_at_cutoff_xyz'=>$direction,
 'status'=>$contact?'REJECT: LUNAR CONTACT':'ADVISORY BRAKING PREVIEW',
 'warning'=>'Fixed-Moon preliminary burn preview with dynamic thrust mass. Real descent must continue to be monitored in live physics; no landing guarantee.'];
}
