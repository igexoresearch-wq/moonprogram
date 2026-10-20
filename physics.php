<?php

declare(strict_types=1);

/**
 * Moon Program gravity/integration core.
 *
 * This file intentionally handles only:
 * - elapsed simulation time
 * - gravity
 * - rigid attached stacks
 * - position/velocity
 * - pitch/yaw/roll rates
 * - spherical surface protection
 *
 * Thrust, fuel burn, staging impulses, docking capture, and RCS torque
 * are separate future modules.
 */

const MOON_G = 6.67430e-11;
const MOON_PHYSICS_LOCK = 'moonprogram_physics_tick';
const MOON_MAX_SUBSTEPS_PER_RUN = 10000;

function moonWrapDegrees(float $degrees): float
{
    $degrees = fmod($degrees, 360.0);

    if ($degrees < 0.0) {
        $degrees += 360.0;
    }

    return $degrees;
}

function moonSplitCentimeters(float $valueCm): array
{
    if (!is_finite($valueCm)) {
        throw new RuntimeException('Physics produced a non-finite position.');
    }

    $whole = floor($valueCm);
    $fraction = round($valueCm - $whole, 9);

    if ($fraction >= 1.0) {
        $whole += 1.0;
        $fraction = 0.0;
    }

    if ($fraction < 0.0) {
        $whole -= 1.0;
        $fraction += 1.0;
    }

    if ($whole > PHP_INT_MAX || $whole < PHP_INT_MIN) {
        throw new RuntimeException('Position exceeded signed 64-bit integer range.');
    }

    return [(int)$whole, $fraction];
}

function moonLoadWorld(PDO $pdo): array
{
    $objectsStmt = $pdo->query(
        "SELECT *
         FROM space_objects
         ORDER BY id
         FOR UPDATE"
    );

    $objects = [];

    foreach ($objectsStmt->fetchAll() as $row) {
        $id = (int)$row['id'];

        $objects[$id] = [
            'id' => $id,
            'slug' => (string)$row['slug'],
            'name' => (string)$row['name'],
            'object_class' => (string)$row['object_class'],
            'component_type' => (string)$row['component_type'],

            'base_mass_kg' => (float)$row['base_mass_kg'],
            'gm_m3_s2' => $row['gm_m3_s2'] === null
                ? null
                : (float)$row['gm_m3_s2'],
            'radius_m' => $row['radius_m'] === null
                ? null
                : (float)$row['radius_m'],

            'x_m' => ((float)$row['x_cm'] + (float)$row['x_subcm']) / 100.0,
            'y_m' => ((float)$row['y_cm'] + (float)$row['y_subcm']) / 100.0,
            'z_m' => ((float)$row['z_cm'] + (float)$row['z_subcm']) / 100.0,

            'vx_m_s' => (float)$row['vx_cm_s'] / 100.0,
            'vy_m_s' => (float)$row['vy_cm_s'] / 100.0,
            'vz_m_s' => (float)$row['vz_cm_s'] / 100.0,

            'pitch_deg' => $row['pitch_deg'] === null
                ? null
                : (float)$row['pitch_deg'],
            'yaw_deg' => $row['yaw_deg'] === null
                ? null
                : (float)$row['yaw_deg'],
            'roll_deg' => $row['roll_deg'] === null
                ? null
                : (float)$row['roll_deg'],

            'pitch_rate_deg_s' => $row['pitch_rate_deg_s'] === null
                ? null
                : (float)$row['pitch_rate_deg_s'],
            'yaw_rate_deg_s' => $row['yaw_rate_deg_s'] === null
                ? null
                : (float)$row['yaw_rate_deg_s'],
            'roll_rate_deg_s' => $row['roll_rate_deg_s'] === null
                ? null
                : (float)$row['roll_rate_deg_s'],

            'produces_gravity' => (bool)$row['produces_gravity'],
            'affected_by_gravity' => (bool)$row['affected_by_gravity'],
            'is_fixed' => (bool)$row['is_fixed'],

            'surface_body_id' => $row['surface_body_id'] === null
                ? null
                : (int)$row['surface_body_id'],
            'surface_locked' => (bool)$row['surface_locked'],
            'on_surface' => (bool)$row['on_surface'],
        ];
    }

    $attachmentsStmt = $pdo->query(
        "SELECT parent_object_id, child_object_id
         FROM object_attachments
         ORDER BY id
         FOR UPDATE"
    );

    $parentOf = [];

    foreach ($attachmentsStmt->fetchAll() as $row) {
        $parentOf[(int)$row['child_object_id']] = (int)$row['parent_object_id'];
    }

    $propellantStmt = $pdo->query(
        "SELECT id, object_id, remaining_kg
         FROM propellant_systems
         ORDER BY id
         FOR UPDATE"
    );

    $propellantMassByObject = [];

    foreach ($propellantStmt->fetchAll() as $row) {
        $objectId = (int)$row['object_id'];

        $propellantMassByObject[$objectId] =
            ($propellantMassByObject[$objectId] ?? 0.0)
            + (float)$row['remaining_kg'];
    }

    return [$objects, $parentOf, $propellantMassByObject];
}

