<?php
declare(strict_types=1);

/**
 * Earth-only conservation regression.
 *
 * This intentionally uses the simulator's large universal coordinate offset
 * rather than centering Earth at zero, so it also tests the precision of
 * subtracting local orbital vectors from ~1e10 m coordinates.
 */

require __DIR__ . '/../shared/physical_constants.php';
require __DIR__ . '/../physics/integrator.php';

$earth = [$earth_center_x, $earth_center_y, $earth_center_z];
$altitude = 185000.0;
$radius = $earth_radius + $altitude;
$mu = $gravitational_constant * $earth_mass;

$position = [
    $earth_center_x + $radius,
    $earth_center_y,
    $earth_center_z,
];

$velocity = [
    0.0,
    sqrt($mu / $radius),
    0.0,
];

$dt = 0.1;
$period = 2.0 * M_PI * sqrt(($radius ** 3) / $mu);
$duration = $period * 3.0;
$steps = (int)ceil($duration / $dt);

function relativePosition(array $position, array $origin): array
{
    return [
        $position[0] - $origin[0],
        $position[1] - $origin[1],
        $position[2] - $origin[2],
    ];
}

function specificEnergy(array $position, array $velocity, array $origin, float $mu): float
{
    $r = relativePosition($position, $origin);
    $radius = sqrt($r[0]**2 + $r[1]**2 + $r[2]**2);
    $speed2 = $velocity[0]**2 + $velocity[1]**2 + $velocity[2]**2;

    return 0.5 * $speed2 - $mu / $radius;
}

function angularMomentumMagnitude(array $position, array $velocity, array $origin): float
{
    $r = relativePosition($position, $origin);

    $hx = $r[1]*$velocity[2] - $r[2]*$velocity[1];
    $hy = $r[2]*$velocity[0] - $r[0]*$velocity[2];
    $hz = $r[0]*$velocity[1] - $r[1]*$velocity[0];

    return sqrt($hx*$hx + $hy*$hy + $hz*$hz);
}

$energy0 = specificEnergy($position, $velocity, $earth, $mu);
$h0 = angularMomentumMagnitude($position, $velocity, $earth);

$maxEnergyRelativeDrift = 0.0;
$maxHRelativeDrift = 0.0;

for ($i = 0; $i < $steps; $i++) {
    $h = min($dt, $duration - $i*$dt);
    if ($h <= 0.0) {
        break;
    }

    // Earth-only use of the exact gravity helper used by the live integrator.
    $a0 = moonSimGravityAcceleration(
        $position,
        $earth,
        [0.0, 0.0, 0.0],
        $mu,
        0.0
    );

    $newPosition = [
        $position[0] + $velocity[0]*$h + 0.5*$a0[0]*$h*$h,
        $position[1] + $velocity[1]*$h + 0.5*$a0[1]*$h*$h,
        $position[2] + $velocity[2]*$h + 0.5*$a0[2]*$h*$h,
    ];

    $a1 = moonSimGravityAcceleration(
        $newPosition,
        $earth,
        [0.0, 0.0, 0.0],
        $mu,
        0.0
    );

    $newVelocity = [
        $velocity[0] + 0.5*($a0[0]+$a1[0])*$h,
        $velocity[1] + 0.5*($a0[1]+$a1[1])*$h,
        $velocity[2] + 0.5*($a0[2]+$a1[2])*$h,
    ];

    $position = $newPosition;
    $velocity = $newVelocity;

    if (($i % 100) === 0 || $i === $steps-1) {
        $energy = specificEnergy($position, $velocity, $earth, $mu);
        $hMag = angularMomentumMagnitude($position, $velocity, $earth);

        $energyDrift = abs(($energy - $energy0) / $energy0);
        $hDrift = abs(($hMag - $h0) / $h0);

        $maxEnergyRelativeDrift = max($maxEnergyRelativeDrift, $energyDrift);
        $maxHRelativeDrift = max($maxHRelativeDrift, $hDrift);
    }
}

printf(
    "Earth-only circular orbit: %.3f km altitude, %.3f s period, %.2f orbits\n",
    $altitude/1000.0,
    $period,
    $duration/$period
);
printf("Max relative specific-energy drift: %.3e\n", $maxEnergyRelativeDrift);
printf("Max relative angular-momentum drift: %.3e\n", $maxHRelativeDrift);

// The tolerance is intentionally much looser than expected Verlet behavior.
// It catches systematic leakage while allowing platform floating-point noise.
$energyTolerance = 1.0e-8;
$hTolerance = 1.0e-8;

if (
    $maxEnergyRelativeDrift > $energyTolerance
    || $maxHRelativeDrift > $hTolerance
) {
    fwrite(STDERR, "FAIL: orbital conservation regression exceeded tolerance.\n");
    exit(1);
}

echo "PASS: orbital conservation stayed within tolerance.\n";
?>
