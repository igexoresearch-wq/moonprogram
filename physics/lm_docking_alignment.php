<?php
declare(strict_types=1);
require_once __DIR__.'/attitude.php';
require_once __DIR__.'/../shared/ascent_guidance_math.php';
require_once __DIR__.'/../shared/docking_closure_guidance.php';
/** Opt-in piloted docking alignment: rotation only; never teleports or edits velocity. */
function moonSimDockingAlignmentStep(mysqli $conn):void{
 static $schema=false;
 if(!$schema){
  $conn->query("CREATE TABLE IF NOT EXISTS lm_docking_guidance (id TINYINT PRIMARY KEY,enabled TINYINT NOT NULL DEFAULT 0,status VARCHAR(90) NOT NULL DEFAULT 'MANUAL')");
  $conn->query("INSERT IGNORE INTO lm_docking_guidance(id) VALUES(1)");
  $schema=true;
 }
 $q=$conn->query("SELECT enabled FROM lm_docking_guidance WHERE id=1");
 $row=$q?$q->fetch_assoc():null;if(!$row||(int)$row['enabled']!==1)return;
 $read=static function(string $table)use($conn):array{
  $r=$conn->query("SELECT attribute,value FROM $table");$out=[];
  if($r)while($x=$r->fetch_assoc())$out[$x['attribute']]=(float)$x['value'];
  return $out;
 };
 $lm=$read('telemetry');$cm=$read('command_module');
 if((int)($cm['dock']??0)!==0||(int)($cm['stage3']??0)!==0||(int)($lm['descent']??1)!==0){
  $conn->query("UPDATE lm_docking_guidance SET enabled=0,status='INHIBITED: VEHICLE ATTACHED' WHERE id=1");
  $conn->query("UPDATE telemetry SET value=0 WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
  return;
 }
 $p=[$lm['x'],$lm['y'],$lm['z']];$v=[$lm['delta_x'],$lm['delta_y'],$lm['delta_z']];
 $cp=[$cm['x'],$cm['y'],$cm['z']];$cv=[$cm['delta_x'],$cm['delta_y'],$cm['delta_z']];
 $target=moonSimDockingClosureReference($cp,$cv,$p,$v);
 if($target['unsafe_fast_closure']){
  $conn->query("UPDATE lm_docking_guidance SET status='HOLD: UNSAFE CLOSURE' WHERE id=1");
  $conn->query("UPDATE telemetry SET value=0 WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
  return;
 }
 $r=moonSimAscentBodyRates(moonSimLoadVehicleQuaternion($conn,'LM'),$target['line_of_sight_xyz'],1.5);
 $pitch=sprintf('%.6f',$r['pitch_rate']);$yaw=sprintf('%.6f',$r['yaw_rate']);
 $conn->query("UPDATE telemetry SET value=CASE attribute WHEN 'pitch_rate' THEN $pitch WHEN 'yaw_rate' THEN $yaw ELSE 0 END WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
 $status=$target['in_proximity_hold_zone']?'CAPTURE ENVELOPE / PILOT HOLD':'ALIGNING / PILOT CONTROLS TRANSLATION';
 $conn->query("UPDATE lm_docking_guidance SET status='$status' WHERE id=1");
}
