<?php
require_once 'db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pitch = isset($_POST['pitch']) ? floatval($_POST['pitch']) : 0;
    $yaw = isset($_POST['yaw']) ? floatval($_POST['yaw']) : 0;
    $thrust = isset($_POST['thrust']) ? floatval($_POST['thrust']) : 0;
    $mode = isset($_POST['mode']) ? $_POST['mode'] : 'AUTO';

    // Update telemetry values in the database
    $conn->query("UPDATE telemetry SET value = $pitch WHERE attribute = 'pitch'");
    $conn->query("UPDATE telemetry SET value = $yaw WHERE attribute = 'yaw'");
    $conn->query("UPDATE telemetry SET value = $thrust WHERE attribute = 'thrust'");
    $conn->query("UPDATE telemetry SET value = '$mode' WHERE attribute = 'mode'");

    echo json_encode(["status" => "success"]);
}
?>
