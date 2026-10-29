<?php
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
$counter = 0;
include 'physics/db.php';

$updateExecuted = false;

// Loop 60 times
while ($counter < 60) {



include 'physics/variables.php';
include 'physics/constants.php';

if ($sim_onSYS == 1) {
include 'physics/coordinate.php';

include 'physics/gravity.php';
include 'physics/air.php';
include 'physics/thrust.php';
include 'physics/fuel.php';
include 'physics/telemetry.php';
include 'physics/constants.php';
include 'physics/variables.php';
include 'physics/constants.php';
include 'physics/CM_AGC.php';


 // Commit transaction


}
else 
{include_once 'physics/reset.php';}


    sleep(1); // Convert to microseconds

    // Increment the counter
    $counter++;
} 
?>