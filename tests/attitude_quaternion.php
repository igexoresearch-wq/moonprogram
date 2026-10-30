<?php
declare(strict_types=1);

require __DIR__ . '/../physics/attitude.php';

function assertNear(float $actual, float $expected, float $tol, string $label): void
{
    if (abs($actual - $expected) > $tol) {
        fwrite(
            STDERR,
            sprintf(
                "FAIL %s: expected %.12f, got %.12f (tol %.3e)\n",
                $label,
                $expected,
                $actual,
                $tol
            )
        );
        exit(1);
    }
}

function assertVectorNear(array $actual, array $expected, float $tol, string $label): void
{
    foreach ([0,1,2] as $i) {
        assertNear((float)$actual[$i], (float)$expected[$i], $tol, "$label[$i]");
    }
}

// Existing simulator convention: 0/0/0 points forward along +X.
$q0 = moonSimQuaternionFromEuler(0.0, 0.0, 0.0);
assertVectorNear(
    moonSimRotateBodyVector($q0, [1.0,0.0,0.0]),
    [1.0,0.0,0.0],
    1.0e-12,
    'zero forward'
);

// Positive pitch turns +X toward +Z.
$qPitch = moonSimQuaternionFromEuler(30.0, 0.0, 0.0);
assertVectorNear(
    moonSimRotateBodyVector($qPitch, [1.0,0.0,0.0]),
    [cos(deg2rad(30.0)), 0.0, sin(deg2rad(30.0))],
    1.0e-12,
    'pitch convention'
);

// Positive yaw turns +X toward -Y, matching the old CSM thrust transform.
$qYaw = moonSimQuaternionFromEuler(0.0, 30.0, 0.0);
assertVectorNear(
    moonSimRotateBodyVector($qYaw, [1.0,0.0,0.0]),
    [cos(deg2rad(30.0)), -sin(deg2rad(30.0)), 0.0],
    1.0e-12,
    'yaw convention'
);

// Euler -> quaternion -> Euler should round-trip away from display singularities.
foreach ([
    [30.0, 40.0, 20.0],
    [-45.0, 270.0, 300.0],
    [89.0, 123.0, 45.0],
    [-89.0, 200.0, 80.0],
] as $case) {
    [$pitch,$yaw,$roll] = $case;
    $e = moonSimQuaternionToEuler(
        moonSimQuaternionFromEuler($pitch,$yaw,$roll)
    );

    assertNear($e['pitch'], $pitch, 1.0e-8, 'roundtrip pitch');
    assertNear(moonSimAngleDifference($e['yaw'],$yaw), 0.0, 1.0e-8, 'roundtrip yaw');
    assertNear(moonSimAngleDifference($e['roll'],$roll), 0.0, 1.0e-8, 'roundtrip roll');
}

// Full rotations must be continuous in quaternion space and return the
// spacecraft body axes to their starting orientation.
foreach ([
    ['pitch', 1.0, 0.0, 0.0],
    ['yaw',   0.0, 1.0, 0.0],
    ['roll',  0.0, 0.0, 1.0],
] as $rotation) {
    [$name,$pitchRate,$yawRate,$rollRate] = $rotation;

    $q = moonSimAdvanceQuaternionBodyRates(
        $q0,
        $pitchRate,
        $yawRate,
        $rollRate,
        360.0,
        0.1
    );

    $norm = sqrt($q[0]**2 + $q[1]**2 + $q[2]**2 + $q[3]**2);
    assertNear($norm, 1.0, 1.0e-12, "$name quaternion norm");

    assertVectorNear(
        moonSimRotateBodyVector($q, [1.0,0.0,0.0]),
        [1.0,0.0,0.0],
        2.0e-7,
        "$name 360 forward"
    );

    assertVectorNear(
        moonSimRotateBodyVector($q, [0.0,1.0,0.0]),
        [0.0,1.0,0.0],
        2.0e-7,
        "$name 360 right"
    );
}

echo "PASS: quaternion attitude convention and full-rotation regression.\n";
?>
