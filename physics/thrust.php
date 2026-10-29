<?php


//engines off calc
// First Stage: Combustion Chambers ON Fraction
$total_query = $conn->query("SELECT COUNT(*) AS total FROM EngineStatus WHERE engine_stage = 'First Stage' AND component = 'Combustion Chamber'");
$total_data = $total_query->fetch_assoc();
$total_chambers = intval($total_data['total']);

$on_query = $conn->query("SELECT COUNT(*) AS on_count FROM EngineStatus WHERE engine_stage = 'First Stage' AND component = 'Combustion Chamber' AND status = 'ON'");
$on_data = $on_query->fetch_assoc();
$on_chambers = intval($on_data['on_count']);

$first_stage_fraction = $total_chambers > 0 ? $on_chambers / $total_chambers : 0;

// Second Stage: Combustion Chambers ON Fraction
$total_query = $conn->query("SELECT COUNT(*) AS total FROM EngineStatus WHERE engine_stage = 'Second Stage' AND component = 'Combustion Chamber'");
$total_data = $total_query->fetch_assoc();
$total_chambers = intval($total_data['total']);

$on_query = $conn->query("SELECT COUNT(*) AS on_count FROM EngineStatus WHERE engine_stage = 'Second Stage' AND component = 'Combustion Chamber' AND status = 'ON'");
$on_data = $on_query->fetch_assoc();
$on_chambers = intval($on_data['on_count']);

$second_stage_fraction = $total_chambers > 0 ? $on_chambers / $total_chambers : 0;


// Third Stage: Combustion Chambers ON Fraction
$total_query = $conn->query("SELECT COUNT(*) AS total FROM EngineStatus WHERE engine_stage = 'Third Stage' AND component = 'Combustion Chamber'");
$total_data = $total_query->fetch_assoc();
$total_chambers = intval($total_data['total']);

$on_query = $conn->query("SELECT COUNT(*) AS on_count FROM EngineStatus WHERE engine_stage = 'Third Stage' AND component = 'Combustion Chamber' AND status = 'ON'");
$on_data = $on_query->fetch_assoc();
$on_chambers = intval($on_data['on_count']);

$third_stage_fraction = $total_chambers > 0 ? $on_chambers / $total_chambers : 0;
// Service Module: Combustion Chambers ON Fraction
$total_query = $conn->query("SELECT COUNT(*) AS total FROM EngineStatus WHERE engine_stage = 'Service Module' AND component = 'Combustion Chamber'");
$total_data = $total_query->fetch_assoc();
$total_chambers = intval($total_data['total']);

$on_query = $conn->query("SELECT COUNT(*) AS on_count FROM EngineStatus WHERE engine_stage = 'Service Module' AND component = 'Combustion Chamber' AND status = 'ON'");
$on_data = $on_query->fetch_assoc();
$on_chambers = intval($on_data['on_count']);

$service_module_fraction = $total_chambers > 0 ? $on_chambers / $total_chambers : 0;


// Lunar Module Ascent: Combustion Chambers ON Fraction
$total_query = $conn->query("SELECT COUNT(*) AS total FROM EngineStatus WHERE engine_stage = 'Lunar Module Ascent' AND component = 'Combustion Chamber'");
$total_data = $total_query->fetch_assoc();
$total_chambers = intval($total_data['total']);

$on_query = $conn->query("SELECT COUNT(*) AS on_count FROM EngineStatus WHERE engine_stage = 'Lunar Module Ascent' AND component = 'Combustion Chamber' AND status = 'ON'");
$on_data = $on_query->fetch_assoc();
$on_chambers = intval($on_data['on_count']);

$lunar_ascent_fraction = $total_chambers > 0 ? $on_chambers / $total_chambers : 0;

// Lunar Module Descent: Combustion Chambers ON Fraction
$total_query = $conn->query("SELECT COUNT(*) AS total FROM EngineStatus WHERE engine_stage = 'Lunar Module Descent' AND component = 'Combustion Chamber'");
$total_data = $total_query->fetch_assoc();
$total_chambers = intval($total_data['total']);

$on_query = $conn->query("SELECT COUNT(*) AS on_count FROM EngineStatus WHERE engine_stage = 'Lunar Module Descent' AND component = 'Combustion Chamber' AND status = 'ON'");
$on_data = $on_query->fetch_assoc();
$on_chambers = intval($on_data['on_count']);

