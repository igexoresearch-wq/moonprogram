<?php
/**
 * Derived simulator state shared by physics, cockpit and ground control.
 *
 * Requires:
 * - shared/physical_constants.php already loaded
 * - current CSM, LM and Moon state variables loaded
 */

// Compatibility with the old normalized RCS gauge values. After the next
// simulator reset these rows contain kilograms directly.
$rawRcsCm = (float)($RCSCM ?? 0.0);
$rawRcsLm = (float)($RCSLM ?? 0.0);

$rcscm = $rawRcsCm > ($csm_rcs_initial_propellant * 2.0)
    ? ($rawRcsCm / 3329.0) * $csm_rcs_initial_propellant
    : $rawRcsCm;

$rcslm = $rawRcsLm > ($lm_rcs_initial_propellant * 2.0)
    ? ($rawRcsLm / 3329.0) * $lm_rcs_initial_propellant
    : $rawRcsLm;

// LM mass.  The descent stage contributes only while attached.
$totalLEM =
    $lander_ascent_empty_mass
    + (float)($fuel_ascentLM ?? 0.0)
    + $rcslm
    + (((int)($descentLM ?? 0) === 1)
        ? $lander_descent_empty_mass + (float)($fuelLM ?? 0.0)
        : 0.0);

// The LM is part of the CSM/launch stack while enclosed with S-IVB or once
// docked to the CSM. This fixes the old behavior where LM mass disappeared
// immediately when stage3 became zero even if docking had occurred.
$lmAttachedMass =
    ((int)($stage3CM ?? 0) === 1 || (int)($dockCM ?? 0) === 1)
    ? $totalLEM
    : 0.0;

$totalSaturnV =
    (((int)($stage1CM ?? 0) === 1)
        ? $stage1_empty_mass + (float)($s1fuelCM ?? 0.0)
        : 0.0)
    + (((int)($stage2CM ?? 0) === 1)
        ? $stage2_empty_mass + (float)($s2fuelCM ?? 0.0)
        : 0.0)
    + (((int)($stage3CM ?? 0) === 1)
        ? $stage3_empty_mass + (float)($s3fuelCM ?? 0.0)
        : 0.0)
    + (((int)($escapeCM ?? 0) === 1)
        ? $escape_tower_empty_mass + (float)($escape_fuelCM ?? $excape_fuelCM ?? 0.0)
        : 0.0)
    + (((int)($service_moduleCM ?? 0) === 1)
        ? $service_module_empty_mass + (float)($fuelCM ?? 0.0)
        : 0.0)
    + $command_module_empty_mass
    + $rcscm
    + $lmAttachedMass;

// Geometry.
$cmEarthX = (float)$xCM - $earth_center_x;
$cmEarthY = (float)$yCM - $earth_center_y;
$cmEarthZ = (float)$zCM - $earth_center_z;

$cmMoonX = (float)$xCM - (float)$xMOON;
$cmMoonY = (float)$yCM - (float)$yMOON;
$cmMoonZ = (float)$zCM - (float)$zMOON;

$lmEarthX = (float)$xLM - $earth_center_x;
$lmEarthY = (float)$yLM - $earth_center_y;
$lmEarthZ = (float)$zLM - $earth_center_z;

$lmMoonX = (float)$xLM - (float)$xMOON;
$lmMoonY = (float)$yLM - (float)$yMOON;
$lmMoonZ = (float)$zLM - (float)$zMOON;

$distance_to_center = sqrt($cmEarthX ** 2 + $cmEarthY ** 2 + $cmEarthZ ** 2);
$distance_to_center_moon = sqrt($cmMoonX ** 2 + $cmMoonY ** 2 + $cmMoonZ ** 2);
$distance_to_centerLM = sqrt($lmEarthX ** 2 + $lmEarthY ** 2 + $lmEarthZ ** 2);
$distance_to_center_moonLM = sqrt($lmMoonX ** 2 + $lmMoonY ** 2 + $lmMoonZ ** 2);

$altitude = $distance_to_center - $earth_radius;
$altitudemoon = $distance_to_center_moon - $moon_radius;
$altitudeLM = $distance_to_centerLM - $earth_radius;
$altitudemoonLM = $distance_to_center_moonLM - $moon_radius;

// Existing "near-side lunar landing site" reference used by the displays.
$dx = (float)$xMOON - $earth_center_x;
$dy = (float)$yMOON - $earth_center_y;
$dz = (float)$zMOON - $earth_center_z;
$earthMoonDistance = sqrt($dx ** 2 + $dy ** 2 + $dz ** 2);

if ($earthMoonDistance > 0.0) {
    $scaling_factor = $moon_radius / $earthMoonDistance;
    $landingsite_x = (float)$xMOON - ($dx * $scaling_factor);
    $landingsite_y = (float)$yMOON - ($dy * $scaling_factor);
    $landingsite_z = (float)$zMOON - ($dz * $scaling_factor);
} else {
    $landingsite_x = (float)$xMOON;
    $landingsite_y = (float)$yMOON;
    $landingsite_z = (float)$zMOON;
}

$craft_dist = sqrt(
    ((float)$xLM - (float)$xCM) ** 2
    + ((float)$yLM - (float)$yCM) ** 2
    + ((float)$zLM - (float)$zCM) ** 2
);

// Legacy circular-orbit speed helper used by existing displays/guidance.
if ($altitude <= $altitudemoon) {
    $orbitvel = $distance_to_center > 0.0
        ? sqrt(($gravitational_constant * $earth_mass) / $distance_to_center)
        : 0.0;
} else {
    $orbitvel = $distance_to_center_moon > 0.0
        ? sqrt(($gravitational_constant * $moon_mass) / $distance_to_center_moon)
        : 0.0;
}
?>
