<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

session_start();
$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator authentication required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/orbital_mechanics.php';
require_once __DIR__.'/shared/orbit_maneuver_planner.php';
function navRows(mysqli $db,string $table):array{
 $q=$db->query("SELECT attribute,value FROM $table");$s=[];
 if($q)while($r=$q->fetch_assoc())$s[$r['attribute']]=(float)$r['value'];return $s;
}
function navVec(array $s,string $prefix=''):array{
 foreach(['x','y','z'] as $a)if(!isset($s[$prefix.$a]))throw new RuntimeException('Missing '.$prefix.$a);
 return [$s[$prefix.'x'],$s[$prefix.'y'],$s[$prefix.'z']];
}
function navH($s):string{return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
if(!isset($_SESSION['moon_nav_token']))$_SESSION['moon_nav_token']=bin2hex(random_bytes(24));
$body=($_GET['body']??'EARTH')==='MOON'?'MOON':'EARTH';
$target=filter_input(INPUT_GET,'target',FILTER_VALIDATE_FLOAT);
$target=($target===false||$target===null)?($body==='MOON'?100000.0:180000.0):max(10000.0,min(5000000.0,$target));
$message='';$error='';$solution=null;$elements=null;$pilot=[];$agcOn=true;
try{
 $sw=$conn->query("SELECT status FROM CMswitches WHERE name='Guidance_Computer' LIMIT 1");
 $switch=$sw?$sw->fetch_assoc():null;
 $agcOn=$switch===null||(int)$switch['status']===1;
 if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
  if(!hash_equals($_SESSION['moon_nav_token'],(string)($_POST['token']??'')))throw new RuntimeException('Invalid command token');
  if($agcOn)throw new RuntimeException('Turn Guidance Computer OFF for manual control');
  $cmd=(string)($_POST['command']??'');
  if($cmd==='cutoff'){
   if(!$conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'"))throw new RuntimeException($conn->error);
  }elseif($cmd==='rates'||$cmd==='throttle'){
   $limits=$cmd==='rates'?['pitch_rate'=>3.0,'yaw_rate'=>3.0,'roll_rate'=>3.0]:['thrust'=>100.0];
   $updates=[];
   foreach($limits as $field=>$limit){
    $v=filter_var($_POST[$field]??null,FILTER_VALIDATE_FLOAT);
    if($v===false||$v===null||!is_finite((float)$v)||abs($v)>$limit||($field==='thrust'&&$v<0))
      throw new RuntimeException('Invalid '.$field);
    $updates[$field]=(float)$v;
   }
   $conn->begin_transaction();
   try{
    foreach($updates as $field=>$v){
     $val=sprintf('%.8f',$v);
     if(!$conn->query("UPDATE command_module SET value=$val WHERE attribute='$field'"))throw new RuntimeException($conn->error);
    }
    $conn->commit();
   }catch(Throwable $e){$conn->rollback();throw $e;}
  }else throw new RuntimeException('Unknown command');
  $message='Command stored. Physics applies it on the next tick.';
 }
 $pilot=navRows($conn,'command_module');$moon=navRows($conn,'moon');
 $center=$body==='EARTH'?[$earth_center_x,$earth_center_y,$earth_center_z]:navVec($moon);
 $bodyVel=$body==='EARTH'?[0,0,0]:navVec($moon,'delta_');
 $mu=$gravitational_constant*($body==='EARTH'?$earth_mass:$moon_mass);
 $rad=$body==='EARTH'?$earth_radius:$moon_radius;
 $position=navVec($pilot);$velocity=navVec($pilot,'delta_');
 $relative=moonSimRelativeVector($velocity,$bodyVel);
 $elements=moonSimOrbitalElements($position,$relative,$center,$mu);
 $solution=moonSimCircularizationEstimate($position,$velocity,$center,$bodyVel,$mu,$rad,(float)$target);
}catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CM Orbital Navigation</title>
<style>
body{background:#0a1420;color:#d7e7f2;font:16px system-ui;margin:0;padding:22px}
main{max-width:1060px;margin:auto}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
section{background:#172538;border:1px solid #46627a;border-radius:10px;padding:18px}
h1,h2{color:#d5edf9}.tag{color:#9fdbff}.warn{color:#ffbc71}
label{display:block;margin:9px 0}input,select,button{padding:9px;background:#263951;color:white;border:1px solid #7593ad;border-radius:5px}
button{cursor:pointer;margin:6px 4px 6px 0}button:disabled{opacity:.45;cursor:not-allowed}
dt{color:#83bedd;margin-top:12px}dd{margin:2px 0 8px;font:600 19px monospace}
nav a{color:#b8ddf5;margin-right:18px}form{margin:10px 0}
</style></head><body><main><nav><a href="pilot.php">Pilot</a><a href="index.php">Cockpit</a><a href="orbit_maneuver.php">Maneuver Advisor</a></nav>
<h1>CM / ORBITAL NAVIGATION</h1><p>Real orbital state and manual cockpit commands. No instant delta-V, scripted burns, or direct position changes.</p>
<?php if($message):?><p class="tag"><?=navH($message)?></p><?php endif;?>
<?php if($error):?><p class="warn"><?=navH($error)?></p><?php endif;?>
<div class="grid">
<section><h2>Orbital state</h2>
<form method="get"><label>Reference body <select name="body"><option <?=$body==='EARTH'?'selected':''?>>EARTH</option><option <?=$body==='MOON'?'selected':''?>>MOON</option></select></label>
<label>Target circular altitude (m) <input type="number" name="target" min="10000" max="5000000" value="<?=(int)$target?>"></label><button>Calculate</button></form>
<?php if($elements):?>
<dl><dt>Specific orbital energy (J/kg)</dt><dd><?=number_format($elements['specific_energy'],1)?></dd>
<dt>Eccentricity</dt><dd><?=number_format($elements['eccentricity'],6)?></dd>
<dt>Periapsis (km)</dt><dd><?=$elements['periapsis_radius']===null?'OPEN':number_format(($elements['periapsis_radius']-$rad)/1000,2)?></dd>
<dt>Apoapsis (km)</dt><dd><?=$elements['apoapsis_radius']===null?'OPEN':number_format(($elements['apoapsis_radius']-$rad)/1000,2)?></dd></dl>
<?php endif;?></section>
<section><h2>Circularization reference</h2>
<?php if($solution):?><dl>
<dt>Applicable at this moment?</dt><dd><?=!empty($solution['applicable_now'])?'YES':'NO — REVIEW GEOMETRY'?></dd>
<dt>Estimated impulsive delta-V (m/s)</dt><dd><?=number_format((float)$solution['signed_delta_v_m_s'],2)?></dd>
<dt>Reference direction, inertial XYZ</dt><dd><?=navH(json_encode($solution['burn_direction']))?></dd></dl>
<p class="warn">This is a planning estimate, not an executable finite-duration burn. Orient the craft and use the physical engine manually.</p><?php endif;?></section>
<section><h2>Manual attitude</h2><p>AGC: <strong><?=$agcOn?'ON — manual changes locked':'OFF — manual controls available'?></strong></p>
<form method="post"><input type="hidden" name="token" value="<?=navH($_SESSION['moon_nav_token'])?>"><input type="hidden" name="command" value="rates">
<?php foreach(['pitch_rate','yaw_rate','roll_rate'] as $axis):?><label><?=navH(strtoupper($axis))?> (°/s) <input type="number" name="<?=navH($axis)?>" min="-3" max="3" step=".1" required value="<?=navH($pilot[$axis]??0)?>"></label><?php endforeach;?>
<button <?=$agcOn?'disabled':''?>>Set physical body rates</button></form></section>
<section><h2>Main-engine throttle</h2><p>Uses the actual stage/propulsion system and propellant. It does not award velocity instantly.</p>
<form method="post"><input type="hidden" name="token" value="<?=navH($_SESSION['moon_nav_token'])?>"><input type="hidden" name="command" value="throttle">
<label>Throttle (%) <input type="number" name="thrust" min="0" max="100" step="1" required value="<?=navH($pilot['thrust']??0)?>"></label><button <?=$agcOn?'disabled':''?>>Set throttle</button></form>
<form method="post"><input type="hidden" name="token" value="<?=navH($_SESSION['moon_nav_token'])?>"><input type="hidden" name="command" value="cutoff">
<button <?=$agcOn?'disabled':''?>>ENGINE CUTOFF</button></form></section>
</div></main></body></html>
