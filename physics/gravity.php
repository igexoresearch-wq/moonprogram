<?php 

// --- Use precomputed distance variables from the first script ---

// Calculate gravitational accelerations for the Command Module (CM)
if ($distance_to_center > ($earth_radius + 0.1)) {
    $earth_gravity_CM = ($gravitational_constant * $earth_mass) / pow($distance_to_center, 2);
} else {
    $earth_gravity_CM = 0; // Prevent pulling through Earth
}

if ($distance_to_center_moon > ($moon_radius + 0.1)) {
    $moon_gravity_CM = ($gravitational_constant * $moon_mass) / pow($distance_to_center_moon, 2);
} else {
    $moon_gravity_CM = 0; // Prevent pulling through Moon
}

if ($distance_to_center < ($earth_radius + 10) && $parachutes_deployedCM == "1" && $distance_to_center > $earth_radius) {
    $earth_gravity_CM = 1;
}

// Calculate gravitational accelerations for the Lunar Module (LM)
if ($distance_to_centerLM > ($earth_radius + 0.1)) {
    $earth_gravity_LM = ($gravitational_constant * $earth_mass) / pow($distance_to_centerLM, 2);
} else {
    $earth_gravity_LM = 0;
}

if ($distance_to_center_moonLM > ($moon_radius + 0.1)) {
    $moon_gravity_LM = ($gravitational_constant * $moon_mass) / pow($distance_to_center_moonLM, 2);
} else {
    $moon_gravity_LM = 0;
}

// --- Acceleration components for CM ---
$acceleration_earth_x_CM = ($earth_gravity_CM * ($earth_center_x - $xCM)) / $distance_to_center;
$acceleration_earth_y_CM = ($earth_gravity_CM * ($earth_center_y - $yCM)) / $distance_to_center;
$acceleration_earth_z_CM = ($earth_gravity_CM * ($earth_center_z - $zCM)) / $distance_to_center;

$acceleration_moon_x_CM = ($moon_gravity_CM * ($xMOON - $xCM)) / $distance_to_center_moon;
$acceleration_moon_y_CM = ($moon_gravity_CM * ($yMOON - $yCM)) / $distance_to_center_moon;
$acceleration_moon_z_CM = ($moon_gravity_CM * ($zMOON - $zCM)) / $distance_to_center_moon;

// --- Acceleration components for LM ---
$acceleration_earth_x_LM = ($earth_gravity_LM * ($earth_center_x - $xLM)) / $distance_to_centerLM;
$acceleration_earth_y_LM = ($earth_gravity_LM * ($earth_center_y - $yLM)) / $distance_to_centerLM;
$acceleration_earth_z_LM = ($earth_gravity_LM * ($earth_center_z - $zLM)) / $distance_to_centerLM;

$acceleration_moon_x_LM = ($moon_gravity_LM * ($xMOON - $xLM)) / $distance_to_center_moonLM;
$acceleration_moon_y_LM = ($moon_gravity_LM * ($yMOON - $yLM)) / $distance_to_center_moonLM;
$acceleration_moon_z_LM = ($moon_gravity_LM * ($zMOON - $zLM)) / $distance_to_center_moonLM;

// --- Combine accelerations ---
$acceleration_x_CM = $acceleration_earth_x_CM + $acceleration_moon_x_CM;
$acceleration_y_CM = $acceleration_earth_y_CM + $acceleration_moon_y_CM;
$acceleration_z_CM = $acceleration_earth_z_CM + $acceleration_moon_z_CM;

$acceleration_x_LM = $acceleration_earth_x_LM + $acceleration_moon_x_LM;
$acceleration_y_LM = $acceleration_earth_y_LM + $acceleration_moon_y_LM;
$acceleration_z_LM = $acceleration_earth_z_LM + $acceleration_moon_z_LM;


?>

