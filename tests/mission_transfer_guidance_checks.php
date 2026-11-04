<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/midcourse_tracking.php';
require_once __DIR__.'/../shared/coupled_loi_targeting.php';
function moonMissionCheck(bool $passed,string $label):void {
 if(!$passed)throw new RuntimeException('FAIL '.$label);
 echo 'PASS '.$label.PHP_EOL;
}
$earth=[0.0,0.0,0.0];$earthMu=3.986004418e14;$moonMu=4.9048695e12;
$r=6371000.0+180000.0;$v=sqrt($earthMu/$r);
$craft=[$r,0,0];$velocity=[0,$v,0];$moon=[384400000.0,0,0];
$moonV=[0,sqrt(($earthMu+$moonMu)/384400000.0),0];
$none=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonV,
 $earthMu,$moonMu,6371000,1737400,36000.0,null,60.0);
$zero=moonSimMidcoursePropagatedPreview($craft,$velocity,$earth,$moon,$moonV,
 $earthMu,$moonMu,6371000,1737400,36000.0,[0.0,0.0,0.0],60.0);
moonMissionCheck(abs($none['predicted_closest_lunar_altitude_km']-$zero['predicted_closest_lunar_altitude_km'])<1e-5,
 'Zero-impulse correction reproduces baseline');
moonMissionCheck(!$none['moon_impact'],'Nominal short coast avoids lunar collision');
try{
 moonSimCoupledLoi($craft,$velocity,$earth,$moon,$moonV,$earthMu,$moonMu,
  1737400,6371000,14000,100.0,90000,314.0,300.0);
 throw new RuntimeException('Fuel limit not enforced');
}catch(RuntimeException $e){
 if(str_contains($e->getMessage(),'not enforced'))throw $e;
 moonMissionCheck(true,'Coupled LOI refuses impossible fuel budget');
}
echo 'Source-level checks passed. Complete mission validation still required.'.PHP_EOL;
