<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/finite_tli_targeting.php';
require_once __DIR__.'/../shared/tli_stack_mass.php';
function checkTli(bool $ok,string $description):void{
 if(!$ok)throw new RuntimeException('FAIL: '.$description);
 echo 'PASS '.$description.PHP_EOL;
}
$earth=[0.0,0.0,0.0];$mu=3.986004418e14;$lunarMu=4.9048695e12;
$r=6371000.0+180000.0;$v=sqrt($mu/$r);
$craft=[$r,0,0];$velocity=[0,$v,0];
$moon=[384400000.0,0,0];$moonVelocity=[0,sqrt(($mu+$lunarMu)/384400000.0),0];
$dir=[0.0,1.0,0.0];$mass=140000.0;$fuel=90000.0;
$thrust=900000.0;$isp=421.0;$burn=120.0;
$preview=moonSimFiniteTliPreview($craft,$velocity,$earth,$moon,$moonVelocity,
 $dir,0.0,$burn,$mass,$fuel,$thrust,$isp,$mu,$lunarMu,1737400.0,6371000.0,
 30000.0,60.0);
$expected=$burn*$thrust/($isp*9.80665);
checkTli(abs($preview['propellant_used_kg']-$expected)<1e-5,'Mass flow follows engine thrust and Isp');
checkTli(abs($preview['propellant_remaining_kg']-($fuel-$expected))<1e-5,'Fuel conservation');
checkTli($preview['burn_duration_seconds']===$burn,'Finite burn duration retained');
checkTli(!$preview['earth_impact_detected'],'Nominal test burn does not strike Earth');
try {
 moonSimFiniteTliPreview($craft,$velocity,$earth,$moon,$moonVelocity,
  $dir,0,200000.0,$mass,$fuel,$thrust,$isp,$mu,$lunarMu,1737400,6371000,300000,60);
 throw new RuntimeException('Fuel overflow accepted');
}catch(RuntimeException $e){
 if(str_contains($e->getMessage(),'accepted'))throw $e;
 echo 'PASS impossible burn is refused'.PHP_EOL;
}
$delayed=moonSimFiniteTliPreview($craft,$velocity,$earth,$moon,$moonVelocity,
 $dir,1200.0,120.0,$mass,$fuel,$thrust,$isp,$mu,$lunarMu,1737400.0,6371000.0,
 30000.0,60.0);
checkTli(abs($delayed['propellant_used_kg']-$expected)<1e-4,'Delayed ignition retains finite burn mass flow');
checkTli($delayed['ignition_direction_inertial_xyz']!==null,'Delayed coast computes ignition-frame attitude');
checkTli(abs($delayed['ignition_direction_inertial_xyz'][0])>0.01,'Delayed ignition follows rotated orbital frame');
$c=['csm_rcs_initial_propellant'=>600.0,'lm_rcs_initial_propellant'=>250.0,
 'command_module_empty_mass'=>5000.0,'stage3_empty_mass'=>10000.0,
 'service_module_empty_mass'=>5000.0,'escape_tower_empty_mass'=>3000.0,
 'lander_ascent_empty_mass'=>2000.0,'lander_descent_empty_mass'=>2000.0];
$cm=['stage1'=>0,'stage2'=>0,'stage3'=>1,'s3fuel'=>4000,'service_module'=>1,
 'fuel'=>1000,'RCS'=>100,'escape'=>0];
$lm=['fuel_ascent'=>300,'descent'=>1,'fuel'=>400,'RCS'=>50];
$expectedMass=5000+10000+4000+5000+1000+100+2000+300+2000+400+50;
checkTli(abs(moonSimTliStackMass($cm,$lm,$c)-$expectedMass)<1e-5,
 'S-IVB wet mass counts CSM and attached LM propellant without detached S-II');
echo 'Finite TLI source-level regression checks defined; run on a PHP checkout.'.PHP_EOL;
