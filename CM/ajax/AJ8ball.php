<?php
include 'db.php';
include 'variables.php';
include 'constants.php';
include 'coordinate.php';
include 'sound.php';
// Fetch current values for ID 1
$rollResult = $conn->query("SELECT roll, pitch, yaw FROM CMguidance WHERE id = 1");

// Check if the queries were successful and fetch the data
if ($rollResult && $row = $rollResult->fetch_assoc()) {
    $COMProll = (float)$row['roll'];
    $COMPpitch = (float)$row['pitch'];
    $COMPyaw = (float)$row['yaw'];
} else {
    $COMProll = 0.0;
    $COMPpitch = 0.0;
    $COMPyaw = 0.0;
}

// Normalize angles
if ($COMPpitch > 180) {
    $COMPpitch = 0 - (360 - $COMPpitch);
}
if ($COMPyaw > 180) {
    $COMPyaw = 0 - (360 - $COMPyaw);
}

// Perform calculations
$skyyaw = -$COMPyaw * 2.5;
$skypitch = $COMPpitch * 2.5;
$skyyawR = $skyyaw + 2200;
$skypitchR = 1172 - $skypitch;

// Send JSON response
echo json_encode([
    'roll' => $COMProll,
    'pitch' => $COMPpitch,
    'yaw' => $COMPyaw,
    'skyyaw' => $skyyaw,
    'skypitch' => $skypitch
]);

?>
