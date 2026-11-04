<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/lm_ascent_targets.php';
require_once __DIR__.'/../shared/ascent_guidance_math.php';
require_once __DIR__.'/attitude.php';
function moonSimLmAscentGuidanceStep(mysqli $conn):void {
 $conn->query("CREATE TABLE IF NOT EXISTS lm_ascent_guidance (id TINYINT UNSIGNED PRIMARY KEY,enabled TINYINT NOT NULL DEFAULT 0,status VARCHAR(100) NOT NULL DEFAULT 'MANUAL',target_altitude_m DOUBLE NOT NULL DEFAULT 100000)");
 $conn->query("INSERT IGNORE INTO lm_ascent_guidance(id) VALUES(1)");
 $r=$conn->query("SELECT enabled,status,target_altitude_m FROM lm_ascent_guidance WHERE id=1");
 $cfg=$r?$r->fetch_assoc():null;
 if(!$cfg||(int)$cfg['enabled']!==1)return;
 $read=static function(string $table)use($conn):array{
  $out=[];$q=$conn->query("SELECT attribute,value FROM $table");
  if($q)while($row=$q->fetch_assoc())$out[$row['attribute']]=(float)$row['value'];
  return $out;
 };
 $lm=$read('telemetry');$moon=$read('moon');$cm=$read('command_module');
 if((int)($lm['descent']??1)!==0||(int)($cm['dock']??0)===1||(int)($cm['stage3']??0)===1||
    (float)($lm['fuel_ascent']??0)<=0||(int)($lm['crash']??0)!==0){
  $conn->query("UPDATE lm_ascent_guidance SET enabled=0,status='INHIBITED' WHERE id=1");
  $conn->query("UPDATE telemetry SET value=0 WHERE attribute IN ('thrust','pitch_rate','yaw_rate','roll_rate')");
  return;
 }
 include __DIR__.'/../shared/physical_constants.php';
 $p=[$lm['x'],$lm['y'],$lm['z']];$v=[$lm['delta_x'],$lm['delta_y'],$lm['delta_z']];
 $mp=[$moon['x'],$moon['y'],$moon['z']];$mv=[$moon['delta_x'],$moon['delta_y'],$moon['delta_z']];
 $mass=$lander_ascent_empty_mass+(float)$lm['fuel_ascent']+max(0,(float)($lm['RCS']??0));
 $target=moonSimLunarAscentTargets($p,$v,$mp,$mv,$gravitational_constant*$moon_mass,
   $moon_radius,$mass,(float)$lm['fuel_ascent'],$lander_ascent_thrust,311.0,
   (float)$cfg['target_altitude_m']);
 $orb=moonSimOrbitalElements(moonSimRelativeVector($p,$mp),moonSimRelativeVector($v,$mv),[0,0,0],$gravitational_constant*$moon_mass);
 $safeOrbit=$orb['specific_energy']<0&&$orb['periapsis_radius']!==null&&
  $orb['periapsis_radius']>$moon_radius+30000.0;
 if($target['cutoff_reference_met']&&$safeOrbit){
  $conn->query("UPDATE telemetry SET value=0 WHERE attribute IN ('thrust','pitch_rate','yaw_rate','roll_rate')");
  $conn->query("UPDATE lm_ascent_guidance SET enabled=0,status='ORBIT INSERTION CUTOFF' WHERE id=1");
  return;
 }
 if(!$target['ascent_possible']){
  $conn->query("UPDATE lm_ascent_guidance SET status='WARNING: INSUFFICIENT PERFORMANCE' WHERE id=1");
 }
 $rates=moonSimAscentBodyRates(moonSimLoadVehicleQuaternion($conn,'LM'),$target['reference_thrust_direction_xyz'],2.0);
 $pitch=sprintf('%.6f',$rates['pitch_rate']);$yaw=sprintf('%.6f',$rates['yaw_rate']);
 $conn->query("UPDATE telemetry SET value=CASE attribute WHEN 'pitch_rate' THEN $pitch WHEN 'yaw_rate' THEN $yaw ELSE 0 END WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
 if($rates['alignment_error_deg']<8.0)$conn->query("UPDATE telemetry SET value=100 WHERE attribute='thrust'");
 else $conn->query("UPDATE telemetry SET value=0 WHERE attribute='thrust'");
}
