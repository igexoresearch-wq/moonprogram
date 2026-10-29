<?php
$prog = 38;

//############# Initiation
include 'programshift.php';

// Constants
$G = 6.67430e-11;  // Gravitational constant (m^3/kg/s^2)
$M_earth = 5.972e24;  // Mass of Earth (kg)
$earth_x = 10000000000;
$earth_y = 10000000000;
$earth_z = 10000000000;

// Compute relative positions (distance from Earth)
$rCM_x = $xCM - $earth_x;
$rCM_y = $yCM - $earth_y;
$rCM_z = $zCM - $earth_z;

$rLM_x = $xLM - $earth_x;
$rLM_y = $yLM - $earth_y;
$rLM_z = $zLM - $earth_z;

// Compute distances from Earth (Current Orbital Radius)
$r_CM_mag = sqrt($rCM_x**2 + $rCM_y**2 + $rCM_z**2);
$r_LM_mag = sqrt($rLM_x**2 + $rLM_y**2 + $rLM_z**2);

// Compute velocities (Magnitude of velocity vector)
$v_CM = sqrt($delta_xCM**2 + $delta_yCM**2 + $delta_zCM**2);
$v_LM = sqrt($delta_xLM**2 + $delta_yLM**2 + $delta_zLM**2);

// Compute Specific Orbital Energy (𝜀) for CM and LM
$energy_CM = ($v_CM**2 / 2) - ($G * $M_earth / $r_CM_mag);
$energy_LM = ($v_LM**2 / 2) - ($G * $M_earth / $r_LM_mag);

// Compute Semi-Major Axis (𝑎) using energy equation
$a_CM = -($G * $M_earth) / (2 * $energy_CM);
$a_LM = -($G * $M_earth) / (2 * $energy_LM);

// Compute Corrected Orbital Periods (Kepler’s Third Law)
$T_CM = 2 * M_PI * sqrt(($a_CM**3) / ($G * $M_earth));
$T_LM = 2 * M_PI * sqrt(($a_LM**3) / ($G * $M_earth));

// Compute the new orbital period after a burn (Hohmann transfer)
$T_new_LM = ($T_CM + $T_LM) / 2;

// Compute angular velocities (Degrees per second)
$omega_CM = 360 / $T_CM;
$omega_LM = 360 / $T_LM;
$omega_new_LM = 360 / $T_new_LM;

// Compute angular velocity differences
$delta_omega_before = $omega_LM - $omega_CM;
$delta_omega_after = $omega_new_LM - $omega_CM;

// Compute Current Orbital Angles (Counterclockwise from +X axis)
$angle_CM = rad2deg(atan2($rCM_y, $rCM_x));  // atan2(y, x)
$angle_LM = rad2deg(atan2($rLM_y, $rLM_x));

// Normalize angles to be within 0 - 360
$angle_CM = fmod(($angle_CM + 360), 360);
$angle_LM = fmod(($angle_LM + 360), 360);

// Compute Relative Angle Difference (Where is LM compared to CM?)
$angle_difference = $angle_LM - $angle_CM;


// Normalize the angle difference to be within -180 to 180
if ($angle_difference > 180) {
    $angle_difference -= 360;
} elseif ($angle_difference < -180) {
    $angle_difference += 360;
}
$timediff = $T_CM - (((270 - $angle_difference)/270)*$T_LM);
// Determine if LM is moving toward or away from CM (based on velocity direction)
$relative_velocity = $omega_LM - $omega_CM;
$direction = ($relative_velocity > 0) ? "><" : "<>";

// Time until LM reaches CM's orbital angle
$time_until_alignment = ($relative_velocity != 0) ? abs($angle_difference / $relative_velocity) : "X";

// Time until rendezvous (when LM reaches 180° opposite CM)
$t_rendezvous = ($delta_omega_before != 0) ? (180 / abs($delta_omega_before)) : "X";

// Compute burn angle (LM must be behind CM by this angle)
$lead_angle = 360 * (1 - ($T_new_LM / $T_CM));
$burn_angle = 180 - $lead_angle;

