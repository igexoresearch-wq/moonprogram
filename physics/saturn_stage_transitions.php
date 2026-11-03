<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/saturn_stage_rules.php';

/**
 * Serialized physical-worker Saturn transition executor.
 * The small runtime table is intentionally persistent: once an engine group
 * is ignited, an operator shutdown must not be silently reversed each tick.
 * The mission clock rewind or prelaunch reset clears these one-shot latches.
 */
function moonSimSaturnStageTransitions(mysqli $conn, bool $automaticEnabled=true): ?string
{
    static $schemaReady=false;
    if (!$schemaReady) {
        if (!$conn->query("CREATE TABLE IF NOT EXISTS saturn_stage_runtime (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            sic_ignited TINYINT(1) NOT NULL DEFAULT 0,
            sii_ignited TINYINT(1) NOT NULL DEFAULT 0,
            sivb_ignited TINYINT(1) NOT NULL DEFAULT 0,
            staging_hold TINYINT(1) NOT NULL DEFAULT 0,
            last_seconds INT NULL
        ) ENGINE=InnoDB")) {
            throw new RuntimeException('Saturn stage runtime schema unavailable: '.$conn->error);
        }
        $schemaReady=true;
    }
    $conn->begin_transaction();
    try {
        $conn->query("INSERT IGNORE INTO saturn_stage_runtime (id) VALUES (1)");
        $timeResult=$conn->query("SELECT seconds,launch_events FROM mission_clock WHERE id=1");
        if (!$timeResult || !($time=$timeResult->fetch_assoc())) {
            throw new RuntimeException('Missing launch mission clock');
        }
        $seconds=(int)$time['seconds'];
        $events=(int)$time['launch_events'];
        $runtimeResult=$conn->query("SELECT * FROM saturn_stage_runtime WHERE id=1 FOR UPDATE");
        if (!$runtimeResult || !($runtime=$runtimeResult->fetch_assoc())) {
            throw new RuntimeException('Saturn ignition latch missing');
        }
        $stateResult=$conn->query("SELECT attribute,value FROM command_module
            WHERE attribute IN ('stage1','stage2','stage3','s1fuel','s2fuel',
                                's3fuel','staging','thrust','ABORT')");
        if (!$stateResult) throw new RuntimeException('Cannot read Saturn stage telemetry');
        $state=[];
        while ($row=$stateResult->fetch_assoc()) {
            $state[(string)$row['attribute']]=(float)$row['value'];
        }
        // A clock rewind is a new simulated flight, never a new ignition
        // within an already-running flight.
        if ($seconds<0 || ($runtime['last_seconds']!==null
            && $seconds<(int)$runtime['last_seconds']) || ($events===0 && $seconds<=0)) {
            $conn->query("UPDATE saturn_stage_runtime
                SET sic_ignited=0,sii_ignited=0,sivb_ignited=0,
                    staging_hold=0,last_seconds=$seconds WHERE id=1");
            $conn->commit();
            return null;
        }
        $action=moonSimSaturnNextAction($state,$runtime,$seconds,$events,$automaticEnabled);
        if ($action!==null && $action['type']==='clear_staging') {
            $conn->query("UPDATE command_module SET value=0 WHERE attribute='staging'");
            $conn->query("UPDATE saturn_stage_runtime SET staging_hold=0 WHERE id=1");
        } elseif ($action!==null && $action['type']==='separate') {
            $flag=$action['flag'];
            $name=$conn->real_escape_string($action['name']);
            $conn->query("UPDATE command_module SET value=0
                WHERE attribute='$flag' AND value=1");
            if ($conn->affected_rows!==1) {
                throw new RuntimeException('Saturn stage changed while separating');
            }
            $conn->query("UPDATE EngineStatus SET status='OFF' WHERE engine_stage='$name'");
            $conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'");
            $conn->query("UPDATE command_module SET value=1 WHERE attribute='staging'");
            $conn->query("UPDATE saturn_stage_runtime SET staging_hold=1 WHERE id=1");
            if ($action['program']!==null) {
                $program=(int)$action['program'];
                $conn->query("UPDATE CMguidance SET execute=0 WHERE id=$program");
            }
        } elseif ($action!==null && $action['type']==='ignite') {
            $name=$conn->real_escape_string($action['name']);
            $ready=$conn->query("SELECT COUNT(*) AS n FROM EngineStatus
                WHERE engine_stage='$name' AND component='Combustion Chamber'
                  AND operational_status='OK'");
            $available=$ready?(int)($ready->fetch_assoc()['n']??0):0;
            if ($available>0) {
                $conn->query("UPDATE EngineStatus SET status='ON'
                    WHERE engine_stage='$name' AND component='Combustion Chamber'
                      AND operational_status='OK'");
                $conn->query("UPDATE command_module SET value=100 WHERE attribute='thrust'");
                $latch=$action['latch'];
                $conn->query("UPDATE saturn_stage_runtime SET $latch=1 WHERE id=1");
                $program=$action['program'];
                // Physical ignition is owned here; no duplicate legacy script.
                $ignitionProgram=$name==='First Stage'?16:($name==='Second Stage'?17:18);
                $conn->query("UPDATE CMguidance SET execute=0 WHERE id=$ignitionProgram");
            }
        }
        $conn->query("UPDATE saturn_stage_runtime SET last_seconds=$seconds WHERE id=1");
        $conn->commit();
        return $action===null?null:($action['type'].' '.($action['name']??''));
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
