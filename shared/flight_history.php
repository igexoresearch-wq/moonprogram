<?php
declare(strict_types=1);
function moonSimEnsureFlightHistory(mysqli $db):void{
 static $ready=false;if($ready)return;
 $db->query("CREATE TABLE IF NOT EXISTS flight_history(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,met INT NOT NULL,cm_x DOUBLE NOT NULL,cm_y DOUBLE NOT NULL,cm_z DOUBLE NOT NULL,cm_vx DOUBLE NOT NULL,cm_vy DOUBLE NOT NULL,cm_vz DOUBLE NOT NULL,cm_fuel DOUBLE NOT NULL,lm_fuel DOUBLE NOT NULL,burn_status VARCHAR(32) NOT NULL,logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(met))");
 $db->query("CREATE TABLE IF NOT EXISTS flight_history_run (id TINYINT PRIMARY KEY,run_id BIGINT NOT NULL DEFAULT 1,last_met INT NOT NULL DEFAULT -900)");
 $db->query("INSERT IGNORE INTO flight_history_run(id,run_id,last_met) VALUES(1,1,-900)");
 $db->query("ALTER TABLE flight_history ADD COLUMN IF NOT EXISTS run_id BIGINT NOT NULL DEFAULT 1");
 $ready=true;
}
function moonSimRecordFlightHistory(mysqli $db):void{
 moonSimEnsureFlightHistory($db);
 $cm=[];$lm=[];
 foreach(['command_module'=>'cm','telemetry'=>'lm'] as $table=>$name){
  $q=$db->query("SELECT attribute,value FROM $table WHERE attribute IN ('x','y','z','delta_x','delta_y','delta_z','fuel')");
  if($q)while($row=$q->fetch_assoc()){if($name==='cm')$cm[$row['attribute']]=(float)$row['value'];else $lm[$row['attribute']]=(float)$row['value'];}
 }
 $clock=$db->query("SELECT seconds FROM mission_clock WHERE id=1");$t=(int)($clock?$clock->fetch_assoc()['seconds']:0);
 if($t<0||$t%30!==0)return;
 $run=$db->query('SELECT run_id,last_met FROM flight_history_run WHERE id=1')->fetch_assoc();
 if($t<(int)$run['last_met']){
   $db->query('UPDATE flight_history_run SET run_id=run_id+1 WHERE id=1');
   $run['run_id']++;
 }
 $runId=(int)$run['run_id']; // bounded persistent snapshot cadence
 $plan=$db->query("SELECT status FROM mission_burn_plan WHERE id=1");
 $status=(string)($plan?$plan->fetch_assoc()['status']:'NONE');
 $recent=$db->query('SELECT met FROM flight_history ORDER BY id DESC LIMIT 1');
 $last=$recent?$recent->fetch_assoc():null;
 if($last!==null&&(int)$last['met']===$t&&$t!==(int)$run['last_met'])return;
 $stmt=$db->prepare("INSERT INTO flight_history(run_id,met,cm_x,cm_y,cm_z,cm_vx,cm_vy,cm_vz,cm_fuel,lm_fuel,burn_status) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
 $v=[(float)($cm['x']??0),(float)($cm['y']??0),(float)($cm['z']??0),(float)($cm['delta_x']??0),(float)($cm['delta_y']??0),(float)($cm['delta_z']??0),(float)($cm['fuel']??0),(float)($lm['fuel']??0)];
 $stmt->bind_param('iidddddddds',$runId,$t,$v[0],$v[1],$v[2],$v[3],$v[4],$v[5],$v[6],$v[7],$status);$stmt->execute();
 $db->query('UPDATE flight_history_run SET last_met='.(int)$t.' WHERE id=1');
}
