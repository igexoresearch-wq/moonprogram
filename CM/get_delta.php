<?php
header('Content-Type: application/json');

// Connect to your database
require 'db.php';
include 'variables.php';
include 'constants.php';


// Assume these variables are already set in your system
echo json_encode([
    'delta_xCM' => $delta_xCM,
    'delta_yCM' => $delta_yCM,
    'delta_zCM' => $delta_zCM,
    'delta_xLM' => $delta_xLM,
    'delta_yLM' => $delta_yLM,
    'delta_zLM' => $delta_zLM
]);
?>