function moonFindRoot(int $objectId, array $parentOf, array $objects): int
{
    $current = $objectId;
    $seen = [];

    while (isset($parentOf[$current])) {
        if (isset($seen[$current])) {
            throw new RuntimeException('Attachment cycle detected.');
        }

        $seen[$current] = true;
        $parent = (int)$parentOf[$current];

        if (!isset($objects[$parent])) {
            throw new RuntimeException('Attachment references a missing parent object.');
        }

        $current = $parent;
    }

    return $current;
}

function moonBuildRootMembers(array $objects, array $parentOf): array
{
    $members = [];

    foreach ($objects as $id => $_object) {
        $rootId = moonFindRoot((int)$id, $parentOf, $objects);
        $members[$rootId][] = (int)$id;
    }

    return $members;
}

function moonCalculateStackMasses(
    array $objects,
    array $rootMembers,
    array $propellantMassByObject
): array {
    $masses = [];

    foreach ($rootMembers as $rootId => $memberIds) {
        $mass = 0.0;

        foreach ($memberIds as $memberId) {
            $mass += $objects[$memberId]['base_mass_kg'];
            $mass += $propellantMassByObject[$memberId] ?? 0.0;
        }

        if (!is_finite($mass) || $mass <= 0.0) {
            throw new RuntimeException(
                "Invalid mass calculated for physics root {$rootId}."
            );
        }

        $masses[(int)$rootId] = $mass;
    }

    return $masses;
}

function moonGravityAccelerations(
    array $objects,
    array $rootMembers,
    array $stackMasses
): array {
    $rootIds = array_map('intval', array_keys($rootMembers));
    $accelerations = [];

    foreach ($rootIds as $targetId) {
        $accelerations[$targetId] = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];

        $target = $objects[$targetId];

        if (!$target['affected_by_gravity']) {
            continue;
        }

        foreach ($rootIds as $sourceId) {
            if ($sourceId === $targetId) {
                continue;
            }

            $source = $objects[$sourceId];

            if (!$source['produces_gravity']) {
                continue;
            }

            $dx = $source['x_m'] - $target['x_m'];
            $dy = $source['y_m'] - $target['y_m'];
            $dz = $source['z_m'] - $target['z_m'];

            $r2 = $dx * $dx + $dy * $dy + $dz * $dz;

            if (!is_finite($r2) || $r2 < 1.0) {
                continue;
            }

            $r = sqrt($r2);

            $mu = $source['gm_m3_s2'];

            if ($mu === null) {
                $mu = MOON_G * $stackMasses[$sourceId];
            }

            $factor = $mu / ($r2 * $r);

            $accelerations[$targetId]['x'] += $dx * $factor;
            $accelerations[$targetId]['y'] += $dy * $factor;
            $accelerations[$targetId]['z'] += $dz * $factor;
        }
    }

    return $accelerations;
}

function moonSynchronizeAttachedObjects(
    array &$objects,
    array $rootMembers
): void {
    foreach ($rootMembers as $rootId => $memberIds) {
        $root = $objects[$rootId];

        foreach ($memberIds as $memberId) {
            if ($memberId === $rootId) {
                continue;
            }

            foreach ([
                'x_m', 'y_m', 'z_m',
                'vx_m_s', 'vy_m_s', 'vz_m_s',
                'pitch_deg', 'yaw_deg', 'roll_deg',
                'pitch_rate_deg_s', 'yaw_rate_deg_s', 'roll_rate_deg_s',
            ] as $field) {
                $objects[$memberId][$field] = $root[$field];
            }

            $objects[$memberId]['on_surface'] = false;
        }
    }
}

