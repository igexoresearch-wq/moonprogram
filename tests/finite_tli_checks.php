<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/finite_tli_targeting.php';
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
echo 'Finite TLI propagation checks passed; live lunar-target optimization is not yet certified.'.PHP_EOL;
