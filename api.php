<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/physics.php';
require_once __DIR__ . '/reset.php';

header('Content-Type: application/json; charset=utf-8');

function moonApiError(string $message, int $status = 400): never
{
    http_response_code($status);

    echo json_encode(
        ['success' => false, 'message' => $message],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

function moonRequireControlKey(array $payload): void
{
    $expected = moonControlKey();

    if ($expected === '') {
        moonApiError(
            'MOON_CONTROL_KEY is not configured on the server.',
            503
        );
    }

    $provided =
        $_SERVER['HTTP_X_CONTROL_KEY']
        ?? ($payload['control_key'] ?? '');

    if (!is_string($provided) || !hash_equals($expected, $provided)) {
        moonApiError('Invalid control key.', 403);
    }
}

function moonDashboardState(PDO $pdo): array
{
    $state = $pdo->query(
        "SELECT *
         FROM simulation_state
         WHERE id = 1"
    )->fetch();

    if (!$state) {
        throw new RuntimeException('simulation_state row 1 is missing.');
    }

    $rows = $pdo->query(
        "SELECT
             o.*,
             COALESCE(p.fuel_remaining_kg, 0) AS fuel_remaining_kg,
             COALESCE(p.fuel_capacity_kg, 0) AS fuel_capacity_kg,
             a.parent_object_id,
             parent.name AS parent_name,
             a.attachment_type
         FROM space_objects AS o
         LEFT JOIN (
             SELECT
                 object_id,
                 SUM(remaining_kg) AS fuel_remaining_kg,
                 SUM(capacity_kg) AS fuel_capacity_kg
             FROM propellant_systems
             GROUP BY object_id
         ) AS p
           ON p.object_id = o.id
         LEFT JOIN object_attachments AS a
           ON a.child_object_id = o.id
         LEFT JOIN space_objects AS parent
           ON parent.id = a.parent_object_id
         ORDER BY o.id"
    )->fetchAll();

    $byId = [];
    $parentOf = [];

    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $byId[$id] = $row;

        if ($row['parent_object_id'] !== null) {
            $parentOf[$id] = (int)$row['parent_object_id'];
        }
    }

    $earth = null;

    foreach ($rows as $row) {
        if ($row['slug'] === 'earth') {
            $earth = $row;
            break;
        }
    }

    if ($earth === null) {
        throw new RuntimeException('Earth object is missing.');
    }

    $earthX = (float)$earth['x_cm'] + (float)$earth['x_subcm'];
    $earthY = (float)$earth['y_cm'] + (float)$earth['y_subcm'];
    $earthZ = (float)$earth['z_cm'] + (float)$earth['z_subcm'];

    $objects = [];
    $stackMasses = [];

    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $current = $id;
        $depth = 0;
        $seen = [];

        while (isset($parentOf[$current])) {
            if (isset($seen[$current])) {
                throw new RuntimeException('Attachment cycle detected.');
            }

            $seen[$current] = true;
            $current = $parentOf[$current];
            $depth++;
        }

        $rootId = $current;

        $fuel = (float)$row['fuel_remaining_kg'];
        $currentMass = (float)$row['base_mass_kg'] + $fuel;

        $stackMasses[$rootId] =
            ($stackMasses[$rootId] ?? 0.0) + $currentMass;

        $xCm = (float)$row['x_cm'] + (float)$row['x_subcm'];
        $yCm = (float)$row['y_cm'] + (float)$row['y_subcm'];
        $zCm = (float)$row['z_cm'] + (float)$row['z_subcm'];

        $vx = (float)$row['vx_cm_s'];
        $vy = (float)$row['vy_cm_s'];
        $vz = (float)$row['vz_cm_s'];

        $objects[] = [
            'id' => $id,
            'slug' => $row['slug'],
            'name' => $row['name'],
            'object_class' => $row['object_class'],
            'component_type' => $row['component_type'],
            'parent_id' => $row['parent_object_id'] === null
                ? null
                : (int)$row['parent_object_id'],
            'parent_name' => $row['parent_name'],
            'attachment_type' => $row['attachment_type'],
            'root_id' => $rootId,
            'depth' => $depth,

            'base_mass_kg' => (float)$row['base_mass_kg'],
            'fuel_remaining_kg' => $fuel,
            'fuel_capacity_kg' => (float)$row['fuel_capacity_kg'],
            'current_mass_kg' => $currentMass,

            'x_km_earth_relative' => ($xCm - $earthX) / 100000.0,
            'y_km_earth_relative' => ($yCm - $earthY) / 100000.0,
            'z_km_earth_relative' => ($zCm - $earthZ) / 100000.0,

            'vx_m_s' => $vx / 100.0,
            'vy_m_s' => $vy / 100.0,
            'vz_m_s' => $vz / 100.0,
            'speed_m_s' => sqrt($vx * $vx + $vy * $vy + $vz * $vz) / 100.0,

            'pitch_deg' => $row['pitch_deg'] === null
                ? null
                : (float)$row['pitch_deg'],
            'yaw_deg' => $row['yaw_deg'] === null
                ? null
                : (float)$row['yaw_deg'],
            'roll_deg' => $row['roll_deg'] === null
                ? null
                : (float)$row['roll_deg'],

            'surface_locked' => (bool)$row['surface_locked'],
            'on_surface' => (bool)$row['on_surface'],
            'is_fixed' => (bool)$row['is_fixed'],
        ];
    }

    foreach ($objects as &$object) {
        $object['stack_mass_kg'] = $stackMasses[$object['root_id']] ?? null;
    }
    unset($object);

    return [
        'success' => true,
        'state' => [
            'running' => (bool)$state['is_running'],
            'tick_seconds' => (float)$state['tick_seconds'],
            'time_scale' => (float)$state['time_scale'],
            'simulated_time_seconds' => (float)$state['simulated_time_seconds'],
            'last_tick_at' => $state['last_tick_at'],
        ],
        'objects' => $objects,
    ];
}

try {
    $pdo = moonDb();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(
            moonDashboardState($pdo),
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        moonApiError('Method not allowed.', 405);
    }

    $payload = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($payload)) {
        $payload = $_POST;
    }

    moonRequireControlKey($payload);

    $action = (string)($payload['action'] ?? '');

    switch ($action) {
        case 'start':
            $stmt = $pdo->prepare(
                "UPDATE simulation_state
                 SET
                     is_running = 1,
                     last_tick_at = NOW(6)
                 WHERE id = 1"
            );
            $stmt->execute();
            $result = ['success' => true, 'message' => 'Simulation started.'];
            break;

        case 'pause':
            $stmt = $pdo->prepare(
                "UPDATE simulation_state
                 SET
                     is_running = 0,
                     last_tick_at = NOW(6)
                 WHERE id = 1"
            );
            $stmt->execute();
            $result = ['success' => true, 'message' => 'Simulation paused.'];
            break;

        case 'step':
            $result = moonRunElapsedPhysics($pdo, 1.0);
            break;

        case 'reset':
            $result = moonResetSimulation($pdo);
            break;

        default:
            moonApiError('Unknown action.');
    }

    echo json_encode(
        $result,
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    error_log('Moon API error: ' . $e->getMessage());
    moonApiError($e->getMessage(), 500);
}
