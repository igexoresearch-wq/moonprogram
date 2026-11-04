<?php
declare(strict_types=1);
require_once __DIR__.'/midcourse_tracking.php';
function moonSimFiniteSpsDeparture(array $p,array $v,array $earth,array $moon,array $mv,
 float $em,float $mm,float $er,float $mr,float $wet,float $fuel,float $thrust,float $isp,
 array $dir,float $duration):array{
 $flow=$thrust/($isp*9.80665);
 if($wet<=$fuel||$fuel<=0||$flow<=0||$duration<=0||$flow*$duration>$fuel||
 abs(moonSimVectorMagnitude($dir)-1)>0.00001)throw new InvalidArgumentException('Invalid burn');
 $t=0.0;
 while($t<$duration){
  $dt=min(5.0,$duration-$t);
  $a=moonSimGravityAcceleration($p,$earth,$moon,$em,$mm);
  $ma=moonSimMoonAcceleration($moon,$earth,$em,$mm);
  for($i=0;$i<3;$i++)$a[$i]+=$thrust*$dir[$i]/($wet-$flow*$t);
  $np=moonSimVerletDrift($p,$v,$a,$dt);
  $nm=moonSimVerletDrift($moon,$mv,$ma,$dt);
  $a2=moonSimGravityAcceleration($np,$earth,$nm,$em,$mm);
  $ma2=moonSimMoonAcceleration($nm,$earth,$em,$mm);
  for($i=0;$i<3;$i++)$a2[$i]+=$thrust*$dir[$i]/($wet-$flow*($t+$dt));
  $v=moonSimVerletKick($v,$a,$a2,$dt);
  $mv=moonSimVerletKick($mv,$ma,$ma2,$dt);
  $p=$np;$moon=$nm;$t+=$dt;
  if(moonSimVectorMagnitude(moonSimRelativeVector($p,$moon))<=$mr||
  moonSimVectorMagnitude(moonSimRelativeVector($p,$earth))<=$er)
    throw new RuntimeException('Surface contact');
 }
 return ['position'=>$p,'velocity'=>$v,'moon'=>$moon,'moon_velocity'=>$mv,
 'fuel_used_kg'=>$flow*$duration,'fuel_remaining_kg'=>$fuel-$flow*$duration];
}
