<?php

function moonSimEnsureSurfaceContactTable(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS surface_contact (
            vehicle ENUM('CM','LM') NOT NULL,
            body ENUM('EARTH','MOON') NOT NULL,
            contact_state ENUM('LANDED','CRASHED') NOT NULL,
            normal_x DOUBLE NOT NULL,
            normal_y DOUBLE NOT NULL,
            normal_z DOUBLE NOT NULL,
            impact_speed DOUBLE NOT NULL DEFAULT 0,
            radial_speed DOUBLE NOT NULL DEFAULT 0,
            tangential_speed DOUBLE NOT NULL DEFAULT 0,
            contacted_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (vehicle)
        ) ENGINE=InnoDB"
    );
}

function moonSimLoadSurfaceContacts(mysqli $conn): array
{
    moonSimEnsureSurfaceContactTable($conn);

    $contacts = [];
    $result = $conn->query(
        "SELECT vehicle, body, contact_state,
                normal_x, normal_y, normal_z,
                impact_speed, radial_speed, tangential_speed
         FROM surface_contact"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $contacts[$row['vehicle']] = [
                'body' => $row['body'],
                'state' => $row['contact_state'],
                'normal' => [
                    (float)$row['normal_x'],
                    (float)$row['normal_y'],
                    (float)$row['normal_z'],
                ],
                'impact_speed' => (float)$row['impact_speed'],
                'radial_speed' => (float)$row['radial_speed'],
                'tangential_speed' => (float)$row['tangential_speed'],
            ];
        }
    }

    return $contacts;
}

function moonSimSegmentSphereHit(
    array $craftStart,
    array $craftEnd,
    array $bodyStart,
    array $bodyEnd,
    float $radius
): ?float {
    $r0 = [
        $craftStart[0] - $bodyStart[0],
        $craftStart[1] - $bodyStart[1],
        $craftStart[2] - $bodyStart[2],
    ];
    $r1 = [
        $craftEnd[0] - $bodyEnd[0],
        $craftEnd[1] - $bodyEnd[1],
        $craftEnd[2] - $bodyEnd[2],
    ];

    $d = [
        $r1[0] - $r0[0],
        $r1[1] - $r0[1],
        $r1[2] - $r0[2],
    ];

    $c = $r0[0]**2 + $r0[1]**2 + $r0[2]**2 - $radius**2;

    // Starting inside/on the sphere is already contact.
    if ($c <= 0.0) {
        return 0.0;
    }

    $a = $d[0]**2 + $d[1]**2 + $d[2]**2;
    if ($a <= 1.0e-18) {
        return null;
    }

    $b = 2.0 * ($r0[0]*$d[0] + $r0[1]*$d[1] + $r0[2]*$d[2]);
    $disc = $b*$b - 4.0*$a*$c;

    if ($disc < 0.0) {
        return null;
    }

    $sqrtDisc = sqrt(max(0.0, $disc));
    $t1 = (-$b - $sqrtDisc) / (2.0*$a);
    $t2 = (-$b + $sqrtDisc) / (2.0*$a);

    $hits = [];
    if ($t1 >= 0.0 && $t1 <= 1.0) {
        $hits[] = $t1;
    }
    if ($t2 >= 0.0 && $t2 <= 1.0) {
        $hits[] = $t2;
    }

    return $hits ? min($hits) : null;
}

function moonSimInterpolate(array $a, array $b, float $t): array
{
    return [
        $a[0] + ($b[0]-$a[0])*$t,
        $a[1] + ($b[1]-$a[1])*$t,
        $a[2] + ($b[2]-$a[2])*$t,
    ];
}

function moonSimNormalizeVector(array $v): array
{
    $n = sqrt($v[0]**2 + $v[1]**2 + $v[2]**2);

    if ($n <= 1.0e-15) {
        return [1.0,0.0,0.0];
    }

    return [$v[0]/$n, $v[1]/$n, $v[2]/$n];
}

function moonSimClassifySurfaceImpact(
    string $vehicle,
    string $body,
    float $impactSpeed,
    float $inwardRadialSpeed,
    float $tangentialSpeed,
    int $lmDescentStage,
    int $cmServiceModule,
    int $cmParachutes
): string {
    if ($vehicle === 'LM' && $body === 'MOON' && $lmDescentStage === 1) {
        // Conservative simplified LM touchdown envelope.
        if ($inwardRadialSpeed <= 3.0 && $tangentialSpeed <= 2.0) {
            return 'LANDED';
        }
    }

    if ($vehicle === 'CM' && $body === 'EARTH' && $cmServiceModule === 0) {
        // Simplified splashdown envelope. Parachutes are expected for a normal
        // mission, but a manually-created very-low-speed contact is still not
        // forced to "crash" merely because a display switch is absent.
        if ($impactSpeed <= 12.0) {
            return 'LANDED';
        }
    }

    return 'CRASHED';
}

function moonSimRecordSurfaceContact(
    mysqli $conn,
    string $vehicle,
    string $body,
    string $state,
    array $normal,
    float $impactSpeed,
    float $inwardRadialSpeed,
    float $tangentialSpeed
): void {
    moonSimEnsureSurfaceContactTable($conn);

    $vehicleEsc = $conn->real_escape_string($vehicle);
    $bodyEsc = $conn->real_escape_string($body);
    $stateEsc = $conn->real_escape_string($state);

    $conn->query(
        "INSERT INTO surface_contact (
            vehicle, body, contact_state,
            normal_x, normal_y, normal_z,
            impact_speed, radial_speed, tangential_speed,
            contacted_at
         ) VALUES (
            '$vehicleEsc', '$bodyEsc', '$stateEsc',
            {$normal[0]}, {$normal[1]}, {$normal[2]},
            $impactSpeed, $inwardRadialSpeed, $tangentialSpeed,
            NOW(6)
         )
         ON DUPLICATE KEY UPDATE
            body=VALUES(body),
            contact_state=VALUES(contact_state),
            normal_x=VALUES(normal_x),
            normal_y=VALUES(normal_y),
            normal_z=VALUES(normal_z),
            impact_speed=VALUES(impact_speed),
            radial_speed=VALUES(radial_speed),
            tangential_speed=VALUES(tangential_speed),
            contacted_at=VALUES(contacted_at)"
    );

    if ($vehicle === 'LM') {
        $crash = $state === 'CRASHED' ? 1 : 0;
        $landed = $state === 'LANDED' ? 1 : 0;

        $conn->query("UPDATE telemetry SET value = $crash WHERE attribute = 'crash'");
        $conn->query("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");
        $conn->query("UPDATE systems SET status = $landed WHERE system = 'landed'");
        $conn->query(
            "UPDATE EngineStatus
             SET status = 'OFF'
             WHERE engine_stage = 'Lunar Module'"
        );
    } else {
        $crash = $state === 'CRASHED' ? 1 : 0;
        $conn->query("UPDATE command_module SET value = $crash WHERE attribute = 'crash'");
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
    }
}

function moonSimClearSurfaceContact(mysqli $conn, string $vehicle): void
{
    $vehicleEsc = $conn->real_escape_string($vehicle);
    $conn->query("DELETE FROM surface_contact WHERE vehicle = '$vehicleEsc'");

    if ($vehicle === 'LM') {
        $conn->query("UPDATE systems SET status = 0 WHERE system = 'landed'");
    }
}

?>
