<?php 

function calculate_angle($xs, $ys) {
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

function calculate_anglemoon($xm, $ym) {
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

$angleCM_E = calculate_angle($xCM, $yCM);
$angleLM_E = calculate_angle($xLM, $yLM);
$angleMOON_E = calculate_angle($xMOON, $yMOON);
$CMx = $xCM - $xMOON;
$CMy = $yCM - $yMOON;
$LMx = $xLM - $xMOON;
$LMy = $yLM - $yMOON;
$angleCM_M = calculate_anglemoon($CMx,$CMy);
$angleLM_M = calculate_anglemoon($LMx,$LMy);
if ($altitude <= $altitudemoon) {$angleorbitCM = $angleCM_E;} else {$angleorbitCM = $angleCM_M;}
if ($altitudeLM <= $altitudemoonLM) {$angleorbitLM = $angleLM_E;} else {$angleorbitLM = $angleLM_M;}

 $horrizonCM = (360 - $angleorbitCM) - 90;
        if ($horrizonCM >= 360) {$horrizonCM = 360 - $horrizonCM;}
        if ($horrizonCM < 0) {$horrizonCM = 360 + $horrizonCM;}
 $horrizonRCM = $horrizonCM - 180;
        if ($horrizonRCM >= 360) {$horrizonRCM = 360 - $horrizonRCM;}
        if ($horrizonRCM < 0) {$horrizonRCM = 360 + $horrizonRCM;}
        // Calculate yaw (angle in the x-y plane, clockwise from the x-axis)
        // Calculate yaw (angle in the x-y plane, clockwise from the x-axis)
        // Calculate yaw (angle in the x-y plane, clockwise from the x-axis)
$yawVCM = round(fmod(360 - rad2deg(atan2($delta_yCM, $delta_xCM)), 360),1);

// Calculate pitch (angle relative to the x-y plane)
$pitchVCM = round(rad2deg(atan2($delta_zCM, sqrt(pow($delta_xCM, 2) + pow($delta_yCM, 2)))),1);
?>