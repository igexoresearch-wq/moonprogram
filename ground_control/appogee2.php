<?php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Earth's position
$earthX = 10000000000;
$earthY = 10000000000;
$earthZ = 10000000000;

// Moon's orbital parameters
$moonOrbitalRadius = 384400000; // Moon's average distance from Earth (meters)
$moonOrbitalPeriod = 27.32 * 86400; // Moon's orbital period in seconds
$moonAngularVelocity = 2 * pi() / $moonOrbitalPeriod; // Radians per second




// Determine whether to use Earth's or Moon's gravity

    // Moon as primary body
    $primaryX = $xMOON;
    $primaryY = $yMOON;
    $primaryZ = $zMOON;
    define('PRIMARY_RADIUS', 1737100); // Moon's radius in meters
    define('MU', 4.902801e12); // Moon's gravitational parameter (m^3/s^2)

    // Adjust velocities relative to the Moon
    $delta_xCM -= $delta_xMOON;
    $delta_yCM -= $delta_yMOON;
    $delta_zCM -= $delta_zMOON;
    $delta_xLM -= $delta_xMOON;
    $delta_yLM -= $delta_yMOON;
    $delta_zLM -= $delta_zMOON;


// Orbital parameter calculation function
function calculateOrbitalParameters($x, $y, $z, $delta_x, $delta_y, $delta_z, $primaryX, $primaryY, $primaryZ) {
    // Position vector relative to the primary body
    $r_vec = [$x - $primaryX, $y - $primaryY, $z - $primaryZ];
    $r_mag = sqrt(array_sum(array_map(fn($v) => $v * $v, $r_vec)));

    // Velocity vector
    $v_vec = [$delta_x, $delta_y, $delta_z];
    $v_mag = sqrt(array_sum(array_map(fn($v) => $v * $v, $v_vec)));

    // Specific angular momentum vector
    $h_vec = [
        $r_vec[1] * $v_vec[2] - $r_vec[2] * $v_vec[1],
        $r_vec[2] * $v_vec[0] - $r_vec[0] * $v_vec[2],
        $r_vec[0] * $v_vec[1] - $r_vec[1] * $v_vec[0]
    ];
    $h_mag = sqrt(array_sum(array_map(fn($v) => $v * $v, $h_vec)));

    // Eccentricity vector
    $e_vec = [];
    foreach ([0, 1, 2] as $i) {
        $e_vec[$i] = ($v_mag ** 2 - MU / $r_mag) * $r_vec[$i] / MU - array_sum(array_map(fn($v, $r) => $v * $r, $v_vec, $r_vec)) * $v_vec[$i] / MU;
    }
    $e_mag = sqrt(array_sum(array_map(fn($v) => $v * $v, $e_vec)));

    // Semi-major axis
    $a = $h_mag ** 2 / (MU * (1 - $e_mag ** 2));

    // True anomaly
    $cos_nu = array_sum(array_map(fn($r, $e) => $r * $e, $r_vec, $e_vec)) / ($r_mag * $e_mag);
    $sin_nu = array_sum(array_map(fn($r, $h) => $r * $h, $r_vec, $h_vec)) / ($r_mag * $h_mag);
    $true_anomaly = atan2($sin_nu, $cos_nu) * 180 / pi();

    // Normalize angles to [0, 360]
    $true_anomaly = fmod($true_anomaly + 360, 360);

    // Apogee and perigee altitudes
    $apogee_altitude = $a * (1 + $e_mag) - PRIMARY_RADIUS;
    $perigee_altitude = $a * (1 - $e_mag) - PRIMARY_RADIUS;

    // Angles of apogee and perigee
    $angle_perigee = fmod(atan2($e_vec[1], $e_vec[0]) * 180 / pi() + 360, 360);
    $angle_apogee = fmod($angle_perigee + 180, 360);

    return [
        'apogee' => $apogee_altitude,
        'perigee' => $perigee_altitude,
        'true_anomaly' => $true_anomaly,
        'eccentricity' => $e_mag,
        'angle_apogee' => $angle_apogee,
        'angle_perigee' => $angle_perigee,
    ];
}