// Compute Pitch (Angle from XY-plane, positive Z) using velocity vector
$pitch_LMx = rad2deg(atan2($delta_zLM, sqrt($delta_xLM**2 + $delta_yLM**2)));

// Compute Yaw (Clockwise when viewed from above) using velocity direction
$yaw_LMx = rad2deg(atan2($delta_yLM, $delta_xLM));

// Compute Delta-V (velocity change for the Hohmann transfer)
$v_circular = sqrt($G * $M_earth / $r_LM_mag);
$v_transfer = sqrt($G * $M_earth * ((2 / $r_LM_mag) - (1 / (($r_LM_mag + $r_CM_mag) / 2))));
$delta_v = round($v_transfer - $v_circular, 2);

// Formatting Outputs
$delta_vx = round(0 + 10 * $delta_v, 0);
$burn_anglex = round(10 * $burn_angle, 0);
$yaw_LMx = round(10 * $yaw_LMx, 0);
$pitch_LMx = round(10 * $pitch_LMx, 0);
$time_until_alignment = round($time_until_alignment / 60, 0);
$relative_velocity = round($relative_velocity * 100, 0);

$yawVLM = round(fmod(360 - rad2deg(atan2($delta_yLM, $delta_xLM)), 360),2);
// Calculate pitch (angle relative to the x-y plane)
$pitchVLM = round(rad2deg(atan2($delta_zLM, sqrt(pow($delta_xLM, 2) + pow($delta_yLM, 2)))),2);

if ($altitudemoonLM < 50000000) {
    $yawVLM = round(fmod(360 - rad2deg(atan2($delta_yLM - $delta_yMOON, $delta_xLM - $delta_xMOON)), 360),10);
    // Calculate pitch (angle relative to the x-y plane)
    $pitchVLM = round(rad2deg(atan2($delta_zLM - $delta_zMOON, sqrt(pow($delta_xLM - $delta_xMOON, 2) + pow($delta_yLM - $delta_yMOON, 2)))),2);
}

 $rx = $xCM - $xLM; 
$ry = $yCM - $yLM; 
$rz = $zCM - $zLM; 
// Compute total relative velocity (magnitude)
$dist3 = sqrt(pow($rx, 2) + pow($ry, 2) + pow($rz, 2));
// Velocity vectors (m/s)
$vx = $delta_xCM - $delta_xLM; 
$vy = $delta_yCM - $delta_yLM; 
$vz = $delta_zCM - $delta_zLM; 

// Compute total relative velocity (magnitude)
$relativeSpeed = sqrt(pow($vx, 2) + pow($vy, 2) + pow($vz, 2));

// Compute radial velocity (dot product method)
$positionMagnitude = sqrt(pow($rx, 2) + pow($ry, 2) + pow($rz, 2));  // Distance between CM and LM
$dot_product = ($rx * $vx) + ($ry * $vy) + ($rz * $vz);
$radialVelocity = ($dot_product / $positionMagnitude);  
// Determine output string format
if ($dist3 < 400000) { $radialVelocity = $relativeSpeed;}
$dist3 = round($dist3); $exp=0;
while ($dist3 > 999) {$dist3 = round($dist3 /10); $exp=$exp + 1;}

$dist3 = $dist3 *10 + $exp;
$yawVLMx = $yawVLM * 10;
$pitchVLMx = $pitchVLM * 10;


 $bdath = sprintf("%06d.%06d", $dist3, $pitchVLMx);

//############################
// Compute the relative position vector
$dx = $xCM - $xLM;
$dy = $yCM - $yLM;
$dz = $zCM - $zLM;

// Compute the relative velocity vector
$dvx = $delta_xCM - $delta_xLM;
$dvy = $delta_yCM - $delta_yLM;
$dvz = $delta_zCM - $delta_zLM;

