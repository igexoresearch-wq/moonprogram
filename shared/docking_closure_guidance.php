<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** Piloted docking closure reference, never changes state or imposes docking. */
function moonSimDockingClosureReference(array $cmP,array $cmV,array $lmP,array $lmV,
 float $maximumClosure=1.0,float $captureRange=10.0):array{
 $separation=moonSimRelativeVector($cmP,$lmP);
 $distance=moonSimVectorMagnitude($separation);
 $dv=moonSimRelativeVector($cmV,$lmV);
 $speed=moonSimVectorMagnitude($dv);
 $line=$distance>0.001?array_map(static fn($x)=>$x/$distance,$separation):[1,0,0];
 $closing=-moonSimVectorDot($line,$dv);
 $side=[];for($j=0;$j<3;$j++)$side[$j]=$dv[$j]+$closing*$line[$j];
 $lateral=moonSimVectorMagnitude($side);
 return ['range_m'=>$distance,'closing_rate_m_s'=>$closing,'lateral_rate_m_s'=>$lateral,
  'relative_velocity_xyz'=>$dv,'line_of_sight_xyz'=>$line,
  'desired_relative_velocity_xyz'=>array_map(static fn($x)=>-$maximumClosure*$x,$line),
  'relative_velocity_correction_xyz'=>array_map(static fn($a,$b)=>$a-$b,
    array_map(static fn($x)=>-$maximumClosure*$x,$line),$dv),
  'in_proximity_hold_zone'=>$distance<$captureRange&&abs($closing)<0.2&&$lateral<0.2,
  'unsafe_fast_closure'=>$distance<100&&($closing>2.0||$lateral>1.0),
  'warning'=>'Relative-state piloting reference only. Docking also requires physical orientation and attachment capture conditions.'];
}
