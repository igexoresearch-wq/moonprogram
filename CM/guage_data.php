<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
include 'db.php';
include 'variables.php';
include 'constants.php';
include 'coordinate.php';

// Default values (Replace these with actual database queries)
$xCM = $xCM ?? 0; $yCM = $yCM ?? 0; $zCM = $zCM ?? 0;
$xLM = $xLM ?? 0; $yLM = $yLM ?? 0; $zLM = $zLM ?? 0;
$delta_xCM = $delta_xCM ?? 0; $delta_yCM = $delta_yCM ?? 0; $delta_zCM = $delta_zCM ?? 0;
$delta_xLM = $delta_xLM ?? 0; $delta_yLM = $delta_yLM ?? 0; $delta_zLM = $delta_zLM ?? 0;

// Retrieve mission clock time (seconds) from the database
$sql = "SELECT seconds FROM mission_clock WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentTime = (int)$row["seconds"]; // Use mission clock instead of system time
} else {
    die(json_encode(["error" => "Mission clock data not found."]));
}

// Compute distance
$rx = $xCM - $xLM;
$ry = $yCM - $yLM;
$rz = $zCM - $zLM;
$objectDistance = sqrt(pow($rx, 2) + pow($ry, 2) + pow($rz, 2));

$filename = "/tmp/distance_data.json"; // Temp file to store distance & mission clock timestamp

// Load previous distance records if available
if (file_exists($filename)) {
    $previousData = json_decode(file_get_contents($filename), true);
} else {
    // Initialize file with three identical values
    $previousData = [
        ["distance" => $objectDistance, "timestamp" => $currentTime],
        ["distance" => $objectDistance, "timestamp" => $currentTime],
        ["distance" => $objectDistance, "timestamp" => $currentTime]
    ];
}

// Ensure we have at least three previous records
if (count($previousData) < 3) {
    $previousData[] = ["distance" => $objectDistance, "timestamp" => $currentTime];
}

// Use the **third-oldest and second-oldest** values for smoothing
$oldestData = $previousData[0];  // Third-oldest record
$middleData = $previousData[1];  // Second-oldest record
$latestData = $previousData[2];  // Most recent before this one

$oldestDistance = $oldestData["distance"];
$middleDistance = $middleData["distance"];
$oldestTime = $oldestData["timestamp"];
$middleTime = $middleData["timestamp"];

// Compute the **average** of the two older distances
$avgOldDistance = ($oldestDistance + $middleDistance) / 2;
$avgOldTime = ($oldestTime + $middleTime) / 2;

// Compute time difference (ensure no division by zero)
$timeDiff = $currentTime - $avgOldTime;
if ($timeDiff > 0) {
    $velocity = ($objectDistance - $avgOldDistance) / $timeDiff; // m/s
} else {
    $velocity = 0;
}

// Update stored distances (keep only the last 3)
$previousData = [
    $middleData,  // Move the second-oldest data to the first slot
    $latestData,  // Move the most recent data to the second slot
    ["distance" => $objectDistance, "timestamp" => $currentTime] // Store new data in the third slot
];

// Save back to file
file_put_contents($filename, json_encode($previousData));



// Compute relative velocity
$vx = $delta_xCM - $delta_xLM;
$vy = $delta_yCM - $delta_yLM;
$vz = $delta_zCM - $delta_zLM;
$relativeSpeed = sqrt(pow($vx, 2) + pow($vy, 2) + pow($vz, 2));

// Compute radial velocity (dot product method)
$positionMagnitude = sqrt(pow($rx, 2) + pow($ry, 2) + pow($rz, 2)); 
$dot_product = ($rx * $vx) + ($ry * $vy) + ($rz * $vz);
$radialVelocity = ($positionMagnitude > 0) ? ($dot_product / $positionMagnitude) : 0;  

// Compute gauge percentages
function computeGaugePercentage($metric, $min, $max) {
    return round((($metric - $min) / ($max - $min)) * 100);
}
if ($objectDistance > 400000){$objectDistance = 0; $velocity = 0; }
// Dynamically adjust the distance gauge scale
if ($objectDistance <= 10) {
    $distanceGauge = computeGaugePercentage($objectDistance, 0, 10);
    $distanceTicks = range(0, 10, 1);
} elseif ($objectDistance <= 100) {
    $distanceGauge = computeGaugePercentage($objectDistance, 0, 100);
    $distanceTicks = range(0, 100, 10);
} elseif ($objectDistance <= 1000) {
    $distanceGauge = computeGaugePercentage($objectDistance, 0, 1000);
    $distanceTicks = range(0, 1000, 100);
} elseif ($objectDistance <= 10000) {
    $distanceGauge = computeGaugePercentage($objectDistance, 0, 10000);
    $distanceTicks = range(0, 10000, 1000);
} else {
    $distanceGauge = computeGaugePercentage($objectDistance, 0, 400000);
    $distanceTicks = range(0, 400000, 40000);
}

// Velocity gauges
if ($velocity > 5){$velocity2 = 5;}
elseif ($velocity < -5){$velocity2 = -5;}
else {$velocity2 =  $velocity;}
$speedGauge = computeGaugePercentage($velocity2, -5, 5);
$speedTicks = range(-5, 5, 1);

$scaledGauge = computeGaugePercentage($velocity, -200, 200);
$scaledTicks = range(-200, 200, 40);

// Ensure JSON is valid
$jsonData = [
    "distanceGauge" => $distanceGauge ?? 0,
    "speedGauge" => $speedGauge ?? 0,
    "scaledGauge" => $scaledGauge ?? 0,
    "distanceTicks" => $distanceTicks ?? [],
    "speedTicks" => $speedTicks ?? [],
    "scaledTicks" => $scaledTicks ?? []
];

echo json_encode($jsonData);
?>

