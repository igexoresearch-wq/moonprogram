<?php
declare(strict_types=1);
/** Earth atmosphere is assumed to rotate with the planet around the z axis. */
function moonSimEntryState(array $p,array $v,array $earth,float $radius,float $omega=7.2921159e-5):array{
 $r=[$p[0]-$earth[0],$p[1]-$earth[1],$p[2]-$earth[2]];
 $d=sqrt($r[0]**2+$r[1]**2+$r[2]**2);
 if($d<=0)throw new InvalidArgumentException('Invalid Earth-centered position');
 $up=array_map(static fn($x)=>$x/$d,$r);
 $air=[-$omega*$r[1],$omega*$r[0],0.0];
 $wind=[$v[0]-$air[0],$v[1]-$air[1],$v[2]-$air[2]];
 $speed=sqrt(array_sum(array_map(static fn($x)=>$x*$x,$wind)));
 $vertical=$wind[0]*$up[0]+$wind[1]*$up[1]+$wind[2]*$up[2];
 $gamma=rad2deg(asin(max(-1,min(1,$vertical/max(1e-9,$speed)))));
 $alt=$d-$radius;
 return ['altitude_m'=>$alt,'air_relative_speed_m_s'=>$speed,'flight_path_angle_deg'=>$gamma,
   'vertical_speed_m_s'=>$vertical,'entry_interface'=> $alt<=120000&&$alt>=0,
   'entry_angle_reference_met'=>$alt<=120000&&$gamma>=-7&&$gamma<=-4,
   'recommended_bank_deg'=>$gamma< -7 ? 75.0 : ($gamma> -4 ? 0.0 : 40.0),
   'warning'=>'Entry corridor guidance reference. Actual bank authority is the CM roll command.'];
}
