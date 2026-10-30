<?php

// Command Module / Saturn / LES propellant.
if ((int)$ABORTCM === 1) {
    $escapeFuel = (float)($escape_fuelCM ?? $excape_fuelCM ?? 0);
    $new_fuel = max(0.0, $escapeFuel - (float)$fuel_consumption);

    if ($new_fuel <= 0.0) {
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
    }

    // Support both the original misspelled row and a future corrected row.
    $conn->query(
        "UPDATE command_module
         SET value = $new_fuel
         WHERE attribute IN ('escape_fuel', 'excape_fuel')"
    );
} elseif ((int)$stage1CM === 1) {
    $new_fuel = max(0.0, (float)$s1fuelCM - (float)$fuel_consumption);
    if ($new_fuel <= 0.0) {
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
    }
    $conn->query("UPDATE command_module SET value = $new_fuel WHERE attribute = 's1fuel'");
} elseif ((int)$stage2CM === 1) {
    $new_fuel = max(0.0, (float)$s2fuelCM - (float)$fuel_consumption);
    if ($new_fuel <= 0.0) {
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
    }
    $conn->query("UPDATE command_module SET value = $new_fuel WHERE attribute = 's2fuel'");
} elseif ((int)$stage3CM === 1) {
    $new_fuel = max(0.0, (float)$s3fuelCM - (float)$fuel_consumption);
    if ($new_fuel <= 0.0) {
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
    }
    $conn->query("UPDATE command_module SET value = $new_fuel WHERE attribute = 's3fuel'");
} elseif ((int)$service_moduleCM === 1) {
    $new_fuel = max(0.0, (float)$fuelCM - (float)$fuel_consumption);
    if ($new_fuel <= 0.0) {
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
    }
    $conn->query("UPDATE command_module SET value = $new_fuel WHERE attribute = 'fuel'");
}

// Lunar Module main-engine propellant.
if ((int)$descentLM === 1) {
    $new_fuelLM = max(0.0, (float)$fuelLM - (float)$fuel_consumption_LM);

    if ($new_fuelLM <= 0.0) {
        $conn->query("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");
        $conn->query(
            "UPDATE EngineStatus
             SET status = 'OFF'
             WHERE engine_stage = 'Lunar Module'
               AND engine_name = 'Descent Engine'"
        );
    }

    $conn->query("UPDATE telemetry SET value = $new_fuelLM WHERE attribute = 'fuel'");
} else {
    $new_fuelLM = max(0.0, (float)$fuel_ascentLM - (float)$fuel_consumption_LM);

    if ($new_fuelLM <= 0.0) {
        $conn->query("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");
        $conn->query(
            "UPDATE EngineStatus
             SET status = 'OFF'
             WHERE engine_stage = 'Lunar Module'
               AND engine_name = 'Ascent Engine'"
        );
    }

    $conn->query("UPDATE telemetry SET value = $new_fuelLM WHERE attribute = 'fuel_ascent'");
}
?>
