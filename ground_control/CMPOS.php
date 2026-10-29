<?php
// Constants
define("G", 6.67430e-11); // Gravitational constant (m^3 kg^-1 s^-2)
define("TIME_STEP", 1); // Time step for simulation (1 second)
define("TOLERANCE", 1e3); // 1 km tolerance for distance comparison

// Variables
$massEarth = 5.972e24; // Mass of Earth (kg)
$earthX = 10000000000; // Earth's X position
$earthY = 10000000000; // Earth's Y position
$earthZ = 10000000000; // Earth's Z position

// CM position and velocity
$xCM = $xCM;
$yCM = $yCM;
$zCM = $zCM;
$delta_xCM = $delta_xCM;
$delta_yCM = $delta_yCM;
$delta_zCM = $delta_zCM;

// Moon's position
$xMOON = $xMOON;
$yMOON = $yMOON;
$zMOON = $zMOON;

// Initial CM distance from Earth
$currentDistance = sqrt(pow($xCM - $earthX, 2) + pow($yCM - $earthY, 2) + pow($zCM - $earthZ, 2));

// Calculate Moon's current distance from Earth
$moonDistance = sqrt(pow($xMOON - $earthX, 2) + pow($yMOON - $earthY, 2) + pow($zMOON - $earthZ, 2));

// Moon's angular velocity (degrees per second)
$moonAngularVelocity = 360 / (27.32 * 24 * 3600); // 27.32 days

// Calculate Moon's initial angular position
$moonInitialAngle = atan2($yMOON - $earthY, $xMOON - $earthX) * (180 / M_PI);
if ($moonInitialAngle < 0) $moonInitialAngle += 360; // Normalize to [0, 360]

// Simulation time
$simulationTime = 0;

// Simulation loop
while ($currentDistance < $moonDistance) {
    // Calculate distance from Earth
    $currentDistance = sqrt(pow($xCM - $earthX, 2) + pow($yCM - $earthY, 2) + pow($zCM - $earthZ, 2));

    // Calculate acceleration components
    $distanceCube = pow($currentDistance, 3); // Avoid repeated computation
    $acceleration_x = -G * $massEarth * ($xCM - $earthX) / $distanceCube;
    $acceleration_y = -G * $massEarth * ($yCM - $earthY) / $distanceCube;
    $acceleration_z = -G * $massEarth * ($zCM - $earthZ) / $distanceCube;

    // Update velocity components
    $delta_xCM += $acceleration_x * TIME_STEP;
    $delta_yCM += $acceleration_y * TIME_STEP;
    $delta_zCM += $acceleration_z * TIME_STEP;

    // Update position components
    $xCM += $delta_xCM * TIME_STEP;
    $yCM += $delta_yCM * TIME_STEP;
    $zCM += $delta_zCM * TIME_STEP;

    // Increment simulation time
    $simulationTime += TIME_STEP;

    // Prevent infinite loop
    if ($simulationTime > 120 * 24 * 3600) { // 30 days max
        echo "Simulation exceeded maximum time.\n";
        break;
    }
}

// Calculate Moon's angular position at crossing time
$moonFinalAngle = fmod($moonInitialAngle + ($moonAngularVelocity * $simulationTime), 360);
if ($moonFinalAngle < 0) $moonFinalAngle += 360; // Normalize

// Output results
echo "Time for CM to reach Moon's distance: " . gmdate("H:i:s", (int)$simulationTime) . " (seconds: $simulationTime)\n";
echo "Moon's angular position at that time: " . number_format($moonFinalAngle, 2) . "¡Æ\n";
?>