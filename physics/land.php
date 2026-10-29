<?php
// Constants
$moonRadius = 1737100; // Moon radius in meters
$earthRadius = 6371000; // Earth radius in meters
$cmSafeLandingSpeed = 12; // Safe landing speed for CM (m/s)
$lmSafeLandingSpeed = 3;  // Safe landing speed for LM (m/s)

// Handle CM Landing/Crash on Earth or Moon
$bodyX = ($altitudeCM <= $altitudemoon) ? $xMOON : 10000000000;
$bodyY = ($altitudeCM <= $altitudemoon) ? $yMOON : 10000000000;
$bodyZ = ($altitudeCM <= $altitudemoon) ? $zMOON : 10000000000;
$bodyRadius = ($altitudeCM <= $altitudemoon) ? $moonRadius : $earthRadius;
$safeLandingSpeed = $cmSafeLandingSpeed;

// Calculate distance to the body center
$distanceToBodyCenter = sqrt(pow($xCM - $bodyX, 2) + pow($yCM - $bodyY, 2) + pow($zCM - $bodyZ, 2));

// Check if the CM has landed or crashed
if ($distanceToBodyCenter <= $bodyRadius) {
    // Calculate velocity magnitude (speed)
    $velocity = sqrt(pow($delta_xCM, 2) + pow($delta_yCM, 2) + pow($delta_zCM, 2));

    // Determine crash or landing
    $crashCM = $velocity > $safeLandingSpeed ? 1 : 0;

    // Match velocities with the body to stay stationary on its surface
    $new_delta_xCM = 0;
    $new_delta_yCM = 0;
    $new_delta_zCM = 0;

    // Adjust position to stay on the surface
    $surfaceFactor = $bodyRadius / $distanceToBodyCenter; // Normalize to the surface
    $xCM = $bodyX + ($xCM - $bodyX) * $surfaceFactor;
    $yCM = $bodyY + ($yCM - $bodyY) * $surfaceFactor;
    $zCM = $bodyZ + ($zCM - $bodyZ) * $surfaceFactor;

    // Update database for CM
    $conn->query("UPDATE command_module SET value = $new_delta_xCM WHERE attribute = 'delta_x'");
    $conn->query("UPDATE command_module SET value = $new_delta_yCM WHERE attribute = 'delta_y'");
    $conn->query("UPDATE command_module SET value = $new_delta_zCM WHERE attribute = 'delta_z'");
    $conn->query("UPDATE command_module SET value = $xCM WHERE attribute = 'x'");
    $conn->query("UPDATE command_module SET value = $yCM WHERE attribute = 'y'");
    $conn->query("UPDATE command_module SET value = $zCM WHERE attribute = 'z'");
    $conn->query("UPDATE command_module SET value = $crashCM WHERE attribute = 'crash'");
}

// Handle LM Landing/Crash on Earth or Moon
$bodyX = ($altitudeLM <= $altitudemoon) ? $xMOON : 10000000000;
$bodyY = ($altitudeLM <= $altitudemoon) ? $yMOON : 10000000000;
$bodyZ = ($altitudeLM <= $altitudemoon) ? $zMOON : 10000000000;
$bodyRadius = ($altitudeLM <= $altitudemoon) ? $moonRadius : $earthRadius;
$safeLandingSpeed = $lmSafeLandingSpeed;

// Calculate distance to the body center
$distanceToBodyCenter = sqrt(pow($xLM - $bodyX, 2) + pow($yLM - $bodyY, 2) + pow($zLM - $bodyZ, 2));

// Check if the LM has landed or crashed
if ($distanceToBodyCenter <= $bodyRadius) {
    // Calculate velocity magnitude (speed)
    $velocity = sqrt(pow($delta_xLM, 2) + pow($delta_yLM, 2) + pow($delta_zLM, 2));

    // Determine crash or landing
    $crashLM = $velocity > $safeLandingSpeed ? 1 : 0;

    // Match velocities with the body to stay stationary on its surface
    $new_delta_xLM = 0;
    $new_delta_yLM = 0;
    $new_delta_zLM = 0;

    // Adjust position to stay on the surface
    $surfaceFactor = $bodyRadius / $distanceToBodyCenter; // Normalize to the surface
    $xLM = $bodyX + ($xLM - $bodyX) * $surfaceFactor;
    $yLM = $bodyY + ($yLM - $bodyY) * $surfaceFactor;
    $zLM = $bodyZ + ($zLM - $bodyZ) * $surfaceFactor;

    // Update database for LM
    $conn->query("UPDATE telemetry SET value = $new_delta_xLM WHERE attribute = 'delta_x'");
    $conn->query("UPDATE telemetry SET value = $new_delta_yLM WHERE attribute = 'delta_y'");
    $conn->query("UPDATE telemetry SET value = $new_delta_zLM WHERE attribute = 'delta_z'");
    $conn->query("UPDATE telemetry SET value = $xLM WHERE attribute = 'x'");
    $conn->query("UPDATE telemetry SET value = $yLM WHERE attribute = 'y'");
    $conn->query("UPDATE telemetry SET value = $zLM WHERE attribute = 'z'");
    $conn->query("UPDATE telemetry SET value = $crashLM WHERE attribute = 'crash'");
}

// Output results for debugging
if (isset($crashCM)) {
    echo $crashCM === 0 ? "CM has landed safely!" : "CM has crashed!";
}
if (isset($crashLM)) {
    echo $crashLM === 0 ? "LM has landed safely!" : "LM has crashed!";
}
?>
