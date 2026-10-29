<?php
if ($altitudemoon < 80000000) {
    // Normalize angles to [0, 360]
$yawCMD = fmod($yawCM + 360, 360);
$yawVCMD = fmod($yawVCMM + 360, 360);
}
else {
// Normalize angles to [0, 360]
$yawCMD = fmod($yawCM + 360, 360);
$yawVCMD = fmod($yawVCM + 360, 360);
}
// Calculate the shortest difference (accounting for circular nature of angles)
$difference = fmod($yawVCMD - $yawCMD + 360, 360);
if ($difference > 180) {
    $difference -= 360; // Convert to the shortest angular difference (-180 to 180)
}

// Determine the yaw rate
if (abs($difference) > 2.5) {
    $yawRate = $difference > 0 ? 2.5 : -2.5; // Rotate at maximum rate (±0.5)
} else {
    $yawRate = $difference; // Rotate by the exact difference if less than ±0.5
}

// Update the database
$conn->query("UPDATE command_module SET value = $yawRate WHERE attribute = 'yaw_rate'");

if ($yaw_rateCM != $yawRate) {  $rcsuse = $RCSCM - ((abs($yawRate - $yaw_rateCM)/ .5)/10);
$stmt = $conn->prepare("UPDATE command_module SET value = $rcsuse WHERE attribute = 'RCS'");
            $stmt->execute();}
?>
