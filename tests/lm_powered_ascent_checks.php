<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/lm_finite_ascent_profile.php';
require_once __DIR__.'/../shared/lm_ascent_validation.php';
function requireAscent(bool $condition,string $description):void {
 if(!$condition)throw new RuntimeException($description);
 echo "PASS: ".$description.PHP_EOL;
}
$mu=4.9048695e12;
$radius=1737400.0;
$zero=[0.0,0.0,0.0];
$start=[$radius+2.0,0.0,0.0];
$velocity=[0.0,0.0,0.0];
$wet=4700.0;$fuel=2300.0;$thrust=15600.0;$isp=311.0;
$burn=20.0;
$flight=moonSimLunarAscentPropagate($start,$velocity,$zero,$zero,
 $mu,$radius,$wet,$fuel,$thrust,$isp,$burn,50000.0,0.0,0.5);
requireAscent(!$flight['lunar_impact'],'APS launch lifts clear of surface');
requireAscent($flight['final_position_moon_xyz'][0]>$start[0],
 'APS-powered launch gains radial distance');
requireAscent(abs($flight['burn_duration_s']-$burn)<1e-8,'APS burn time accounted');
$expectedFuel=$thrust/($isp*9.80665)*$burn;
requireAscent(abs($flight['fuel_consumed_kg']-$expectedFuel)<1e-6,
 'APS propellant consumption matches thrust and specific impulse');
requireAscent($flight['fuel_consumed_kg']<$fuel,'APS burn within tank capacity');
$telemetry=['descent'=>0,'fuel_ascent'=>$fuel-$flight['fuel_consumed_kg'],'thrust'=>0];
$cm=['dock'=>0,'stage3'=>0];
$event=['event_name'=>'ORBIT_CUTOFF','descent_attached'=>0,'fuel_kg'=>$telemetry['fuel_ascent']];
requireAscent(moonSimAscentStagePersistenceCheck($telemetry,$cm,$event,
 $radius+100000.0,$radius)['pass'],'Stage/APS cutoff persistence example');
$bad=$telemetry;$bad['descent']=1;
requireAscent(!moonSimAscentStagePersistenceCheck($bad,$cm,$event,
 $radius+100000.0,$radius)['pass'],'Attached descent stage blocks validation');
echo "This verifies powered initial ascent and persistence predicates, NOT complete orbital insertion or database transactions.".PHP_EOL;
