<?php
declare(strict_types=1);
session_start();
$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator login required');
}
require __DIR__.'/physics/db.php';
if(!isset($_SESSION['moon_burn_csrf']))$_SESSION['moon_burn_csrf']=bin2hex(random_bytes(16));
$message='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!hash_equals($_SESSION['moon_burn_csrf'],(string)($_POST['csrf']??'')))exit('Invalid request token');
 $action=(string)($_POST['action']??'');
 if($action==='cancel'){
  $conn->query("UPDATE mission_burn_plan SET status='CANCELLED' WHERE id=1");
  $conn->query("UPDATE command_module SET value=0 WHERE attribute IN ('thrust','pitch_rate','yaw_rate','roll_rate')");
  $conn->query("UPDATE EngineStatus SET status='OFF' WHERE engine_stage IN ('Service Module','Third Stage')");
  $message='BURN CANCELLED';
 }elseif($action==='arm'){
  $r=$conn->query("SELECT seconds FROM mission_clock WHERE id=1");
  $clock=$r?$r->fetch_assoc():null;$met=(int)($clock['seconds']??-900);
  $phase=(string)($_POST['phase']??'TLI');
  if(!in_array($phase,['TLI','MCC','LOI','TEI'],true))exit('Unknown maneuver phase');
  $stateQuery=$conn->query("SELECT attribute,value FROM command_module WHERE attribute IN ('stage3','service_module','s3fuel','fuel','ABORT')");
  $state=[];
  if($stateQuery)while($row=$stateQuery->fetch_assoc())$state[$row['attribute']]=(float)$row['value'];
  $ready=$phase==='TLI'
   ?(int)($state['stage3']??0)===1&&(float)($state['s3fuel']??0)>0
   :(int)($state['stage3']??0)===0&&(int)($state['service_module']??0)===1&&(float)($state['fuel']??0)>0;
  if(!$ready||(int)($state['ABORT']??0)!==0)exit('Engine or vehicle configuration unavailable for requested burn');
  $delay=filter_var($_POST['delay']??null,FILTER_VALIDATE_INT);
  $duration=filter_var($_POST['duration']??null,FILTER_VALIDATE_INT);
  $dir=[];foreach(['x','y','z'] as $axis)$dir[]=filter_var($_POST[$axis]??null,FILTER_VALIDATE_FLOAT);
  $norm=sqrt((float)$dir[0]**2+(float)$dir[1]**2+(float)$dir[2]**2);
  if($met<0||$delay===false||$duration===false||$delay<90||$duration<10||$duration>900||$norm<0.5||!is_finite($norm))
   exit('Invalid burn plan');
  $values=array_map(static fn($v)=>sprintf('%.12f',(float)$v/$norm),$dir);
  $start=$met+$delay;$end=$start+$duration;
  $conn->query("REPLACE INTO mission_burn_plan(id,phase,status,ignition_met,cutoff_met,dir_x,dir_y,dir_z)
  VALUES(1,'$phase','ARMED',$start,$end,{$values[0]},{$values[1]},{$values[2]})");
  $message='BURN PLAN ARMED — MONITOR ATTITUDE AND THRUST';
 }
}
$r=$conn->query("SELECT * FROM mission_burn_plan WHERE id=1");
$plan=$r?$r->fetch_assoc():null;
function out($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><meta charset="utf-8"><title>CSM Burn Control</title>
<style>body{background:#14191b;color:#d8dfd6;font:18px monospace;padding:25px;max-width:750px;margin:auto}input,button{background:#30383b;color:#fff;padding:12px;margin:6px;border:1px solid #90968c}label{display:block}</style></head><body><?php require_once __DIR__.'/shared/station_navigation.php'; moonSimStationNav('CSM BURNS'); ?>
<h1>CSM / BURN CONTROL</h1><p>Physical S-IVB or SPS burn; AGC-on and attitude alignment required. Enter verified FIDO numbers. This does not calculate the maneuver.</p>
<p><?=out($message)?></p><p>CURRENT PLAN: <?=out($plan['status']??'NONE')?> / PHASE <?=out($plan['phase']??'---')?> / IGNITION MET <?=out($plan['ignition_met']??'---')?> / CUTOFF MET <?=out($plan['cutoff_met']??'---')?></p>
<form method="post"><input type="hidden" name="csrf" value="<?=out($_SESSION['moon_burn_csrf'])?>">
<label>Burn type <select name="phase"><option value="TLI">TLI / S-IVB</option><option value="MCC">MIDCOURSE / SPS</option><option value="LOI">LUNAR ORBIT INSERTION / SPS</option><option value="TEI">TRANS EARTH INJECTION / SPS</option></select></label><label>Ignition delay (seconds, minimum 90) <input type="number" name="delay" min="90" required></label>
<label>Burn duration (seconds) <input type="number" name="duration" min="10" max="900" required></label>
<?php foreach(['x','y','z'] as $axis):?><label>Direction <?=strtoupper($axis)?> <input type="number" step="any" name="<?=$axis?>" required></label><?php endforeach;?>
<button name="action" value="arm">ARM VERIFIED MANEUVER</button><button name="action" value="cancel" formnovalidate>CANCEL / ENGINE CUTOFF</button>
</form><a href="index.php" style="color:#a9caab">RETURN TO COCKPIT</a></body></html>
