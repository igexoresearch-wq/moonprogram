<?php
// Database connection




// Step 2: Determine the current stage
$stage_query = $conn->query("SELECT attribute, value FROM command_module WHERE attribute IN ('stage1', 'stage2', 'stage3', 'service_module')");
$stages = [];
while ($row = $stage_query->fetch_assoc()) {
    $stages[$row['attribute']] = intval($row['value']);
}

$current_stage = '';
if ($stages['stage1'] === 1 && $stages['stage2'] === 1 && $stages['stage3'] === 1 && $stages['service_module'] === 1) {
    $current_stage = 'First Stage';
} elseif ($stages['stage1'] === 0 && $stages['stage2'] === 1 && $stages['stage3'] === 1 && $stages['service_module'] === 1) {
    $current_stage = 'Second Stage';
} elseif ($stages['stage2'] === 0 && $stages['stage3'] === 1 && $stages['service_module'] === 1) {
    $current_stage = 'Third Stage';
} elseif ($stages['stage3'] === 0 && $stages['service_module'] === 1) {
    $current_stage = 'Service Module';
}

// Step 3: Simulate stress for components in the current stage
$components_query = $conn->query("SELECT id, engine_name, component, probability, status FROM EngineStatus WHERE engine_stage = '$current_stage'");
while ($component = $components_query->fetch_assoc()) {
    $component_id = $component['id'];
    $engine_name = $component['engine_name'];
    $component_name = $component['component'];
    $probability = floatval($component['probability']);
    $status = $component['status'];

    // Only deduct probability if the component is ON
    if ($status === 'ON') {
        // Fetch fuel consumption and available fuel
        $fuel_query = $conn->query("SELECT fuel_consumption_rate, fuel_available FROM engine_fuel WHERE engine_stage = '$current_stage'");
        $fuel_data = $fuel_query->fetch_assoc();
        $fuel_consumption_rate = floatval($fuel_data['fuel_consumption_rate']);
        $fuel_available = floatval($fuel_data['fuel_available']);

        // Calculate reliability
        $runtime = $fuel_available / $fuel_consumption_rate;  // Time engines can run
        $reliability = ($runtime * 2) / 10000;

        // Apply random stress (0.0 to 2.0)
        $stress_factor = mt_rand(0, 25) / 10.0;
        $stressed_reliability = $reliability * $stress_factor;

        // Adjust probability using thrust percentage
        $thrust_query = $conn->query("SELECT value FROM command_module WHERE attribute = 'thrust'");
        $thrust_value = $thrust_query->fetch_assoc() ? floatval($thrust_query['value']) : 0;
        $thrust_percentage = $thrust_value / 100;

        // Final adjustment to probability
        $adjustment = $stressed_reliability * $thrust_percentage;
        $new_probability = max($probability - $adjustment, 0); // Ensure probability doesn't go below 0

        // Update component probability
        $conn->query("UPDATE EngineStatus SET probability = $new_probability WHERE id = $component_id");

        // Step 4: Handle component failure
        if ($new_probability <= 0) {
            // If the combustion chamber fails, simulate an explosion
            if ($component_name === 'Combustion Chamber') {
                // Mark all components in the current stage as OFF and MALF
                $conn->query("UPDATE EngineStatus SET status = 'OFF', operational_status = 'MALF' WHERE engine_stage = '$current_stage'");

                // Set the stage value in command_module to 0
                $stage_attribute = strtolower(str_replace(' ', '', $current_stage)); // Convert stage name to attribute (e.g., "First Stage" -> "stage1")
                $conn->query("UPDATE command_module SET value = 0 WHERE attribute = '$stage_attribute'");

                // Log the explosion
                echo "Explosion detected in $current_stage! All components marked as MALF. Stage set to 0 in command_module.\n";

                // Break the loop to prevent further simulation on this stage
                break;
            }

            // Otherwise, mark this component as MALF and turn off its engine
            $conn->query("UPDATE EngineStatus SET operational_status = 'MALF' WHERE id = $component_id");
            $conn->query("UPDATE EngineStatus SET status = 'OFF', operational_status = 'MALF' WHERE engine_name = '$engine_name'");

            // Log the event
            echo "Engine '$engine_name' has malfunctioned. All components turned OFF.\n";
        }
    }
}

// Close connection

?>
