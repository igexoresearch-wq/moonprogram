<?php
/**
 * Moon Simulator authoritative physical data.
 *
 * Baseline:
 * - Saturn V AS-506 / Apollo 11 where mission-specific values are available.
 * - Apollo 11 CSM-107 / LM-5 spacecraft masses and propellant loads.
 *
 * This file contains physical constants and baseline vehicle data only.
 * Mission profiles will later be able to override mission-specific loads,
 * but physics, cockpit and ground-control code must all start from this
 * same source instead of maintaining contradictory copies.
 *
 * Primary references used for this baseline:
 * - NASA Apollo 11 Press Kit / Saturn V AS-506 data
 * - NASA Apollo 11 Mission Operation Report weight summary
 * - NASA Apollo 11 Flight Plan consumables tables
 * - NASA Apollo propulsion performance reports
 */

// Fundamental / celestial data (SI).
$gravitational_constant = 6.67430e-11;   // m^3 kg^-1 s^-2
$earth_radius = 6371000.0;               // m, mean-radius simulator model
$earth_mass = 5.9723e24;                 // kg
$moon_radius = 1737400.0;                // m, mean lunar radius
$moon_mass = 7.342e22;                   // kg

// Existing simulator universal-coordinate origin.
$earth_center_x = 10000000000.0;
$earth_center_y = 10000000000.0;
$earth_center_z = 10000000000.0;

// Atmosphere constants retained for the current atmosphere model.
// Reentry phase will later replace the simple exponential model.
$air_density_at_sea_level = 1.225;       // kg/m^3
$scale_height = 8500.0;                  // m

// Standard gravity, used for Isp -> mass-flow conversions.
$standard_gravity = 9.80665;             // m/s^2

// -------------------------------------------------------------------------
// Saturn V AS-506 / Apollo 11 baseline
// -------------------------------------------------------------------------

// Stage thrust.
$stage1_thrust = 7653854.0 * 4.4482216152605;  // N, S-IC at liftoff
$stage2_thrust = 1150000.0 * 4.4482216152605;  // N, nominal five J-2 total
$stage3_thrust = 203779.0 * 4.4482216152605;   // N, S-IVB J-2 upper quoted range

// Inert/dry stage mass.
$stage1_empty_mass = 288750.0 * 0.45359237;    // kg
$stage2_empty_mass = 79918.0 * 0.45359237;     // kg
$stage3_empty_mass = 25000.0 * 0.45359237;     // kg

// Baseline loaded propellant represented by the simulator's stage fuel rows.
$stage1_initial_fuel = (3307855.0 + 1426069.0) * 0.45359237; // LOX + RP-1, kg
$stage2_initial_fuel = (821022.0 + 158221.0) * 0.45359237;    // LOX + LH2, kg
$stage3_initial_fuel = (192023.0 + 43500.0) * 0.45359237;     // LOX + LH2, kg

// The current simulator has one aggregate fuel-rate value per stage.
// These rates are selected to consume the documented loaded propellant over
// the approximate nominal burn duration. Phase 7 will model cutoff/residuals
// and actual stage event timing more explicitly.
$stage1_fuel_rate = $stage1_initial_fuel / (2.8 * 60.0);       // kg/s
$stage2_fuel_rate = $stage2_initial_fuel / (6.1 * 60.0);       // kg/s
$stage3_fuel_rate = $stage3_initial_fuel / ((2.4 + 5.6) * 60.0); // kg/s across both burns

// -------------------------------------------------------------------------
// Apollo 11 CSM-107 baseline
// -------------------------------------------------------------------------

$command_module_empty_mass = 12250.0 * 0.45359237; // kg, launch weight in current model
$service_module_empty_mass = 10555.0 * 0.45359237; // kg, inert mass
$escape_tower_empty_mass = 8910.0 * 0.45359237;    // kg, LES system

$service_module_thrust = 20500.0 * 4.4482216152605; // N, SPS
$csm_thrust = $service_module_thrust;                // legacy alias
$escape_tower_thrust = 147000.0 * 4.4482216152605;  // N, LES main motor

$service_module_initial_fuel = 40803.0 * 0.45359237; // kg, Apollo 11 SPS loaded propellant
$csm_rcs_initial_propellant = 1342.4 * 0.45359237;   // kg, Apollo 11 SM-RCS nominal loaded

// SPS flight performance is about 314 s Isp.
$service_module_fuel_rate =
    $service_module_thrust / (314.0 * $standard_gravity);       // kg/s

// -------------------------------------------------------------------------
// Apollo 11 LM-5 baseline
// -------------------------------------------------------------------------

$lander_ascent_empty_mass = 4804.0 * 0.45359237;   // kg
$lander_descent_empty_mass = 4483.0 * 0.45359237;  // kg
$lm_rcs_initial_propellant = 604.0 * 0.45359237;    // kg
$descent_initial_fuel = 18100.0 * 0.45359237;       // kg
$ascent_initial_fuel = 5214.0 * 0.45359237;         // kg

$lander_descent_thrust = 9870.0 * 4.4482216152605; // N, Apollo 11 max-rated DPS
$lander_ascent_thrust = 3500.0 * 4.4482216152605;  // N, APS nominal

// Full-throttle flow derived from documented flight-performance Isp.
// Throttle-dependent DPS behavior is handled by multiplying this flow by
// the same commanded fraction used for thrust.
$descent_stage_fuel_rate =
    $lander_descent_thrust / (304.0 * $standard_gravity);       // kg/s
$ascent_stage_fuel_rate =
    $lander_ascent_thrust / (310.0 * $standard_gravity);        // kg/s

// Compatibility aliases used by older display code.
$baseline_stage1_fuel = $stage1_initial_fuel;
$baseline_stage2_fuel = $stage2_initial_fuel;
$baseline_stage3_fuel = $stage3_initial_fuel;
$baseline_sps_fuel = $service_module_initial_fuel;
$baseline_lm_descent_fuel = $descent_initial_fuel;
$baseline_lm_ascent_fuel = $ascent_initial_fuel;
?>
