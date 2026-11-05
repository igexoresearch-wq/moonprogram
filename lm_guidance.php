<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

session_start();
$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator login required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/physics/lm_descent_assist.php';
require_once __DIR__.'/shared/lm_landing_estimate.php';
require_once __DIR__.'/shared/physical_constants.php';
moonSimEnsureLmAssist($conn);
if(!isset($_SESSION['lm_assist_token']))$_SESSION['lm_assist_token']=bin2hex(random_bytes(16));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!hash_equals($_SESSION['lm_assist_token'],(string)($_POST['token']??'')))exit('Invalid command token');
 $mode=(string)($_POST['mode']??'manual');
 if($mode==='manual'){
  $conn->query("UPDATE lm_descent_assist SET enabled=0,attitude_assist=0,status='MANUAL' WHERE id=1");
  $conn->query("UPDATE telemetry SET value=0 WHERE attribute IN ('thrust','pitch_rate','yaw_rate','roll_rate')");
 }elseif($mode==='throttle')$conn->query("UPDATE lm_descent_assist SET enabled=1,attitude_assist=0 WHERE id=1");
 elseif($mode==='guided')$conn->query("UPDATE lm_descent_assist SET enabled=1,attitude_assist=1 WHERE id=1");
}
$q=$conn->query("SELECT * FROM lm_descent_assist WHERE id=1");$r=$q?$q->fetch_assoc():[];
$landing=moonSimLmLandingEstimate((float)($r['altitude_m']??0),(float)($r['radial_speed_m_s']??0),(float)($r['tangential_speed_m_s']??0),$gravitational_constant*$moon_mass,$moon_radius);
function lmGuideOut($x):string{return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><meta charset="utf-8"><title>LM Guidance</title>
<style>body{background:#141714;color:#c3d4bd;font:17px monospace;max-width:800px;margin:30px auto}button{padding:12px;background:#384435;color:white;border:1px solid #788d72}p{line-height:1.7}</style></head>
<body><h1>LM — POWERED DESCENT GUIDANCE</h1>
<p>MODE: <?=lmGuideOut($r['status']??'---')?><br>ALT M: <?=lmGuideOut($r['altitude_m']??'---')?><br>RADIAL M/S: <?=lmGuideOut($r['radial_speed_m_s']??'---')?><br>HORIZONTAL M/S: <?=lmGuideOut($r['tangential_speed_m_s']??'---')?></p>
<h2>LANDING / FLIGHT COMPUTER READOUT</h2><p>BALLISTIC T-SURFACE: <?=lmGuideOut($landing['time_to_surface_unpowered_s']??'---')?> S<br>CONTACT ENVELOPE: <?=!empty($landing['soft_landing_envelope'])?'WITHIN LIMITS':'NOT WITHIN LIMITS'?></p>
<form method="post"><input type="hidden" name="token" value="<?=lmGuideOut($_SESSION['lm_assist_token'])?>">
<button name="mode" value="guided">GUIDED ATTITUDE + THROTTLE</button>
<button name="mode" value="throttle">THROTTLE ONLY / PILOT STEERING</button>
<button name="mode" value="manual">MANUAL TAKEOVER / CUT THRUST</button></form>
<p>The pilot may take over at any point. Guidance uses measured Moon-relative state and the existing physical LM engine; it does not move the spacecraft directly.</p>
<a href="lm_descent_setup.php" style="color:#bde6ad">DESCENT SETUP</a></body></html>
