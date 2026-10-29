<?php

// Fetch values from 'command_module'
$result = $conn->query("SELECT attribute, value FROM command_module");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $variableName = $row['attribute'] . 'CM';
        $$variableName = $row['value']; // Dynamically create variable
    }
}
$airspeed = sqrt(($delta_xCM * $delta_xCM) + ($delta_yCM * $delta_yCM) + ($delta_zCM * $delta_zCM));
// Fetch values from 'systems'
$result = $conn->query("SELECT system, status FROM systems");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $variableName = $row['system'] . 'SYS';
        $$variableName = $row['status']; // Dynamically create variable
    }
}

// Fetch values from 'telemetry'
$result = $conn->query("SELECT attribute, value FROM telemetry");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $variableName = $row['attribute'] . 'LM';
        $$variableName = $row['value']; // Dynamically create variable
    }
}

// Fetch values from 'guidance'
$result = $conn->query("SELECT noun, verb FROM guidance");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $variableName = $row['noun'] . 'AGC';
        $$variableName = $row['verb']; // Dynamically create variable
    }
}

// Fetch values from 'moon'
$result = $conn->query("SELECT attribute, value FROM moon");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $variableName = $row['attribute'] . 'MOON';
        $$variableName = $row['value']; // Dynamically create variable
    }
}




// Array of columns to query
$columns = [
    'pitch', 'roll', 'yaw', 'pitch_rate', 'roll_rate',
    'yaw_rate', 'thrust', 'seconds', 'altitude', 'delta_V',
    'delta_x', 'delta_y', 'delta_z'
];

// Loop through each column, fetch the value, and assign to a variable
foreach ($columns as $column) {
    $query = "SELECT $column FROM CMguidance WHERE id = 1";
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        ${"IMU_" . $column} = $row[$column];
    } else {
        ${"IMU_" . $column} = null; // Default to null if query fails
    }
}
// Array of columns to query
$columns = [
    'pitch', 'roll', 'yaw', 'pitch_rate', 'roll_rate',
    'yaw_rate', 'thrust', 'seconds', 'altitude', 'delta_V',
    'delta_x', 'delta_y', 'delta_z'
];

// Loop through each column, fetch the value, and assign to a variable
foreach ($columns as $column) {
    $query = "SELECT $column FROM CMguidance WHERE id = 2";
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        ${"IMUHOLD_" . $column} = $row[$column];
    } else {
        ${"IMUHOLD_" . $column} = null; // Default to null if query fails
    }
}
// Fetch values from 'command_module systems'
$result = $conn->query("SELECT id, status FROM csm_systems");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $variableName =  'CSM_SYS_' . $row['id'];
        $$variableName = $row['status']; // Dynamically create variable
    }
}

// Fetch values from 'switches'
$result = $conn->query("SELECT name, status FROM CMswitches");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $variableName = $row['name'] . '_SWITCH';
        $$variableName = $row['status']; // Dynamically create variable
    }
}

?>
