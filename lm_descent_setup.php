<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Authentication required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/lm_descent_initialization.php';
require_once __DIR__.'/shared/lm_powered_descent_targets.php';
require_once __DIR__.'/shared/lm_ascent_targets.php';
require_once __DIR__.'/shared/lm_descent_burn_preview.php';
function lunarRows(mysqli $db,string $table):array{
 $q=$db->query("SELECT attribute,value FROM $table");$out=[];
 if(!$q)throw new RuntimeException('Telemetry unavailable');
 while($r=$q->fetch_assoc())$out[$r['attribute']]=(float)$r['value'];
 return $out;
}
function lunarXYZ(array $r,string $prefix=''):array{
 foreach(['x','y','z'] as $a)if(!isset($r[$prefix.$a]))throw new RuntimeException('Incomplete vector');
 return [$r[$prefix.'x'],$r[$prefix.'y'],$r[$prefix.'z']];
}
function lunarOut($v):string{return htmlspecialchars(is_array($v)?json_encode($v):(is_bool($v)?($v?'YES':'NO'):(string)$v),ENT_QUOTES,'UTF-8');}
try{
 $lm=lunarRows($conn,'telemetry');$moon=lunarRows($conn,'moon');
 if((int)($lm['descent']??0)!==1)throw new RuntimeException('LM descent stage unavailable');
 $data=moonSimDescentInitialization(lunarXYZ($lm),lunarXYZ($lm,'delta_'),
   lunarXYZ($moon),lunarXYZ($moon,'delta_'),
   $gravitational_constant*$moon_mass,$moon_radius);
 $mass=$lander_ascent_empty_mass+$lander_descent_empty_mass+
  max(0,(float)($lm['fuel_ascent']??0))+max(0,(float)($lm['fuel']??0))+
  max(0,(float)($lm['RCS']??0));
 $data['braking']=moonSimPoweredDescentTargets(lunarXYZ($lm),lunarXYZ($lm,'delta_'),
  lunarXYZ($moon),lunarXYZ($moon,'delta_'),$gravitational_constant*$moon_mass,
  $moon_radius,$mass,max(0,(float)($lm['fuel']??0)),
  $lander_descent_thrust,311.0);
 if(!empty($data['braking']['descent_initialization_feasible'])){
  $candidateDuration=min(300.0,0.5*max(0,(float)($lm['fuel']??0))*311.0*9.80665/$lander_descent_thrust);
  if($candidateDuration>5.0)$data['finite_braking_preview']=moonSimDescentBurnPreview(
   lunarXYZ($lm),lunarXYZ($lm,'delta_'),lunarXYZ($moon),lunarXYZ($moon,'delta_'),
   $gravitational_constant*$moon_mass,$moon_radius,$mass,
   max(0,(float)($lm['fuel']??0)),$lander_descent_thrust,311.0,$candidateDuration);
 }
}catch(Throwable $e){$data=['status'=>'UNAVAILABLE','reason'=>$e->getMessage()];}
?><!doctype html><html><head><meta charset="utf-8"><title>LM Descent Setup</title>
<style>body{background:#101310;color:#bfd5ae;font:16px monospace;max-width:850px;margin:30px auto}dt{margin-top:13px;color:#a3ce9c}dd{color:#fff}a{color:#bce9a2}</style></head>
<body><h1>LM / DESCENT ORBIT PREPARATION</h1><p>Computed reference only. No automatic deorbit burn is issued.</p>
<dl><?php foreach($data as $key=>$value):?><dt><?=lunarOut(strtoupper($key))?></dt><dd><?=lunarOut($value)?></dd><?php endforeach;?></dl>
<p>Confirm actual LM separation, descent engine and propellant, alignment and ground approval before initiating a descent.</p>
<a href="lm_guidance.php">LM DESCENT GUIDANCE / PILOT TAKEOVER</a> | <a href="pilot.php">LM PILOT</a></body></html>
