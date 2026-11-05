<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/lm_rendezvous_reference.php';
require_once __DIR__.'/shared/lm_rendezvous_transfer.php';
require_once __DIR__.'/shared/docking_closure_guidance.php';
function rdRows(mysqli $conn,string $table):array{
 $q=$conn->query("SELECT attribute,value FROM $table");$r=[];
 if($q)while($v=$q->fetch_assoc())$r[$v['attribute']]=(float)$v['value'];
 return $r;
}
function rdVector(array $s,string $p=''):array{
 foreach(['x','y','z'] as $a)if(!isset($s[$p.$a]))throw new RuntimeException('Missing tracking coordinates');
 return [$s[$p.'x'],$s[$p.'y'],$s[$p.'z']];
}
function rdPrint($x):string{return htmlspecialchars(is_array($x)?json_encode($x):(string)$x,ENT_QUOTES,'UTF-8');}
try{
 $cm=rdRows($conn,'command_module');$lm=rdRows($conn,'telemetry');$moon=rdRows($conn,'moon');
 $ans=moonSimLunarRendezvousReference(rdVector($lm),rdVector($lm,'delta_'),
  rdVector($cm),rdVector($cm,'delta_'),rdVector($moon),rdVector($moon,'delta_'),
  $gravitational_constant*$moon_mass,$moon_radius);
 $ans['transfer_study']=moonSimRendezvousTransferSearch(rdVector($lm),rdVector($lm,'delta_'),
 rdVector($cm),rdVector($cm,'delta_'),rdVector($moon),rdVector($moon,'delta_'),
 $gravitational_constant*$moon_mass,$moon_radius);
 $ans['docking_closure']=moonSimDockingClosureReference(rdVector($cm),rdVector($cm,'delta_'),rdVector($lm),rdVector($lm,'delta_'));
 $fuel=max(0.0,(float)($lm['fuel_ascent']??0));
 $wetMass=$lander_ascent_empty_mass+$fuel;
 if($fuel>0 && (int)($lm['descent']??1)===0)
  $ans['finite_aps_rendezvous']=moonSimFiniteRendezvousSearch(
    rdVector($lm),rdVector($lm,'delta_'),rdVector($cm),rdVector($cm,'delta_'),
    rdVector($moon),rdVector($moon,'delta_'),
    $gravitational_constant*$moon_mass,$moon_radius,
    $wetMass,$fuel,$lander_ascent_thrust,311.0);
}catch(Throwable $e){$ans=['error'=>$e->getMessage()];}
?><!doctype html><html lang="en"><meta charset="utf-8"><title>LM Rendezvous</title>
<body style="background:#0b140c;color:#b7e4b2;font:17px monospace;max-width:900px;margin:30px auto">
<h1>LM / CSM RENDEZVOUS REFERENCE</h1><p>Read-only relative-state and orbital phasing computation. No burn commanded.</p>
<?php foreach($ans as $name=>$value):?><p><?=rdPrint(strtoupper($name))?> : <?=rdPrint($value)?></p><?php endforeach;?>
<a style="color:#d4edc0" href="pilot.php">LM PILOT</a></body></html>
