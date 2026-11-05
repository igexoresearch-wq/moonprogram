<?php
declare(strict_types=1);

/**
 * Append-only authoritative post-step flight frames.
 *
 * Reproduces the recorded flight exactly as observed, including manual
 * inputs and engine state. Replay is read-only and NEVER advances the live
 * physics engine, rewrites spacecraft state or invents a successful mission.
 */
function moonSimReplayReadRows(mysqli $db, string $table): array {
 $allowed=['command_module','telemetry','moon','EngineStatus','systems',
  'mission_clock','CMprogram_register','mission_burn_plan',
  'lm_ascent_guidance','lm_docking_guidance','reentry_state','surface_contact',
  'vehicle_attitude','attitude_state'];
 if(!in_array($table,$allowed,true))throw new InvalidArgumentException('Unknown replay table');
 try {
  $result=$db->query('SELECT * FROM `'.$table.'`');
  $rows=[];
  while($row=$result->fetch_assoc()){
   ksort($row,SORT_STRING);
   $rows[]=$row;
  }
  usort($rows,static fn(array $a,array $b)=>strcmp(
    json_encode($a,JSON_THROW_ON_ERROR),json_encode($b,JSON_THROW_ON_ERROR)));
  return $rows;
 } catch(mysqli_sql_exception $e) {
  // Optional tables are absent on some initial schema versions.
  if($e->getCode()===1146)return [];
  throw $e;
 }
}

function moonSimEnsureReplayFrames(mysqli $db):void {
 static $initialized=false;
 if($initialized)return;
 $db->query("CREATE TABLE IF NOT EXISTS flight_replay_frames (
   run_id BIGINT NOT NULL,
   met INT NOT NULL,
   frame_json LONGTEXT NOT NULL,
   sha256 CHAR(64) NOT NULL,
   recorded_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
   PRIMARY KEY(run_id,met)
 ) ENGINE=InnoDB");
 $initialized=true;
}

function moonSimReplayCurrentOrbitMarkers(array $states):array {
 require_once __DIR__.'/orbital_mechanics.php';
 require __DIR__.'/physical_constants.php';
 $lookup=static function(array $rows):array{
  $values=[];
  foreach($rows as $row)if(isset($row['attribute'],$row['value']))
   $values[(string)$row['attribute']]=(float)$row['value'];
  return $values;
 };
 $cm=$lookup($states['command_module']??[]);
 $lm=$lookup($states['telemetry']??[]);
 $moon=$lookup($states['moon']??[]);
 $results=[];
 foreach(['CM'=>[$cm,[$earth_center_x,$earth_center_y,$earth_center_z],
                    [0.0,0.0,0.0],$gravitational_constant*$earth_mass,$earth_radius],
          'LM'=>[$lm,[$moon['x']??NAN,$moon['y']??NAN,$moon['z']??NAN],
                    [$moon['delta_x']??NAN,$moon['delta_y']??NAN,$moon['delta_z']??NAN],
                    $gravitational_constant*$moon_mass,$moon_radius]] as $name=>$params){
  [$craft,$center,$centerV,$mu,$radius]=$params;
  $keys=['x','y','z','delta_x','delta_y','delta_z'];
  if(count(array_intersect($keys,array_keys($craft)))!==6)continue;
  $pos=[(float)$craft['x'],(float)$craft['y'],(float)$craft['z']];
  $vel=[(float)$craft['delta_x'],(float)$craft['delta_y'],(float)$craft['delta_z']];
  if(!array_reduce([...$pos,...$vel,...$center,...$centerV],
      static fn(bool $ok,float $v)=>$ok&&is_finite($v),true))continue;
  $rel=array_map(static fn($x,$y)=>$x-$y,$pos,$center);
  $relativeVelocity=array_map(static fn($x,$y)=>$x-$y,$vel,$centerV);
  $orb=moonSimOrbitalElements($rel,$relativeVelocity,[0,0,0],$mu);
  $results[$name]=[
   'altitude_m'=>sqrt(array_sum(array_map(static fn($x)=>$x*$x,$rel)))-$radius,
   'periapsis_altitude_m'=>$orb['periapsis_radius']===null?null:$orb['periapsis_radius']-$radius,
   'apoapsis_altitude_m'=>$orb['apoapsis_radius']===null?null:$orb['apoapsis_radius']-$radius,
   'specific_energy_j_kg'=>$orb['specific_energy'],
  ];
 }
 return $results;
}

function moonSimRecordFlightReplayFrame(mysqli $db,int $runId,int $met):void {
 moonSimEnsureReplayFrames($db);
 $tables=['command_module','telemetry','moon','EngineStatus','systems',
    'mission_clock','CMprogram_register','mission_burn_plan','lm_ascent_guidance',
    'lm_docking_guidance','reentry_state','surface_contact','vehicle_attitude','attitude_state'];
 $states=[];
 foreach($tables as $table)$states[$table]=moonSimReplayReadRows($db,$table);
 $events=[];
 try {
  $result=$db->query('SELECT mission_epoch FROM flight_event_observed WHERE id=1');
  $epoch=$result?$result->fetch_assoc()['mission_epoch']??null:null;
  if($epoch!==null){
   $stmt=$db->prepare("SELECT event_key,old_value,new_value
     FROM flight_event_log WHERE mission_epoch=? AND mission_seconds=?
     ORDER BY id ASC LIMIT 500");
   $stmt->bind_param('si',$epoch,$met);
   $stmt->execute();
   $events=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }
 }catch(mysqli_sql_exception $e){if($e->getCode()!==1146)throw $e;}
 $frame=['format'=>'moon-flight-frame-v1','run_id'=>$runId,'met'=>$met,
         'state'=>$states,'events'=>$events,
         'orbital_markers'=>moonSimReplayCurrentOrbitMarkers($states)];
 $json=json_encode($frame,JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION);
 $digest=hash('sha256',$json);
 $insert=$db->prepare("INSERT IGNORE INTO flight_replay_frames
    (run_id,met,frame_json,sha256) VALUES(?,?,?,?)");
 $insert->bind_param('iiss',$runId,$met,$json,$digest);
 $insert->execute();
}
