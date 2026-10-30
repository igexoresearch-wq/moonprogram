<?php

/*
 * Propulsion availability is still based on the original EngineStatus model.
 * This pass intentionally preserves the existing stage/throttle behavior while
 * fixing broken engine lookups and the LES/LM wiring.
 */

$engineFraction = static function (
    mysqli $conn,
    string $stage,
    ?string $engineName = null
): float {
    $stageEsc = $conn->real_escape_string($stage);
    $engineClause = '';

    if ($engineName !== null) {
        $engineEsc = $conn->real_escape_string($engineName);
        $engineClause = " AND engine_name = '$engineEsc'";
    }

    $totalQuery = $conn->query(
        "SELECT COUNT(*) AS total
         FROM EngineStatus
         WHERE engine_stage = '$stageEsc'
           $engineClause
           AND component = 'Combustion Chamber'"
    );
    $total = $totalQuery ? (int)($totalQuery->fetch_assoc()['total'] ?? 0) : 0;

    $onQuery = $conn->query(
        "SELECT COUNT(*) AS on_count
         FROM EngineStatus
         WHERE engine_stage = '$stageEsc'
           $engineClause
           AND component = 'Combustion Chamber'
           AND status = 'ON'
           AND operational_status = 'OK'"
    );
    $on = $onQuery ? (int)($onQuery->fetch_assoc()['on_count'] ?? 0) : 0;

    return $total > 0 ? $on / $total : 0.0;
};

$first_stage_fraction = $engineFraction($conn, 'First Stage');
$second_stage_fraction = $engineFraction($conn, 'Second Stage');
$third_stage_fraction = $engineFraction($conn, 'Third Stage');

// SPS ignition follows the existing Service Propulsion Engine cockpit switch
// and thrust command. RCS no longer piggybacks on SPS throttle.
$spsSwitchResult = $conn->query(
    "SELECT status
     FROM CMswitches
     WHERE name = 'Service_Propulsion_Engine'
     LIMIT 1"
);
$spsSwitchRow = $spsSwitchResult ? $spsSwitchResult->fetch_assoc() : null;
$spsEnabled = !$spsSwitchRow || (int)$spsSwitchRow['status'] === 1;

$spsShouldIgnite =
    (int)$service_moduleCM === 1
    && $spsEnabled
    && (float)$thrustCM > 0.0
    && (float)$fuelCM > 0.0;

$conn->query(
    "UPDATE EngineStatus
     SET status = 'OFF'
     WHERE engine_stage = 'Service Module'"
);

if ($spsShouldIgnite) {
    $conn->query(
        "UPDATE EngineStatus
         SET status = 'ON'
         WHERE engine_stage = 'Service Module'
           AND operational_status = 'OK'"
    );
}

$service_module_fraction = $engineFraction($conn, 'Service Module');

/*
 * LM engine state was previously impossible to use after reset:
 * reset correctly turns every EngineStatus row OFF, but there was no active
 * path that turned the selected LM engine back ON.  Treat the existing LM
 * thrust command as the ignition/throttle command for now.  Later guidance
 * work can put a more detailed ignition interlock in front of this without
 * changing the cockpit control.
 */
$lmThrottle = max(0.0, min(100.0, (float)$thrustLM));
$lmDescentActive = ((int)$descentLM === 1);
$lmActiveEngine = $lmDescentActive ? 'Descent Engine' : 'Ascent Engine';
$lmFuelAvailable = $lmDescentActive
    ? ((float)$fuelLM > 0.0)
    : ((float)$fuel_ascentLM > 0.0);
$lmPropulsionAvailable = !isset($propulsionSYS) || (int)$propulsionSYS === 1;
$lmShouldIgnite = $lmThrottle > 0.0 && $lmFuelAvailable && $lmPropulsionAvailable;

// Only one LM main engine can be active at a time.
$conn->query("UPDATE EngineStatus SET status = 'OFF' WHERE engine_stage = 'Lunar Module'");
if ($lmShouldIgnite) {
    $lmEngineEsc = $conn->real_escape_string($lmActiveEngine);
    $conn->query(
        "UPDATE EngineStatus
         SET status = 'ON'
         WHERE engine_stage = 'Lunar Module'
           AND engine_name = '$lmEngineEsc'
           AND operational_status = 'OK'"
    );
}

$lunar_ascent_fraction = $engineFraction($conn, 'Lunar Module', 'Ascent Engine');
$lunar_descent_fraction = $engineFraction($conn, 'Lunar Module', 'Descent Engine');

