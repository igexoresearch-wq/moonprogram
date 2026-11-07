<?php
declare(strict_types=1);

/**
 * Read-only preflight. The simulator must not execute RESET or advance
 * physics against an empty/incomplete reconstructed moonland database.
 * This deliberately does not create tables or seed fictitious state.
 */
function moonSimWorkerPreflight(mysqli $conn): array {
 $issues=[];
 $required=['command_module','telemetry','moon','mission_clock','systems','guidance',
  'CMguidance','CMprogram_register','CMswitches','EngineStatus'];
 $stmt=$conn->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()');
 $stmt->execute();
 $present=[];
 foreach($stmt->get_result() as $row)$present[strtolower((string)$row['TABLE_NAME'])]=true;
 foreach($required as $name){
  if(!isset($present[strtolower($name)]))$issues[]='Missing table: '.$name;
 }
 $checks=[
  'command_module'=>['x','y','z','delta_x','delta_y','delta_z','stage1','stage2','stage3','s1fuel','s2fuel','s3fuel'],
  'telemetry'=>['x','y','z','delta_x','delta_y','delta_z','descent','fuel','fuel_ascent'],
  'moon'=>['x','y','z','delta_x','delta_y','delta_z']
 ];
 foreach($checks as $table=>$attributes){
  if(!isset($present[strtolower($table)]))continue;
  try{
   $found=[];
   $rows=$conn->query('SELECT attribute FROM '.$table);
   while($row=$rows->fetch_assoc())$found[(string)$row['attribute']]=true;
   foreach($attributes as $attr)if(!isset($found[$attr]))$issues[]='Missing row: '.$table.'.'.$attr;
  }catch(mysqli_sql_exception $e){$issues[]='Invalid table structure: '.$table;}
 }
 foreach(['mission_clock'=>1,'CMprogram_register'=>1,'systems'=>21] as $table=>$id){
  if(!isset($present[strtolower($table)]))continue;
  try{
   $result=$conn->query('SELECT 1 FROM '.$table.' WHERE id='.(int)$id.' LIMIT 1');
   if(!$result || !$result->fetch_row())$issues[]='Missing row: '.$table.'.id='.$id;
  }catch(mysqli_sql_exception $e){
   // Legacy systems schema identifies the simulator by name, not ID.
   if($table!=='systems')$issues[]='Invalid table structure: '.$table;
  }
 }
 if(isset($present['systems'])){
  try{
   $result=$conn->query("SELECT 1 FROM systems WHERE system='sim_on' LIMIT 1");
   if(!$result || !$result->fetch_row())$issues[]='Missing row: systems.sim_on';
  }catch(mysqli_sql_exception $e){$issues[]='Invalid systems table: missing system column';}
 }
 return $issues;
}
