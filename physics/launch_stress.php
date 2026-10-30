<?php

/*
 * Legacy launch-engine stress model.
 *
 * This file is not currently called by physicsupdate.php, but it is kept
 * repairable for the later failure-simulation pass.  The original version
 * referenced a nonexistent engine_fuel table and wrote operational_status
 * = 'MALF', which is not valid for the current OK/FAILED enum.
 */

$stage_query = $conn->query(
    "SELECT attribute, value
     FROM command_module
     WHERE attribute IN ('stage1','stage2','stage3','service_module')"
);

$stages = [
    'stage1' => 0,
    'stage2' => 0,
    'stage3' => 0,
    'service_module' => 0,
];

if ($stage_query) {
    while ($row = $stage_query->fetch_assoc()) {
        $stages[$row['attribute']] = (int)$row['value'];
    }
}

$current_stage = null;
$fuel_attribute = null;
$fuel_rate = 0.0;
$stage_attribute = null;

if ($stages['stage1'] === 1) {
    $current_stage = 'First Stage';
    $fuel_attribute = 's1fuel';
    $fuel_rate = (float)$stage1_fuel_rate;
    $stage_attribute = 'stage1';
} elseif ($stages['stage2'] === 1) {
    $current_stage = 'Second Stage';
    $fuel_attribute = 's2fuel';
    $fuel_rate = (float)$stage2_fuel_rate;
    $stage_attribute = 'stage2';
} elseif ($stages['stage3'] === 1) {
    $current_stage = 'Third Stage';
    $fuel_attribute = 's3fuel';
    $fuel_rate = (float)$stage3_fuel_rate;
    $stage_attribute = 'stage3';
} elseif ($stages['service_module'] === 1) {
    $current_stage = 'Service Module';
    $fuel_attribute = 'fuel';
    $fuel_rate = (float)$service_module_fuel_rate;
    $stage_attribute = 'service_module';
}

if ($current_stage !== null && $fuel_attribute !== null && $fuel_rate > 0.0) {
    $fuel_attribute_esc = $conn->real_escape_string($fuel_attribute);
    $fuel_result = $conn->query(
        "SELECT value
         FROM command_module
         WHERE attribute = '$fuel_attribute_esc'
         LIMIT 1"
    );
    $fuel_available = ($fuel_result && $fuel_row = $fuel_result->fetch_assoc())
        ? max(0.0, (float)$fuel_row['value'])
        : 0.0;

    $components_query = $conn->query(
        "SELECT id, engine_name, component, probability, status, operational_status
         FROM EngineStatus
         WHERE engine_stage = '" . $conn->real_escape_string($current_stage) . "'"
    );

    if ($components_query) {
        while ($component = $components_query->fetch_assoc()) {
            if (
                $component['status'] !== 'ON'
                || $component['operational_status'] !== 'OK'
            ) {
                continue;
            }

            $component_id = (int)$component['id'];
            $engine_name = (string)$component['engine_name'];
            $component_name = (string)$component['component'];
            $probability = (float)$component['probability'];

            // Preserve the original general stress concept, but derive runtime
            // from the actual live stage propellant instead of a missing table.
            $runtime = $fuel_available / $fuel_rate;
            $reliability_scale = ($runtime * 2.0) / 10000.0;
            $stress_factor = mt_rand(0, 25) / 10.0;

            $thrust_result = $conn->query(
                "SELECT value
                 FROM command_module
                 WHERE attribute = 'thrust'
                 LIMIT 1"
            );
            $thrust_value = ($thrust_result && $thrust_row = $thrust_result->fetch_assoc())
                ? max(0.0, min(100.0, (float)$thrust_row['value']))
                : 0.0;

            $adjustment =
                $reliability_scale
                * $stress_factor
                * ($thrust_value / 100.0);

            $new_probability = max(0.0, $probability - $adjustment);

            $conn->query(
                "UPDATE EngineStatus
                 SET probability = $new_probability
                 WHERE id = $component_id"
            );

            if ($new_probability > 0.0) {
                continue;
            }

            $engine_name_esc = $conn->real_escape_string($engine_name);

            if ($component_name === 'Combustion Chamber') {
                $stage_esc = $conn->real_escape_string($current_stage);
                $conn->query(
                    "UPDATE EngineStatus
                     SET status = 'OFF',
                         operational_status = 'FAILED'
                     WHERE engine_stage = '$stage_esc'"
                );

                $stage_attribute_esc = $conn->real_escape_string($stage_attribute);
                $conn->query(
                    "UPDATE command_module
                     SET value = 0
                     WHERE attribute = '$stage_attribute_esc'"
                );

                error_log("Moon Simulator: combustion chamber failure in $current_stage");
                break;
            }

            $conn->query(
                "UPDATE EngineStatus
                 SET status = 'OFF',
                     operational_status = 'FAILED'
                 WHERE engine_name = '$engine_name_esc'"
            );

            error_log("Moon Simulator: engine component failure in $engine_name");
        }
    }
}
?>
