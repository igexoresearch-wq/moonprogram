<?php 



$angleCM_E = calculate_anglex($xCM, $yCM);
$angleLM_E = calculate_anglex($xLM, $yLM);
$angleMOON_E = calculate_anglex($xMOON, $yMOON);
$CMx = $xCM - $xMOON;
$CMy = $yCM - $yMOON;
$LMx = $xLM - $xMOON;
$LMy = $yLM - $yMOON;
$angleCM_M = calculate_anglemoonx($CMx,$CMy);
$angleLM_M = calculate_anglemoonx($LMx,$LMy);
if ($altitude <= $altitudemoon) {$angleorbitCM = $angleCM_E;} else {$angleorbitCM = $angleCM_M;}
if ($altitudeLM <= $altitudemoonLM) {$angleorbitLM = $angleLM_E;} else {$angleorbitLM = $angleLM_M;}
$angleorbitLM = round($angleorbitLM,1);
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

$yawVCM = round(fmod(360 - rad2deg(atan2($delta_yCM, $delta_xCM)), 360),1);
// Calculate pitch (angle relative to the x-y plane)
$pitchVCM = round(rad2deg(atan2($delta_zCM, sqrt(pow($delta_xCM, 2) + pow($delta_yCM, 2)))),1);
if ($altitudemoon < 80000000){
$yawVCMM = round(fmod(360 - rad2deg(atan2($delta_yCM - $delta_yMOON, $delta_xCM - $delta_xMOON)), 360),1);
// Calculate pitch (angle relative to the x-y plane)
$pitchVCMM = round(rad2deg(atan2($delta_zCM - $delta_zMOON, sqrt(pow($delta_xCM - $delta_xMOON, 2) + pow($delta_yCM - $delta_yMOON, 2)))),1);
} else { $yawVCMM = 0; $pitchVCMM = 0;}


?>