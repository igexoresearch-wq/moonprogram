<?php
include 'db.php';


// Starting ID and ending ID
$start_id = 100;
$end_id = 153;

// Fetch the initial pitch_degrees value for ID 15
$result = $conn->query("SELECT pitch_degrees FROM Apollo11LaunchTelemetry WHERE id = $start_id");
if ($result && $row = $result->fetch_assoc()) {
    $current_value = floatval($row['pitch_degrees']); // Starting value

    // Iteratively update pitch_degrees for subsequent rows
    for ($id = $start_id + 1; $id <= $end_id; $id++) {
        $current_value -= 0.94; // Subtract 0.47 from the previous value

        // Update the next row with the calculated value
        $conn->query("UPDATE Apollo11LaunchTelemetry SET pitch_degrees = $current_value WHERE id = $id");
    }
} else {
    echo "Error: Could not fetch initial value for ID $start_id.";
}

// Close the database connection
$conn->close();

echo "Pitch degrees updated successfully from ID $start_id to $end_id.";
?>

