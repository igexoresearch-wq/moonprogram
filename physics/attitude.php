<?php

/**
 * Quaternion attitude state for CSM and LM.
 *
 * Operator-facing convention is preserved:
 * - local +X = forward / main-engine thrust direction
 * - positive pitch moves the nose toward world +Z at zero attitude
 * - positive yaw moves the nose toward world -Y at zero attitude
 * - positive roll rotates about local +X
 *
 * The corresponding Euler composition is:
 *     R = Rz(-yaw) * Ry(-pitch) * Rx(roll)
 */

function moonSimQuaternionNormalize(array $q): array
{
    $n = sqrt($q[0]**2 + $q[1]**2 + $q[2]**2 + $q[3]**2);

    if ($n <= 1.0e-15) {
        return [1.0, 0.0, 0.0, 0.0];
    }

    return [$q[0]/$n, $q[1]/$n, $q[2]/$n, $q[3]/$n];
}

function moonSimQuaternionMultiply(array $a, array $b): array
{
    [$aw,$ax,$ay,$az] = $a;
    [$bw,$bx,$by,$bz] = $b;

    return [
        $aw*$bw - $ax*$bx - $ay*$by - $az*$bz,
        $aw*$bx + $ax*$bw + $ay*$bz - $az*$by,
        $aw*$by - $ax*$bz + $ay*$bw + $az*$bx,
        $aw*$bz + $ax*$by - $ay*$bx + $az*$bw,
    ];
}

function moonSimAxisQuaternion(float $x, float $y, float $z, float $angleRad): array
{
    $half = 0.5 * $angleRad;
    $s = sin($half);

    return [cos($half), $x*$s, $y*$s, $z*$s];
}

function moonSimQuaternionFromEuler(
    float $pitchDeg,
    float $yawDeg,
    float $rollDeg
): array {
    $qx = moonSimAxisQuaternion(1.0, 0.0, 0.0, deg2rad($rollDeg));
    $qy = moonSimAxisQuaternion(0.0, 1.0, 0.0, deg2rad(-$pitchDeg));
    $qz = moonSimAxisQuaternion(0.0, 0.0, 1.0, deg2rad(-$yawDeg));

    return moonSimQuaternionNormalize(
        moonSimQuaternionMultiply(
            moonSimQuaternionMultiply($qz, $qy),
            $qx
        )
    );
}

function moonSimQuaternionMatrix(array $q): array
{
    [$w,$x,$y,$z] = moonSimQuaternionNormalize($q);

    return [
        [
            1.0 - 2.0*($y*$y + $z*$z),
            2.0*($x*$y - $z*$w),
            2.0*($x*$z + $y*$w),
        ],
        [
            2.0*($x*$y + $z*$w),
            1.0 - 2.0*($x*$x + $z*$z),
            2.0*($y*$z - $x*$w),
        ],
        [
            2.0*($x*$z - $y*$w),
            2.0*($y*$z + $x*$w),
            1.0 - 2.0*($x*$x + $y*$y),
        ],
    ];
}

function moonSimWrap360(float $angle): float
{
    $angle = fmod($angle, 360.0);
    return $angle < 0.0 ? $angle + 360.0 : $angle;
}

function moonSimQuaternionToEuler(array $q): array
{
    $r = moonSimQuaternionMatrix($q);

    // Standard ZYX extraction where theta=-pitch and psi=-yaw.
    $sinTheta = max(-1.0, min(1.0, -$r[2][0]));
    $theta = asin($sinTheta);

    $cosTheta = cos($theta);

    if (abs($cosTheta) > 1.0e-9) {
        $roll = atan2($r[2][1], $r[2][2]);
        $psi = atan2($r[1][0], $r[0][0]);
    } else {
        // Euler display is singular here, but quaternion physics is not.
        // Choose roll=0 and place the remaining heading in psi.
        $roll = 0.0;
        $psi = atan2(-$r[0][1], $r[1][1]);
    }

    return [
        'pitch' => -rad2deg($theta),
        'yaw' => moonSimWrap360(-rad2deg($psi)),
        'roll' => moonSimWrap360(rad2deg($roll)),
    ];
}

function moonSimRotateBodyVector(array $q, array $v): array
{
    $r = moonSimQuaternionMatrix($q);

    return [
        $r[0][0]*$v[0] + $r[0][1]*$v[1] + $r[0][2]*$v[2],
        $r[1][0]*$v[0] + $r[1][1]*$v[1] + $r[1][2]*$v[2],
        $r[2][0]*$v[0] + $r[2][1]*$v[1] + $r[2][2]*$v[2],
    ];
}

