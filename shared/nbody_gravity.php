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

    // Our simulation uses Earth-fixed coordinates. The Moon also accelerates
    // Earth's origin, so only its DIFFERENTIAL (tidal) acceleration may act
    // on an Earth-relative spacecraft. Without this indirect term a stable
    // LEO accumulates an artificial uniform Moon-induced displacement.
    $ex = $moon[0] - $earth[0];
    $ey = $moon[1] - $earth[1];
    $ez = $moon[2] - $earth[2];
    $em2 = $ex*$ex + $ey*$ey + $ez*$ez;
    if ($em2 > 1.0 && $moonMu != 0.0) {
        $ef = $moonMu / ($em2 * sqrt($em2));
        $ax -= $ex * $ef;
        $ay -= $ey * $ef;
        $az -= $ez * $ef;
    }

    return [$ax, $ay, $az];
}

function moonSimMoonAcceleration(
    array $moon,
    array $earth,
    float $earthMu,
    float $moonMu = 0.0
): array {
    $dx = $earth[0] - $moon[0];
    $dy = $earth[1] - $moon[1];
    $dz = $earth[2] - $moon[2];
    $r2 = $dx*$dx + $dy*$dy + $dz*$dz;

    if ($r2 <= 1.0) {
        return [0.0, 0.0, 0.0];
    }

    $r = sqrt($r2);
    // Earth-Moon relative acceleration uses the sum of both GM values.
    $factor = ($earthMu + $moonMu) / ($r2 * $r);

    return [$dx*$factor, $dy*$factor, $dz*$factor];
}

function moonSimVecAdd(array $a, array $b): array
{
    return [$a[0]+$b[0], $a[1]+$b[1], $a[2]+$b[2]];
}

