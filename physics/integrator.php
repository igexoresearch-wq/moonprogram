<?php

/**
 * Translational velocity-Verlet integrator.
 *
 * This intentionally leaves attitude integration to the separate attitude
 * layer.  It advances CSM, LM and Moon together so all gravity for a substep
 * is evaluated from a coherent state.
 */

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

function moonSimAdvanceTranslation(
    mysqli $conn,
    float $seconds,
    float $substepSeconds,
    array $cmNonGravityAcceleration,
    array $lmNonGravityAcceleration
): void {
    global
        $xCM, $yCM, $zCM,
        $delta_xCM, $delta_yCM, $delta_zCM,
        $xLM, $yLM, $zLM,
        $delta_xLM, $delta_yLM, $delta_zLM,
        $xMOON, $yMOON, $zMOON,
        $delta_xMOON, $delta_yMOON, $delta_zMOON,
        $earth_center_x, $earth_center_y, $earth_center_z,
        $earth_radius, $moon_radius,
        $earth_mass, $moon_mass, $gravitational_constant,
        $dockCM, $stage3CM;

    $earth = [
        (float)$earth_center_x,
        (float)$earth_center_y,
        (float)$earth_center_z,
    ];

    $cmPos = [(float)$xCM, (float)$yCM, (float)$zCM];
    $cmVel = [(float)$delta_xCM, (float)$delta_yCM, (float)$delta_zCM];

    $lmPos = [(float)$xLM, (float)$yLM, (float)$zLM];
    $lmVel = [(float)$delta_xLM, (float)$delta_yLM, (float)$delta_zLM];

    $moonPos = [(float)$xMOON, (float)$yMOON, (float)$zMOON];
    $moonVel = [(float)$delta_xMOON, (float)$delta_yMOON, (float)$delta_zMOON];

    $earthMu = (float)$gravitational_constant * (float)$earth_mass;
    $moonMu = (float)$gravitational_constant * (float)$moon_mass;

    $attached = ((int)$dockCM === 1 || (int)$stage3CM === 1);

    $cmEarthRadius = sqrt(
        ($cmPos[0]-$earth[0])**2
        + ($cmPos[1]-$earth[1])**2
        + ($cmPos[2]-$earth[2])**2
    );

    // Preserve the current prelaunch pad hold until Phase 7 replaces it with
    // an explicit support-force/liftoff model.
    $cmPadHeld = $cmEarthRadius <= ((float)$earth_radius + 0.001);

    $remaining = max(0.0, $seconds);
    $hDefault = max(0.001, $substepSeconds);

    while ($remaining > 1.0e-12) {
        $h = min($hDefault, $remaining);

        $aMoon0 = moonSimMoonAcceleration($moonPos, $earth, $earthMu);

        $aCmGravity0 = moonSimGravityAcceleration(
            $cmPos,
            $earth,
            $moonPos,
            $earthMu,
            $moonMu
        );
        $aLmGravity0 = moonSimGravityAcceleration(
            $lmPos,
            $earth,
            $moonPos,
            $earthMu,
            $moonMu
        );

        $aCm0 = moonSimVecAdd($aCmGravity0, $cmNonGravityAcceleration);
        $aLm0 = moonSimVecAdd($aLmGravity0, $lmNonGravityAcceleration);

        // Drift positions using v*dt + 1/2*a*dt^2.
        $moonPosNew = [
            $moonPos[0] + $moonVel[0]*$h + 0.5*$aMoon0[0]*$h*$h,
            $moonPos[1] + $moonVel[1]*$h + 0.5*$aMoon0[1]*$h*$h,
            $moonPos[2] + $moonVel[2]*$h + 0.5*$aMoon0[2]*$h*$h,
        ];

        if ($cmPadHeld) {
            $cmPosNew = $cmPos;
        } else {
            $cmPosNew = [
                $cmPos[0] + $cmVel[0]*$h + 0.5*$aCm0[0]*$h*$h,
                $cmPos[1] + $cmVel[1]*$h + 0.5*$aCm0[1]*$h*$h,
                $cmPos[2] + $cmVel[2]*$h + 0.5*$aCm0[2]*$h*$h,
            ];
        }

        if ($attached) {
            $lmPosNew = $cmPosNew;
        } else {
            $lmPosNew = [
                $lmPos[0] + $lmVel[0]*$h + 0.5*$aLm0[0]*$h*$h,
                $lmPos[1] + $lmVel[1]*$h + 0.5*$aLm0[1]*$h*$h,
                $lmPos[2] + $lmVel[2]*$h + 0.5*$aLm0[2]*$h*$h,
            ];
        }

        // Re-evaluate gravity from the new coherent position snapshot.
        $aMoon1 = moonSimMoonAcceleration($moonPosNew, $earth, $earthMu);
        $aCmGravity1 = moonSimGravityAcceleration(
            $cmPosNew,
            $earth,
            $moonPosNew,
            $earthMu,
            $moonMu
        );
        $aLmGravity1 = moonSimGravityAcceleration(
            $lmPosNew,
            $earth,
            $moonPosNew,
            $earthMu,
            $moonMu
        );

        $aCm1 = moonSimVecAdd($aCmGravity1, $cmNonGravityAcceleration);
        $aLm1 = moonSimVecAdd($aLmGravity1, $lmNonGravityAcceleration);

        $moonVelNew = [
            $moonVel[0] + 0.5*($aMoon0[0]+$aMoon1[0])*$h,
            $moonVel[1] + 0.5*($aMoon0[1]+$aMoon1[1])*$h,
            $moonVel[2] + 0.5*($aMoon0[2]+$aMoon1[2])*$h,
        ];

        if ($cmPadHeld) {
            $cmVelNew = [0.0, 0.0, 0.0];
        } else {
            $cmVelNew = [
                $cmVel[0] + 0.5*($aCm0[0]+$aCm1[0])*$h,
                $cmVel[1] + 0.5*($aCm0[1]+$aCm1[1])*$h,
                $cmVel[2] + 0.5*($aCm0[2]+$aCm1[2])*$h,
            ];
        }

        if ($attached) {
            $lmVelNew = $cmVelNew;
        } else {
            $lmVelNew = [
                $lmVel[0] + 0.5*($aLm0[0]+$aLm1[0])*$h,
                $lmVel[1] + 0.5*($aLm0[1]+$aLm1[1])*$h,
                $lmVel[2] + 0.5*($aLm0[2]+$aLm1[2])*$h,
            ];
        }

        $moonPos = $moonPosNew;
        $moonVel = $moonVelNew;
        $cmPos = $cmPosNew;
        $cmVel = $cmVelNew;
        $lmPos = $lmPosNew;
        $lmVel = $lmVelNew;

        $remaining -= $h;
    }

    // Publish values back to globals for guidance modules in this request.
    [$xCM, $yCM, $zCM] = $cmPos;
    [$delta_xCM, $delta_yCM, $delta_zCM] = $cmVel;
    [$xLM, $yLM, $zLM] = $lmPos;
    [$delta_xLM, $delta_yLM, $delta_zLM] = $lmVel;
    [$xMOON, $yMOON, $zMOON] = $moonPos;
    [$delta_xMOON, $delta_yMOON, $delta_zMOON] = $moonVel;

    $fmt = static fn(float $v): string => sprintf('%.12f', $v);

    $conn->query(
        "UPDATE command_module SET value = CASE attribute
            WHEN 'x' THEN " . $fmt($cmPos[0]) . "
            WHEN 'y' THEN " . $fmt($cmPos[1]) . "
            WHEN 'z' THEN " . $fmt($cmPos[2]) . "
            WHEN 'delta_x' THEN " . $fmt($cmVel[0]) . "
            WHEN 'delta_y' THEN " . $fmt($cmVel[1]) . "
            WHEN 'delta_z' THEN " . $fmt($cmVel[2]) . "
         END
         WHERE attribute IN ('x','y','z','delta_x','delta_y','delta_z')"
    );

    $conn->query(
        "UPDATE telemetry SET value = CASE attribute
            WHEN 'x' THEN " . $fmt($lmPos[0]) . "
            WHEN 'y' THEN " . $fmt($lmPos[1]) . "
            WHEN 'z' THEN " . $fmt($lmPos[2]) . "
            WHEN 'delta_x' THEN " . $fmt($lmVel[0]) . "
            WHEN 'delta_y' THEN " . $fmt($lmVel[1]) . "
            WHEN 'delta_z' THEN " . $fmt($lmVel[2]) . "
         END
         WHERE attribute IN ('x','y','z','delta_x','delta_y','delta_z')"
    );

    $conn->query(
        "UPDATE moon SET value = CASE attribute
            WHEN 'x' THEN " . $fmt($moonPos[0]) . "
            WHEN 'y' THEN " . $fmt($moonPos[1]) . "
            WHEN 'z' THEN " . $fmt($moonPos[2]) . "
            WHEN 'delta_x' THEN " . $fmt($moonVel[0]) . "
            WHEN 'delta_y' THEN " . $fmt($moonVel[1]) . "
            WHEN 'delta_z' THEN " . $fmt($moonVel[2]) . "
         END
         WHERE attribute IN ('x','y','z','delta_x','delta_y','delta_z')"
    );

    // Preserve the original automatic docking behavior for now. Phase 11
    // replaces this with closure-speed and alignment capture criteria.
    if (!$attached && (int)$stage3CM === 0) {
        $dx = $lmPos[0] - $cmPos[0];
        $dy = $lmPos[1] - $cmPos[1];
        $dz = $lmPos[2] - $cmPos[2];

        if (sqrt($dx*$dx + $dy*$dy + $dz*$dz) < 10.0) {
            $conn->query("UPDATE command_module SET value = 1 WHERE attribute = 'dock'");
            $dockCM = 1;
        }
    }
}
?>
