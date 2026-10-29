<?php
$prog = 9;


//#############Initiation
include 'programshift.php';






// Calculate the shortest difference (accounting for circular nature of angles)
$differenceY = fmod($IMUHOLD_yaw - $yawCM + 360, 360);
if ($differenceY > 180) {
    $differenceY -= 360; // Convert to the shortest angular difference (-180 to 180)
}
$differenceP = $IMUHOLD_pitch - $pitchCM;

// Normalize to [-90, 90]
if ($differenceP > 90) {
    $differenceP -= 180;
} elseif ($differenceP < -90) {
    $differenceP += 180;
}
$differenceR = fmod($IMUHOLD_roll - $rollCM + 360, 360);
if ($differenceR > 180) {
    $differenceR -= 360; // Convert to the shortest angular difference (-180 to 180)
}
// Determine the yaw rate
if (abs($differenceY) > 1.5) {
    $yawRate = $differenceY > 0 ? 1.5 : -1.5; // Rotate at maximum rate (±0.5)
} else {
    $yawRate = $differenceY / 2; // Rotate by the exact difference if less than ±0.5
}
if (abs($differenceP) > 1.5) {
    $pitchRate = $differenceP > 0 ? 1.5 : -1.5; // Rotate at maximum rate (±0.5)
} else {
    $pitchRate = $differenceP / 2; // Rotate by the exact difference if less than ±0.5
}
if (abs($differenceR) > 1.5) {
    $rollRate = $differenceR > 0 ? 1.5 : -1.5; // Rotate at maximum rate (±0.5)
} else {
    $rollRate = $differenceR / 2; // Rotate by the exact difference if less than ±0.5
}

// Update the database
$conn->query("UPDATE command_module SET value = $yawRate WHERE attribute = 'yaw_rate'");
$conn->query("UPDATE command_module SET value = $pitchRate WHERE attribute = 'pitch_rate'");
$conn->query("UPDATE command_module SET value = $rollRate WHERE attribute = 'roll_rate'");

if ($yaw_rateCM != $yawRate) {  $rcsuseY = $RCSCM - (abs($yawRate - $yaw_rateCM)/ .5);
$stmt = $conn->prepare("UPDATE command_module SET value = $rcsuseY WHERE attribute = 'RCS'");
            $stmt->execute();}
if ($pitch_rateCM != $pitchRate) {  $rcsuseP = $RCSCM - (abs($pitchRate - $pitch_rateCM)/ .5);
$stmt = $conn->prepare("UPDATE command_module SET value = $rcsuseP WHERE attribute = 'RCS'");
            $stmt->execute();}
if ($roll_rateCM != $rollRate) {  $rcsuseR = $RCSCM - (abs($rollRate - $roll_rateCM)/ .5);
$stmt = $conn->prepare("UPDATE command_module SET value = $rcsuseR WHERE attribute = 'RCS'");
            $stmt->execute();}
$tg = 0;

if ($differenceY <= 0.0001) {
    $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'yaw_rate'");
$conn->query("UPDATE command_module SET value = $IMUHOLD_yaw WHERE attribute = 'yaw'");   $tg = $tg +1;  } 
    if ($differenceR <= 0.0001) {
$conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'pitch_rate'");
$conn->query("UPDATE command_module SET value = $IMUHOLD_pitch WHERE attribute = 'pitch'");  $tg = $tg +1;}
if ($differencer <= 0.0001) {
$conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'roll_rate'");
$conn->query("UPDATE command_module SET value = $IMUHOLD_roll WHERE attribute = 'roll'"); $tg = $tg +1;}
    
   if ($tg == 3) { 
         include 'progclear.php';
   }
            
?>