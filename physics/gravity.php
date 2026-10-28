<?php

declare(strict_types=1);

const PHYSICS_G = 6.67430e-11;

function physicsGravity(
    array $objects,
    array $rootMembers,
    array $stackMasses
): array {
    $accelerations = [];

    foreach ($rootMembers as $targetId => $_members) {
        $accelerations[$targetId] = ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];

        if (!$objects[$targetId]['affected_by_gravity']) {
            continue;
        }

        foreach ($rootMembers as $sourceId => $_sourceMembers) {
            if ($sourceId === $targetId || !$objects[$sourceId]['produces_gravity']) {
                continue;
            }

            $dx = $objects[$sourceId]['x_m'] - $objects[$targetId]['x_m'];
            $dy = $objects[$sourceId]['y_m'] - $objects[$targetId]['y_m'];
            $dz = $objects[$sourceId]['z_m'] - $objects[$targetId]['z_m'];

            $r2 = $dx * $dx + $dy * $dy + $dz * $dz;

            if (!is_finite($r2) || $r2 < 1.0) {
                continue;
            }

            $r = sqrt($r2);
            $factor = PHYSICS_G * $stackMasses[$sourceId] / ($r2 * $r);

            $accelerations[$targetId]['x'] += $dx * $factor;
            $accelerations[$targetId]['y'] += $dy * $factor;
            $accelerations[$targetId]['z'] += $dz * $factor;
        }
    }

    return $accelerations;
}
