<?php
function calculateReentryTemperature($altitude, $airspeed) {
    // Constants
    $rho0 = 1.225; // Sea-level air density (kg/m^3)
    $H = 100000;    // Scale height for atmosphere model (m)
    $kappa = 1.83e-4; // Empirical constant for Apollo (s^2 m^-1)
    $sigma = 5.67e-8; // Stefan-Boltzmann constant (W/m^2 K^4)
    $epsilon = 0.9;   // Emissivity of heat shield material

    // Validate inputs to prevent invalid calculations
    if ($altitude > 100000) {
 
        return 0; // Return a default value or handle appropriately
    }
    if ($airspeed <= 0) {

        return 0;
    }

    // Step 1: Calculate air density at the given altitude
    $rho = $rho0 * pow(1 - ($altitude / $H), 4.256);

    // Step 2: Calculate heat flux
    $q = $kappa * pow($rho, 0.5) * pow($airspeed, 3);

    // Step 3: Calculate stagnation point temperature
    $temp = pow($q / ($sigma * $epsilon), 0.25);

    return $temp; // Temperature in Kelvin
}?>