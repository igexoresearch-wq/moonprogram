<?php
/** Pure Newtonian Earth/Moon gravity functions used by the live physics worker. */
function moonSimGravityAcceleration(
    array $position,
    array $earth,
    array $moon,
    float $earthMu,
    float $moonMu
): array {
    $ax = 0.0;
    $ay = 0.0;
    $az = 0.0;

    $dx = $earth[0] - $position[0];
    $dy = $earth[1] - $position[1];
    $dz = $earth[2] - $position[2];
    $r2 = $dx*$dx + $dy*$dy + $dz*$dz;

    if ($r2 > 1.0) {
        $r = sqrt($r2);
        $factor = $earthMu / ($r2 * $r);
        $ax += $dx * $factor;
        $ay += $dy * $factor;
        $az += $dz * $factor;
    }

    $dx = $moon[0] - $position[0];
    $dy = $moon[1] - $position[1];
    $dz = $moon[2] - $position[2];
    $r2 = $dx*$dx + $dy*$dy + $dz*$dz;

    if ($r2 > 1.0) {
        $r = sqrt($r2);
        $factor = $moonMu / ($r2 * $r);
        $ax += $dx * $factor;
        $ay += $dy * $factor;
        $az += $dz * $factor;
    }

    return [$ax, $ay, $az];
}

function moonSimMoonAcceleration(
    array $moon,
    array $earth,
    float $earthMu
): array {
    $dx = $earth[0] - $moon[0];
    $dy = $earth[1] - $moon[1];
    $dz = $earth[2] - $moon[2];
    $r2 = $dx*$dx + $dy*$dy + $dz*$dz;

    if ($r2 <= 1.0) {
        return [0.0, 0.0, 0.0];
    }

    $r = sqrt($r2);
    $factor = $earthMu / ($r2 * $r);

    return [$dx*$factor, $dy*$factor, $dz*$factor];
}

function moonSimVecAdd(array $a, array $b): array
{
    return [$a[0]+$b[0], $a[1]+$b[1], $a[2]+$b[2]];
}

