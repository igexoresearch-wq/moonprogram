<?php

declare(strict_types=1);

/*
 * Rotate a local engine vector into world XYZ.
 *
 * Convention:
 * local +X = nose/forward
 * positive pitch = nose toward +Z
 * positive yaw = nose toward +Y
 * roll = rotation around local +X
 */
function physicsRotateLocalVector(
    float $x,
    float $y,
    float $z,
    float $pitchDeg,
    float $yawDeg,
    float $rollDeg
): array {
    $r = deg2rad($rollDeg);
    $p = deg2rad($pitchDeg);
    $yA = deg2rad($yawDeg);

    // Rx(roll)
    $x1 = $x;
    $y1 = $y * cos($r) - $z * sin($r);
    $z1 = $y * sin($r) + $z * cos($r);

    // Ry(-pitch), so +pitch sends +X toward +Z
    $x2 = $x1 * cos($p) - $z1 * sin($p);
    $y2 = $y1;
    $z2 = $x1 * sin($p) + $z1 * cos($p);

    // Rz(yaw)
    return [
        'x' => $x2 * cos($yA) - $y2 * sin($yA),
        'y' => $x2 * sin($yA) + $y2 * cos($yA),
        'z' => $z2,
    ];
}

function physicsPropulsion(
    PDO $pdo,
    float $dt,
    array $objects,
    array $rootMembers,
    array $stackMasses
): array {
    $accelerations = [];

    foreach ($rootMembers as $rootId => $_members) {
        $accelerations[$rootId] = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];
    }

    $engineRows = $pdo->query(
        "SELECT
            le.id AS live_engine_id,
            le.live_object_id,
            le.quantity,
            le.throttle_percent,
            le.enabled,
            le.local_x,
            le.local_y,
            le.local_z,
            ed.id AS engine_definition_id,
            ed.thrust_n,
            ed.fuel_rate_kg_s
         FROM live_engines AS le
         JOIN engine_definitions AS ed
           ON ed.id = le.engine_definition_id
         WHERE le.enabled = 1
           AND le.throttle_percent > 0"
    )->fetchAll();

    $requirementStmt = $pdo->prepare(
        "SELECT propellant_type, mass_fraction
         FROM engine_propellant_requirements
         WHERE engine_definition_id = :engine_id"
    );

    $tankStmt = $pdo->prepare(
        "SELECT id, remaining_kg
         FROM live_fuel_tanks
         WHERE live_object_id = :object_id
           AND propellant_type = :propellant_type
         ORDER BY id
         FOR UPDATE"
    );

    $tankUpdate = $pdo->prepare(
        "UPDATE live_fuel_tanks
         SET remaining_kg = GREATEST(0, remaining_kg - :used)
         WHERE id = :id"
    );

    foreach ($engineRows as $engine) {
        $objectId = (int)$engine['live_object_id'];

        if (!isset($objects[$objectId])) {
            continue;
        }

        $rootId = physicsRootId($objectId, $objects);
        $throttle = max(0.0, min(1.0, (float)$engine['throttle_percent'] / 100.0));
        $quantity = max(1, (int)$engine['quantity']);

        $requestedFuel =
            (float)$engine['fuel_rate_kg_s']
            * $quantity
            * $throttle
            * $dt;

        $burnScale = 1.0;

        $requirementStmt->execute([
            'engine_id' => (int)$engine['engine_definition_id'],
        ]);
        $requirements = $requirementStmt->fetchAll();

        if ($requestedFuel > 0.0) {
            if (!$requirements) {
                $burnScale = 0.0;
            } else {
                foreach ($requirements as $requirement) {
                    $required =
                        $requestedFuel
                        * (float)$requirement['mass_fraction'];

                    if ($required <= 0.0) {
                        continue;
                    }

                    $tankStmt->execute([
                        'object_id' => $objectId,
                        'propellant_type' => $requirement['propellant_type'],
                    ]);
                    $tanks = $tankStmt->fetchAll();

                    $available = 0.0;
                    foreach ($tanks as $tank) {
                        $available += (float)$tank['remaining_kg'];
                    }

                    $burnScale = min($burnScale, $available / $required);
                }

                $burnScale = max(0.0, min(1.0, $burnScale));

                foreach ($requirements as $requirement) {
                    $remainingToUse =
                        $requestedFuel
                        * (float)$requirement['mass_fraction']
                        * $burnScale;

                    if ($remainingToUse <= 0.0) {
                        continue;
                    }

                    $tankStmt->execute([
                        'object_id' => $objectId,
                        'propellant_type' => $requirement['propellant_type'],
                    ]);

                    foreach ($tankStmt->fetchAll() as $tank) {
                        if ($remainingToUse <= 0.0) {
                            break;
                        }

                        $available = (float)$tank['remaining_kg'];
                        $use = min($available, $remainingToUse);

                        $tankUpdate->execute([
                            'used' => number_format($use, 9, '.', ''),
                            'id' => (int)$tank['id'],
                        ]);

                        $remainingToUse -= $use;
                    }
                }
            }
        }

        if ($burnScale <= 0.0) {
            continue;
        }

        $thrust =
            (float)$engine['thrust_n']
            * $quantity
            * $throttle
            * $burnScale;

        if ($thrust <= 0.0 || $stackMasses[$rootId] <= 0.0) {
            continue;
        }

        $direction = physicsRotateLocalVector(
            (float)$engine['local_x'],
            (float)$engine['local_y'],
            (float)$engine['local_z'],
            (float)($objects[$rootId]['pitch_deg'] ?? 0.0),
            (float)($objects[$rootId]['yaw_deg'] ?? 0.0),
            (float)($objects[$rootId]['roll_deg'] ?? 0.0)
        );

        $length = sqrt(
            $direction['x'] * $direction['x']
            + $direction['y'] * $direction['y']
            + $direction['z'] * $direction['z']
        );

        if ($length <= 0.0) {
            continue;
        }

        $accel = $thrust / $stackMasses[$rootId];

        $accelerations[$rootId]['x'] += $accel * $direction['x'] / $length;
        $accelerations[$rootId]['y'] += $accel * $direction['y'] / $length;
        $accelerations[$rootId]['z'] += $accel * $direction['z'] / $length;
    }

    return $accelerations;
}
