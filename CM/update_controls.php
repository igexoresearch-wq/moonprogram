<?php
require 'db.php';

header('Content-Type: application/json'); // Ensure proper JSON output

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $adjustment = 0.1;
    $response = ["status" => "error", "message" => "Invalid action"];

    // Debugging log
    error_log("Received action: " . $action);

    if ($action === "trim") {
        // Reset pitch, yaw, and roll rates to zero
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute IN ('roll_rate', 'yaw_rate', 'pitch_rate')");

        // Fetch current pitch, roll, yaw values
        $query = $conn->query("SELECT attribute, value FROM command_module WHERE attribute IN ('pitch', 'roll', 'yaw')");
        $angles = [];
        while ($row = $query->fetch_assoc()) {
            $angles[$row['attribute']] = round($row['value'], 0);
        }

        // Update pitch, roll, yaw with rounded values
        if (!empty($angles)) {
            $conn->query("UPDATE command_module SET value = {$angles['pitch']} WHERE attribute = 'pitch'");
            $conn->query("UPDATE command_module SET value = {$angles['roll']} WHERE attribute = 'roll'");
            $conn->query("UPDATE command_module SET value = {$angles['yaw']} WHERE attribute = 'yaw'");
            $response = ["status" => "success", "message" => "Trim applied"];
        } else {
            $response = ["status" => "error", "message" => "Failed to fetch pitch, roll, yaw"];
        }
    }

    if ($action === "shut_off_engine") {
        if ($conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'")) {
            $response = ["status" => "success", "message" => "Engine shut off"];
        } else {
            $response = ["status" => "error", "message" => "Failed to shut off engine"];
        }
    }

    if ($action === "100P") {
        if ($conn->query("UPDATE command_module SET value = 100 WHERE attribute = 'thrust'")) {
            $response = ["status" => "success", "message" => "Thrust set to 100%"];
        } else {
            $response = ["status" => "error", "message" => "Failed to set thrust to 100%"];
        }
    }

    // Movement & thrust adjustments
    $queries = [
        "increase_pitch" => "UPDATE command_module SET value = value + $adjustment WHERE attribute = 'pitch_rate'",
        "decrease_pitch" => "UPDATE command_module SET value = value - $adjustment WHERE attribute = 'pitch_rate'",
        "increase_yaw" => "UPDATE command_module SET value = value + $adjustment WHERE attribute = 'yaw_rate'",
        "decrease_yaw" => "UPDATE command_module SET value = value - $adjustment WHERE attribute = 'yaw_rate'",
        "increase_roll" => "UPDATE command_module SET value = value + $adjustment WHERE attribute = 'roll_rate'",
        "decrease_roll" => "UPDATE command_module SET value = value - $adjustment WHERE attribute = 'roll_rate'",
        "increase_thrust" => "UPDATE command_module SET value = LEAST(100, value + 1) WHERE attribute = 'thrust'",
        "decrease_thrust" => "UPDATE command_module SET value = GREATEST(0, value - 1) WHERE attribute = 'thrust'",
    ];

    if (isset($queries[$action])) {
        if ($conn->query($queries[$action])) {
            $response = ["status" => "success"];
        } else {
            $response = ["status" => "error", "message" => "Failed to execute: " . $queries[$action]];
        }
    }

    echo json_encode($response);
    exit;
}
?>
