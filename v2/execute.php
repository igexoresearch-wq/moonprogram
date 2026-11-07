<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/physics.php';
// Invoke once per minute from cron; runs up to 60 simulated one-second ticks.
// Prevent concurrent workers via a MySQL advisory lock.
$lock=$conn->query("SELECT GET_LOCK('moon_v2_execute',0) AS locked")->fetch_assoc();
if((int)$lock['locked']!==1)exit;
try {
 for($tick=0;$tick<60;$tick++){
  $conn->begin_transaction();
  try{
   moonV2Physics($conn,1.0);
   // Future systems: include another one-tick module HERE.
   $conn->query('UPDATE sim_clock SET tick=tick+1,updated_at=NOW() WHERE id=1');
   $conn->commit();
  }catch(Throwable $e){$conn->rollback();throw $e;}
 }
}finally{$conn->query("SELECT RELEASE_LOCK('moon_v2_execute')");}