function moonSimEnsureAttitudeTable(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS attitude_state (
            vehicle VARCHAR(8) NOT NULL,
            qw DOUBLE NOT NULL,
            qx DOUBLE NOT NULL,
            qy DOUBLE NOT NULL,
            qz DOUBLE NOT NULL,
            display_pitch DOUBLE NOT NULL DEFAULT 0,
            display_yaw DOUBLE NOT NULL DEFAULT 0,
            display_roll DOUBLE NOT NULL DEFAULT 0,
            updated_at TIMESTAMP(6) NOT NULL
                DEFAULT CURRENT_TIMESTAMP(6)
                ON UPDATE CURRENT_TIMESTAMP(6),
            PRIMARY KEY (vehicle)
        ) ENGINE=InnoDB"
    );
}

function moonSimReadVehicleEuler(mysqli $conn, string $vehicle): array
{
    $table = $vehicle === 'CM' ? 'command_module' : 'telemetry';

    $result = $conn->query(
        "SELECT attribute, value
         FROM $table
         WHERE attribute IN ('pitch','yaw','roll')"
    );

    $angles = ['pitch'=>0.0, 'yaw'=>0.0, 'roll'=>0.0];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $angles[$row['attribute']] = (float)$row['value'];
        }
    }

    return $angles;
}

function moonSimLoadVehicleQuaternion(mysqli $conn, string $vehicle): array
{
    moonSimEnsureAttitudeTable($conn);

    $angles = moonSimReadVehicleEuler($conn, $vehicle);
    $vehicleEsc = $conn->real_escape_string($vehicle);

    $result = $conn->query(
        "SELECT qw,qx,qy,qz,display_pitch,display_yaw,display_roll
         FROM attitude_state
         WHERE vehicle = '$vehicleEsc'
         LIMIT 1"
    );
    $row = $result ? $result->fetch_assoc() : null;

    if (!$row) {
        $q = moonSimQuaternionFromEuler(
            $angles['pitch'],
            $angles['yaw'],
            $angles['roll']
        );

        $conn->query(
            "INSERT INTO attitude_state (
                vehicle,qw,qx,qy,qz,
                display_pitch,display_yaw,display_roll
             ) VALUES (
                '$vehicleEsc',
                {$q[0]},{$q[1]},{$q[2]},{$q[3]},
                {$angles['pitch']},{$angles['yaw']},{$angles['roll']}
             )"
        );

        return $q;
    }

    /*
     * Guidance programs can still intentionally write Euler angles directly.
     * If the DB angle differs from the last angle this quaternion wrote,
     * treat it as an external command and resynchronize from the DB.
     */
    $externalChange =
        abs($angles['pitch'] - (float)$row['display_pitch']) > 1.0e-5
        || abs(moonSimAngleDifference($angles['yaw'], (float)$row['display_yaw'])) > 1.0e-5
        || abs(moonSimAngleDifference($angles['roll'], (float)$row['display_roll'])) > 1.0e-5;

    if ($externalChange) {
        $q = moonSimQuaternionFromEuler(
            $angles['pitch'],
            $angles['yaw'],
            $angles['roll']
        );

        moonSimStoreVehicleQuaternion($conn, $vehicle, $q, $angles);
        return $q;
    }

    return moonSimQuaternionNormalize([
        (float)$row['qw'],
        (float)$row['qx'],
        (float)$row['qy'],
        (float)$row['qz'],
    ]);
}

function moonSimAngleDifference(float $a, float $b): float
{
    $d = fmod(($a - $b) + 180.0, 360.0);
    if ($d < 0.0) {
        $d += 360.0;
    }
    return $d - 180.0;
}

