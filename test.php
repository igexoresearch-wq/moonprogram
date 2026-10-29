<?php


// Check if there is enough RCS fuel
if ($RCSCM < 1) {
    echo "Insufficient RCS fuel to perform the maneuver.";
    return;
}

// Calculate differences
$pitch_diff = calculateShortestDifference($IMU_pitch, $pitchCM);
$roll_diff = calculateShortestDifference($IMU_roll, $rollCM);
$yaw_diff = calculateShortestDifference($IMU_yaw, $yawCM);

// Adjust internal rates
$IMU_pitch_rate = min(abs($pitch_diff / 10), 5) * ($pitch_diff < 0 ? -1 : 1);
$IMU_roll_rate = min(abs($roll_diff / 10), 5) * ($roll_diff < 0 ? -1 : 1);
$IMU_yaw_rate = min(abs($yaw_diff / 10), 5) * ($yaw_diff < 0 ? -1 : 1);

// Track fuel deduction for this iteration
$fuelDeduction = 0;

// Update real-world rates and deduct fuel for changes
function updateRealWorldRate(&$realRate, $internalRate, &$fuelDeduction) {
    $change = abs($realRate - $internalRate);
    $fuelDeduction += floor($change / 0.5); // Deduct 1 RCS per 0.5-degree change
    $realRate = $internalRate; // Update real-world rate to match internal rate
}

updateRealWorldRate($pitch_rateCM, $IMU_pitch_rate, $fuelDeduction);
updateRealWorldRate($roll_rateCM, $IMU_roll_rate, $fuelDeduction);
updateRealWorldRate($yaw_rateCM, $IMU_yaw_rate, $fuelDeduction);

// Deduct fuel
$RCSCM -= $fuelDeduction;

// Check if maneuver is complete
if ($IMU_pitch == $pitchCM && $IMU_roll == $rollCM && $IMU_yaw == $yawCM) {
    // Reset rates
    $IMU_pitch_rate = 0;
    $IMU_roll_rate = 0;
    $IMU_yaw_rate = 0;

    $fuelDeduction = 0; // Track fuel needed to reset rates

    updateRealWorldRate($pitch_rateCM, 0, $fuelDeduction);
    updateRealWorldRate($roll_rateCM, 0, $fuelDeduction);
    updateRealWorldRate($yaw_rateCM, 0, $fuelDeduction);

    // Deduct fuel for resetting rates
    $RCSCM -= $fuelDeduction;

    // Reset execute flag in CMguidance
    $conn->query("UPDATE CMguidance SET execute = 0 WHERE id = 2");
}

// Update the database with new values
$conn->query("UPDATE command_module SET value = $pitch_rateCM WHERE attribute = 'pitch_rate'");
$conn->query("UPDATE command_module SET value = $roll_rateCM WHERE attribute = 'roll_rate'");
$conn->query("UPDATE command_module SET value = $yaw_rateCM WHERE attribute = 'yaw_rate'");
$conn->query("UPDATE command_module SET value = $RCSCM WHERE attribute = 'RCS'");

$conn->query("UPDATE CMguidance SET pitch_rate = $IMU_pitch_rate, roll_rate = $IMU_roll_rate, yaw_rate = $IMU_yaw_rate WHERE id = 1");
?>
