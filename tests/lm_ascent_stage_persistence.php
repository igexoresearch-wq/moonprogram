<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/lm_ascent_validation.php';
$radius=1737400.0;
$telemetry=['descent'=>0,'fuel_ascent'=>525.5,'thrust'=>0];
$cm=['dock'=>0,'stage3'=>0];
$event=['event_name'=>'ORBIT_CUTOFF','descent_attached'=>0,'fuel_kg'=>525.5];
$ok=moonSimAscentStagePersistenceCheck($telemetry,$cm,$event,$radius+100000,$radius);
if(!$ok['pass'])throw new RuntimeException('Nominal stage persistence failed');
$bad=$telemetry;$bad['descent']=1;
if(moonSimAscentStagePersistenceCheck($bad,$cm,$event,$radius+100000,$radius)['pass'])
 throw new RuntimeException('Failed to catch attached stage');
$bad=$event;$bad['fuel_kg']=900;
if(moonSimAscentStagePersistenceCheck($telemetry,$cm,$bad,$radius+100000,$radius)['pass'])
 throw new RuntimeException('Failed to catch fuel disagreement');
if(moonSimAscentStagePersistenceCheck($telemetry,$cm,$event,$radius+20000,$radius)['pass'])
 throw new RuntimeException('Failed to catch perilune hazard');
$bad=$telemetry;$bad['fuel_ascent']=NAN;
if(moonSimAscentStagePersistenceCheck($bad,$cm,$event,$radius+100000,$radius)['pass'])
 throw new RuntimeException('Failed to reject nonfinite APS fuel');
$bad=$telemetry;unset($bad['thrust']);
if(moonSimAscentStagePersistenceCheck($bad,$cm,$event,$radius+100000,$radius)['pass'])
 throw new RuntimeException('Failed to reject missing thrust state');
$bad=$event;$bad['fuel_kg']=INF;
if(moonSimAscentStagePersistenceCheck($telemetry,$cm,$bad,$radius+100000,$radius)['pass'])
 throw new RuntimeException('Failed to reject corrupt cutoff log');
$aps=['Ascent Engine'=>'OFF'];
if(!moonSimAscentStagePersistenceCheck($telemetry,$cm,$event,$radius+100000,$radius,$aps)['pass'])
 throw new RuntimeException('APS OFF engine-state persistence failed');
if(moonSimAscentStagePersistenceCheck($telemetry,$cm,$event,$radius+100000,$radius,
 ['Ascent Engine'=>'ON'])['pass'])
 throw new RuntimeException('Failed to reject burning APS after insertion');
echo "PASS: staged ascent state and cutoff event consistency\\n";
