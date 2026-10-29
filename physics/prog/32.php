<?php
//################Trigger setting
$buffer = 30; // Buffer range in degrees
$trigger = 0; // 0 = not triggered, 1 = triggered

// Normalize angles to [0, 360]
$orbitAngle = fmod($angleorbitCM + 360, 360);
$imuAngle = fmod($IMUHOLD_orbit_angle + 360, 360);

// Define the buffer range
$lowerBound = $imuAngle;
$upperBound = fmod($imuAngle + $buffer + 360, 360);
if ($upperBound > 359) {$upperBound = $upperBound - 360; }
// Check if orbit angle is within the buffer range
if ($lowerBound < $upperBound) {
    // Normal case (no wrap-around)
    if ($orbitAngle >= $lowerBound && $orbitAngle <= $upperBound) {
        $trigger = 1; // Trigger set
    } else {
        $trigger = 0; // Trigger reset
    }
} else {
    // Handle wrap-around (e.g., buffer crosses 360°)
    if ($orbitAngle >= $lowerBound || $orbitAngle <= $upperBound) {
        $trigger = 1; // Trigger set
    } else {
        $trigger = 0; // Trigger reset
    }
}
?>