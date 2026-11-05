<?php
declare(strict_types=1);
session_start();
$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator authorization required');
}
require __DIR__.'/physics/db.php';
if(!isset($_SESSION['lm_docking_csrf']))$_SESSION['lm_docking_csrf']=bin2hex(random_bytes(16));
$conn->query("CREATE TABLE IF NOT EXISTS lm_docking_guidance(id TINYINT PRIMARY KEY,enabled TINYINT NOT NULL DEFAULT 0,status VARCHAR(90) NOT NULL DEFAULT 'MANUAL')");
$conn->query("INSERT IGNORE INTO lm_docking_guidance(id) VALUES(1)");
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!hash_equals($_SESSION['lm_docking_csrf'],(string)($_POST['csrf']??'')))exit('Invalid command token');
 $mode=(string)($_POST['mode']??'');
 if($mode==='manual'){
  $conn->query("UPDATE lm_docking_guidance SET enabled=0,status='MANUAL' WHERE id=1");
  $conn->query("UPDATE telemetry SET value=0 WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
 }elseif($mode==='align')$conn->query("UPDATE lm_docking_guidance SET enabled=1,status='ALIGNING' WHERE id=1");
}
$r=$conn->query("SELECT enabled,status FROM lm_docking_guidance WHERE id=1");$state=$r?$r->fetch_assoc():[];
function dgEscape($x):string{return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><meta charset="utf-8"><title>LM Docking Guidance</title>
<body style="background:#0c130c;color:#cbe4c2;font:17px monospace;max-width:750px;margin:30px auto"><?php require_once __DIR__.'/shared/station_navigation.php'; moonSimStationNav('LM DOCKING'); ?>
<h1>LM / DOCKING ALIGNMENT</h1>
<p>STATUS: <?=dgEscape($state['status']??'UNAVAILABLE')?></p>
<p>The autopilot can align the LM attitude toward the CSM. Closing speed and translation remain pilot-controlled through existing RCS. Physical docking requires the existing capture constraints.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=dgEscape($_SESSION['lm_docking_csrf'])?>">
<button name="mode" value="align">ENABLE ALIGNMENT</button>
<button name="mode" value="manual">PILOT TAKEOVER / STOP STEERING</button></form>
<p><a href="lm_rendezvous_reference.php" style="color:#c5edac">RELATIVE STATE AND CLOSURE</a> | <a href="pilot.php" style="color:#c5edac">LM PILOT</a></p>
</body></html>