// Results for LM
$lmResults = calculateOrbitalParameters($xLM, $yLM, $zLM, $delta_xLM, $delta_yLM, $delta_zLM, $primaryX, $primaryY, $primaryZ);

// Results for CM
$cmResults = calculateOrbitalParameters($xCM, $yCM, $zCM, $delta_xCM, $delta_yCM, $delta_zCM, $primaryX, $primaryY, $primaryZ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbital Parameters</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
        }
        th {
            text-align: right;
        }
        td {
            text-align: left;
        }
    </style>
</head>
<body>
<h1>Orbital Parameters</h1>

<h2>Lunar Module (LM)</h2>
<table>
    <tr>
        <th>Apogee:</th>
        <td><?php echo number_format($lmResults['apogee'], 2); ?> meters</td>
    </tr>
    <tr>
        <th>Perigee:</th>
        <td><?php echo number_format($lmResults['perigee'], 2); ?> meters</td>
    </tr>
    <tr>
        <th>True Anomaly:</th>
        <td><?php echo number_format($lmResults['true_anomaly'], 2); ?> degrees</td>
    </tr>
    <tr>
        <th>Angle at Apogee:</th>
        <td><?php echo number_format($lmResults['angle_apogee'], 2); ?> degrees</td>
    </tr>
    <tr>
        <th>Angle at Perigee:</th>
        <td><?php echo number_format($lmResults['angle_perigee'], 2); ?> degrees</td>
    </tr>
</table>

<h2>Command Module (CM)</h2>
<table>
    <tr>
        <th>Apogee:</th>
        <td><?php echo number_format($cmResults['apogee'], 2); ?> meters</td>
    </tr>
    <tr>
        <th>Perigee:</th>
        <td><?php echo number_format($cmResults['perigee'], 2); ?> meters</td>
    </tr>
    <tr>
        <th>True Anomaly:</th>
        <td><?php echo number_format($cmResults['true_anomaly'], 2); ?> degrees</td>
    </tr>
    <tr>
        <th>Angle at Apogee:</th>
        <td><?php echo number_format($cmResults['angle_apogee'], 2); ?> degrees</td>
    </tr>
    <tr>
        <th>Angle at Perigee:</th>
        <td><?php echo number_format($cmResults['angle_perigee'], 2); ?> degrees</td>
    </tr>
