<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include 'db.php';
include 'variables.php';
include 'constants.php';
include 'coordinate.php';

// Compute roll decimal position
$rollOnesPlace = floor($rollCM) % 10;
$rollDecimalPart = $rollCM - floor($rollCM);
$rollDec = ($rollOnesPlace + $rollDecimalPart)-.1;
$rollPosition = (($rollDec / 10) * 300); // Map to 300px div

// Compute yaw decimal position
$yawOnesPlace = floor($yawCM) % 10;
$yawDecimalPart = $yawCM - floor($yawCM);
$yawDec = ($yawOnesPlace + $yawDecimalPart)-.1;
$yawPosition = ($yawDec / 10) * 300; // Map to 300px div

// Return JSON
echo json_encode([
    "rollCM" => $rollCM,
    "rollPosition" => $rollPosition,
    "yawCM" => $yawCM,
    "yawPosition" => $yawPosition
]);
?>
