<?php 
include 'db.php';
include 'variables.php';
include 'constants.php';
function compute_total_energy($x, $y, $z, $dx, $dy, $dz, $earth_x, $earth_y, $earth_z, $moon_x, $moon_y, $moon_z, $G, $M_earth, $M_moon) {
    // Compute distances from Earth and Moon
    $r_earth = sqrt(pow($x - $earth_x, 2) + pow($y - $earth_y, 2) + pow($z - $earth_z, 2));
    $r_moon = sqrt(pow($x - $moon_x, 2) + pow($y - $moon_y, 2) + pow($z - $moon_z, 2));

    // Compute velocity squared (v^2)
    $v_squared = pow($dx, 2) + pow($dy, 2) + pow($dz, 2);

    // Compute gravitational potential energy terms
    $U_earth = -$G * $M_earth / $r_earth;
    $U_moon = -$G * $M_moon / $r_moon;

    // Compute total energy: E = 1/2 v^2 + U_earth + U_moon
    return (0.5 * $v_squared) + $U_earth + $U_moon;
}
function compute_total_energy2($x, $y, $z, $dx, $dy, $dz, $earth_x, $earth_y, $earth_z, $moon_x, $moon_y, $moon_z, $G, $M_earth, $M_moon, $deltafact) {
    // Compute distances from Earth and Moon
    $r_earth = sqrt(pow($x - $earth_x, 2) + pow($y - $earth_y, 2) + pow($z - $earth_z, 2));
    $r_moon = sqrt(pow($x - $moon_x, 2) + pow($y - $moon_y, 2) + pow($z - $moon_z, 2));

    // Compute velocity squared (v^2)
    $v_squared = pow($dx, 2) + pow($dy, 2) + pow($dz, 2);

    // Compute gravitational potential energy terms
    $U_earth = (-$G *$deltafact) * $M_earth / $r_earth;
    $U_moon = (-$G *$deltafact) * $M_moon / $r_moon;

    // Compute total energy: E = 1/2 v^2 + U_earth + U_moon
    return (0.5 * $v_squared) + $U_earth + $U_moon;
}
// --- Use precomputed distance variables from the first script ---
// Calculate gravitational accelerations for the Command Module (CM)
if ($distance_to_center > ($earth_radius + 0.1)) {
    $earth_gravity_CM_grav = ($gravitational_constant * $earth_mass) / pow($distance_to_center, 2);
} else {
    $earth_gravity_CM_grav = 0; // Prevent pulling through Earth
}

if ($distance_to_center_moon > ($moon_radius + 0.1)) {
    $moon_gravity_CM_grav = ($gravitational_constant * $moon_mass) / pow($distance_to_center_moon, 2);
} else {
    $moon_gravity_CM_grav = 0; // Prevent pulling through Moon
}

if ($distance_to_center < ($earth_radius + 10) && $parachutes_deployedCM == 1 && $distance_to_center > $earth_radius) {
    $earth_gravity_CM_grav = 1;
}

// Calculate gravitational accelerations for the Lunar Module (LM)
if ($distance_to_centerLM > ($earth_radius + 0.1)) {
    $earth_gravity_LM_grav = ($gravitational_constant * $earth_mass) / pow($distance_to_centerLM, 2);
} else {
    $earth_gravity_LM_grav = 0;
}

if ($distance_to_center_moonLM > ($moon_radius + 0.1)) {
    $moon_gravity_LM_grav = ($gravitational_constant * $moon_mass) / pow($distance_to_center_moonLM, 2);
} else {
    $moon_gravity_LM_grav = 0;
}

// --- Acceleration components for CM ---
$acceleration_earth_x_CM_grav = ($earth_gravity_CM_grav * ($earth_center_x - $xCM)) / $distance_to_center;
$acceleration_earth_y_CM_grav = ($earth_gravity_CM_grav * ($earth_center_y - $yCM)) / $distance_to_center;
$acceleration_earth_z_CM_grav = ($earth_gravity_CM_grav * ($earth_center_z - $zCM)) / $distance_to_center;
echo $acceleration_earth_z_CM_grav;
$acceleration_moon_x_CM_grav = ($moon_gravity_CM_grav * ($xMOON - $xCM)) / $distance_to_center_moon;
$acceleration_moon_y_CM_grav = ($moon_gravity_CM_grav * ($yMOON - $yCM)) / $distance_to_center_moon;
$acceleration_moon_z_CM_grav = ($moon_gravity_CM_grav * ($zMOON - $zCM)) / $distance_to_center_moon;

// --- Acceleration components for LM ---
$acceleration_earth_x_LM_grav = ($earth_gravity_LM_grav * ($earth_center_x - $xLM)) / $distance_to_centerLM;
$acceleration_earth_y_LM_grav = ($earth_gravity_LM_grav * ($earth_center_y - $yLM)) / $distance_to_centerLM;
$acceleration_earth_z_LM_grav = ($earth_gravity_LM_grav * ($earth_center_z - $zLM)) / $distance_to_centerLM;

$acceleration_moon_x_LM_grav = ($moon_gravity_LM_grav * ($xMOON - $xLM)) / $distance_to_center_moonLM;
$acceleration_moon_y_LM_grav = ($moon_gravity_LM_grav * ($yMOON - $yLM)) / $distance_to_center_moonLM;
$acceleration_moon_z_LM_grav = ($moon_gravity_LM_grav * ($zMOON - $zLM)) / $distance_to_center_moonLM;

