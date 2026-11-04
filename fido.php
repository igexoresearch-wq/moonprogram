<?php
declare(strict_types=1);
$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator authentication required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/orbit_maneuver_planner.php';
require_once __DIR__.'/shared/finite_tli_targeting.php';
function fidoRead(mysqli $conn,string $table):array{
 $result=$conn->query("SELECT attribute,value FROM $table");$rows=[];
 if(!$result)throw new RuntimeException('Telemetry unavailable');
 while($row=$result->fetch_assoc())$rows[$row['attribute']]=(float)$row['value'];
 return $rows;
}
function fidoXYZ(array $data,string $prefix=''):array{
 foreach(['x','y','z'] as $a)if(!isset($data[$prefix.$a]))throw new RuntimeException('Tracking vector incomplete');
 return [$data[$prefix.'x'],$data[$prefix.'y'],$data[$prefix.'z']];
}
function fidoDisplay($value):string{
 if(is_bool($value))return $value?'YES':'NO';
 if($value===null)return '---';
 if(is_array($value))return htmlspecialchars(json_encode($value),ENT_QUOTES,'UTF-8');
 if(is_float($value))return number_format($value,3,'.','');
 return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
}
$fields=[];$answer=null;$error='';
try{
 $cm=fidoRead($conn,'command_module');$moon=fidoRead($conn,'moon');
 $earth=[$earth_center_x,$earth_center_y,$earth_center_z];
 $craft=fidoXYZ($cm);$velocity=fidoXYZ($cm,'delta_');
 $moonPos=fidoXYZ($moon);$moonVel=fidoXYZ($moon,'delta_');
 $mu=$gravitational_constant*$earth_mass;$moonMu=$gravitational_constant*$moon_mass;
 $orbit=moonSimOrbitalElements($craft,$velocity,$earth,$mu);
 $fields=[
  'REFERENCE'=>'EARTH CENTER / SI',
  'ORBIT_ECC'=>$orbit['eccentricity'],
  'ALTITUDE_KM'=>($orbit['radius']-$earth_radius)/1000,
  'PERIGEE_KM'=>$orbit['periapsis_radius']===null?null:($orbit['periapsis_radius']-$earth_radius)/1000,
  'APOGEE_KM'=>$orbit['apoapsis_radius']===null?null:($orbit['apoapsis_radius']-$earth_radius)/1000,
  'V_INERTIAL_X'=>$velocity[0],'V_INERTIAL_Y'=>$velocity[1],'V_INERTIAL_Z'=>$velocity[2],
  'MOON_TRACK_X'=>$moonPos[0],'MOON_TRACK_Y'=>$moonPos[1],'MOON_TRACK_Z'=>$moonPos[2],
 ];
 if(($_GET['solve']??'')==='TLI'){
  $reference=moonSimTranslunarReference($craft,$velocity,$earth,[0,0,0],$moonPos,$mu);
  if(!$reference['valid_near_circular_departure'])throw new RuntimeException('Departure orbit radial velocity excessive');
  if((int)($cm['stage1']??0)!==0||(int)($cm['stage2']??0)!==0||(int)($cm['stage3']??0)!==1)
   throw new RuntimeException('TLI calculation requires attached S-IVB and no lower stages');
  $fuel=(float)($cm['s3fuel']??0);
  if($fuel<=0)throw new RuntimeException('No S-IVB propellant');
  $mass=$command_module_empty_mass+$service_module_empty_mass+
    $stage3_empty_mass+$fuel+max(0,(float)($cm['fuel']??0))+
    $lander_ascent_empty_mass+$lander_descent_empty_mass+
    max(0,(float)($cm['s2fuel']??0));
  // Mass estimate may omit specific mission consumables; display assumptions.
  $answer=moonSimFiniteTliSearch($craft,$velocity,$earth,$moonPos,$moonVel,
    $reference['reference_burn_direction'],
    max(0,(float)$reference['reference_signed_delta_v_m_s']),
    $mass,$fuel,$stage3_thrust,421.0,$mu,$moonMu,$moon_radius,$earth_radius);
  $answer['assumed_stack_mass_kg']=$mass;
  $answer['assumed_propellant_kg']=$fuel;
  $answer['engine_model']='S-IVB J-2 NOMINAL / 421 S';
 }
}catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>FIDO / Tracking and Targeting</title><style>body{background:#010601;color:#8ed790;font:16px 'Courier New',monospace;margin:0;padding:25px}
main{max-width:1000px;margin:auto}pre{white-space:pre-wrap}table{border-collapse:collapse;width:100%}
td{padding:7px 18px 7px 0;border-bottom:1px solid #1b371e}td:last-child{text-align:right;color:#d2f9b5}
a,button{background:none;border:1px solid #396d41;color:#b4f3ac;font:inherit;padding:9px;cursor:pointer}
h1{font-size:21px;letter-spacing:2px}h2{font-size:17px}section{margin:24px 0}strong{color:#ffad8a}
</style></head><body><main><h1>FLIGHT DYNAMICS OFFICER // FIDO</h1>
<p>TRACKING / EARTH REFERENCE &nbsp;&nbsp; NO EXTERNAL CAMERA</p><p><a href="ground.php">GROUND</a> <a href="orbit_maneuver.php">ADVISOR</a></p>
<section><h2>TELEMETRY STREAM</h2><table>
<?php foreach($fields as $key=>$value):?><tr><td><?=fidoDisplay($key)?></td><td><?=fidoDisplay($value)?></td></tr><?php endforeach;?></table></section>
<section><h2>GROUND COMPUTATION</h2>
<p>Tracking data are displayed separately from computed burn solutions. Request a calculation; do not treat the result as flight authorization.</p>
<form method="get"><button name="solve" value="TLI">RUN FINITE TLI SEARCH</button></form>
<?php if($error):?><p><strong>COMPUTATION FAILED: <?=fidoDisplay($error)?></strong></p><?php endif;?>
<?php if($answer):?><table>
<?php foreach($answer as $key=>$value):?><tr><td><?=fidoDisplay(strtoupper($key))?></td><td><?=fidoDisplay($value)?></td></tr><?php endforeach;?></table>
<p>NOTICE: Candidate is an estimate. Verify inclination, engine performance, and arrival geometry before transmitting guidance to crew. No engine commands issued.</p><?php endif;?>
</section><p>FIDO&gt; _</p></main></body></html>