function moonApplySurfaceProtection(int $rootId, array &$objects): void
{
    $object = &$objects[$rootId];
    $bodyId = $object['surface_body_id'];

    if ($bodyId === null || !isset($objects[$bodyId])) {
        $object['on_surface'] = false;
        return;
    }

    $body = $objects[$bodyId];

    if ($body['radius_m'] === null || $body['radius_m'] <= 0.0) {
        return;
    }

    $dx = $object['x_m'] - $body['x_m'];
    $dy = $object['y_m'] - $body['y_m'];
    $dz = $object['z_m'] - $body['z_m'];

    $distance = sqrt($dx * $dx + $dy * $dy + $dz * $dz);

    if ($distance <= 1.0e-12) {
        $ux = 1.0;
        $uy = 0.0;
        $uz = 0.0;
    } else {
        $ux = $dx / $distance;
        $uy = $dy / $distance;
        $uz = $dz / $distance;
    }

    if ($object['surface_locked']) {
        $object['x_m'] = $body['x_m'] + $ux * $body['radius_m'];
        $object['y_m'] = $body['y_m'] + $uy * $body['radius_m'];
        $object['z_m'] = $body['z_m'] + $uz * $body['radius_m'];

        $object['vx_m_s'] = $body['vx_m_s'];
        $object['vy_m_s'] = $body['vy_m_s'];
        $object['vz_m_s'] = $body['vz_m_s'];
        $object['on_surface'] = true;
        return;
    }

    if ($distance >= $body['radius_m']) {
        $object['on_surface'] =
            abs($distance - $body['radius_m']) <= 0.01;
        return;
    }

    $object['x_m'] = $body['x_m'] + $ux * $body['radius_m'];
    $object['y_m'] = $body['y_m'] + $uy * $body['radius_m'];
    $object['z_m'] = $body['z_m'] + $uz * $body['radius_m'];

    $rvx = $object['vx_m_s'] - $body['vx_m_s'];
    $rvy = $object['vy_m_s'] - $body['vy_m_s'];
    $rvz = $object['vz_m_s'] - $body['vz_m_s'];

    $radialSpeed = $rvx * $ux + $rvy * $uy + $rvz * $uz;

    if ($radialSpeed < 0.0) {
        $rvx -= $radialSpeed * $ux;
        $rvy -= $radialSpeed * $uy;
        $rvz -= $radialSpeed * $uz;
    }

    $object['vx_m_s'] = $body['vx_m_s'] + $rvx;
    $object['vy_m_s'] = $body['vy_m_s'] + $rvy;
    $object['vz_m_s'] = $body['vz_m_s'] + $rvz;
    $object['on_surface'] = true;
}

function moonAdvancePhysicsStep(
    float $dt,
    array &$objects,
    array $parentOf,
    array $propellantMassByObject
): void {
    $rootMembers = moonBuildRootMembers($objects, $parentOf);
    $stackMasses = moonCalculateStackMasses(
        $objects,
        $rootMembers,
        $propellantMassByObject
    );

    $a0 = moonGravityAccelerations($objects, $rootMembers, $stackMasses);

    foreach ($rootMembers as $rootId => $_members) {
        if ($objects[$rootId]['is_fixed'] || $objects[$rootId]['surface_locked']) {
            moonApplySurfaceProtection((int)$rootId, $objects);
            continue;
        }

        $objects[$rootId]['vx_m_s'] += 0.5 * $a0[$rootId]['x'] * $dt;
        $objects[$rootId]['vy_m_s'] += 0.5 * $a0[$rootId]['y'] * $dt;
        $objects[$rootId]['vz_m_s'] += 0.5 * $a0[$rootId]['z'] * $dt;

        $objects[$rootId]['x_m'] += $objects[$rootId]['vx_m_s'] * $dt;
        $objects[$rootId]['y_m'] += $objects[$rootId]['vy_m_s'] * $dt;
        $objects[$rootId]['z_m'] += $objects[$rootId]['vz_m_s'] * $dt;

        if ($objects[$rootId]['pitch_deg'] !== null) {
            $objects[$rootId]['pitch_deg'] = moonWrapDegrees(
                $objects[$rootId]['pitch_deg']
                + ($objects[$rootId]['pitch_rate_deg_s'] ?? 0.0) * $dt
            );
        }

        if ($objects[$rootId]['yaw_deg'] !== null) {
            $objects[$rootId]['yaw_deg'] = moonWrapDegrees(
                $objects[$rootId]['yaw_deg']
                + ($objects[$rootId]['yaw_rate_deg_s'] ?? 0.0) * $dt
            );
        }

        if ($objects[$rootId]['roll_deg'] !== null) {
            $objects[$rootId]['roll_deg'] = moonWrapDegrees(
                $objects[$rootId]['roll_deg']
                + ($objects[$rootId]['roll_rate_deg_s'] ?? 0.0) * $dt
            );
        }

        moonApplySurfaceProtection((int)$rootId, $objects);
    }

    moonSynchronizeAttachedObjects($objects, $rootMembers);

    $a1 = moonGravityAccelerations($objects, $rootMembers, $stackMasses);

    foreach ($rootMembers as $rootId => $_members) {
        if ($objects[$rootId]['is_fixed'] || $objects[$rootId]['surface_locked']) {
            continue;
        }

        $objects[$rootId]['vx_m_s'] += 0.5 * $a1[$rootId]['x'] * $dt;
        $objects[$rootId]['vy_m_s'] += 0.5 * $a1[$rootId]['y'] * $dt;
        $objects[$rootId]['vz_m_s'] += 0.5 * $a1[$rootId]['z'] * $dt;

        moonApplySurfaceProtection((int)$rootId, $objects);
    }

    moonSynchronizeAttachedObjects($objects, $rootMembers);
}

