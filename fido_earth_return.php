<?php
declare(strict_types=1);
$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator login required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/tei_return_targeting.php';
require_once __DIR__.'/shared/earth_return_corridor.php';
function erRows(mysqli $conn,string $table):array{
 $q=$conn->query("SELECT attribute,value FROM $table");$data=[];
 if(!$q)throw new RuntimeException('Missing tracking table');
 while($r=$q->fetch_assoc())$data[$r['attribute']]=(float)$r['value'];
 return $data;
}
function erVector(array $r,string $p=''):array{return [(float)($r[$p.'x']??0),(float)($r[$p.'y']??0),(float)($r[$p.'z']??0)];}
function erOut($x):string{return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
$answer=[];$error='';
try{
 $cm=erRows($conn,'command_module');$moon=erRows($conn,'moon');
 $earth=[$earth_center_x,$earth_center_y,$earth_center_z];
 $position=erVector($cm);$velocity=erVector($cm,'delta_');
 $moonP=erVector($moon);$moonV=erVector($moon,'delta_');
 if(($_GET['study']??'')==='RETURN'){
  $answer=moonSimReturnCourseSearch($position,$velocity,$earth,$moonP,$moonV,
   $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
   $earth_radius,$moon_radius);
 }
 if(($_GET['study']??'')==='TEI'){
  $fuel=max(0.0,(float)($cm['fuel']??0));
  $mass=$command_module_empty_mass+$service_module_empty_mass+$fuel;
  $answer=moonSimTeiReference($position,$velocity,$earth,$moonP,$moonV,
   $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
   $earth_radius,$moon_radius,$mass,$fuel,$service_module_thrust,314.0);
 }
}catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html><meta charset="utf-8"><title>FIDO Earth Return</title>
<body style="background:#0d1511;color:#d2e7ce;font:16px monospace;margin:30px auto;max-width:850px">
<h1>FIDO / EARTH RETURN</h1><p>Ground computer predictions, not automatic burns.</p>
<form method="get"><button name="study" value="TEI">SEARCH TRANS-EARTH DEPARTURE</button>
<button name="study" value="RETURN">SEARCH RETURN COURSE CORRECTIONS</button></form>
<?php if($error):?><p>NO SOLUTION: <?=erOut($error)?></p><?php endif;?>
<?php if($answer):?><pre><?=erOut(json_encode($answer,JSON_PRETTY_PRINT))?></pre><?php endif;?>
<p>Flight Director: verify time, direction, SPS fuel and predicted corridor before requesting crew action.</p>
<a href="csm_burn_control.php" style="color:#bbdabb">CREW SPS BURN CONTROL</a></body></html>
