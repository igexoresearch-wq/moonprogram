<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/ascent_guidance_math.php';
require_once __DIR__.'/attitude.php';
function moonSimMissionBurnStep(mysqli $conn,bool $enabled):void {
 $r=$conn->query("SELECT * FROM mission_burn_plan WHERE id=1");
 $p=$r?$r->fetch_assoc():null;
 if(!$p||$p['status']!=='ARMED')return;
 $clock=$conn->query("SELECT seconds FROM mission_clock WHERE id=1");
 $row=$clock?$clock->fetch_assoc():null;$met=(int)($row['seconds']??-900);
 $start=(int)$p['ignition_met'];$stop=(int)$p['cutoff_met'];
 $phase=(string)$p['phase'];
 if(!in_array($phase,['TLI','MCC','LOI','TEI'],true))return;
 $engineStage=$phase==='TLI'?'Third Stage':'Service Module';
 if(!$enabled) {
  $conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'");
  $conn->query("UPDATE EngineStatus SET status='OFF' WHERE engine_stage='$engineStage'");
  $conn->query("UPDATE mission_burn_plan SET status='CANCELLED' WHERE id=1");
  return;
 }
 if($met>=$stop) {
  $conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'");
  $conn->query("UPDATE EngineStatus SET status='OFF' WHERE engine_stage='$engineStage'");
  $conn->query("UPDATE command_module SET value=0 WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
  $conn->query("UPDATE mission_burn_plan SET status='COMPLETE' WHERE id=1");
  return;
 }
 if($met<$start-45)return;
 $r=$conn->query("SELECT attribute,value FROM command_module");$s=[];
 if($r)while($v=$r->fetch_assoc())$s[$v['attribute']]=(float)$v['value'];
 if((int)($s['ABORT']??0)!==0){
  $conn->query("UPDATE mission_burn_plan SET status='CANCELLED' WHERE id=1");
  $conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'");
  return;
 }
 $engineAvailable=$phase==='TLI'
  ?(int)($s['stage3']??0)===1&&(float)($s['s3fuel']??0)>0
  :(int)($s['stage3']??0)===0&&(int)($s['service_module']??0)===1&&(float)($s['fuel']??0)>0;
 if(!$engineAvailable){
  $conn->query("UPDATE mission_burn_plan SET status='CANCELLED' WHERE id=1");
  $conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'");
  return;
 }
 $dir=[(float)$p['dir_x'],(float)$p['dir_y'],(float)$p['dir_z']];
 $rate=moonSimAscentBodyRates(moonSimLoadVehicleQuaternion($conn,'CM'),$dir,3.0);
 $pitch=sprintf('%.5f',$rate['pitch_rate']);$yaw=sprintf('%.5f',$rate['yaw_rate']);
 $conn->query("UPDATE command_module SET value=CASE attribute WHEN 'pitch_rate' THEN $pitch WHEN 'yaw_rate' THEN $yaw ELSE 0 END WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
 if($met>=$start && $rate['alignment_error_deg']>=5.0) {
  $conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'");
  $conn->query("UPDATE EngineStatus SET status='OFF' WHERE engine_stage='$engineStage'");
 }
 if($met>=$start && $rate['alignment_error_deg']<5.0 && $engineAvailable) {
  $conn->query("UPDATE command_module SET value=100 WHERE attribute='thrust'");
  if($phase==='TLI')$conn->query("UPDATE EngineStatus SET status='ON' WHERE engine_stage='Third Stage' AND operational_status='OK'");
 }
}
