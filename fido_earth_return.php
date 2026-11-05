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
require_once __DIR__.'/shared/return_abort_budget.php';
require_once __DIR__.'/shared/entry_guidance.php';
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
 $entryReference=moonSimEntryState($position,$velocity,$earth,$earth_radius);
 if(($_GET['study']??'')==='RETURN'){
  $answer=moonSimReturnCourseSearch($position,$velocity,$earth,$moonP,$moonV,
   $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
   $earth_radius,$moon_radius);
  $fuel=max(0.0,(float)($cm['fuel']??0));
  if($fuel>0&&(int)($cm['service_module']??0)===1){
   $mass=$command_module_empty_mass+$service_module_empty_mass+$fuel;
   $answer['finite_sps_correction']=moonSimFiniteReturnCorrection(
     $position,$velocity,$earth,$moonP,$moonV,
     $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
     $earth_radius,$moon_radius,$mass,$fuel,$service_module_thrust,314.0);
  }
  if(!empty($answer['candidate'])){
   $answer['entry_interface_estimate_seconds']=$answer['candidate']['return_preview']['numerical_preview']['entry_interface_120km_time_s']??null;
   $answer['closest_earth_arrival_seconds']=$answer['candidate']['return_preview']['numerical_preview']['post_lunar_earth_closest_approach_time_s']??null;
  }
 }
 if(in_array(($_GET['study']??''),['TEI','ABORT'],true)){
  $fuel=max(0.0,(float)($cm['fuel']??0));
  $mass=$command_module_empty_mass+$service_module_empty_mass+$fuel;
  $answer=moonSimTeiReference($position,$velocity,$earth,$moonP,$moonV,
   $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
   $earth_radius,$moon_radius,$mass,$fuel,$service_module_thrust,314.0);
  if(!empty($answer['candidate'])){
   $answer['manual_return_fuel_budget']=moonSimReturnAbortBudget($mass,$fuel,$service_module_thrust,314.0,
     (float)$answer['candidate']['duration_seconds']);
   $answer['recovery_gate']=moonSimReturnRecoveryGate($answer['manual_return_fuel_budget'],$answer['candidate']['predicted_return'],$cm);
   $answer['recovery_entry_candidate']=$answer['candidate']['predicted_return']['post_lunar_earth_min_altitude_m']>=65000&&$answer['candidate']['predicted_return']['post_lunar_earth_min_altitude_m']<=140000;
  }
 }
}catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html><meta charset="utf-8"><title>FIDO Earth Return</title>
<body style="background:#0d1511;color:#d2e7ce;font:16px monospace;margin:30px auto;max-width:850px"><?php require_once __DIR__.'/shared/station_navigation.php'; moonSimStationNav('EARTH RETURN'); ?>
<h1>FIDO / EARTH RETURN</h1><p>Ground computer predictions, not automatic burns.</p>
<form method="get"><button name="study" value="TEI">SEARCH TRANS-EARTH DEPARTURE</button>
<button name="study" value="RETURN">SEARCH RETURN COURSE CORRECTIONS</button><button name="study" value="ABORT">ABORT RETURN STUDY</button></form>
<h2>LIVE EARTH ENTRY REFERENCE</h2><pre><?=erOut(json_encode($entryReference??[],JSON_PRETTY_PRINT))?></pre>
<?php if($error):?><p>NO SOLUTION: <?=erOut($error)?></p><?php endif;?>
<?php if($answer):?><pre><?=erOut(json_encode($answer,JSON_PRETTY_PRINT))?></pre><?php endif;?>
<p>Flight Director: verify time, direction, SPS fuel and predicted corridor before requesting crew action.</p>
<a href="csm_burn_control.php" style="color:#bbdabb">CREW SPS BURN CONTROL</a></body></html>
