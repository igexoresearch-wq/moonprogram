<?php
require 'db.php'; // Ensure database connection

// Verb-to-Column Mapping
$verb_to_column = [
    1 => 'pitch', 2 => 'roll', 3 => 'yaw', 4 => 'pitch_rate', 5 => 'roll_rate',
    6 => 'yaw_rate', 7 => 'thrust', 8 => 'seconds', 9 => 'altitude', 10 => 'delta_V',
    11 => 'delta_x', 12 => 'orbit_angle', 13 => 'velocity'
];

// Fetch values for ID 2 and ID 3
$query = "SELECT * FROM CMguidance WHERE id IN (2,3,4,5)";
$result = $conn->query($query);

$guidance_data = [];
while ($row = $result->fetch_assoc()) {
    $guidance_data[$row['id']] = $row;
}

// Ensure `guidance_data` has expected keys before using them
$guidance_data[2] = $guidance_data[2] ?? [];
$guidance_data[3] = $guidance_data[3] ?? [];
$guidance_data[4] = $guidance_data[4] ?? [];
$guidance_data[5] = $guidance_data[5] ?? [];

// Get the hold value from ID 3
$hold_value = isset($guidance_data[3]['hold']) ? (int) $guidance_data[3]['hold'] : 0;

// Determine which column to fetch based on the hold value
$display_column = isset($verb_to_column[$hold_value]) ? $verb_to_column[$hold_value] : null;

// Fetch values safely, ensuring defaults exist
$value_id2 = ($display_column && isset($guidance_data[2][$display_column])) ? $guidance_data[2][$display_column] : '0000000';
$value_id3 = ($display_column && isset($guidance_data[3][$display_column])) ? $guidance_data[3][$display_column] : '0000000';
$delta_V_value = isset($guidance_data[2]['velocity']) ? $guidance_data[2]['velocity'] : '0000000';

// Fetch nonmonic safely
$nonmonic_display = "0000000";
if ($value_id3 > 0) {
    $nonmonic_query = "SELECT nonmonic FROM CMguidance WHERE id = $value_id3 LIMIT 1";
    $nonmonic_result = $conn->query($nonmonic_query);
    if ($nonmonic_row = $nonmonic_result->fetch_assoc()) {
        $nonmonic_display = isset($nonmonic_row['nonmonic']) ? $nonmonic_row['nonmonic'] : "0000000";
    }
}

// Prepare response JSON
$response = [
    "noun" => isset($guidance_data[4]['hold']) ? (string) $guidance_data[4]['hold'] : "0",
    "verb" => isset($guidance_data[5]['hold']) ? (string) $guidance_data[5]['hold'] : "0",
    "value" => (string) $value_id2,
    "nonmonic" => (string) $nonmonic_display,
    "data_value" => (string) $delta_V_value
];

echo json_encode($response);
?>
