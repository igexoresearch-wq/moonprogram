<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';
/** Moon-relative ascent targeting from current LM state and available APS propellant. */
function moonSimLunarAscentTargets(array $p,array $v,array $moonP,array $moonV,
 float $mu,float $radius,float $mass,float $fuel,float $thrust,float $isp,
 float $orbitAltitude=100000.0):array{
 if($mu<=0||$radius<=0||$mass<=0||$fuel<=0||$fuel>=$mass||$thrust<=0||$isp<=0||$orbitAltitude<20000)
  throw new InvalidArgumentException('Invalid ascent inputs');
 $rel=moonSimRelativeVector($p,$moonP);$r=moonSimVectorMagnitude($rel);
 if($r<$radius-1000)throw new RuntimeException('Vehicle below lunar surface');
 $up=array_map(static fn($x)=>$x/max(1.0,$r),$rel);
 $rv=moonSimRelativeVector($v,$moonV);
 $radial=moonSimVectorDot($rv,$up);
 $horizontal=[$rv[0]-$radial*$up[0],$rv[1]-$radial*$up[1],$rv[2]-$radial*$up[2]];
 $currentHorizontal=moonSimVectorMagnitude($horizontal);
 $heading=$currentHorizontal>1?array_map(static fn($x)=>$x/$currentHorizontal,$horizontal):moonSimVectorCross([0,0,1],$up);
 $hn=moonSimVectorMagnitude($heading);
 if($hn<1e-8)$heading=moonSimVectorCross([0,1,0],$up);
 $hn=moonSimVectorMagnitude($heading);$heading=array_map(static fn($x)=>$x/$hn,$heading);
 $alt=$r-$radius;$targetRadius=$radius+$orbitAltitude;
 $desiredCircular=sqrt($mu/$targetRadius);$maxDv=$isp*9.80665*log($mass/($mass-$fuel));
 $availableTwr=$thrust/$mass/($mu/($r*$r));
 $remainingAltitude=max(0.0,$orbitAltitude-$alt);
 $targetRadial=min(350.0,sqrt(max(0.0,2.0*($thrust/$mass-$mu/($r*$r))*$remainingAltitude)));
 $requiredHorizontalDv=max(0.0,$desiredCircular-$currentHorizontal);
 // Conservative live ideal delta-v budget: include vertical residual and a
 // gravity-loss allowance over the horizontal acceleration time.
 $horizontalAcceleration=max(1e-6,$thrust/$mass);
 $timeToGain=$requiredHorizontalDv/$horizontalAcceleration;
 $estimatedGravityLoss=($mu/($r*$r))*min(600.0,$timeToGain)*0.25;
 $totalRequiredDv=sqrt($requiredHorizontalDv**2+max(0.0,$targetRadial-$radial)**2)
   +$estimatedGravityLoss;
 $fuelRequired=$mass*(1-exp(-$totalRequiredDv/($isp*9.80665)));
 $fuelReserve=$fuel-$fuelRequired;
 $pitchDeg=$alt<3000?75.0:($alt<20000?45.0:($alt<$orbitAltitude*0.8?15.0:0.0));
 if($alt>=20000) $pitchDeg=max(-5.0,min(40.0,0.05*($targetRadial-$radial)));
 // Circular-orbit insertion requires bound energy and a safe perilune,
 // not merely altitude or a near-circular instantaneous speed.
 $elements=moonSimOrbitalElements($rel,$rv,[0,0,0],$mu);
 $peri=$elements['periapsis_radius']===null?null:$elements['periapsis_radius']-$radius;
 $apo=$elements['apoapsis_radius']===null?null:$elements['apoapsis_radius']-$radius;
 $insertionReady=$elements['specific_energy']<0 && $peri!==null
  && $peri>=30000 && $apo!==null && abs($apo-$orbitAltitude)<20000
  && abs($radial)<40 && abs($currentHorizontal-$desiredCircular)<50;
 $pitch=deg2rad($pitchDeg);
 $dir=[];
 for($i=0;$i<3;$i++)$dir[$i]=$up[$i]*sin($pitch)+$heading[$i]*cos($pitch);
 return [
  'altitude_m'=>$alt,'radial_speed_m_s'=>$radial,'horizontal_speed_m_s'=>$currentHorizontal,
  'desired_orbit_altitude_m'=>$orbitAltitude,'circular_speed_at_target_m_s'=>$desiredCircular,
  'available_ideal_delta_v_m_s'=>$maxDv,'initial_thrust_to_weight'=>$availableTwr,
  'estimated_required_delta_v_m_s'=>$totalRequiredDv,
  'estimated_required_fuel_kg'=>$fuelRequired,
  'estimated_fuel_reserve_kg'=>$fuelReserve,
  'target_radial_speed_m_s'=>$targetRadial,'horizontal_speed_shortfall_m_s'=>$requiredHorizontalDv,
  'estimated_horizontal_acceleration_s'=>$requiredHorizontalDv/max(1e-5,$thrust/$mass),
  'recommended_pitch_deg'=>$pitchDeg,'reference_thrust_direction_xyz'=>$dir,
  'ascent_possible'=>$availableTwr>1.0&&$fuelReserve>0,
  'predicted_perilune_altitude_m'=>$peri,
  'predicted_apolune_altitude_m'=>$apo,
  'cutoff_reference_met'=>$insertionReady,
  'warning'=>'Preliminary lunar ascent pitch schedule; not a closed-loop orbital insertion guarantee.'
 ];
}
