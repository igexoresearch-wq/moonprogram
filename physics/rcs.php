<?php

include_once __DIR__ . '/../shared/rcs_commands.php';

/**
 * Consume queued translational RCS pulses for this one-second simulator step.
 *
 * A short pulse is converted to its exact one-second-equivalent delta-v
 * contribution. The central Verlet integrator then applies that acceleration
 * along with gravity/main-engine acceleration. Longer pulses remain queued
 * and are consumed over successive simulator seconds.
 */

function moonSimConsumeRcsPulses(
    mysqli $conn,
    array $cmQuaternion,
    array $lmQuaternion,
    float $stepSeconds = 1.0
): array {
    global
        $RCSCM, $RCSLM,
        $service_moduleCM,
        $totalSaturnV, $totalLEM,
        $sm_rcs_single_thrust, $sm_rcs_single_flow_rate,
        $sm_rcs_translation_jets,
        $lm_rcs_single_thrust, $lm_rcs_single_flow_rate,
        $lm_rcs_translation_jets;

    moonSimEnsureRcsQueue($conn);

    $result = $conn->query(
        "SELECT id, vehicle, body_x, body_y, body_z, remaining_seconds
         FROM rcs_pulse_queue
         WHERE completed_at IS NULL
         ORDER BY id ASC"
    );

    $acceleration = [
        'CM' => [0.0, 0.0, 0.0],
        'LM' => [0.0, 0.0, 0.0],
    ];

    if (!$result) {
        return $acceleration;
    }

    $cmFuel = max(0.0, (float)$RCSCM);
    $lmFuel = max(0.0, (float)$RCSLM);

    // CSM translational RCS is the Service Module RCS and is unavailable
    // after SM separation.
    $cmSwitchResult = $conn->query(
        "SELECT status
         FROM CMswitches
         WHERE name = 'RCS_Thrusters'
         LIMIT 1"
    );
    $cmSwitchRow = $cmSwitchResult ? $cmSwitchResult->fetch_assoc() : null;
    $cmEnabled =
        (int)$service_moduleCM === 1
        && (!$cmSwitchRow || (int)$cmSwitchRow['status'] === 1);

    $lmSwitchResult = $conn->query(
        "SELECT status
         FROM systems
         WHERE system = 'RCS'
         LIMIT 1"
    );
    $lmSwitchRow = $lmSwitchResult ? $lmSwitchResult->fetch_assoc() : null;
    $lmEnabled = !$lmSwitchRow || (int)$lmSwitchRow['status'] === 1;

    while ($pulse = $result->fetch_assoc()) {
        $id = (int)$pulse['id'];
        $vehicle = (string)$pulse['vehicle'];
        $remaining = max(0.0, (float)$pulse['remaining_seconds']);

        if ($remaining <= 1.0e-9) {
            $conn->query(
                "UPDATE rcs_pulse_queue
                 SET remaining_seconds = 0,
                     completed_at = NOW(6)
                 WHERE id = $id"
            );
            continue;
        }

        $body = [
            (float)$pulse['body_x'],
            (float)$pulse['body_y'],
            (float)$pulse['body_z'],
        ];

        if ($vehicle === 'CM') {
            if (!$cmEnabled || $cmFuel <= 0.0) {
                // The command is no longer physically executable.
                $conn->query(
                    "UPDATE rcs_pulse_queue
                     SET completed_at = NOW(6)
                     WHERE id = $id"
                );
                continue;
            }

            $jetCount = max(1, (int)$sm_rcs_translation_jets);
            $force = (float)$sm_rcs_single_thrust * $jetCount;
            $flow = (float)$sm_rcs_single_flow_rate * $jetCount;
            $mass = max(1.0, (float)$totalSaturnV);
            $world = moonSimRotateBodyVector($cmQuaternion, $body);

            $burn = min($remaining, $stepSeconds);
            if ($flow > 0.0) {
                $burn = min($burn, $cmFuel / $flow);
            }

            if ($burn > 0.0) {
                $duty = $burn / $stepSeconds;
                $a = ($force / $mass) * $duty;

                $acceleration['CM'][0] += $world[0] * $a;
                $acceleration['CM'][1] += $world[1] * $a;
                $acceleration['CM'][2] += $world[2] * $a;

                $used = $flow * $burn;
                $cmFuel = max(0.0, $cmFuel - $used);
                $remaining = max(0.0, $remaining - $burn);
            }
        } elseif ($vehicle === 'LM') {
            if (!$lmEnabled || $lmFuel <= 0.0) {
                $conn->query(
                    "UPDATE rcs_pulse_queue
                     SET completed_at = NOW(6)
                     WHERE id = $id"
                );
                continue;
            }

            $jetCount = max(1, (int)$lm_rcs_translation_jets);
            $force = (float)$lm_rcs_single_thrust * $jetCount;
            $flow = (float)$lm_rcs_single_flow_rate * $jetCount;
            $mass = max(1.0, (float)$totalLEM);
            $world = moonSimRotateBodyVector($lmQuaternion, $body);

            $burn = min($remaining, $stepSeconds);
            if ($flow > 0.0) {
                $burn = min($burn, $lmFuel / $flow);
            }

            if ($burn > 0.0) {
                $duty = $burn / $stepSeconds;
                $a = ($force / $mass) * $duty;

                $acceleration['LM'][0] += $world[0] * $a;
                $acceleration['LM'][1] += $world[1] * $a;
                $acceleration['LM'][2] += $world[2] * $a;

                $used = $flow * $burn;
                $lmFuel = max(0.0, $lmFuel - $used);
                $remaining = max(0.0, $remaining - $burn);
            }
        } else {
            $remaining = 0.0;
        }

        if ($remaining <= 1.0e-9) {
            $conn->query(
                "UPDATE rcs_pulse_queue
                 SET remaining_seconds = 0,
                     completed_at = NOW(6)
                 WHERE id = $id"
            );
        } else {
            $conn->query(
                "UPDATE rcs_pulse_queue
                 SET remaining_seconds = " . sprintf('%.9f', $remaining) . "
                 WHERE id = $id"
            );
        }
    }

    $conn->query(
        "UPDATE command_module
         SET value = " . sprintf('%.9f', $cmFuel) . "
         WHERE attribute = 'RCS'"
    );
    $conn->query(
        "UPDATE telemetry
         SET value = " . sprintf('%.9f', $lmFuel) . "
         WHERE attribute = 'RCS'"
    );

    $RCSCM = $cmFuel;
    $RCSLM = $lmFuel;

    return $acceleration;
}

?>
