<?php

declare(strict_types=1);

function physicsRecordTelemetry(
    PDO $pdo,
    array $objects,
    array $rootMembers,
    array $stackMasses,
    string $recordedAt
): void {
    $stmt = $pdo->prepare(
        "INSERT INTO telemetry (
            live_object_id, recorded_at,
            x_cm, y_cm, z_cm,
            vx_cm_s, vy_cm_s, vz_cm_s,
            pitch_deg, yaw_deg, roll_deg,
            total_mass_kg
         ) VALUES (
            :object_id, :recorded_at,
            :x_cm, :y_cm, :z_cm,
            :vx, :vy, :vz,
            :pitch, :yaw, :roll,
            :mass
         )"
    );

    foreach ($objects as $id => $object) {
        $rootId = physicsRootId((int)$id, $objects);

        $stmt->execute([
            'object_id' => (int)$id,
            'recorded_at' => $recordedAt,
            'x_cm' => (int)floor($object['x_m'] * 100.0),
            'y_cm' => (int)floor($object['y_m'] * 100.0),
            'z_cm' => (int)floor($object['z_m'] * 100.0),
            'vx' => number_format($object['vx_m_s'] * 100.0, 9, '.', ''),
            'vy' => number_format($object['vy_m_s'] * 100.0, 9, '.', ''),
            'vz' => number_format($object['vz_m_s'] * 100.0, 9, '.', ''),
            'pitch' => $object['pitch_deg'],
            'yaw' => $object['yaw_deg'],
            'roll' => $object['roll_deg'],
            'mass' => number_format($stackMasses[$rootId], 6, '.', ''),
        ]);
    }
}
