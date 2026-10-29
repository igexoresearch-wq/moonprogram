<?php
include 'db.php'; // Ensure this file contains your database connection
include 'variables.php';
include 'constants.php';

// Define the attribute to update
$attribute_name = "factor"; // Change this to the actual attribute

// Fetch the current value from the database
$query = "SELECT value FROM command_module WHERE attribute = '$attribute_name'";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$current_value = isset($row['value']) ? floatval($row['value']) : 0.0000;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Angular Momentum</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .button-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .button {
            padding: 10px;
            font-size: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h2>Current Factor Value: <span id="value_display"><?php echo number_format($current_value, 3); ?></span></h2>

<div class="button-container">
    <button class="button" onclick="updateValue(-0.0001)">➖ Decrease</button>
    <button class="button" onclick="updateValue(0.0001)">➕ Increase</button>
</div>
<div class="button-container">
    <button class="button" onclick="updateValue(-0.001)">➖ Decrease</button>
    <button class="button" onclick="updateValue(0.001)">➕ Increase</button>
</div>
<div class="button-container">
    <button class="button" onclick="updateValue(-0.01)">➖ Decrease</button>
    <button class="button" onclick="updateValue(0.01)">➕ Increase</button>
</div>

<h2>Specific Angular Momentum: <span id="angular_momentum">Loading...</span></h2>

<script>
function updateValue(change) {
    $.post("update_value.php", { change: change }, function(response) {
        $("#value_display").text(response);
    });
}

// Function to fetch and update angular momentum dynamically
function fetchAngularMomentum() {
    $.get("calculate_angular_momentum.php", function(response) {
        $("#angular_momentum").text(response);
    });
}

// Update angular momentum every second
setInterval(fetchAngularMomentum, 1000);
fetchAngularMomentum(); // Run immediately on page load
</script>

</body>
</html>