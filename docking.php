<?php
// Database connection
$servername = "localhost";
$username = "victorywebproductions";
$password = "CHANGE_ME";
$dbname = "moonland";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch the 'value' for attributes x, y, z, delta_x, delta_y, delta_z from the command_module table
$sql_command_module = "SELECT attribute, value FROM command_module WHERE attribute IN ('x', 'y', 'z', 'delta_x', 'delta_y', 'delta_z')";
$result_command_module = $conn->query($sql_command_module);

// Initialize variables for command_module
$command_module_x = $command_module_y = $command_module_z = $command_module_delta_x = $command_module_delta_y = $command_module_delta_z = 0;

// Check if query returns results and store values
if ($result_command_module->num_rows > 0) {
    while ($row_command_module = $result_command_module->fetch_assoc()) {
        switch ($row_command_module['attribute']) {
            case 'x':
                $command_module_x = $row_command_module['value'];
                break;
            case 'y':
                $command_module_y = $row_command_module['value'];
                break;
            case 'z':
                $command_module_z = $row_command_module['value'];
                break;
            case 'delta_x':
                $command_module_delta_x = $row_command_module['value'];
                break;
            case 'delta_y':
                $command_module_delta_y = $row_command_module['value'];
                break;
            case 'delta_z':
                $command_module_delta_z = $row_command_module['value'];
                break;
        }
    }
} else {
    echo "No results found in command_module.";
}

// Query to fetch the 'value' for attributes x, y, z, delta_x, delta_y, delta_z from the telemetry table
$sql_telemetry = "SELECT attribute, value FROM telemetry WHERE attribute IN ('x', 'y', 'z', 'delta_x', 'delta_y', 'delta_z')";
$result_telemetry = $conn->query($sql_telemetry);

// Initialize variables for telemetry
$telemetry_x = $telemetry_y = $telemetry_z = $telemetry_delta_x = $telemetry_delta_y = $telemetry_delta_z = 0;

// Check if query returns results and store values
if ($result_telemetry->num_rows > 0) {
    while ($row_telemetry = $result_telemetry->fetch_assoc()) {
        switch ($row_telemetry['attribute']) {
            case 'x':
                $telemetry_x = $row_telemetry['value'];
                break;
            case 'y':
                $telemetry_y = $row_telemetry['value'];
                break;
            case 'z':
                $telemetry_z = $row_telemetry['value'];
                break;
            case 'delta_x':
                $telemetry_delta_x = $row_telemetry['value'];
                break;
            case 'delta_y':
                $telemetry_delta_y = $row_telemetry['value'];
                break;
            case 'delta_z':
                $telemetry_delta_z = $row_telemetry['value'];
                break;
        }
    }
} else {
    echo "No results found in telemetry.";
}

// Close the database connection
$conn->close();

// Calculate the distance between the two sets of coordinates using the distance formula
$distance = sqrt(
    pow($telemetry_x - $command_module_x, 2) +
    pow($telemetry_y - $command_module_y, 2) +
    pow($telemetry_z - $command_module_z, 2)
);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Background Image Example</title>
    <style>
        /* Apply the background image to the whole page */
        body {
          
   
    overflow: hidden; /* Disable scrolling */
        margin: 0;
            height: 200vh;  /* Full height of the viewport */
            background-image: url('moon.jpg'); /* Replace with your image's URL */
            background-size: cover; /* Ensures the image covers the whole screen */
            background-position: center center; /* Centers the image */
            background-repeat: no-repeat; /* Prevents the image from repeating */
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;  /* Text color for better visibility */
            font-family: Arial, sans-serif;
        }

        /* Styling for the content */
        .content {
            text-align: center;
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.5);  /* Transparent background for content */
            border-radius: 10px;
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="content">
   
    </div>
</body>
</html>
