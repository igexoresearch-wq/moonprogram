<?php

include 'db.php';
include 'variables.php';
include 'constants.php';
include 'functions.php';
include 'coordinate.php';


function calculate_anglex($xs, $ys) {
    // Origin adjustment
    $origin = 10000000000;
    $x_adjusted = $xs - $origin;
    $y_adjusted = $ys - $origin;

    // Calculate the angle in radians
    $theta = atan2($y_adjusted, $x_adjusted);

    // Convert radians to degrees
    $theta_degrees = $theta * (180 / pi());

    // Ensure the angle is in the range 0 to 360
    if ($theta_degrees < 0) {
        $theta_degrees += 360;
    }

    return $theta_degrees;
}

function calculate_anglemoonx($xm, $ym) {
    // Origin adjustment
    $x_adjusted = $xm;
    $y_adjusted = $ym;

    // Calculate the angle in radians
    $theta = atan2($y_adjusted, $x_adjusted);

    // Convert radians to degrees
    $theta_degrees = $theta * (180 / pi());

    // Ensure the angle is in the range 0 to 360
    if ($theta_degrees < 0) {
        $theta_degrees += 360;
    }

    return $theta_degrees;
}

//################Trigger setting
$buffer = 20; // Buffer range in degrees
$trigger = 0; // 0 = not triggered, 1 = triggered

// Normalize angles to [0, 360]
$orbitAngle = fmod($angleorbitCM + 360, 360);
$imuAngle = fmod($IMUHOLD_orbit_angle + 360, 360);

// Define the buffer range
$lowerBound = $imuAngle;
$upperBound = fmod($imuAngle + $buffer + 360, 360);
if ($upperBound > 359) {$upperBound = $upperBound - 360; }
    

// Debugging outputs (optional)
echo "Orbit Angle: $orbitAngle\n";
echo "IMU Angle: $imuAngle\n";
echo "Closing in: $upperBound\n";
echo "Lower Bound: $lowerBound\n";
echo "Upper Bound: $upperBound\n";


// Check if orbit angle is within the buffer range
if ($lowerBound <= $upperBound) {
    // Normal case (no wrap-around)
    if ($orbitAngle >= $lowerBound && $orbitAngle <= $upperBound) {
        $trigger = 1; // Trigger set
    } else {
        $trigger = 0; // Trigger reset
    }
} else {
    // Wrap-around case
    if ($orbitAngle >= $lowerBound || $orbitAngle <= $upperBound) {
        $trigger = 1; // Trigger set
    } else {
        $trigger = 0; // Trigger reset
    }
}

// Output final trigger state
echo "Trigger State: $trigger\n";


?>