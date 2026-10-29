<?php
header('Content-Type: application/json');
require_once 'db.php';

require_once 'variables.php';
require_once 'constants.php';
// Check if LM should be included
$includeLM = ($dockCM != 1 && $stage3CM != 1);

$data = [
    "pitchCM" => $pitchCM, 
    "rollCM" => $rollCM, 
    "yawCM" => $yawCM, 
    "xCM" => $xCM, 
    "yCM" => $yCM, 
    "zCM" => $zCM, 
    "xMOON" => $xMOON, 
    "yMOON" => $yMOON, 
    "zMOON" => $zMOON, 
    "dockCM" => $dockCM, 
    "stage3CM" => $stage3CM, 
    "altitude" => $altitude
];

// Only include LM data if it's undocked
if ($includeLM) {
    $data["xLM"] = $xLM;
    $data["yLM"] = $yLM;
    $data["zLM"] = $zLM;
}

echo json_encode($data);
?>