$lunar_descent_fraction = $total_chambers > 0 ? $on_chambers / $total_chambers : 0;



// Calculate thrust and fuel consumption for the Command Module
if ($escapeCM == 1 && $ABORT_SWITCH == 1) {
    $current_thrust = $stage1_thrust * ($thrustCM / 100 * $first_stage_fraction);
    $fuel_consumption = 1;
    $current_mass = $totalSaturnV; // Use total Saturn V mass
} elseif ($stage1CM == 1) {
    $current_thrust = $stage1_thrust * ($thrustCM / 100 * $first_stage_fraction);
    $fuel_consumption = $stage1_fuel_rate * ($thrustCM / 100 * $first_stage_fraction);
    $current_mass = $totalSaturnV; // Use total Saturn V mass
} elseif ($stage2CM == 1) {
    $current_thrust = $stage2_thrust * ($thrustCM / 100 * $second_stage_fraction);
    $fuel_consumption = $stage2_fuel_rate * ($thrustCM / 100 * $second_stage_fraction);
    $current_mass = $totalSaturnV; // Still within Saturn V
} elseif ($stage3CM == 1) {
    $current_thrust = $stage3_thrust * ($thrustCM / 100 * $third_stage_fraction);
    $fuel_consumption = $stage3_fuel_rate * ($thrustCM / 100 * $third_stage_fraction);
    $current_mass = $totalSaturnV; // Adjust if you split off the upper stage
} elseif ($service_moduleCM == 1) {
    $current_thrust = $service_module_thrust * (($thrustCM + $thrustersCM) / 100) ;
    $fuel_consumption = $service_module_fuel_rate * (($thrustCM + abs($thrustersCM)) / 100);
    $current_mass = $totalSaturnV; // Post-Saturn mass, depending on configuration
} else {
    $current_thrust = 0;
    $fuel_consumption = 0;
    $current_mass = $totalSaturnV; // Assume intact for fallback
}





    $pitch_rad = deg2rad($pitchCM);
    $yaw_rad = deg2rad($yawCM);
    $roll_rad = deg2rad($rollCM);

    // Transform thrust vector based on pitch, yaw, and roll
    $thrust_x = $current_thrust * cos($pitch_rad) * cos($yaw_rad);
    $thrust_y = $current_thrust * cos($pitch_rad) * (-1 * sin($yaw_rad));
    $thrust_z = $current_thrust * sin($pitch_rad);
       $thrustAccelerationCMx = $thrust_x / $current_mass;
      $thrustAccelerationCMy = $thrust_y / $current_mass;
     $thrustAccelerationCMz = $thrust_z / $current_mass;
$thrustFuelConsumptionCM = $fuel_consumption;

// Calculate thrust and fuel consumption for the Lunar Module
if ($descentLM == 1) {
    $current_thrust_LM = $lander_descent_thrust * ($thrustLM / 100 * $lunar_descent_fraction);
    $fuel_consumption_LM = $descent_stage_fuel_rate * ($thrustLM / 100 *$lunar_descent_fraction);
    $current_mass_LM = $totalLEM;
} else {
     $current_thrust_LM = $lander_ascent_thrust * ($thrustLM / 100 * $lunar_ascent_fraction);
    $fuel_consumption_LM = $ascent_stage_fuel_rate * ($thrustLM / 100 * $lunar_ascent_fraction);
    $current_mass_LM = $totalLEM;
}

    $pitch_rad = deg2rad($pitchLM);
    $yaw_rad = deg2rad($yawLM);
    $roll_rad = deg2rad($rollLM);

    // Transform thrust vector based on pitch, yaw, and roll
    $thrust_x = $current_thrust_LM * cos($pitch_rad) * cos($yaw_rad);
    $thrust_y = $current_thrust_LM * cos($pitch_rad) * sin($yaw_rad);
    $thrust_z = $current_thrust_LM * sin($pitch_rad);
       $thrustAccelerationLMx = $thrust_x / $current_mass_LM;
      $thrustAccelerationLMy = $thrust_y / $current_mass_LM;
     $thrustAccelerationLMz = $thrust_z / $current_mass_LM;
$thrustFuelConsumptionLM = $fuel_consumption_LM;

$update_query = "UPDATE command_module SET value = 0 WHERE attribute = 'thrusters'";
 if ($conn->query($update_query) === TRUE) {
        }  
?>
