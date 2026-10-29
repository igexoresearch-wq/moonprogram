<?php
// Constants for Earth and Moon
$earth_radius = 6371000; // Radius of Earth in meters
$earth_mass = 5.972e24; // Mass of Earth in kilograms
$moon_radius = 1737100; // Radius of the Moon in meters
$moon_mass = 7.342e22;  // Mass of the Moon in kilograms
$gravitational_constant = 6.67430e-11; // Gravitational constant in m^3/kg/s^2

// New Earth center coordinates
$earth_center_x = 10000000000;
$earth_center_y = 10000000000;
$earth_center_z = 10000000000;

// Constants for Earth's atmosphere
$earth_radius = 6371000; // Radius of Earth in meters
$air_density_at_sea_level = 1.225; // kg/m^3
$scale_height = 8500; // Scale height of atmosphere in meters



// Define thrust and fuel usage rate per stage with historical accuracy and 2 decimal precision
$stage1_thrust = 7607000.00; // Thrust in Newtons
$stage2_thrust = 1019700.00; // Thrust in Newtons
$stage3_thrust = 100000.00;  // Thrust in Newtons
$lander_ascent_thrust = 15600.00; // Thrust in Newtons (Ascent Stage)
$lander_descent_thrust = 45000.00 * 0.6; // Thrust in Newtons (Descent Stage at 60%)
$csm_thrust = 91100.00; // Thrust in Newtons (CSM Engine)
$escape_tower_thrust = 2200000.00; // Thrust in Newtons (Escape Tower)

$stage1_fuel_rate = 12800; // kg/s for Stage 1 (S-IC)
$stage2_fuel_rate = 2300;  // kg/s for Stage 2 (S-II)
$stage3_fuel_rate = 250;   // kg/s for Stage 3 (S-IVB)
$service_module_fuel_rate = 10.2; // kg/s for Service Module
$descent_stage_fuel_rate = 8.5;   // kg/s for LM Descent Stage
$ascent_stage_fuel_rate = 3.5;    // kg/s for LM Ascent Stage

// Define empty mass of each stage (kg)
$stage1_empty_mass = 131000.00;
$stage2_empty_mass = 40000.00;
$stage3_empty_mass = 13500.00;
$lander_ascent_empty_mass = 4475.00; // Ascent Stage
$lander_descent_empty_mass = 10334.00; // Descent Stage
$command_module_empty_mass = 5500.00; // Command Module
$service_module_empty_mass = 24625.00; // Service Module
$escape_tower_empty_mass = 4000.00; // Escape Tower

$rcscm = ($RCSCM / 3329) * 275;
$rcslm = ($RCSLM / 3329) * 275;

// Calculate total mass of LEM ($totalLEM)
$totalLEM = ($lander_ascent_empty_mass + $fuel_ascentLM) + (($lander_descent_empty_mass + $fuelLM) * $descentLM) + $rcslm;

// Calculate total mass of Saturn V ($totalSaturnV)
$totalSaturnV = (($stage1_empty_mass + $s1fuelCM) * $stage1CM) + (($stage2_empty_mass + $s2fuelCM) * $stage2CM) + (($stage3_empty_mass + $s3fuelCM + $totalLEM) * $stage3CM) + ($escape_tower_empty_mass * $escapeCM) + ($service_module_empty_mass * $service_moduleCM) + $command_module_empty_mass  + $rcscm;  

// Calculate distance from the command module to Earth's center
$distance_to_center = sqrt(
    pow($xCM - $earth_center_x, 2) + 
    pow($yCM - $earth_center_y, 2) + 
    pow($zCM - $earth_center_z, 2)
);

// Calculate distance from the CM to Moon's center
$distance_to_center_moon = sqrt(
    pow($xCM - $xMOON, 2) + 
    pow($yCM - $yMOON, 2) + 
    pow($zCM - $zMOON, 2)
);

// Calculate distance from the LM to Earth's center
$distance_to_centerLM = sqrt(
    pow($xLM - $earth_center_x, 2) + 
    pow($yLM - $earth_center_y, 2) + 
    pow($zLM - $earth_center_z, 2)
);

// Calculate distance from the LM to Moon's center
$distance_to_center_moonLM = sqrt(
    pow($xLM - $xMOON, 2) + 
    pow($yLM - $yMOON, 2) + 
    pow($zLM - $zMOON, 2)
);

// Calculate CM altitude above Earth's surface
$altitudemoon = $distance_to_center_moon - $moon_radius;

// Calculate CM altitude above Earth's surface
$altitude = $distance_to_center - $earth_radius;

// Calculate LM altitude above Earth's surface
$altitudemoonLM = $distance_to_center_moonLM - $moon_radius;

// Calculate LM altitude above Earth's surface
$altitudeLM = $distance_to_centerLM - $earth_radius;

// Calculate landing site ($landingsite)
$dx = $xMOON - $earth_center_x;
$dy = $yMOON - $earth_center_y;
$dz = $zMOON - $earth_center_z;
$distance = sqrt(($dx * $dx) + ($dy * $dy) + ($dz * $dz));
$scaling_factor = $moon_radius / $distance;
$landingsite_x = $earth_center_x + ($dx * $scaling_factor);
$landingsite_y = $earth_center_y + ($dy * $scaling_factor);
$landingsite_z = $earth_center_z + ($dz * $scaling_factor);
//reentry temp calc
$temp = 0;
 if ($altitude < 100000 && $service_moduleCM == 0) {
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
}

$temp = calculateReentryTemperature($altitude, $airspeed);
}
//craft distances
$craft_dist = sqrt(
    pow($xLM - $xCM, 2) +
    pow($yLM - $yCM, 2) +
    pow($zLM - $zCM, 2)
);


?>