function moonSimStoreVehicleQuaternion(
    mysqli $conn,
    string $vehicle,
    array $q,
    ?array $euler = null
): void {
    $q = moonSimQuaternionNormalize($q);
    $euler = $euler ?? moonSimQuaternionToEuler($q);
    $vehicleEsc = $conn->real_escape_string($vehicle);

    $conn->query(
        "INSERT INTO attitude_state (
            vehicle,qw,qx,qy,qz,
            display_pitch,display_yaw,display_roll
         ) VALUES (
            '$vehicleEsc',
            {$q[0]},{$q[1]},{$q[2]},{$q[3]},
            {$euler['pitch']},{$euler['yaw']},{$euler['roll']}
         )
         ON DUPLICATE KEY UPDATE
            qw=VALUES(qw),
            qx=VALUES(qx),
            qy=VALUES(qy),
            qz=VALUES(qz),
            display_pitch=VALUES(display_pitch),
            display_yaw=VALUES(display_yaw),
            display_roll=VALUES(display_roll)"
    );
}

function moonSimAdvanceQuaternionBodyRates(
    array $q,
    float $pitchRateDeg,
    float $yawRateDeg,
    float $rollRateDeg,
    float $seconds,
    float $substepSeconds = 0.1
): array {
    $remaining = max(0.0, $seconds);
    $hDefault = max(0.001, $substepSeconds);

    // Body angular velocity matching the simulator's Euler sign convention.
    $omega = [
        deg2rad($rollRateDeg),
        deg2rad(-$pitchRateDeg),
        deg2rad(-$yawRateDeg),
    ];

    while ($remaining > 1.0e-12) {
        $h = min($hDefault, $remaining);

        $omegaQuat = [0.0, $omega[0], $omega[1], $omega[2]];
        $qDot = moonSimQuaternionMultiply($q, $omegaQuat);

        $q = moonSimQuaternionNormalize([
            $q[0] + 0.5*$qDot[0]*$h,
            $q[1] + 0.5*$qDot[1]*$h,
            $q[2] + 0.5*$qDot[2]*$h,
            $q[3] + 0.5*$qDot[3]*$h,
        ]);

        $remaining -= $h;
    }

    return $q;
}

function moonSimAdvanceAttitude(mysqli $conn, float $seconds = 1.0): void
{
    global
        $pitch_rateCM, $yaw_rateCM, $roll_rateCM,
        $pitch_rateLM, $yaw_rateLM, $roll_rateLM,
        $dockCM, $stage3CM,
        $cmQuaternion, $lmQuaternion;

    $cmQuaternion = moonSimLoadVehicleQuaternion($conn, 'CM');
    $lmQuaternion = moonSimLoadVehicleQuaternion($conn, 'LM');

    $cmQuaternion = moonSimAdvanceQuaternionBodyRates(
        $cmQuaternion,
        (float)$pitch_rateCM,
        (float)$yaw_rateCM,
        (float)$roll_rateCM,
        $seconds,
        0.1
    );

    if ((int)$dockCM === 1 || (int)$stage3CM === 1) {
        $lmQuaternion = $cmQuaternion;
    } else {
        $lmQuaternion = moonSimAdvanceQuaternionBodyRates(
            $lmQuaternion,
            (float)$pitch_rateLM,
            (float)$yaw_rateLM,
            (float)$roll_rateLM,
            $seconds,
            0.1
        );
    }

    $cmEuler = moonSimQuaternionToEuler($cmQuaternion);
    $lmEuler = moonSimQuaternionToEuler($lmQuaternion);

    moonSimStoreVehicleQuaternion($conn, 'CM', $cmQuaternion, $cmEuler);
    moonSimStoreVehicleQuaternion($conn, 'LM', $lmQuaternion, $lmEuler);

    $conn->query(
        "UPDATE command_module SET value = CASE attribute
            WHEN 'pitch' THEN " . sprintf('%.8f', $cmEuler['pitch']) . "
            WHEN 'yaw' THEN " . sprintf('%.8f', $cmEuler['yaw']) . "
            WHEN 'roll' THEN " . sprintf('%.8f', $cmEuler['roll']) . "
         END
         WHERE attribute IN ('pitch','yaw','roll')"
    );

    $conn->query(
        "UPDATE telemetry SET value = CASE attribute
            WHEN 'pitch' THEN " . sprintf('%.8f', $lmEuler['pitch']) . "
            WHEN 'yaw' THEN " . sprintf('%.8f', $lmEuler['yaw']) . "
            WHEN 'roll' THEN " . sprintf('%.8f', $lmEuler['roll']) . "
         END
         WHERE attribute IN ('pitch','yaw','roll')"
    );
}

function moonSimResetAttitudeState(mysqli $conn): void
{
    moonSimEnsureAttitudeTable($conn);
    $conn->query("DELETE FROM attitude_state");
}
?>
