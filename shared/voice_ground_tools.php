<?php
declare(strict_types=1);
/** Ground tools expose only allowlisted live measurements and source labels. */
function moonSimVoiceTelemetry(mysqli $conn,string $table,array $attributes):array{
 if(!in_array($table,['command_module','telemetry','moon','mission_clock'],true))
  throw new InvalidArgumentException('Unknown telemetry source');
 $allowed=['x','y','z','delta_x','delta_y','delta_z','fuel','fuel_ascent',
  'descent','stage3','service_module','dock','crash','thrust','pitch','yaw','roll','seconds'];
 $attributes=array_values(array_intersect($attributes,$allowed));
 if($attributes===[])return [];
 if($table==='mission_clock'){
  $q=$conn->query("SELECT seconds FROM mission_clock WHERE id=1");
  return ['seconds'=>$q?(int)($q->fetch_assoc()['seconds']??0):null,
    'reference'=>'mission_clock.seconds'];
 }
 $names=implode(',',array_map(static fn($a)=>"'".$a."'",$attributes));
 $q=$conn->query("SELECT attribute,value FROM $table WHERE attribute IN ($names)");
 $out=[];
 if($q)while($row=$q->fetch_assoc())$out[$row['attribute']]=(float)$row['value'];
 return ['values'=>$out,'reference'=>$table.'.attribute/value'];
}
function moonSimVoiceMissionContext(mysqli $conn):array{
 return ['met'=>moonSimVoiceTelemetry($conn,'mission_clock',['seconds']),
 'cm'=>moonSimVoiceTelemetry($conn,'command_module',['fuel','stage3','service_module','dock','crash','thrust']),
 'lm'=>moonSimVoiceTelemetry($conn,'telemetry',['fuel','fuel_ascent','descent','crash','thrust']),
 'moon'=>moonSimVoiceTelemetry($conn,'moon',['x','y','z'])];
}
