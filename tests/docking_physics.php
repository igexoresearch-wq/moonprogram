<?php
declare(strict_types=1);

require __DIR__ . '/../physics/attitude.php';
require __DIR__ . '/../physics/docking.php';

function dockingFail(string $message): void
{
    fwrite(STDERR, "FAIL: $message\n");
    exit(1);
}

function dockingNear(float $a, float $b, float $tol, string $message): void
{
    if (abs($a-$b) > $tol) {
        dockingFail($message . " expected $b got $a");
    }
}

$q0 = moonSimQuaternionFromEuler(0.0,0.0,0.0);
$q10 = moonSimQuaternionFromEuler(10.0,0.0,0.0);
$q20 = moonSimQuaternionFromEuler(20.0,0.0,0.0);

dockingNear(
    moonSimQuaternionSeparationDeg($q0,$q10),
    10.0,
    1.0e-8,
    '10-degree attitude separation'
);

if (moonSimQuaternionSeparationDeg($q0,$q20) <= 15.0) {
    dockingFail('attitude limit helper did not distinguish >15 degree error');
}

$cmMass = 30000.0;
$lmMass = 15000.0;
$relativeSpeed = 0.30;
$direction = [1.0,0.0,0.0];

[$cmV,$lmV] = moonSimApplySeparationVelocity(
    [100.0,20.0,-5.0],
    [100.0,20.0,-5.0],
    $direction,
    $relativeSpeed,
    $cmMass,
    $lmMass
);

$initialMomentum = ($cmMass+$lmMass)*100.0;
$finalMomentum = $cmMass*$cmV[0] + $lmMass*$lmV[0];

dockingNear(
    $finalMomentum,
    $initialMomentum,
    1.0e-8,
    'separation momentum conservation'
);

dockingNear(
    abs($lmV[0]-$cmV[0]),
    $relativeSpeed,
    1.0e-12,
    'relative separation speed'
);

echo "PASS: docking attitude and separation regression.\n";
?>