// Calculate thrust and fuel consumption for the Saturn/CSM stack.
if ((int)$escapeCM === 1 && (int)$ABORTCM === 1) {
    // Launch Escape System.  Manual/automatic abort code sets ABORT and thrust.
    $current_thrust = (float)$escape_tower_thrust * ((float)$thrustCM / 100.0);
    $fuel_consumption = 1.0;

    // After abort the launch vehicle/service module are separated.  Keep this
    // mass calculation local until the full authoritative mass pass.
    $current_mass = (float)$command_module_empty_mass
        + (float)$escape_tower_empty_mass
        + (float)$rcscm
        + (float)($escape_fuelCM ?? 0);
} elseif ((int)$stage1CM === 1) {
    $current_thrust = (float)$stage1_thrust
        * ((float)$thrustCM / 100.0 * $first_stage_fraction);
    $fuel_consumption = (float)$stage1_fuel_rate
        * ((float)$thrustCM / 100.0 * $first_stage_fraction);
    $current_mass = (float)$totalSaturnV;
} elseif ((int)$stage2CM === 1) {
    $current_thrust = (float)$stage2_thrust
        * ((float)$thrustCM / 100.0 * $second_stage_fraction);
    $fuel_consumption = (float)$stage2_fuel_rate
        * ((float)$thrustCM / 100.0 * $second_stage_fraction);
    $current_mass = (float)$totalSaturnV;
} elseif ((int)$stage3CM === 1) {
    $current_thrust = (float)$stage3_thrust
        * ((float)$thrustCM / 100.0 * $third_stage_fraction);
    $fuel_consumption = (float)$stage3_fuel_rate
        * ((float)$thrustCM / 100.0 * $third_stage_fraction);
    $current_mass = (float)$totalSaturnV;
} elseif ((int)$service_moduleCM === 1) {
    // Preserve the original temporary CSM-RCS behavior for this repair pass.
    // Dedicated body-axis RCS physics is scheduled later in WORK_PLAN.md.
    $current_thrust = (float)$service_module_thrust
        * (((float)$thrustCM + (float)$thrustersCM) / 100.0);
    $fuel_consumption = (float)$service_module_fuel_rate
        * (((float)$thrustCM + abs((float)$thrustersCM)) / 100.0);
    $current_mass = (float)$totalSaturnV;
} else {
    $current_thrust = 0.0;
    $fuel_consumption = 0.0;
    $current_mass = max(1.0, (float)$totalSaturnV);
}

$current_mass = max(1.0, $current_mass);

// Main engines thrust along spacecraft-local +X. The quaternion converts that
// body-forward vector into the universal XYZ frame without an Euler singularity.
if (!isset($cmQuaternion)) {
    $cmQuaternion = moonSimLoadVehicleQuaternion($conn, 'CM');
}
$cmForward = moonSimRotateBodyVector($cmQuaternion, [1.0, 0.0, 0.0]);

$thrust_x = $current_thrust * $cmForward[0];
$thrust_y = $current_thrust * $cmForward[1];
$thrust_z = $current_thrust * $cmForward[2];

$thrustAccelerationCMx = $thrust_x / $current_mass;
$thrustAccelerationCMy = $thrust_y / $current_mass;
$thrustAccelerationCMz = $thrust_z / $current_mass;
$thrustFuelConsumptionCM = $fuel_consumption;

// Lunar Module main propulsion.
if ($lmDescentActive) {
    $current_thrust_LM = (float)$lander_descent_thrust
        * ($lmThrottle / 100.0 * $lunar_descent_fraction);
    $fuel_consumption_LM = (float)$descent_stage_fuel_rate
        * ($lmThrottle / 100.0 * $lunar_descent_fraction);
} else {
    $current_thrust_LM = (float)$lander_ascent_thrust
        * ($lmThrottle / 100.0 * $lunar_ascent_fraction);
    $fuel_consumption_LM = (float)$ascent_stage_fuel_rate
        * ($lmThrottle / 100.0 * $lunar_ascent_fraction);
}

$current_mass_LM = max(1.0, (float)$totalLEM);

if (!isset($lmQuaternion)) {
    $lmQuaternion = moonSimLoadVehicleQuaternion($conn, 'LM');
}
$lmForward = moonSimRotateBodyVector($lmQuaternion, [1.0, 0.0, 0.0]);

$thrust_x = $current_thrust_LM * $lmForward[0];
$thrust_y = $current_thrust_LM * $lmForward[1];
$thrust_z = $current_thrust_LM * $lmForward[2];

$thrustAccelerationLMx = $thrust_x / $current_mass_LM;
$thrustAccelerationLMy = $thrust_y / $current_mass_LM;
$thrustAccelerationLMz = $thrust_z / $current_mass_LM;
$thrustFuelConsumptionLM = $fuel_consumption_LM;

// The legacy scalar "thrusters" field is no longer used for physics.
$conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrusters'");
?>
