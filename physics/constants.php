<?php 
// Constants for Earth and Moon (as strings)
$earth_radius = "6371000";          // Radius of Earth in meters
$earth_mass = "5972300000000000000000000";  // Mass of Earth in kilograms (5.972 × 10^24)
$moon_radius = "1737100";           // Radius of the Moon in meters
$moon_mass = "734100000000000000000000";  // Mass of the Moon in kilograms (7.341 × 10^22)
 $gravitational_constant = "0.0000000000667490"; // Gravitational constant in m^3/kg/s^2 (6.67430 × 10^-11)

// New Earth center coordinates (converted to strings)
$earth_center_x = "10000000000";
$earth_center_y = "10000000000";
$earth_center_z = "10000000000";

// Constants for Earth's atmosphere
$air_density_at_sea_level = "1.225"; // kg/m^3
$scale_height = "8500"; // Scale height of atmosphere in meters

// Thrust values as strings
$stage1_thrust = "34500000.00";
$stage2_thrust = "5141000.00";
$stage3_thrust = "1033100.00";
$service_module_thrust = "91200.00";
$lander_ascent_thrust = "15600.00";
$lander_descent_thrust = "27000.00"; // 45000 * 0.6 = 27000
$csm_thrust = "91100.00";
$escape_tower_thrust = "2200000.00";

// Fuel rates as strings
$stage1_fuel_rate = "12363";
$stage2_fuel_rate = "1267";
$stage3_fuel_rate = "215";
$service_module_fuel_rate = "54";
$descent_stage_fuel_rate = "8.5";
$ascent_stage_fuel_rate = "3.5";

// Empty mass of each stage as strings
$stage1_empty_mass = "137000.00";
$stage2_empty_mass = "40100.00";
$stage3_empty_mass = "15200.00";
$lander_ascent_empty_mass = "4475.00";
$lander_descent_empty_mass = "10334.00";
$command_module_empty_mass = "5500.00";
$service_module_empty_mass = "24625.00";
$escape_tower_empty_mass = "4000.00";

// RCS calculations
$rcscm = bcdiv(bcmul($RCSCM, "275", 10), "3329", 10);
$rcslm = bcdiv(bcmul($RCSLM, "275", 10), "3329", 10);

// Calculate total mass of LEM
$totalLEM = bcadd(
    bcadd($lander_ascent_empty_mass, $fuel_ascentLM, 10),
    bcmul(bcadd($lander_descent_empty_mass, $fuelLM, 10), $descentLM, 10),
    10
);
$totalLEM = bcadd($totalLEM, $rcslm, 10);

// Calculate total mass of Saturn V
$totalSaturnV = bcadd(
    bcadd(
        bcmul(bcadd($stage1_empty_mass, $s1fuelCM, 10), $stage1CM, 10),
        bcmul(bcadd($stage2_empty_mass, $s2fuelCM, 10), $stage2CM, 10),
        10
    ),
    bcadd(
        bcmul(bcadd(bcadd($stage3_empty_mass, $s3fuelCM, 10), $totalLEM, 10), $stage3CM, 10),
        bcadd(bcmul($escape_tower_empty_mass, $escapeCM, 10), 
        bcadd(bcmul($service_module_empty_mass, $service_moduleCM, 10), 
        bcadd($command_module_empty_mass, $rcscm, 10), 10), 10), 10
    ), 10
);

// Calculate distances using bcmath functions
$distance_to_center = bcsqrt(
    bcadd(
        bcadd(bcpow(bcsub($xCM, $earth_center_x, 10), "2", 10), bcpow(bcsub($yCM, $earth_center_y, 10), "2", 10), 10),
        bcpow(bcsub($zCM, $earth_center_z, 10), "2", 10), 10
    ), 10
);

$distance_to_center_moon = bcsqrt(
    bcadd(
        bcadd(bcpow(bcsub($xCM, $xMOON, 10), "2", 10), bcpow(bcsub($yCM, $yMOON, 10), "2", 10), 10),
        bcpow(bcsub($zCM, $zMOON, 10), "2", 10), 10
    ), 10
);

$distance_to_centerLM = bcsqrt(
    bcadd(
        bcadd(bcpow(bcsub($xLM, $earth_center_x, 10), "2", 10), bcpow(bcsub($yLM, $earth_center_y, 10), "2", 10), 10),
        bcpow(bcsub($zLM, $earth_center_z, 10), "2", 10), 10
    ), 10
);

$distance_to_center_moonLM = bcsqrt(
    bcadd(
        bcadd(bcpow(bcsub($xLM, $xMOON, 10), "2", 10), bcpow(bcsub($yLM, $yMOON, 10), "2", 10), 10),
        bcpow(bcsub($zLM, $zMOON, 10), "2", 10), 10
    ), 10
);

// Calculate altitudes
$altitude = bcsub($distance_to_center, $earth_radius, 10);
$altitudemoon = bcsub($distance_to_center_moon, $moon_radius, 10);
$altitudeLM = bcsub($distance_to_centerLM, $earth_radius, 10);
$altitudemoonLM = bcsub($distance_to_center_moonLM, $moon_radius, 10);

// Calculate landing site
$dx = bcsub($xMOON, $earth_center_x, 10);
$dy = bcsub($yMOON, $earth_center_y, 10);
$dz = bcsub($zMOON, $earth_center_z, 10);
$distance = bcsqrt(bcadd(bcadd(bcpow($dx, "2", 10), bcpow($dy, "2", 10), 10), bcpow($dz, "2", 10), 10), 10);
$scaling_factor = bcdiv($moon_radius, $distance, 10);
$landingsite_x = bcadd($earth_center_x, bcmul($dx, $scaling_factor, 10), 10);
$landingsite_y = bcadd($earth_center_y, bcmul($dy, $scaling_factor, 10), 10);
$landingsite_z = bcadd($earth_center_z, bcmul($dz, $scaling_factor, 10), 10);

// Reentry temperature calculation
$temp = "0";
if (bccomp($altitude, "100000") < 0 && $service_moduleCM == "0") {
    include_once 'physics/temp.php';
    $temp = calculateReentryTemperature($altitude, $airspeed);
}

// Craft distances
$craft_dist = bcsqrt(
    bcadd(
        bcadd(bcpow(bcsub($xLM, $xCM, 10), "2", 10), bcpow(bcsub($yLM, $yCM, 10), "2", 10), 10),
        bcpow(bcsub($zLM, $zCM, 10), "2", 10), 10
    ), 10
);

// Orbital velocity calculation
$orbitvel = bccomp($altitude, $altitudemoon) < 0 ?
    bcsqrt(bcdiv(bcmul($gravitational_constant, $earth_mass, 10), $distance_to_center, 10), 10) :
    bcsqrt(bcdiv(bcmul($gravitational_constant, $moon_mass, 10), $distance_to_center_moon, 10), 10);

?>

