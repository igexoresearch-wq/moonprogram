<?php
session_start(); // Start or resume the session

include 'db.php';
include 'variables.php';
include 'constants.php';

// Define gravitational constants (in appropriate units)
$G = 6.67430e-11; // m^3/kg/s^2
$M_EARTH = 5.972e24; // kg
$M_MOON = 7.34767309e22; // kg

// Compute CM's position relative to Earth's center
$rx = $xCM - 10000000000;
$ry = $yCM - 10000000000;
$rz = $zCM - 10000000000;

// Compute distance to Earth's center
$r_earth = sqrt(($rx ** 2) + ($ry ** 2) + ($rz ** 2));

// Assign velocity components
$vx = $delta_xCM;
$vy = $delta_yCM;
$vz = $delta_zCM;

// Compute velocity magnitude
$v_total = sqrt(($vx ** 2) + ($vy ** 2) + ($vz ** 2));

// Compute CM's position relative to the Moon
$rx_moon = $xCM - $xMOON;
$ry_moon = $yCM - $yMOON;
$rz_moon = $zCM - $zMOON;

// Compute distance to Moon's center
$r_moon = sqrt(($rx_moon ** 2) + ($ry_moon ** 2) + ($rz_moon ** 2));

// Compute kinetic energy (mass = 1 unit)
$KE = 0.5 * ($v_total ** 2);

// Compute gravitational potential energy
$U_earth = -($G * $M_EARTH) / $r_earth;
$U_moon = -($G * $M_MOON) / $r_moon;

// Compute total energy
$E_total = $KE + $U_earth + $U_moon;

// Retrieve previous total energy from session (default to 0 if not set)
$previous_E_total = isset($_SESSION['E_total']) ? $_SESSION['E_total'] : 0;

// Compute the difference from the last iteration
$E_difference = $E_total - $previous_E_total;

// Store the new total energy for the next iteration
$_SESSION['E_total'] = $E_total;

// Return results dynamically
echo json_encode([
    "E_difference" => number_format($E_difference, 6)
]);
?>