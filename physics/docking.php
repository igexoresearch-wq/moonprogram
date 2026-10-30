<?php

include_once __DIR__ . '/../shared/docking_commands.php';

function moonSimQuaternionSeparationDeg(array $a, array $b): float
{
    $a = moonSimQuaternionNormalize($a);
    $b = moonSimQuaternionNormalize($b);

    // q and -q represent the same orientation.
    $dot = abs(
        $a[0]*$b[0]
        + $a[1]*$b[1]
        + $a[2]*$b[2]
        + $a[3]*$b[3]
    );
    $dot = max(-1.0, min(1.0, $dot));

    return rad2deg(2.0 * acos($dot));
}

function moonSimApplySeparationVelocity(
    array $cmVelocity,
    array $lmVelocity,
    array $direction,
    float $relativeSpeed,
    float $cmMass,
    float $lmMass
): array {
    $cmMass = max(1.0, $cmMass);
    $lmMass = max(1.0, $lmMass);
    $sum = $cmMass + $lmMass;

    $cmDv = $relativeSpeed * ($lmMass / $sum);
    $lmDv = $relativeSpeed * ($cmMass / $sum);

    return [
        [
            $cmVelocity[0] + $direction[0]*$cmDv,
            $cmVelocity[1] + $direction[1]*$cmDv,
            $cmVelocity[2] + $direction[2]*$cmDv,
        ],
        [
            $lmVelocity[0] - $direction[0]*$lmDv,
            $lmVelocity[1] - $direction[1]*$lmDv,
            $lmVelocity[2] - $direction[2]*$lmDv,
        ],
    ];
}

/**
 * Consume docking/separation commands before the physical step.
 * Returns true if live vehicle state changed and variables/constants should be
 * reloaded before forces are calculated.
 */
function moonSimConsumeDockingCommands(
    mysqli $conn,
    array $cmQuaternion,
    array $lmQuaternion
): bool {
    global
        $dockCM, $stage3CM,
        $delta_xCM, $delta_yCM, $delta_zCM,
        $delta_xLM, $delta_yLM, $delta_zLM,
        $totalSaturnV, $totalLEM,
        $stage3_empty_mass, $s3fuelCM;

    moonSimEnsureDockingCommandTables($conn);

    $result = $conn->query(
        "SELECT id, command_type
         FROM docking_command_queue
         WHERE processed_at IS NULL
         ORDER BY id ASC"
    );

    if (!$result) {
        return false;
    }

    $changed = false;

    while ($command = $result->fetch_assoc()) {
        $id = (int)$command['id'];
        $type = (string)$command['command_type'];

        if ($type === 'UNDOCK' && (int)$dockCM === 1 && (int)$stage3CM === 0) {
            $direction = moonSimRotateBodyVector(
                $cmQuaternion,
                [1.0,0.0,0.0]
            );

            $cmVelocity = [
                (float)$delta_xCM,
                (float)$delta_yCM,
                (float)$delta_zCM,
            ];
            $lmVelocity = [
                (float)$delta_xLM,
                (float)$delta_yLM,
                (float)$delta_zLM,
            ];

            // totalSaturnV currently includes docked LM mass; remove it to get
            // the CSM-side mass for momentum-conserving spring separation.
            $cmMass = max(1.0, (float)$totalSaturnV - (float)$totalLEM);
            $lmMass = max(1.0, (float)$totalLEM);

            [$cmVelocity,$lmVelocity] = moonSimApplySeparationVelocity(
                $cmVelocity,
                $lmVelocity,
                $direction,
                0.30,
                $cmMass,
                $lmMass
            );

            [$delta_xCM,$delta_yCM,$delta_zCM] = $cmVelocity;
            [$delta_xLM,$delta_yLM,$delta_zLM] = $lmVelocity;
            $dockCM = 0;

            $conn->query("UPDATE command_module SET value=0 WHERE attribute='dock'");
            $conn->query(
                "UPDATE command_module SET value = CASE attribute
                    WHEN 'delta_x' THEN {$cmVelocity[0]}
                    WHEN 'delta_y' THEN {$cmVelocity[1]}
                    WHEN 'delta_z' THEN {$cmVelocity[2]}
                 END
                 WHERE attribute IN ('delta_x','delta_y','delta_z')"
            );
            $conn->query(
                "UPDATE telemetry SET value = CASE attribute
                    WHEN 'delta_x' THEN {$lmVelocity[0]}
                    WHEN 'delta_y' THEN {$lmVelocity[1]}
                    WHEN 'delta_z' THEN {$lmVelocity[2]}
                 END
                 WHERE attribute IN ('delta_x','delta_y','delta_z')"
            );

            $conn->query(
                "UPDATE docking_runtime
                 SET redock_inhibit_until = DATE_ADD(NOW(6), INTERVAL 10 SECOND),
                     last_release_at = NOW(6)
                 WHERE id=1"
            );

            $changed = true;
        }

        if ($type === 'SIVB_SEPARATE' && (int)$stage3CM === 1) {
            /*
             * The original simulator does not retain S-IVB as an independent
             * post-separation object. Preserve that architecture: LM state
             * stands in for the LM/S-IVB side during transposition/extraction,
             * while the CSM receives a small physical separation velocity.
             */
            $direction = moonSimRotateBodyVector(
                $cmQuaternion,
                [1.0,0.0,0.0]
            );

            $cmVelocity = [
                (float)$delta_xCM,
                (float)$delta_yCM,
                (float)$delta_zCM,
            ];
            $lmVelocity = [
                (float)$delta_xLM,
                (float)$delta_yLM,
                (float)$delta_zLM,
            ];

            $cmSideMass = max(
                1.0,
                (float)$totalSaturnV
                - (float)$totalLEM
                - (float)$stage3_empty_mass
                - (float)$s3fuelCM
            );
            $lmSideMass = max(
                1.0,
                (float)$totalLEM
                + (float)$stage3_empty_mass
                + (float)$s3fuelCM
            );

            [$cmVelocity,$lmVelocity] = moonSimApplySeparationVelocity(
                $cmVelocity,
                $lmVelocity,
                $direction,
                0.50,
                $cmSideMass,
                $lmSideMass
            );

            [$delta_xCM,$delta_yCM,$delta_zCM] = $cmVelocity;
            [$delta_xLM,$delta_yLM,$delta_zLM] = $lmVelocity;
            $stage3CM = 0;
            $dockCM = 0;

            $conn->query("UPDATE command_module SET value=0 WHERE attribute='stage3'");
            $conn->query("UPDATE command_module SET value=0 WHERE attribute='dock'");
            $conn->query("UPDATE command_module SET value=0 WHERE attribute='thrust'");
            $conn->query(
                "UPDATE EngineStatus
                 SET status='OFF'
                 WHERE engine_stage='Third Stage'"
            );

            $conn->query(
                "UPDATE command_module SET value = CASE attribute
                    WHEN 'delta_x' THEN {$cmVelocity[0]}
                    WHEN 'delta_y' THEN {$cmVelocity[1]}
                    WHEN 'delta_z' THEN {$cmVelocity[2]}
                 END
                 WHERE attribute IN ('delta_x','delta_y','delta_z')"
            );
            $conn->query(
                "UPDATE telemetry SET value = CASE attribute
                    WHEN 'delta_x' THEN {$lmVelocity[0]}
                    WHEN 'delta_y' THEN {$lmVelocity[1]}
                    WHEN 'delta_z' THEN {$lmVelocity[2]}
                 END
                 WHERE attribute IN ('delta_x','delta_y','delta_z')"
            );

            $conn->query(
                "UPDATE docking_runtime
                 SET redock_inhibit_until = DATE_ADD(NOW(6), INTERVAL 10 SECOND),
                     last_release_at = NOW(6)
                 WHERE id=1"
            );

            $changed = true;
        }

        $conn->query(
            "UPDATE docking_command_queue
             SET processed_at=NOW(6)
             WHERE id=$id"
        );
    }

    return $changed;
}