// --- Combine accelerations ---
$acceleration_x_CM_grav = $acceleration_earth_x_CM_grav + $acceleration_moon_x_CM_grav;
$acceleration_y_CM_grav = $acceleration_earth_y_CM_grav + $acceleration_moon_y_CM_grav;
$acceleration_z_CM_grav = $acceleration_earth_z_CM_grav + $acceleration_moon_z_CM_grav;

$acceleration_x_LM_grav = $acceleration_earth_x_LM_grav + $acceleration_moon_x_LM_grav;
$acceleration_y_LM_grav = $acceleration_earth_y_LM_grav + $acceleration_moon_y_LM_grav;
$acceleration_z_LM_grav = $acceleration_earth_z_LM_grav + $acceleration_moon_z_LM_grav;

// Function to compute total energy (kinetic + gravitational potential)


// Compute total energy BEFORE updates (without thrust)
$energy_before_CM = compute_total_energy($xCM, $yCM, $zCM, $delta_xCM, $delta_yCM, $delta_zCM, $earth_center_x, $earth_center_y, $earth_center_z, $xMOON, $yMOON, $zMOON, $gravitational_constant, $earth_mass, $moon_mass);
$energy_before_LM = compute_total_energy($xLM, $yLM, $zLM, $delta_xLM, $delta_yLM, $delta_zLM, $earth_center_x, $earth_center_y, $earth_center_z, $xMOON, $yMOON, $zMOON, $gravitational_constant, $earth_mass, $moon_mass);



// Update velocity (No thrust)
$new_delta_xCM_grav = $delta_xCM + $acceleration_x_CM_grav;
$new_delta_yCM_grav = $delta_yCM + $acceleration_y_CM_grav;
$new_delta_zCM_grav = $delta_zCM + $acceleration_z_CM_grav;

// Update position
$xCM_grav = $xCM + $delta_xCM + ($factorCM * $acceleration_x_CM_grav);
$yCM_grav = $yCM + $delta_yCM + ($factorCM * $acceleration_y_CM_grav);
$zCM_grav = $zCM + $delta_zCM + ($factorCM * $acceleration_z_CM_grav);

// Update Lunar Module (LM)
$new_delta_xLM_grav = $delta_xLM + $acceleration_x_LM_grav;
$new_delta_yLM_grav = $delta_yLM + $acceleration_y_LM_grav;
$new_delta_zLM_grav = $delta_zLM + $acceleration_z_LM_grav;

$xLM_grav = $xLM + $delta_xLM + (0.5 * $acceleration_x_LM_grav);
$yLM_grav = $yLM + $delta_yLM + (0.5 * $acceleration_y_LM_grav);
$zLM_grav = $zLM + $delta_zLM + (0.5 * $acceleration_z_LM_grav);

// Compute total energy AFTER updates (without thrust)
$energy_after_CM = compute_total_energy($xCM_grav, $yCM_grav, $zCM_grav, $new_delta_xCM_grav, $new_delta_yCM_grav, $new_delta_zCM_grav, $earth_center_x, $earth_center_y, $earth_center_z, $xMOON, $yMOON, $zMOON, $gravitational_constant, $earth_mass, $moon_mass);
$energy_after_LM = compute_total_energy($xLM_grav, $yLM_grav, $zLM_grav, $new_delta_xLM_grav, $new_delta_yLM_grav, $new_delta_zLM_grav, $earth_center_x, $earth_center_y, $earth_center_z, $xMOON, $yMOON, $zMOON, $gravitational_constant, $earth_mass, $moon_mass);
$CMenlossxx =  number_format(1 - (($energy_after_CM - $energy_before_CM)), 10, '.', '');

// Echo energy changes
if ($energy_before_CM < 0) {
 $CMenloss =  number_format(1 - ((($energy_after_CM - $energy_before_CM))/$energy_before_CM), 10, '.', '');
  $LMenloss =  number_format(1 - ((($energy_after_LM - $energy_before_LM))/$energy_before_LM), 10, '.', '');
}


// Update velocity (No thrust)
$new_delta_xCM_grav = $delta_xCM + $acceleration_x_CM_grav * $CMenloss;
$new_delta_yCM_grav = $delta_yCM + $acceleration_y_CM_grav * $CMenloss;
$new_delta_zCM_grav = $delta_zCM + $acceleration_z_CM_grav * $CMenloss;

// Update position
$xCM_grav = $xCM + ($delta_xCM + (0.5 * $acceleration_x_CM_grav ));
$yCM_grav = $yCM + ($delta_yCM + (0.5 * $acceleration_y_CM_grav ));
$zCM_grav = $zCM + ($delta_zCM + (0.5 * $acceleration_z_CM_grav ));
$CMenlosss =  number_format(1 - ((($energy_after_CM - $energy_before_CM)/2)/$energy_before_CM), 10, '.', '');
$energy_after_CMa = compute_total_energy2($xCM_grav, $yCM_grav, $zCM_grav, $new_delta_xCM_grav, $new_delta_yCM_grav, $new_delta_zCM_grav, $earth_center_x, $earth_center_y, $earth_center_z, $xMOON, $yMOON, $zMOON, $gravitational_constant, $earth_mass, $moon_mass, $CMenlosss);
 $CMenlossa =  number_format(1 + (($energy_after_CMa - $energy_before_CM)/$energy_before_CM), 10, '.', '');
  $CMenlossb =  number_format($energy_after_CMa - $energy_before_CM, 10, '.', '');
  $CMenlossc =  number_format($energy_after_CM - $energy_before_CM, 10, '.', '');
echo "xx<br>".$CMenloss. "xx".$CMenlossxx;

?>