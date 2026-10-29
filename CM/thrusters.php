<?php
header('Content-Type: application/json');

// Include database connection
include 'db.php'; // Adjust this to your actual DB connection file

// Ensure POST request is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['thruster'])) {
    $thruster = $_POST['thruster']; // Get thruster type (axis, rever, etc.)
    
    // Define thrust values
    $thrustValues = [
        "axis" => 0.05,
        "rever" => -0.05,
        "axis2" => 0.5,
        "rever2" => -0.5,
        "axis3" => 5.0,
        "rever3" => -5.0
    ];

    // Check if the thruster key exists
    if (isset($thrustValues[$thruster])) {
        $thrustValue = $thrustValues[$thruster];

        // Update the database
        $conn->query("UPDATE command_module SET value = $thrustValue WHERE attribute = 'thrusters'");

        // Send response with the sound effect
        echo json_encode([
            "status" => "success",
            "message" => "Thrust applied: $thrustValue",
            "sound" => "poof-of-smoke-87381.mp3"
        ]);
        exit();
    }
}

// If invalid request
echo json_encode(["status" => "error", "message" => "Invalid request"]);
exit();
?>
