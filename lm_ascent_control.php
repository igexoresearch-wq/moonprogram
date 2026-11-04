<?php
declare(strict_types=1);
session_start();
$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator authentication required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/lm_finite_ascent_profile.php';
$ascentPlan=null;$ascentError=null;
if(($_GET['study']??'')==='1'){
 try{
  $load=function(string $table)use($conn):array{
   $r=$conn->query("SELECT attribute,value FROM $table");$out=[];
   if($r)while($row=$r->fetch_assoc())$out[$row['attribute']]=(float)$row['value'];
   return $out;
  };
  $lm=$load('telemetry');$moon=$load('moon');
  $vector=static fn(array $a,string $p=''):array=>[(float)($a[$p.'x']??0),(float)($a[$p.'y']??0),(float)($a[$p.'z']??0)];
  $wet=$lander_ascent_empty_mass+max(0,(float)($lm['fuel_ascent']??0));
  $ascentPlan=moonSimLunarAscentProfileSearch(
    $vector($lm),$vector($lm,'delta_'),$vector($moon),$vector($moon,'delta_'),
    $gravitational_constant*$moon_mass,$moon_radius,
    $wet,max(0,(float)($lm['fuel_ascent']??0)),$lander_ascent_thrust,311.0);
 }catch(Throwable $e){$ascentError=$e->getMessage();}
}
if(!isset($_SESSION['ascent_csrf']))$_SESSION['ascent_csrf']=bin2hex(random_bytes(16));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!hash_equals($_SESSION['ascent_csrf'],(string)($_POST['csrf']??'')))exit('Invalid command token');
 $mode=(string)($_POST['mode']??'');
 if($mode==='manual'){
  $conn->query("UPDATE lm_ascent_guidance SET enabled=0,status='MANUAL' WHERE id=1");
  $conn->query("UPDATE telemetry SET value=0 WHERE attribute IN ('thrust','pitch_rate','yaw_rate','roll_rate')");
 }elseif($mode==='guidance'){
  $alt=filter_var($_POST['altitude']??null,FILTER_VALIDATE_INT);
  if($alt===false||$alt<30000||$alt>300000)exit('Invalid target altitude');
  $q=$conn->query("SELECT attribute,value FROM telemetry WHERE attribute IN ('descent','fuel_ascent')");
  $state=[];if($q)while($row=$q->fetch_assoc())$state[$row['attribute']]=(float)$row['value'];
  if((int)($state['descent']??1)!==0||(float)($state['fuel_ascent']??0)<=0)exit('Separate descent stage and verify APS fuel');
  $conn->query("UPDATE lm_ascent_guidance SET enabled=1,status='GUIDED ASCENT',target_altitude_m=$alt WHERE id=1");
 }
}
$q=$conn->query("SELECT * FROM lm_ascent_guidance WHERE id=1");$row=$q?$q->fetch_assoc():[];
function ascentOut($x):string{return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><meta charset="utf-8"><title>LM Ascent</title>
<style>body{background:#131811;color:#d4e4c8;font:17px monospace;max-width:740px;margin:30px auto}button,input{padding:12px;background:#354335;color:white;border:1px solid #74866c}</style></head><body>
<h1>LM APS — ASCENT GUIDANCE</h1><p><a href="?study=1">CALCULATE LIVE FINITE ASCENT PROFILE</a></p>
<?php if($ascentError):?><p>PROFILE NOT AVAILABLE: <?=ascentOut($ascentError)?></p><?php endif;?>
<?php if($ascentPlan):?><h2>APS FLIGHT PROFILE / REFERENCE ONLY</h2><pre><?=ascentOut(json_encode($ascentPlan,JSON_PRETTY_PRINT))?></pre><?php endif;?><p>STATUS: <?=ascentOut($row['status']??'---')?>. Normal thrust, propellant and quaternion attitude dynamics apply. The pilot may disengage anytime.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=ascentOut($_SESSION['ascent_csrf'])?>">
<label>Desired orbital altitude (m) <input type="number" name="altitude" min="30000" max="300000" value="<?=ascentOut($row['target_altitude_m']??100000)?>"></label>
<button name="mode" value="guidance">ENABLE APS ASCENT GUIDANCE</button>
<button name="mode" value="manual" formnovalidate>MANUAL TAKEOVER / THRUST OFF</button></form><p>Required: descent-stage separation, engine availability and a verified ascent trajectory. The pitch program is an estimate, not guaranteed orbit insertion.</p><a href="pilot.php" style="color:#c6e5ae">LM PILOT</a></body></html>
