<?php
include_once __DIR__ . '/../shared/physical_constants.php';


// Reset Saturn / CSM live state to the original launch-site configuration.
$updates = [
    ['attribute' => 'x', 'value' => '10006371000'],
    ['attribute' => 'y', 'value' => '10000000000'],
    ['attribute' => 'z', 'value' => '10000000000'],
    ['attribute' => 'delta_x', 'value' => '0'],
    ['attribute' => 'delta_y', 'value' => '0'],
    ['attribute' => 'delta_z', 'value' => '0'],
    ['attribute' => 'dock', 'value' => '0'],
    ['attribute' => 'stage1', 'value' => '1'],
    ['attribute' => 'stage2', 'value' => '1'],
    ['attribute' => 'stage3', 'value' => '1'],
    ['attribute' => 's1fuel', 'value' => number_format($stage1_initial_fuel, 9, '.', '')],
    ['attribute' => 's2fuel', 'value' => number_format($stage2_initial_fuel, 9, '.', '')],
    ['attribute' => 's3fuel', 'value' => number_format($stage3_initial_fuel, 9, '.', '')],
    ['attribute' => 'service_module', 'value' => '1'],
    ['attribute' => 'fuel', 'value' => number_format($service_module_initial_fuel, 9, '.', '')],
    ['attribute' => 'RCS', 'value' => number_format($csm_rcs_initial_propellant, 9, '.', '')],
    ['attribute' => 'pitch', 'value' => '0'],
    ['attribute' => 'roll', 'value' => '0'],
    ['attribute' => 'yaw', 'value' => '0'],
    ['attribute' => 'pitch_rate', 'value' => '0'],
    ['attribute' => 'roll_rate', 'value' => '0'],
    ['attribute' => 'yaw_rate', 'value' => '0'],
    ['attribute' => 'thrust', 'value' => '0'],
    ['attribute' => 'parachutes_deployed', 'value' => '0'],
    ['attribute' => 'escape', 'value' => '1'],
    ['attribute' => 'ABORT', 'value' => '0'],
    ['attribute' => 'staging', 'value' => '0'],
    ['attribute' => 'thrusters', 'value' => '0'],
    ['attribute' => 'heat_shield', 'value' => '1420000'],
    ['attribute' => 'crash', 'value' => '0'],
    ['attribute' => 'engine_on', 'value' => '0'],
];

$stmt = $conn->prepare("UPDATE command_module SET value = ? WHERE attribute = ?");
foreach ($updates as $update) {
    $value = $update['value'];
    $attribute = $update['attribute'];
    $stmt->bind_param('ss', $value, $attribute);
    $stmt->execute();
}

// The original database misspells this attribute as "excape_fuel".
// Update either spelling so old and corrected database snapshots both reset.
$conn->query(
    "UPDATE command_module
     SET value = 30
     WHERE attribute IN ('escape_fuel', 'excape_fuel')"
);

// Reset Lunar Module live state.
$updates = [
    ['attribute' => 'x', 'value' => '10006370999'],
    ['attribute' => 'y', 'value' => '10000000000'],
    ['attribute' => 'z', 'value' => '10000000000'],
    ['attribute' => 'delta_x', 'value' => '0'],
    ['attribute' => 'delta_y', 'value' => '0'],
    ['attribute' => 'delta_z', 'value' => '0'],
    ['attribute' => 'descent', 'value' => '1'],
    ['attribute' => 'map_x', 'value' => '0'],
    ['attribute' => 'map_y', 'value' => '0'],
    ['attribute' => 'map_zoom', 'value' => '1'],
    ['attribute' => 'fuel', 'value' => number_format($descent_initial_fuel, 9, '.', '')],
    ['attribute' => 'RCS', 'value' => number_format($lm_rcs_initial_propellant, 9, '.', '')],
    ['attribute' => 'pitch', 'value' => '0'],
    ['attribute' => 'roll', 'value' => '0'],
    ['attribute' => 'yaw', 'value' => '0'],
    ['attribute' => 'pitch_rate', 'value' => '0'],
    ['attribute' => 'roll_rate', 'value' => '0'],
    ['attribute' => 'yaw_rate', 'value' => '0'],
    ['attribute' => 'thrust', 'value' => '0'],
    ['attribute' => 'fuel_ascent', 'value' => number_format($ascent_initial_fuel, 9, '.', '')],
    ['attribute' => 'crash', 'value' => '0'],
];

$stmt = $conn->prepare("UPDATE telemetry SET value = ? WHERE attribute = ?");
foreach ($updates as $update) {
    $value = $update['value'];
    $attribute = $update['attribute'];
    $stmt->bind_param('ss', $value, $attribute);
    $stmt->execute();
}

// Restore original LM/general system availability, but leave the simulator OFF
// until the operator explicitly starts the mission.
$conn->query("UPDATE systems SET status = 1 WHERE id BETWEEN 1 AND 10");
$conn->query("UPDATE systems SET probability = 10000 WHERE id BETWEEN 1 AND 13");
$conn->query("UPDATE systems SET status = 0 WHERE id BETWEEN 11 AND 13");
$conn->query("UPDATE systems SET status = 0 WHERE id BETWEEN 21 AND 22");

// Reset the guidance computer working state without deleting its program library.
$conn->query(
    "UPDATE CMguidance
     SET execute = 0,
         terminate = 0"
);

$conn->query(
    "UPDATE CMguidance
     SET roll = 0,
         pitch = 0,
         yaw = 0,
         roll_rate = 0,
         pitch_rate = 0,
         yaw_rate = 0,
         thrust = 0,
         seconds = 0,
         hold = 0,
         altitude = 0,
         delta_V = 0,
         delta_x = 0,
         delta_y = 0,
         delta_z = 0
     WHERE id = 1"
);

$conn->query(
    "UPDATE CMprogram_register
     SET program_number = 0,
         seconds = NULL"
);

// Reset mission clock to the prelaunch hold.
$conn->query(
    "UPDATE mission_clock
     SET launch_events = 0,
         minutes = 0,
         seconds = -900
     WHERE id = 1"
);

// All engine components start OFF.  The launch guidance and LM throttle
// commands explicitly turn the appropriate engine components ON.
$conn->query(
    "UPDATE EngineStatus
     SET status = 'OFF',
         operational_status = 'OK',
         probability = 10000"
);

$conn->query("TRUNCATE TABLE CMtelemetry");

// Quaternion orientation is derived again from the reset pitch/yaw/roll values
// on the first active physics step.
if (function_exists('moonSimResetAttitudeState')) {
    moonSimResetAttitudeState($conn);
} else {
    // Safe fallback when reset.php is run outside the normal physics worker.
    $conn->query("DROP TABLE IF EXISTS attitude_state");
}

?>
