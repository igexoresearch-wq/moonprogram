<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

$secret=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit('Operator login required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/finite_loi_targeting.php';
require_once __DIR__.'/shared/coupled_loi_targeting.php';
function loiData(mysqli $conn,string $table):array{
 $result=$conn->query("SELECT attribute,value FROM $table");$data=[];
 if($result)while($r=$result->fetch_assoc())$data[$r['attribute']]=(float)$r['value'];
 return $data;
}
function loiVec(array $d,string $prefix=''):array{
 return [(float)($d[$prefix.'x']??0),(float)($d[$prefix.'y']??0),(float)($d[$prefix.'z']??0)];
}
function loiEsc($x):string{return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');}
$error='';$output=null;$duration=filter_input(INPUT_GET,'duration',FILTER_VALIDATE_FLOAT);
if($duration===false||$duration===null)$duration=180.0;
try{
 $cm=loiData($conn,'command_module');$moon=loiData($conn,'moon');
 if((int)($cm['stage3']??0)!==0||(int)($cm['service_module']??0)!==1)
  throw new RuntimeException('LOI requires detached S-IVB and available service module');
 $fuel=max(0.0,(float)($cm['fuel']??0));
 $rcs=max(0.0,(float)($cm['RCS']??0));
 if($rcs>$csm_rcs_initial_propellant*2.0)$rcs=$rcs/3329.0*$csm_rcs_initial_propellant;
 $mass=$command_module_empty_mass+$service_module_empty_mass+$fuel+$rcs;
 if((int)($cm['dock']??0)===1){
  $lm=loiData($conn,'telemetry');
  $lmRcs=max(0.0,(float)($lm['RCS']??0));
  if($lmRcs>$lm_rcs_initial_propellant*2.0)$lmRcs=$lmRcs/3329.0*$lm_rcs_initial_propellant;
  $mass+=$lander_ascent_empty_mass+max(0.0,(float)($lm['fuel_ascent']??0))+$lmRcs;
  if((int)($lm['descent']??0)===1)
   $mass+=$lander_descent_empty_mass+max(0.0,(float)($lm['fuel']??0));
 }
 $p=moonSimRelativeVector(loiVec($cm),loiVec($moon));
 $v=moonSimRelativeVector(loiVec($cm,'delta_'),loiVec($moon,'delta_'));
 $target=filter_input(INPUT_GET,'target',FILTER_VALIDATE_FLOAT);
 if($target===false||$target===null)$target=120000.0;
 $target=max(80000.0,min(1000000.0,(float)$target));
 if(($_GET['mode']??'estimate')==='target'){
  $earth=[$earth_center_x,$earth_center_y,$earth_center_z];
  $output=moonSimCoupledLoiSearch(loiVec($cm),loiVec($cm,'delta_'),$earth,
   loiVec($moon),loiVec($moon,'delta_'),$gravitational_constant*$earth_mass,
   $gravitational_constant*$moon_mass,$moon_radius,$earth_radius,
   $mass,$fuel,$service_module_thrust,314.0,$target,80000.0);
 }else{
  $output=moonSimFiniteLoiEstimate($p,$v,$gravitational_constant*$moon_mass,$moon_radius,
   $mass,$fuel,$service_module_thrust,314.0,(float)$duration,1.0);
 }
}catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html><head><meta charset="utf-8"><title>LOI Reference Console</title>
<style>body{background:#010601;color:#a1dc9d;font:17px monospace;max-width:860px;margin:30px auto}input,button{background:#172517;color:#d4f4c8;padding:9px;border:1px solid #557e52}dt{margin:15px 0 5px}dd{color:#fff}</style></head><body>
<h1>LOI / FINITE-BURN REFERENCE</h1><p><a href="csm_burn_control.php">CREW LOI ARM / CANCEL</a> — <a href="index.php">CM PANEL</a></p><p>PROCEDURE: Verify SPS availability and burn target. Align attitude. Arm LOI. Monitor engine and fuel. Cancel on deviation, then recompute.</p><p>Calculation only. No engine commands. Target search propagates the moving Moon and both gravitational fields; manual entry and verification required.</p>
<form method="get"><label>Burn seconds <input name="duration" type="number" min="1" max="1000" value="<?=loiEsc($duration)?>"></label><button name="mode" value="estimate">COMPUTE DURATION</button><br><label>Target apoapsis (m) <input type="number" name="target" min="80000" max="1000000" value="120000"></label><button name="mode" value="target">SEARCH BOUND LOI</button></form>
<?php if($error):?><p>NOT AVAILABLE: <?=loiEsc($error)?></p><?php endif;?>
<?php if($output):?><dl><?php foreach($output as $key=>$value):?><dt><?=loiEsc(strtoupper($key))?></dt>
<dd><?=loiEsc(is_array($value)?json_encode($value):($value===null?'---':(is_bool($value)?($value?'YES':'NO'):$value)))?></dd><?php endforeach;?></dl><?php endif;?>
</body></html>
