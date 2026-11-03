<?php
declare(strict_types=1);

/**
 * Apply physical Saturn stage separation once, independently of the textual
 * CMguidance programs. Called after AGC from the serialized physics tick.
 * No forced ignition, attitude correction or synthetic velocity kick.
 */
function moonSimSaturnStageTransitions(mysqli $conn): ?string
{
    $result=$conn->query("SELECT attribute,value FROM command_module
        WHERE attribute IN ('stage1','stage2','stage3','s1fuel','s2fuel','ABORT')");
    if (!$result) throw new RuntimeException('Cannot read Saturn staging telemetry');
    $state=[];
    while ($row=$result->fetch_assoc()) $state[(string)$row['attribute']]=(float)$row['value'];
    if ((int)($state['ABORT']??0)===1) return null;
    $clock=$conn->query("SELECT seconds,launch_events FROM mission_clock WHERE id=1");
    if (!$clock || !($time=$clock->fetch_assoc())) return null;
    if ((int)$time['seconds']<0 || (int)$time['launch_events']!==1) return null;

    $first=(int)($state['stage1']??0)===1;
    $second=(int)($state['stage2']??0)===1;
    $third=(int)($state['stage3']??0)===1;
    if (!$third) return null;
    if ($first && $second && (float)($state['s1fuel']??INF)<=0.0) {
        $stage='First Stage';$flag='stage1';$program=19;
    } elseif (!$first && $second && (float)($state['s2fuel']??INF)<=0.0) {
        $stage='Second Stage';$flag='stage2';$program=20;
    } else {
        return null;
    }

    // A transaction guarantees the stage flag and departing engine shutdown
    // change together. Rows missing from the database must stop, not masquerade
    // as a successful separation.
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE command_module SET value=0
            WHERE attribute='$flag' AND value=1");
        if ($conn->affected_rows!==1) throw new RuntimeException('Missing expected Saturn stage flag');
        $conn->query("UPDATE EngineStatus SET status='OFF'
            WHERE engine_stage='$stage'");
        $conn->query("UPDATE command_module SET value=0
            WHERE attribute='thrust'");
        $conn->query("UPDATE command_module SET value=1
            WHERE attribute='staging'");
        // Pending duplicate legacy AGC separation would otherwise be applied
        // after this transition by a later physics tick.
        $conn->query("UPDATE CMguidance SET execute=0 WHERE id=$program");
        $conn->commit();
        return $stage;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