function moonSimTryDockCapture(
    mysqli $conn,
    array $cmPosition,
    array $cmVelocity,
    array $lmPosition,
    array $lmVelocity,
    array $cmQuaternion,
    array $lmQuaternion,
    int $stage3,
    int $dock
): bool {
    if ($stage3 !== 0 || $dock !== 0) {
        return false;
    }

    moonSimEnsureDockingCommandTables($conn);

    $runtime = $conn->query(
        "SELECT
            redock_inhibit_until,
            CASE
              WHEN redock_inhibit_until IS NOT NULL
               AND redock_inhibit_until > NOW(6)
              THEN 1 ELSE 0
            END AS inhibited
         FROM docking_runtime
         WHERE id=1"
    );
    $runtimeRow = $runtime ? $runtime->fetch_assoc() : null;

    if ($runtimeRow && (int)$runtimeRow['inhibited'] === 1) {
        return false;
    }

    $r = [
        $lmPosition[0]-$cmPosition[0],
        $lmPosition[1]-$cmPosition[1],
        $lmPosition[2]-$cmPosition[2],
    ];
    $distance = sqrt($r[0]**2+$r[1]**2+$r[2]**2);

    if ($distance > 2.0) {
        return false;
    }

    $v = [
        $lmVelocity[0]-$cmVelocity[0],
        $lmVelocity[1]-$cmVelocity[1],
        $lmVelocity[2]-$cmVelocity[2],
    ];
    $relativeSpeed = sqrt($v[0]**2+$v[1]**2+$v[2]**2);

    if ($relativeSpeed > 0.30) {
        return false;
    }

    if ($distance > 1.0e-6) {
        $normal = [$r[0]/$distance,$r[1]/$distance,$r[2]/$distance];
        $radialVelocity = $v[0]*$normal[0]+$v[1]*$normal[1]+$v[2]*$normal[2];

        // Positive radial velocity means the vehicles are separating.
        if ($radialVelocity > 0.05) {
            return false;
        }
    }

    $attitudeError = moonSimQuaternionSeparationDeg(
        $cmQuaternion,
        $lmQuaternion
    );

    if ($attitudeError > 15.0) {
        return false;
    }

    $conn->query("UPDATE command_module SET value=1 WHERE attribute='dock'");
    $conn->query(
        "UPDATE telemetry SET value = CASE attribute
            WHEN 'x' THEN {$cmPosition[0]}
            WHEN 'y' THEN {$cmPosition[1]}
            WHEN 'z' THEN {$cmPosition[2]}
            WHEN 'delta_x' THEN {$cmVelocity[0]}
            WHEN 'delta_y' THEN {$cmVelocity[1]}
            WHEN 'delta_z' THEN {$cmVelocity[2]}
         END
         WHERE attribute IN ('x','y','z','delta_x','delta_y','delta_z')"
    );
    moonSimStoreVehicleQuaternion($conn, 'LM', $cmQuaternion);

    $conn->query(
        "UPDATE docking_runtime
         SET redock_inhibit_until=NULL,
             last_capture_at=NOW(6)
         WHERE id=1"
    );

    return true;
}

?>
