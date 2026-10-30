<?php

/**
 * Transitional attitude update.
 *
 * This preserves the original Euler-angle/rate operator interface while the
 * translation integrator is repaired. Phase 6 will replace this internal
 * Euler integration with quaternion/rotation-matrix dynamics.
 */

function moonSimWrap360(float $angle): float
{
    $angle = fmod($angle, 360.0);
    return $angle < 0.0 ? $angle + 360.0 : $angle;
}

function moonSimLegacyEulerStep(
    float $pitch,
    float $yaw,
    float $roll,
    float &$pitchRate,
    float $yawRate,
    float $rollRate,
    float $dt
): array {
    $newPitch = $pitch + $pitchRate * $dt;
    $newYaw = $yaw + $yawRate * $dt;
    $newRoll = $roll + $rollRate * $dt;

    // Preserve the original representation behavior until Phase 6.
    if ($newPitch < -90.0) {
        $newPitch = -90.0 - ($newPitch + 90.0);
        $pitchRate *= -1.0;
        $newYaw += 180.0;
    }

    if ($newPitch > 90.0) {
        $newPitch = 90.0 - ($newPitch - 90.0);
        $pitchRate *= -1.0;
        $newYaw -= 180.0;
    }

    return [
        round($newPitch, 6),
        round(moonSimWrap360($newYaw), 6),
        round(moonSimWrap360($newRoll), 6),
    ];
}

function moonSimAdvanceLegacyAttitude(mysqli $conn, float $dt = 1.0): void
{
    global
        $pitchCM, $yawCM, $rollCM,
        $pitch_rateCM, $yaw_rateCM, $roll_rateCM,
        $pitchLM, $yawLM, $rollLM,
        $pitch_rateLM, $yaw_rateLM, $roll_rateLM,
        $dockCM, $stage3CM;

    $cmPitchRate = (float)$pitch_rateCM;

    [$newPitchCM, $newYawCM, $newRollCM] = moonSimLegacyEulerStep(
        (float)$pitchCM,
        (float)$yawCM,
        (float)$rollCM,
        $cmPitchRate,
        (float)$yaw_rateCM,
        (float)$roll_rateCM,
        $dt
    );

    $pitchCM = $newPitchCM;
    $yawCM = $newYawCM;
    $rollCM = $newRollCM;
    $pitch_rateCM = $cmPitchRate;

    if ((int)$dockCM === 1 || (int)$stage3CM === 1) {
        $newPitchLM = $newPitchCM;
        $newYawLM = $newYawCM;
        $newRollLM = $newRollCM;
        $lmPitchRate = $cmPitchRate;
        $pitch_rateLM = $cmPitchRate;
        $yaw_rateLM = (float)$yaw_rateCM;
        $roll_rateLM = (float)$roll_rateCM;
    } else {
        $lmPitchRate = (float)$pitch_rateLM;

        [$newPitchLM, $newYawLM, $newRollLM] = moonSimLegacyEulerStep(
            (float)$pitchLM,
            (float)$yawLM,
            (float)$rollLM,
            $lmPitchRate,
            (float)$yaw_rateLM,
            (float)$roll_rateLM,
            $dt
        );

        $pitch_rateLM = $lmPitchRate;
    }

    $pitchLM = $newPitchLM;
    $yawLM = $newYawLM;
    $rollLM = $newRollLM;

    $conn->query(
        "UPDATE command_module SET value = CASE attribute
            WHEN 'pitch' THEN " . sprintf('%.6f', $newPitchCM) . "
            WHEN 'yaw' THEN " . sprintf('%.6f', $newYawCM) . "
            WHEN 'roll' THEN " . sprintf('%.6f', $newRollCM) . "
            WHEN 'pitch_rate' THEN " . sprintf('%.6f', $cmPitchRate) . "
         END
         WHERE attribute IN ('pitch','yaw','roll','pitch_rate')"
    );

    $conn->query(
        "UPDATE telemetry SET value = CASE attribute
            WHEN 'pitch' THEN " . sprintf('%.6f', $newPitchLM) . "
            WHEN 'yaw' THEN " . sprintf('%.6f', $newYawLM) . "
            WHEN 'roll' THEN " . sprintf('%.6f', $newRollLM) . "
            WHEN 'pitch_rate' THEN " . sprintf('%.6f', (float)$pitch_rateLM) . "
            WHEN 'yaw_rate' THEN " . sprintf('%.6f', (float)$yaw_rateLM) . "
            WHEN 'roll_rate' THEN " . sprintf('%.6f', (float)$roll_rateLM) . "
         END
         WHERE attribute IN ('pitch','yaw','roll','pitch_rate','yaw_rate','roll_rate')"
    );
}
?>
