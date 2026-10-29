<?php

include 'moon.php';

if ($altitude <= 0.001) { 
    // Update positions based on new delta velocities
    $new_delta_xCM = 0;
    $new_delta_yCM = 0;
    $new_delta_zCM = 0;
    $acceleration_x_CM = 0;
    $acceleration_y_CM = 0;
    $acceleration_z_CM = 0;
    $new_rollCM = $rollCM;
    $new_pitchCM = $pitchCM;
    $new_yawCM = $yawCM;
} else {
    if ($altitude < 100000) {
        if ($temp > 800 && $altitude < 100000) { 
            $newheat = $heat_shieldCM - $temp;
            $conn->query("UPDATE command_module SET value = $newheat WHERE attribute = 'heat_shield'");
        }
        
        // Only calculate drag below 500,000 meters
        $acceleration_x_CM -= $drag_acc_x;
        $acceleration_y_CM -= $drag_acc_y;
        $acceleration_z_CM -= $drag_acc_z;
    }

    // Calculate new roll, pitch, and yaw values
    $new_rollCM = round($roll_rateCM + $rollCM, 4);
    $new_pitchCM = round($pitch_rateCM + $pitchCM, 4);
    $new_yawCM = round($yaw_rateCM + $yawCM, 4);

    if ($new_pitchCM < -90) { 
        $new_pitchCM = -90 - ($new_pitchCM + 90); 
        $pitch_rateCM *= -1;
        $new_yawCM = round(180 + $new_yawCM, 4);
        $conn->query("UPDATE command_module SET value = $pitch_rateCM WHERE attribute = 'pitch_rate'");
    }
    if ($new_pitchCM > 90) { 
        $new_pitchCM = 90 - ($new_pitchCM - 90); 
        $pitch_rateCM *= -1;
        $new_yawCM = round($new_yawCM - 180, 4);
        $conn->query("UPDATE command_module SET value = $pitch_rateCM WHERE attribute = 'pitch_rate'");
    }
    
    $new_rollCM = ($new_rollCM < 0) ? 360 + $new_rollCM : (($new_rollCM >= 360) ? $new_rollCM - 360 : $new_rollCM);
    $new_yawCM = ($new_yawCM < 0) ? 360 + $new_yawCM : (($new_yawCM >= 360) ? $new_yawCM - 360 : $new_yawCM);

$xCM = sprintf("%.20f", $xCM);
$yCM = sprintf("%.20f", $yCM);
$zCM = sprintf("%.20f", $zCM);

$delta_xCM = sprintf("%.20f", $delta_xCM);
$delta_yCM = sprintf("%.20f", $delta_yCM);
$delta_zCM = sprintf("%.20f", $delta_zCM);

$thrustAccelerationCMx = sprintf("%.20f", $thrustAccelerationCMx);
$thrustAccelerationCMy = sprintf("%.20f", $thrustAccelerationCMy);
$thrustAccelerationCMz = sprintf("%.20f", $thrustAccelerationCMz);

$acceleration_x_CM = sprintf("%.20f", $acceleration_x_CM);
$acceleration_y_CM = sprintf("%.20f", $acceleration_y_CM);
$acceleration_z_CM = sprintf("%.20f", $acceleration_z_CM);

// Calculate new delta velocities
$new_delta_xCM = $delta_xCM + $acceleration_x_CM;
$new_delta_yCM = $delta_yCM + $acceleration_y_CM;
$new_delta_zCM = $delta_zCM + $acceleration_z_CM;

$new_delta_xCM += $thrustAccelerationCMx;
$new_delta_yCM += $thrustAccelerationCMy;
$new_delta_zCM += $thrustAccelerationCMz;

// Update positions based on new velocities
$xCM += $delta_xCM + (($thrustAccelerationCMx + $acceleration_x_CM) * $factorCM);
$yCM += $delta_yCM + (($thrustAccelerationCMy + $acceleration_y_CM) * $factorCM);
$zCM += $delta_zCM + (($thrustAccelerationCMz + $acceleration_z_CM) * $factorCM);

$delta_xCM = $new_delta_xCM;
$delta_yCM = $new_delta_yCM;
$delta_zCM = $new_delta_zCM;
}

// **Batch Update for `command_module`**
$conn->query("
    UPDATE command_module SET value = CASE attribute
        WHEN 'roll' THEN $new_rollCM
        WHEN 'pitch' THEN $new_pitchCM
        WHEN 'yaw' THEN $new_yawCM
        WHEN 'delta_x' THEN $new_delta_xCM
        WHEN 'delta_y' THEN $new_delta_yCM
        WHEN 'delta_z' THEN $new_delta_zCM
        WHEN 'x' THEN $xCM
        WHEN 'y' THEN $yCM
        WHEN 'z' THEN $zCM
    END
    WHERE attribute IN ('roll', 'pitch', 'yaw', 'delta_x', 'delta_y', 'delta_z', 'x', 'y', 'z')
");

// Lunar Module (LM) Calculations
$new_delta_xLM = $delta_xLM + $acceleration_x_LM;
$new_delta_yLM = $delta_yLM + $acceleration_y_LM;
$new_delta_zLM = $delta_zLM + $acceleration_z_LM;

$xLM += $delta_xLM + (($thrustAccelerationLMx + $acceleration_x_LM) * $factorCM);
$yLM += $delta_yLM + (($thrustAccelerationLMy + $acceleration_y_LM) * $factorCM);
$zLM += $delta_zLM + (($thrustAccelerationLMz + $acceleration_z_LM) * $factorCM);

$new_delta_xLM += $thrustAccelerationLMx;
$new_delta_yLM += $thrustAccelerationLMy;
$new_delta_zLM += $thrustAccelerationLMz;

$delta_xLM = $new_delta_xLM;
$delta_yLM = $new_delta_yLM;
$delta_zLM = $new_delta_zLM;

if ($dockCM == "1" || $stage3CM == "1") {
    // Sync LM values to CM values when docked
    $new_delta_xLM = $new_delta_xCM;
    $new_delta_yLM = $new_delta_yCM;
    $new_delta_zLM = $new_delta_zCM;

    $xLM = $xCM;
    $yLM = $yCM;
    $zLM = $zCM;
}

// **Batch Update for `telemetry`**
$conn->query("
    UPDATE telemetry SET value = CASE attribute
        WHEN 'delta_x' THEN $new_delta_xLM
        WHEN 'delta_y' THEN $new_delta_yLM
        WHEN 'delta_z' THEN $new_delta_zLM
        WHEN 'x' THEN $xLM
        WHEN 'y' THEN $yLM
        WHEN 'z' THEN $zLM
    END
    WHERE attribute IN ('delta_x', 'delta_y', 'delta_z', 'x', 'y', 'z')
");

// **Docking**
if ($craft_dist < 10 && $stage3CM == 0) {
    $conn->query("UPDATE command_module SET value = 1 WHERE attribute = 'dock'");
}

?>