function moonPersistWorld(PDO $pdo, array $objects): void
{
    $stmt = $pdo->prepare(
        "UPDATE space_objects
         SET
             x_cm = :x_cm,
             y_cm = :y_cm,
             z_cm = :z_cm,
             x_subcm = :x_subcm,
             y_subcm = :y_subcm,
             z_subcm = :z_subcm,
             vx_cm_s = :vx_cm_s,
             vy_cm_s = :vy_cm_s,
             vz_cm_s = :vz_cm_s,
             pitch_deg = :pitch_deg,
             yaw_deg = :yaw_deg,
             roll_deg = :roll_deg,
             pitch_rate_deg_s = :pitch_rate_deg_s,
             yaw_rate_deg_s = :yaw_rate_deg_s,
             roll_rate_deg_s = :roll_rate_deg_s,
             on_surface = :on_surface
         WHERE id = :id"
    );

    foreach ($objects as $object) {
        [$xCm, $xSub] = moonSplitCentimeters($object['x_m'] * 100.0);
        [$yCm, $ySub] = moonSplitCentimeters($object['y_m'] * 100.0);
        [$zCm, $zSub] = moonSplitCentimeters($object['z_m'] * 100.0);

        $stmt->execute([
            'x_cm' => $xCm,
            'y_cm' => $yCm,
            'z_cm' => $zCm,
            'x_subcm' => number_format($xSub, 9, '.', ''),
            'y_subcm' => number_format($ySub, 9, '.', ''),
            'z_subcm' => number_format($zSub, 9, '.', ''),
            'vx_cm_s' => number_format($object['vx_m_s'] * 100.0, 9, '.', ''),
            'vy_cm_s' => number_format($object['vy_m_s'] * 100.0, 9, '.', ''),
            'vz_cm_s' => number_format($object['vz_m_s'] * 100.0, 9, '.', ''),
            'pitch_deg' => $object['pitch_deg'] === null
                ? null
                : number_format($object['pitch_deg'], 3, '.', ''),
            'yaw_deg' => $object['yaw_deg'] === null
                ? null
                : number_format($object['yaw_deg'], 3, '.', ''),
            'roll_deg' => $object['roll_deg'] === null
                ? null
                : number_format($object['roll_deg'], 3, '.', ''),
            'pitch_rate_deg_s' => $object['pitch_rate_deg_s'],
            'yaw_rate_deg_s' => $object['yaw_rate_deg_s'],
            'roll_rate_deg_s' => $object['roll_rate_deg_s'],
            'on_surface' => $object['on_surface'] ? 1 : 0,
            'id' => $object['id'],
        ]);
    }
}