</table>
<?php
function calculate_maneuver(
    $xCM, $yCM, $zCM, $delta_xCM, $delta_yCM, $delta_zCM,
    $xLM, $yLM, $zLM, $delta_xLM, $delta_yLM, $delta_zLM,
    $xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON
) {
    // Normalize velocities relative to the Moon
    $delta_xCM_relative = $delta_xCM - $delta_xMOON;
    $delta_yCM_relative = $delta_yCM - $delta_yMOON;
    $delta_zCM_relative = $delta_zCM - $delta_zMOON;

    $delta_xLM_relative = $delta_xLM - $delta_xMOON;
    $delta_yLM_relative = $delta_yLM - $delta_yMOON;
    $delta_zLM_relative = $delta_zLM - $delta_zMOON;

    // Compute altitude differences
    $altitudeCM = sqrt($xCM**2 + $yCM**2 + $zCM**2);
    $altitudeLM = sqrt($xLM**2 + $yLM**2 + $zLM**2);
    $altitude_difference = $altitudeCM - $altitudeLM;

    // Compute angle difference (relative position in orbit)
    $angle_CM = atan2($yCM, $xCM);
    $angle_LM = atan2($yLM, $xLM);
    $relative_angle = rad2deg($angle_LM - $angle_CM);

    // Normalize to 0-360° range
    if ($relative_angle < 0) {
        $relative_angle += 360;
    }

    // Determine Up or Down Status
    $up = 0;
    $down = 0;
    $level = 0;
    $vel = 0;

    // **Phase 1: Above and Moving Toward Drop Position**
    if ($altitude_difference > 0 && ($relative_angle > 0 && $relative_angle < 90)) {
        $up = 3; // Approaching drop point
        if ($relative_angle < 60) $up = 2;
        if ($relative_angle < 46) $up = 1; // Reached optimal drop angle

        // Compute delta-v needed for a retrograde burn to lower orbit
        $vel = -abs(($altitudeCM - $altitudeLM) * 0.015); // Rough estimate
    }

    // **Phase 2: Below and Moving Toward Intercept Position**
    if ($altitude_difference < 0 && ($relative_angle > 180 && $relative_angle < 270)) {
        $down = 3; // Approaching intercept point
        if ($relative_angle > 225) $down = 2;
        if ($relative_angle > 244) $down = 1; // Ready for final burn

        // Compute delta-v needed for a prograde burn to raise orbit
        $vel = abs(($altitudeLM - $altitudeCM) * 0.015);
    }

    // **Phase 3: At Correct Altitude and Ready for Final Burn**
    if (abs($altitude_difference) < 10 && abs($relative_angle - 45) < 1) {
        $level = 1;
        $vel = 0; // No further adjustment needed
    }

    // Output results
    return [
        "up" => $up,
        "down" => $down,
        "level" => $level,
        "vel" => $vel
    ];
}

?>
<?php
// Call the function
$result = calculate_maneuver($xCM, $yCM, $zCM, $delta_xCM, $delta_yCM, $delta_zCM, 
                             $xLM, $yLM, $zLM, $delta_xLM, $delta_yLM, $delta_zLM, 
                             $xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON);

// Adjust Delta-V Calculation
$altitudeCM = sqrt(($xCM - $xMOON)**2 + ($yCM - $yMOON)**2 + ($zCM - $zMOON)**2);
$altitudeLM = sqrt(($xLM - $xMOON)**2 + ($yLM- $yMOON)**2  + ($zLM - $zMOON)**2);
$altitude_difference = $altitudeCM - $altitudeLM;

// Approximate orbital velocity at CM's altitude
$mu = 4.9028e12; // Gravitational parameter of the Moon in m³/s²
$v_orbital = sqrt($mu / $altitudeCM);

// Scale delta-v based on altitude difference
$vel_adjusted = ($altitude_difference / ($altitudeCM + $altitudeLM)) * $v_orbital;
$vel_adjusted = round($vel_adjusted, 2); // Round for readability

// Print Output
echo "Up: " . $result["up"] . "\n";
echo "Down: " . $result["down"] . "\n";
echo "Level: " . $result["level"] . "\n";
echo "Velocity Change Needed: " . $vel_adjusted . " m/s\n";
echo $altitudemoon .'|';
echo $altitudemoonLM .'|';
echo $altitude_difference;
?>


