<?php
$updates = [
    ['attribute' => 'x', 'value' => 10006371000],
    ['attribute' => 'y', 'value' => 10000000000],
    ['attribute' => 'z', 'value' => 10000000000],
    ['attribute' => 'delta_x', 'value' => 0],
    ['attribute' => 'delta_y', 'value' => 0],
    ['attribute' => 'delta_z', 'value' => 0],
    ['attribute' => 'dock', 'value' => 0],
    ['attribute' => 'stage1', 'value' => 1],
    ['attribute' => 'stage2', 'value' => 1],
    ['attribute' => 'stage3', 'value' => 1],
    ['attribute' => 's1fuel', 'value' => 2077000],
    ['attribute' => 's2fuel', 'value' => 456100],
    ['attribute' => 's3fuel', 'value' => 121480],
    ['attribute' => 'service_module', 'value' => 1],
    ['attribute' => 'fuel', 'value' => 40590],
    ['attribute' => 'RCS', 'value' => 3329],
    ['attribute' => 'pitch', 'value' => 0],
    ['attribute' => 'roll', 'value' => 0],
    ['attribute' => 'yaw', 'value' => 0],
    ['attribute' => 'pitch_rate', 'value' => 0],
    ['attribute' => 'roll_rate', 'value' => 0],
    ['attribute' => 'yaw_rate', 'value' => 0],
    ['attribute' => 'thrust', 'value' => 0],
    ['attribute' => 'parachutes_deployed', 'value' => 0],
    ['attribute' => 'escape', 'value' => 1],
    ['attribute' => 'ABORT', 'value' => 0],
    ['attribute' => 'escape_fuel', 'value' => 30],
    ['attribute' => 'heat_shield', 'value' => 1420000],
    ['attribute' => 'crash', 'value' => 0],
];

// Use '?' placeholders and bind parameters
$stmt = $conn->prepare("UPDATE command_module SET value = ? WHERE attribute = ?");
foreach ($updates as $update) {
    $stmt->bind_param('is', $update['value'], $update['attribute']);
    $stmt->execute();
}

$updates = [
    ['attribute' => 'x', 'value' => 10006370999],
    ['attribute' => 'y', 'value' => 10000000000],
    ['attribute' => 'z', 'value' => 10000000000],
    ['attribute' => 'delta_x', 'value' => 0],
    ['attribute' => 'delta_y', 'value' => 0],
    ['attribute' => 'delta_z', 'value' => 0],
    ['attribute' => 'descent', 'value' => 1],
    ['attribute' => 'map_x', 'value' => 0],
    ['attribute' => 'map_y', 'value' => 0],
    ['attribute' => 'map_zoom', 'value' => 1],
    ['attribute' => 'fuel', 'value' => 8242],
    ['attribute' => 'RCS', 'value' => 3329],
    ['attribute' => 'pitch', 'value' => 0],
    ['attribute' => 'roll', 'value' => 0],
    ['attribute' => 'yaw', 'value' => 0],
    ['attribute' => 'pitch_rate', 'value' => 0],
    ['attribute' => 'roll_rate', 'value' => 0],
    ['attribute' => 'yaw_rate', 'value' => 0],
    ['attribute' => 'thrust', 'value' => 0],
    ['attribute' => 'fuel_ascent', 'value' => 2378],
    ['attribute' => 'crash', 'value' => 0],
];

// Use '?' placeholders and bind parameters for telemetry updates
$stmt = $conn->prepare("UPDATE telemetry SET value = ? WHERE attribute = ?");
foreach ($updates as $update) {
    $stmt->bind_param('is', $update['value'], $update['attribute']);
    $stmt->execute();
}

// Update systems statuses and probabilities
$conn->query("UPDATE systems SET status = 1 WHERE id BETWEEN 1 AND 10");
$conn->query("UPDATE systems SET probability = 10000 WHERE id BETWEEN 1 AND 13");
$conn->query("UPDATE systems SET status = 0 WHERE id BETWEEN 11 AND 13");
$conn->query("UPDATE systems SET status = 0 WHERE id BETWEEN 21 AND 22");



// Define the values to update
$updates = [
    'roll' => 0,
    'pitch' => 0,
    'yaw' => 0,
    'roll_rate' => 0,
    'pitch_rate' =>0,
    'yaw_rate' => 0,
    'thrust' => 0,
    'seconds' => 0,
    'hold' => 0,
    'altitude' => 0,
    'delta_V' => 0,
    'delta_x' => 0,
    'delta_y' => 0,
    'delta_z' => 0,
    'terminate' => 0,
    'execute' => 0,
];

// Build the SQL query dynamically
$sql = "UPDATE CMguidance SET ";
$setParts = [];
foreach ($updates as $column => $value) {
    $setParts[] = "$column = ?";
}
$sql .= implode(", ", $setParts);
$sql .= " WHERE id = 1";

// Prepare the SQL statement
$stmt = $conn->prepare($sql);

// Bind the parameters
$types = str_repeat('i', count($updates)); // 'i' for integer
$stmt->bind_param($types, ...array_values($updates));

// Execute the query
$stmt->execute();


//reset mission clock
$updateQuery = "UPDATE mission_clock SET seconds = -900 WHERE id = 1";
if ($conn->query($updateQuery) === TRUE) {}
//reset componets of engine
$reset_query = "
    UPDATE EngineStatus
    SET 
        status = 'OFF',                -- Reset all components to OFF
        operational_status = 'OK',     -- Reset operational status to OK
        probability = 10000            -- Reset probability to the default value
";

$conn->query($reset_query);
$sql = "TRUNCATE TABLE CMtelemetry";

if ($conn->query($sql) === TRUE) {

} 

?>