function moonRunElapsedPhysics(
    PDO $pdo,
    ?float $forcedSimulatedSeconds = null
): array {
    $lockStmt = $pdo->prepare("SELECT GET_LOCK(:name, 0)");
    $lockStmt->execute(['name' => MOON_PHYSICS_LOCK]);

    if ((int)$lockStmt->fetchColumn() !== 1) {
        return [
            'success' => false,
            'skipped' => true,
            'message' => 'Another physics worker is already running.',
        ];
    }

    try {
        $pdo->beginTransaction();

        $stateStmt = $pdo->query(
            "SELECT
                 *,
                 NOW(6) AS database_now,
                 CASE
                     WHEN last_tick_at IS NULL THEN NULL
                     ELSE TIMESTAMPDIFF(MICROSECOND, last_tick_at, NOW(6))
                 END AS elapsed_microseconds
             FROM simulation_state
             WHERE id = 1
             FOR UPDATE"
        );

        $state = $stateStmt->fetch();

        if (!$state) {
            throw new RuntimeException('simulation_state row 1 is missing.');
        }

        $databaseNow = (string)$state['database_now'];

        if ($forcedSimulatedSeconds === null && !(bool)$state['is_running']) {
            $sync = $pdo->prepare(
                "UPDATE simulation_state
                 SET last_tick_at = :now
                 WHERE id = 1"
            );
            $sync->execute(['now' => $databaseNow]);
            $pdo->commit();

            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Simulation paused.',
            ];
        }

        if ($forcedSimulatedSeconds === null && $state['last_tick_at'] === null) {
            $sync = $pdo->prepare(
                "UPDATE simulation_state
                 SET last_tick_at = :now
                 WHERE id = 1"
            );
            $sync->execute(['now' => $databaseNow]);
            $pdo->commit();

            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Physics clock initialized.',
            ];
        }

        $tickSeconds = (float)$state['tick_seconds'];
        $timeScale = (float)$state['time_scale'];

        if ($tickSeconds <= 0.0 || $timeScale <= 0.0) {
            throw new RuntimeException('Invalid tick_seconds or time_scale.');
        }

        if ($forcedSimulatedSeconds !== null) {
            if (!is_finite($forcedSimulatedSeconds) || $forcedSimulatedSeconds <= 0.0) {
                throw new InvalidArgumentException('Forced step must be positive.');
            }

            $realElapsedSeconds = 0.0;
            $simulatedSeconds = $forcedSimulatedSeconds;
        } else {
            $elapsedMicroseconds = max(0, (int)$state['elapsed_microseconds']);
            $realElapsedSeconds = $elapsedMicroseconds / 1000000.0;
            $simulatedSeconds = $realElapsedSeconds * $timeScale;
        }

        if ($simulatedSeconds <= 1.0e-9) {
            $pdo->commit();

            return [
                'success' => true,
                'skipped' => true,
                'message' => 'No measurable elapsed time.',
            ];
        }

        $fullSteps = (int)floor($simulatedSeconds / $tickSeconds);
        $remainder = $simulatedSeconds - ($fullSteps * $tickSeconds);

        if ($remainder < 1.0e-9) {
            $remainder = 0.0;
        }

        $stepCount = $fullSteps + ($remainder > 0.0 ? 1 : 0);

        if ($stepCount > MOON_MAX_SUBSTEPS_PER_RUN) {
            throw new RuntimeException(
                'Too much elapsed time for one physics run; increase cron frequency or reduce time_scale.'
            );
        }

        [$objects, $parentOf, $propellantMassByObject] = moonLoadWorld($pdo);

        for ($i = 0; $i < $fullSteps; $i++) {
            moonAdvancePhysicsStep(
                $tickSeconds,
                $objects,
                $parentOf,
                $propellantMassByObject
            );
        }

        if ($remainder > 0.0) {
            moonAdvancePhysicsStep(
                $remainder,
                $objects,
                $parentOf,
                $propellantMassByObject
            );
        }

        moonPersistWorld($pdo, $objects);

        $newSimulationTime =
            (float)$state['simulated_time_seconds'] + $simulatedSeconds;

        $updateState = $pdo->prepare(
            "UPDATE simulation_state
             SET
                 simulated_time_seconds = :sim_time,
                 last_tick_at = :last_tick
             WHERE id = 1"
        );

        $updateState->execute([
            'sim_time' => number_format($newSimulationTime, 6, '.', ''),
            'last_tick' => $databaseNow,
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'skipped' => false,
            'steps' => $stepCount,
            'full_steps' => $fullSteps,
            'remainder_seconds' => $remainder,
            'real_elapsed_seconds' => $realElapsedSeconds,
            'simulated_seconds' => $simulatedSeconds,
            'simulation_time_seconds' => $newSimulationTime,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    } finally {
        try {
            $release = $pdo->prepare("SELECT RELEASE_LOCK(:name)");
            $release->execute(['name' => MOON_PHYSICS_LOCK]);
        } catch (Throwable $ignored) {
        }
    }
}