<?php
function calculate_intercept_angle(
    $xCM, $yCM, $zCM, $delta_xCM, $delta_yCM, $delta_zCM,
    $xLM, $yLM, $zLM, $delta_xLM, $delta_yLM, $delta_zLM,
    $xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON
) {
    // Normalize positions relative to the Moon
    $xCM_rel = $xCM - $xMOON;
    $yCM_rel = $yCM - $yMOON;
    $zCM_rel = $zCM - $zMOON;

    $xLM_rel = $xLM - $xMOON;
    $yLM_rel = $yLM - $yMOON;
    $zLM_rel = $zLM - $zMOON;

    // Normalize velocities relative to the Moon
    $delta_xCM_relative = $delta_xCM - $delta_xMOON;
    $delta_yCM_relative = $delta_yCM - $delta_yMOON;
    $delta_zCM_relative = $delta_zCM - $delta_zMOON;

    $delta_xLM_relative = $delta_xLM - $delta_xMOON;
    $delta_yLM_relative = $delta_yLM - $delta_yMOON;
    $delta_zLM_relative = $delta_zLM - $delta_zMOON;

    // Compute orbital radii (altitudes) relative to the Moon
    $moon_radius = 1737400; // Moon's radius in meters
    $rCM = sqrt($xCM_rel**2 + $yCM_rel**2 + $zCM_rel**2);
    $rLM = sqrt($xLM_rel**2 + $yLM_rel**2 + $zLM_rel**2);
    $altitude_difference = $rLM - $rCM;

    // Compute forward distance to LM (how far it is ahead in the Moon’s frame)
    $LM_angle = atan2($yLM_rel, $xLM_rel);
    $CM_angle = atan2($yCM_rel, $xCM_rel);
    $relative_angle = rad2deg($LM_angle - $CM_angle);
    if ($relative_angle < 0) {
        $relative_angle += 360;
    }

    // Approximate forward distance
    $distance_to_LM_forward = $relative_angle / 360 * (2 * pi() * $rCM);

    // Compute Intercept Horizon Angle (θ_LM)
    $theta_LM_rad = atan2($altitude_difference, $distance_to_LM_forward);
    $theta_LM = rad2deg($theta_LM_rad);

    // Compute Current Orbital Vector Angle (θ_CM) using velocity relative to the Moon
    $current_velocity_vector_rad = atan2($delta_yCM_relative, $delta_xCM_relative);
    $theta_CM = rad2deg($current_velocity_vector_rad);
    if ($theta_CM < 0) {
        $theta_CM += 360;
    }

    // Determine if intercept is approaching
    $angle_difference = abs($theta_CM - $theta_LM);
    $intercept_condition = ($angle_difference < 0.1) ? "Intercepting" : "Adjust Needed";

    // Compute Time to Intercept if close enough
    $time_to_intercept = "N/A";
    if ($angle_difference < 0.1) {
        $mu = 4.9028e12; // Moon's gravitational parameter (m³/s²)
        $orbital_period = 2 * pi() * sqrt(pow($rCM + $moon_radius, 3) / $mu); // Kepler's Law
        $time_to_intercept = ($orbital_period / 360) * $angle_difference; // Estimate time in seconds
        $time_to_intercept = round($time_to_intercept, 2); // Round for clarity
    }

    // Output results
    return [
        "theta_LM" => round($theta_LM, 2),
        "theta_CM" => round($theta_CM, 2),
        "adjustment_needed" => round($theta_LM - $theta_CM, 2),
        "status" => $intercept_condition,
        "time_to_intercept_seconds" => $time_to_intercept
    ];
}


// Call the function
$result = calculate_intercept_angle($xCM, $yCM, $zCM, $delta_xCM, $delta_yCM, $delta_zCM, 
                                    $xLM, $yLM, $zLM, $delta_xLM, $delta_yLM, $delta_zLM, 
                                    $xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON);

// Display the output in a table without borders
echo "<table style='border-collapse: collapse; font-family: Arial, sans-serif;'>";
echo "<tr><td><strong>Intercept Horizon Angle (θ_LM):</strong></td><td>" . $result["theta_LM"] . "°</td></tr>";
echo "<tr><td><strong>Current Orbital Vector Angle (θ_CM):</strong></td><td>" . $result["theta_CM"] . "°</td></tr>";
echo "<tr><td><strong>Angle Adjustment Needed:</strong></td><td>" . $result["adjustment_needed"] . "°</td></tr>";
echo "<tr><td><strong>Status:</strong></td><td>" . $result["status"] . "</td></tr>";
echo "<tr><td><strong>Time to Intercept:</strong></td><td>" . ($result["time_to_intercept_seconds"] !== "N/A" ? $result["time_to_intercept_seconds"] . " seconds" : "N/A") . "</td></tr>";
echo "</table>";
?>




</body>
</html>

