<?php

// Retrieve values from the command_module table
$query = "SELECT attribute, value FROM command_module";
$result = $conn->query($query);

$telemetry = [];
while ($row = $result->fetch_assoc()) {
    $telemetry[$row['attribute']] = $row['value'];
}

// Adjust position relative to Earth's center
$rxx = $xCM - 10000000000;
$ryx = $yCM - 10000000000;
$rzx = $zCM - 10000000000;

// Assign velocity components
$vxx = $delta_xCM;
$vyx = $delta_yCM;
$vzx = $delta_zCM;

// Compute specific angular momentum components (cross product r × v)
$hxx = ($ryx * $vzx) - ($rzx * $vyx);
$hyx = ($rzx * $vxx) - ($rxx * $vzx);
$hzx = ($rxx * $vyx) - ($ryx * $vxx);

// Compute total magnitude of specific angular momentum
$h_total = sqrt(($hxx ** 2) + ($hyx ** 2) + ($hzx ** 2));

// Get current timestamp
$current_timestamp = date('Y-m-d H:i:s');

// Retrieve the latest timestamp from CMtelemetry
$last_timestamp_query = "SELECT timestamp FROM CMtelemetry ORDER BY timestamp DESC LIMIT 1";
$last_timestamp_result = $conn->query($last_timestamp_query);

if ($last_timestamp_result->num_rows > 0) {
    $last_row = $last_timestamp_result->fetch_assoc();
    $last_timestamp = $last_row['timestamp'];

    // Calculate the time difference in seconds
    $timediff = strtotime($current_timestamp) - strtotime($last_timestamp);
} else {
    // If no previous record exists, set timediff to 0
    $timediff = 0;
}

// Prepare SQL statement to insert telemetry data with timestamp and time difference
$sql = "INSERT INTO CMtelemetry (
    stage1, stage2, stage3, s1fuel, s2fuel, s3fuel, fuel, pitch, roll, yaw, 
    x, y, z, thrust, RCS, parachutes_deployed, ABORT, staging, 
    temp, heat_shield, delta_x, delta_y, delta_z, dock, velocity, AM, timestamp, timediff
) VALUES (
    '{$telemetry['stage1']}', '{$telemetry['stage2']}', '{$telemetry['stage3']}', 
    '{$telemetry['s1fuel']}', '{$telemetry['s2fuel']}', '{$telemetry['s3fuel']}', '{$telemetry['fuel']}', 
    '{$telemetry['pitch']}', '{$telemetry['roll']}', '{$telemetry['yaw']}', 
    '{$telemetry['x']}', '{$telemetry['y']}', '{$telemetry['z']}', 
    '{$current_thrust}', '{$telemetry['RCS']}', '{$telemetry['parachutes_deployed']}', 
    '{$telemetry['ABORT']}', '{$telemetry['staging']}', '{$temp}', 
    '{$telemetry['heat_shield']}', '{$telemetry['delta_x']}', '{$telemetry['delta_y']}', 
    '{$telemetry['delta_z']}', '{$telemetry['dock']}', '{$IMU_delta_V}', '{$h_total}', '{$current_timestamp}', '{$sleep_time}'
)";

// Execute the query
if ($conn->query($sql) === TRUE) {
    // Successful insertion
} else {
    // Handle the error
}

?>

