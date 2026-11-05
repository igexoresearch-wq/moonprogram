<?php
/**
 * Record actual changes observed in authoritative spacecraft state.
 * This does not issue commands, simulate failures, or change trajectories.
 * Call from the central physics worker after AGC execution.
 */
function moonSimRecordFlightTransitions(mysqli $conn): void
{
    static $ready = false;
    if (!$ready) {
        $conn->query("CREATE TABLE IF NOT EXISTS flight_event_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            mission_epoch DATETIME(6) NOT NULL,
            mission_seconds INT NOT NULL,
            event_key VARCHAR(110) NOT NULL,
            old_value VARCHAR(120) NOT NULL,
            new_value VARCHAR(120) NOT NULL,
            recorded_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            KEY idx_epoch_time (mission_epoch, mission_seconds)
        ) ENGINE=InnoDB");
        $conn->query("CREATE TABLE IF NOT EXISTS flight_event_observed (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            mission_epoch DATETIME(6) NOT NULL,
            snapshot_json TEXT NOT NULL
        ) ENGINE=InnoDB");
        $ready = true;
    }
    $clock = $conn->query("SELECT seconds FROM mission_clock WHERE id=1");
    $seconds = $clock ? (int)($clock->fetch_assoc()['seconds'] ?? -900) : -900;
    $state = [];
    $rows = $conn->query("SELECT attribute,value FROM command_module WHERE attribute IN ('stage1','stage2','stage3','service_module','escape','dock','ABORT','parachutes_deployed')");
    if ($rows) while ($row=$rows->fetch_assoc()) {
        $state['CM.'.$row['attribute']] = (string)$row['value'];
    }
    $rows = $conn->query("SELECT attribute,value FROM telemetry WHERE attribute IN ('descent','crash')");
    if ($rows) while ($row=$rows->fetch_assoc()) {
        $state['LM.'.$row['attribute']] = (string)$row['value'];
    }
    $rows = $conn->query("SELECT engine_stage,engine_name,component,status FROM EngineStatus WHERE component='Combustion Chamber'");
    if ($rows) while ($row=$rows->fetch_assoc()) {
        $state['ENGINE.'.$row['engine_stage'].'.'.$row['engine_name']] = (string)$row['status'];
    }
    // Record observed guidance/burn status transitions in the same mission epoch.
    foreach ([
      'mission_burn_plan'=>['phase','status','ignition_met','cutoff_met'],
      'lm_ascent_guidance'=>['enabled','status','target_altitude_m'],
      'lm_docking_guidance'=>['enabled','status'],
    ] as $table=>$columns) {
      $result=$conn->query("SHOW TABLES LIKE '".$table."'");
      if(!$result||$result->num_rows===0)continue;
      $columnsSql=implode(',',array_map(static fn($v)=>"`".$v."`",$columns));
      $q=$conn->query("SELECT ".$columnsSql." FROM ".$table." WHERE id=1 LIMIT 1");
      if(!$q||!($record=$q->fetch_assoc()))continue;
      foreach($record as $key=>$value)$state[strtoupper($table).'.'.$key]=(string)$value;
    }
    // Observe real AGC/burn guidance transitions, never synthesize a mission event.
    $rows=$conn->query("SELECT program_number FROM CMprogram_register LIMIT 1");
    if($rows && ($row=$rows->fetch_assoc()))$state['AGC.program_number']=(string)$row['program_number'];
    $rows=$conn->query("SELECT phase,status FROM mission_burn_plan WHERE id=1");
    if($rows && ($row=$rows->fetch_assoc())){
        $state['BURN.phase']=(string)$row['phase'];
        $state['BURN.status']=(string)$row['status'];
    }
    $rows=$conn->query("SELECT chute_state,crew_survives FROM reentry_state WHERE id=1");
    if($rows && ($row=$rows->fetch_assoc())){
        $state['ENTRY.chute']=(string)$row['chute_state'];
        $state['ENTRY.crew_survives']=(string)$row['crew_survives'];
    }
    ksort($state);
    $current = json_encode($state, JSON_THROW_ON_ERROR);
    $priorResult = $conn->query("SELECT mission_epoch,snapshot_json FROM flight_event_observed WHERE id=1");
    $prior = $priorResult ? $priorResult->fetch_assoc() : null;

    // At reset, establish a new event epoch and clear the observation latch.
    // Preserve all earlier flight events for post-flight investigation.
    if ($seconds === -900) {
        $epoch = date('Y-m-d H:i:s.u');
        $stmt=$conn->prepare("REPLACE INTO flight_event_observed (id,mission_epoch,snapshot_json) VALUES (1,?,?)");
        $stmt->bind_param('ss',$epoch,$current); $stmt->execute();
        return;
    }
    if ($prior === null) {
        $epoch = date('Y-m-d H:i:s.u');
        $stmt=$conn->prepare("INSERT INTO flight_event_observed (id,mission_epoch,snapshot_json) VALUES (1,?,?)");
        $stmt->bind_param('ss',$epoch,$current); $stmt->execute();
        return;
    }
    $old=json_decode((string)$prior['snapshot_json'],true) ?: [];
    $epoch=(string)$prior['mission_epoch'];
    $insert=$conn->prepare("INSERT INTO flight_event_log (mission_epoch,mission_seconds,event_key,old_value,new_value) VALUES (?,?,?,?,?)");
    foreach ($state as $key=>$new) {
        if (!array_key_exists($key,$old)) continue;
        $previous=(string)$old[$key];
        if ($previous===$new) continue;
        $insert->bind_param('sisss',$epoch,$seconds,$key,$previous,$new);
        $insert->execute();
    }
    $stmt=$conn->prepare("UPDATE flight_event_observed SET snapshot_json=? WHERE id=1");
    $stmt->bind_param('s',$current); $stmt->execute();
}
