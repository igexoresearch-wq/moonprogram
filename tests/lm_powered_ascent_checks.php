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

/*
 * Complete fuel-limited reference trajectory from lunar liftoff into a
 * bound orbit. Unlike the short liftoff check, orbital acceptance uses
 * the propagated postburn elements, not a fabricated circular state.
 */
$insertion=moonSimLunarAscentProfileSearch(
 $start,$velocity,$zero,$zero,$mu,$radius,$wet,$fuel,$thrust,$isp,100000.0
);
requireAscent(!$insertion['lunar_impact'],'Full reference ascent avoids lunar impact');
requireAscent($insertion['lunar_bound'],'Full reference ascent reaches bound lunar orbit');
requireAscent($insertion['final_perilune_altitude_m']>=30000,
 'Full reference ascent maintains safe lunar periapsis');
requireAscent($insertion['final_apolune_altitude_m']!==null,
 'Full reference ascent has finite lunar apoapsis');
requireAscent($insertion['fuel_consumed_kg']>0 &&
 $insertion['fuel_consumed_kg']<=$fuel,'Full ascent respects APS fuel mass');
requireAscent($insertion['burn_duration_s']>20.0,
 'Full ascent includes sustained powered flight');
$remaining=$fuel-$insertion['fuel_consumed_kg'];
$telemetry=['descent'=>0,'fuel_ascent'=>$remaining,'thrust'=>0];
$event=['event_name'=>'ORBIT_CUTOFF','descent_attached'=>0,'fuel_kg'=>$remaining];
requireAscent(moonSimAscentStagePersistenceCheck(
 $telemetry,$cm,$event,$radius+$insertion['final_perilune_altitude_m'],$radius
)['pass'],'Actual postburn orbit state passes ascent-stage cutoff invariants');
$unsafe=$telemetry;$unsafe['descent']=1;
requireAscent(!moonSimAscentStagePersistenceCheck(
 $unsafe,$cm,$event,$radius+$insertion['final_perilune_altitude_m'],$radius
)['pass'],'Unseparated descent stage fails postburn verification');
echo 'Full APS reference burn: '.json_encode([
 'burn_s'=>$insertion['burn_duration_s'],
 'fuel_used_kg'=>$insertion['fuel_consumed_kg'],
 'perilune_m'=>$insertion['final_perilune_altitude_m'],
 'apolune_m'=>$insertion['final_apolune_altitude_m']
 ],JSON_THROW_ON_ERROR).PHP_EOL;
echo "Reference trajectory uses same finite-ascent model as planner; DB worker lifecycle is independently validated at deployment.".PHP_EOL;