// Compute radial velocity (rate of change in separation along the radial direction)
$dot_product = ($dx * $dvx) + ($dy * $dvy) + ($dz * $dvz);
$distance = sqrt($dx**2 + $dy**2 + $dz**2);
$relative_velocity_magnitude = sqrt($dvx**2 + $dvy**2 + $dvz**2);
// Avoid division by zero (if CM and LM are at the same position)
if ($distance > 0) {
    // Compute vertical velocity (altitude rate of change)
    $dot_product = ($dx * $dvx) + ($dy * $dvy) + ($dz * $dvz);
    $relative_vertical_velocity = $dot_product / $distance;

    // Compute horizontal velocity (orbital closing/separating)
    $relative_horizontal_velocity = sqrt($relative_velocity_magnitude**2 - $relative_vertical_velocity**2);

    // Determine if CM is closing in (negative = closing, positive = moving away)
    $closing_in = ($dot_product < 0) ? true : false;

    // Compute approach angle (atan of altitude difference / horizontal distance)
    $altitude_difference = abs($dz); // Vertical separation
    $horizontal_distance = sqrt($dx**2 + $dy**2); // In-plane distance
    if ($horizontal_distance > 0) {
        $approach_angle_rad = atan($altitude_difference / $horizontal_distance);
        $approach_angle_deg = rad2deg($approach_angle_rad);
    } else {
        $approach_angle_rad = 0;
        $approach_angle_deg = 0;
    }
} else {
    $relative_vertical_velocity = 0;
    $relative_horizontal_velocity = 0;
    $closing_in = false;
    $approach_angle_rad = 0;
    $approach_angle_deg = 0;
}




if ($relative_horizontal_velocity == 0){$CMA = 400;}else{
$CMA = round(atan($relative_vertical_velocity/$relative_horizontal_velocity),4);}
 $CMA = rad2deg($CMA);
$diffang = $CMA - $approach_angle_deg;
if (!isset($holdval)){$holdval = 0;}
$diffangx = $holdval - $diffang;
$holdval = $diffang;


// Initialize an array to store the last 10 values
$filename = "/tmp/diffangx_temp.json"; // Temporary file to store last 60 iterations

// Load existing data if file exists
if (file_exists($filename)) {
    $diffangx_history = json_decode(file_get_contents($filename), true);
} else {
    $diffangx_history = [];
}

// Add the new value to the array
$diffangx_history[] = $diffangx;

// Keep only the last 60 values
if (count($diffangx_history) > 50) {
    array_shift($diffangx_history); // Remove oldest value
}

// Compute the moving average of last 60 values
$diffangx_avg = array_sum($diffangx_history) / count($diffangx_history);

// Save back to file for next cron execution
file_put_contents($filename, json_encode($diffangx_history));


$Rrel_xx = $xLM - $xCM;
$Rrel_yx = $yLM - $yCM;
$Rrel_zx = $zLM - $zCM;

// Compute yaw angle (XY plane direction from CM to LM, counterclockwise from +X axis)
$yaw_global_rad = atan2($Rrel_yx, $Rrel_xx);
$yaw_global_deg = fmod(rad2deg($yaw_global_rad) + 360, 360); 
$yaw_global_deg = round(360-($yaw_global_deg * 10),0);
     $bdata = sprintf("%04d.%04d.%04d.%04d", $dist3, $radialVelocity, $yaw_global_deg, $pitchVLMx);



//######################

// Store results in database
$delta_vz = $IMU_delta_V - $delta_v;
if ($thrustCM > 0) {
    $burn_angle = $IMUHOLD_orbit_angle;
    $delta_vz = $IMUHOLD_delta_V;
}
$conn->query("UPDATE CMguidance SET thrust = '$bdata', delta_V = $radialVelocity, orbit_angle = $yawVLM, velocity = $timediff WHERE id = 2");

// Prevent redundant database writes
if (!isset($updateExecuted) || !$updateExecuted) {
    $conn->query("UPDATE CMguidance SET thrust = 38 WHERE id = 3");
    $updateExecuted = true;
}
?>
