<?php
declare(strict_types=1);

function moonSimVectorDot(array $a, array $b): float
{
    return $a[0]*$b[0] + $a[1]*$b[1] + $a[2]*$b[2];
}

function moonSimVectorCross(array $a, array $b): array
{
    return [
        $a[1]*$b[2] - $a[2]*$b[1],
        $a[2]*$b[0] - $a[0]*$b[2],
        $a[0]*$b[1] - $a[1]*$b[0],
    ];
}

function moonSimVectorMagnitude(array $v): float
{
    return sqrt(moonSimVectorDot($v, $v));
}

function moonSimRelativeVector(array $position, array $origin): array
{
    return [
        $position[0] - $origin[0],
        $position[1] - $origin[1],
        $position[2] - $origin[2],
    ];
}

function moonSimOrbitalElements(
    array $position,
    array $velocity,
    array $origin,
    float $mu
): array {
    if ($mu <= 0.0) {
        throw new InvalidArgumentException('Gravitational parameter must be positive.');
    }

    $rVector = moonSimRelativeVector($position, $origin);
    $radius = moonSimVectorMagnitude($rVector);
    $speed = moonSimVectorMagnitude($velocity);

    if ($radius <= 0.0) {
        throw new InvalidArgumentException('Position cannot be at the central body origin.');
    }

    $hVector = moonSimVectorCross($rVector, $velocity);
    $hMagnitude = moonSimVectorMagnitude($hVector);
    $specificEnergy = 0.5*$speed*$speed - $mu/$radius;

    $vCrossH = moonSimVectorCross($velocity, $hVector);
    $eccentricityVector = [
        $vCrossH[0]/$mu - $rVector[0]/$radius,
        $vCrossH[1]/$mu - $rVector[1]/$radius,
        $vCrossH[2]/$mu - $rVector[2]/$radius,
    ];
    $eccentricity = moonSimVectorMagnitude($eccentricityVector);

    $semiMajorAxis = abs($specificEnergy) > 1.0e-12
        ? -$mu/(2.0*$specificEnergy)
        : INF;

    $periapsisRadius = null;
    $apoapsisRadius = null;

    if (is_finite($semiMajorAxis) && $semiMajorAxis > 0.0 && $eccentricity < 1.0) {
        $periapsisRadius = $semiMajorAxis*(1.0-$eccentricity);
        $apoapsisRadius = $semiMajorAxis*(1.0+$eccentricity);
    }

    return [
        'radius' => $radius,
        'speed' => $speed,
        'specific_energy' => $specificEnergy,
        'angular_momentum_vector' => $hVector,
        'angular_momentum' => $hMagnitude,
        'eccentricity_vector' => $eccentricityVector,
        'eccentricity' => $eccentricity,
        'semi_major_axis' => $semiMajorAxis,
        'periapsis_radius' => $periapsisRadius,
        'apoapsis_radius' => $apoapsisRadius,
    ];
}

function moonSimVisVivaSpeed(float $mu, float $radius, float $semiMajorAxis): float
{
    if ($mu <= 0.0 || $radius <= 0.0 || $semiMajorAxis == 0.0) {
        throw new InvalidArgumentException('Invalid vis-viva inputs.');
    }

    $term = $mu*(2.0/$radius - 1.0/$semiMajorAxis);
    if ($term < 0.0) {
        throw new RuntimeException('Vis-viva inputs produce an imaginary speed.');
    }

    return sqrt($term);
}
?>