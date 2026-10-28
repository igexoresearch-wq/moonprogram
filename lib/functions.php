<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function instantiateDefinition(
    PDO $pdo,
    int $definitionId,
    ?int $missionId,
    string $instanceName,
    ?int $parentObjectId = null,
    bool $attached = false
): int {
    $pdo->beginTransaction();

    try {
        $definitionStmt = $pdo->prepare(
            "SELECT * FROM object_definitions WHERE id = :id FOR UPDATE"
        );
        $definitionStmt->execute(['id' => $definitionId]);
        $definition = $definitionStmt->fetch();

        if (!$definition) {
            throw new RuntimeException('Object definition not found.');
        }

        $xCm = 0;
        $yCm = 0;
        $zCm = 0;
        $vx = '0';
        $vy = '0';
        $vz = '0';
        $pitch = $definition['has_orientation'] ? '0.0000' : null;
        $yaw = $definition['has_orientation'] ? '0.0000' : null;
        $roll = $definition['has_orientation'] ? '0.0000' : null;

        if ($parentObjectId !== null) {
            $parentStmt = $pdo->prepare(
                "SELECT * FROM live_objects WHERE id = :id FOR UPDATE"
            );
            $parentStmt->execute(['id' => $parentObjectId]);
            $parent = $parentStmt->fetch();

            if (!$parent) {
                throw new RuntimeException('Parent live object not found.');
            }

            $xCm = (int)$parent['x_cm'];
            $yCm = (int)$parent['y_cm'];
            $zCm = (int)$parent['z_cm'];
            $vx = $parent['vx_cm_s'];
            $vy = $parent['vy_cm_s'];
            $vz = $parent['vz_cm_s'];

            if ($definition['has_orientation']) {
                $pitch = $parent['pitch_deg'] ?? '0.0000';
                $yaw = $parent['yaw_deg'] ?? '0.0000';
                $roll = $parent['roll_deg'] ?? '0.0000';
            }
        }

        $insert = $pdo->prepare(
            "INSERT INTO live_objects (
                mission_id, definition_id, name,
                parent_object_id, is_attached,
                dry_mass_kg, radius_m,
                x_cm, y_cm, z_cm,
                vx_cm_s, vy_cm_s, vz_cm_s,
                pitch_deg, yaw_deg, roll_deg,
                pitch_rate_deg_s, yaw_rate_deg_s, roll_rate_deg_s,
                produces_gravity, affected_by_gravity
             ) VALUES (
                :mission_id, :definition_id, :name,
                :parent_object_id, :is_attached,
                :dry_mass_kg, :radius_m,
                :x_cm, :y_cm, :z_cm,
                :vx, :vy, :vz,
                :pitch, :yaw, :roll,
                :pitch_rate, :yaw_rate, :roll_rate,
                :produces_gravity, :affected_by_gravity
             )"
        );

        $insert->execute([
            'mission_id' => $missionId,
            'definition_id' => $definitionId,
            'name' => $instanceName,
            'parent_object_id' => $parentObjectId,
            'is_attached' => $attached ? 1 : 0,
            'dry_mass_kg' => $definition['dry_mass_kg'],
            'radius_m' => $definition['radius_m'],
            'x_cm' => $xCm,
            'y_cm' => $yCm,
            'z_cm' => $zCm,
            'vx' => $vx,
            'vy' => $vy,
            'vz' => $vz,
            'pitch' => $pitch,
            'yaw' => $yaw,
            'roll' => $roll,
            'pitch_rate' => $definition['has_orientation'] ? '0.00000' : null,
            'yaw_rate' => $definition['has_orientation'] ? '0.00000' : null,
            'roll_rate' => $definition['has_orientation'] ? '0.00000' : null,
            'produces_gravity' => $definition['produces_gravity'],
            'affected_by_gravity' => $definition['affected_by_gravity'],
        ]);

        $liveId = (int)$pdo->lastInsertId();

        $pdo->prepare(
            "INSERT INTO live_fuel_tanks (
                live_object_id, definition_tank_id, name,
                propellant_type, capacity_kg, remaining_kg
             )
             SELECT
                :live_id, id, name,
                propellant_type, capacity_kg, capacity_kg
             FROM definition_fuel_tanks
             WHERE object_definition_id = :definition_id"
        )->execute([
            'live_id' => $liveId,
            'definition_id' => $definitionId,
        ]);

        $pdo->prepare(
            "INSERT INTO live_engines (
                live_object_id, engine_definition_id,
                mount_definition_id, quantity, throttle_percent,
                enabled, local_x, local_y, local_z
             )
             SELECT
                :live_id, engine_definition_id,
                id, quantity, 0,
                1, local_x, local_y, local_z
             FROM definition_engine_mounts
             WHERE object_definition_id = :definition_id"
        )->execute([
            'live_id' => $liveId,
            'definition_id' => $definitionId,
        ]);

        $pdo->commit();
        return $liveId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function totalLiveObjectMass(PDO $pdo, int $liveObjectId): float
{
    $stmt = $pdo->prepare(
        "SELECT
            o.dry_mass_kg
            + COALESCE((SELECT SUM(remaining_kg)
                        FROM live_fuel_tanks
                        WHERE live_object_id = o.id), 0)
            + COALESCE((SELECT SUM(ed.dry_mass_kg * le.quantity)
                        FROM live_engines AS le
                        JOIN engine_definitions AS ed
                          ON ed.id = le.engine_definition_id
                        WHERE le.live_object_id = o.id), 0)
          AS total_mass
         FROM live_objects AS o
         WHERE o.id = :id"
    );
    $stmt->execute(['id' => $liveObjectId]);

    return (float)$stmt->fetchColumn();
}
