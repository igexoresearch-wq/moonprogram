<?php
// Database connection
$host = 'localhost';
$dbname = 'moonland';
$username = 'victorywebproductions';
$password = 'CHANGE_ME';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Constants
    $gravitational_constant = 6.67430e-11; // m^3⋅kg^−1⋅s^−2
    $earth_mass = 5.972e24;               // kg
    $moon_mass = 7.34767309e22;           // kg
    $earthX = 10000000000;                // Earth's fixed X position
    $earthY = 10000000000;                // Earth's fixed Y position
    $earthZ = 10000000000;                          // Earth's fixed Z position

    // Time settings
    $time_step = 60; // 1 minute in seconds
    $total_steps = 80 * 60; // 80 hours in minutes

    // Fetch initial positions and velocities of the Command Module
    $cmdQuery = "SELECT attribute, value FROM command_module WHERE attribute IN ('x', 'y', 'z', 'delta_x', 'delta_y', 'delta_z')";
    $cmdStmt = $conn->prepare($cmdQuery);
    $cmdStmt->execute();
    $command_module = $cmdStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $cmd_x = $command_module['x'];
    $cmd_y = $command_module['y'];
    $cmd_z = $command_module['z'];
    $cmd_vx = $command_module['delta_x'];
    $cmd_vy = $command_module['delta_y'];
    $cmd_vz = $command_module['delta_z'];

    // Fetch initial positions and velocities of the Moon
    $moonQuery = "SELECT attribute, value FROM moon WHERE attribute IN ('x', 'y', 'z', 'delta_x', 'delta_y', 'delta_z')";
    $moonStmt = $conn->prepare($moonQuery);
    $moonStmt->execute();
    $moon = $moonStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $moon_x = $moon['x'];
    $moon_y = $moon['y'];
    $moon_z = $moon['z'];
    $moon_vx = $moon['delta_x'];
    $moon_vy = $moon['delta_y'];
    $moon_vz = $moon['delta_z'];

    // Clear the `command_module_predictions` table
    $conn->exec("TRUNCATE TABLE command_module_predictions");

    // Prepare the insert query
    $insertQuery = $conn->prepare("INSERT INTO command_module_predictions (timestamp, x, y, z) VALUES (:timestamp, :x, :y, :z)");
    $currentTime = new DateTime();

   for ($step = 0; $step <= $total_steps; $step++) {
    // Gravitational force from Earth
    $distance_to_earth = sqrt(pow($cmd_x - $earthX, 2) + pow($cmd_y - $earthY, 2) + pow($cmd_z - $earthZ, 2));
    if ($distance_to_earth > 0) {
        $force_earth = $gravitational_constant * $earth_mass / pow($distance_to_earth, 2);

        // Calculate angles
        $theta_earth = atan2($earthY - $cmd_y, $earthX - $cmd_x);
        $phi_earth = acos(($earthZ - $cmd_z) / $distance_to_earth);

        // Decompose acceleration into components
        $acceleration_x_earth = $force_earth * sin($phi_earth) * cos($theta_earth);
        $acceleration_y_earth = $force_earth * sin($phi_earth) * sin($theta_earth);
        $acceleration_z_earth = $force_earth * cos($phi_earth);
    } else {
        $acceleration_x_earth = $acceleration_y_earth = $acceleration_z_earth = 0;
    }

    // Gravitational force from Moon
    $distance_to_moon = sqrt(pow($cmd_x - $moon_x, 2) + pow($cmd_y - $moon_y, 2) + pow($cmd_z - $moon_z, 2));
    if ($distance_to_moon > 0) {
        $force_moon = $gravitational_constant * $moon_mass / pow($distance_to_moon, 2);

        // Calculate angles
        $theta_moon = atan2($moon_y - $cmd_y, $moon_x - $cmd_x);
        $phi_moon = acos(($moon_z - $cmd_z) / $distance_to_moon);

        // Decompose acceleration into components
        $acceleration_x_moon = $force_moon * sin($phi_moon) * cos($theta_moon);
        $acceleration_y_moon = $force_moon * sin($phi_moon) * sin($theta_moon);
        $acceleration_z_moon = $force_moon * cos($phi_moon);
    } else {
        $acceleration_x_moon = $acceleration_y_moon = $acceleration_z_moon = 0;
    }

    // Total accelerations on the CM
    $cmd_ax = $acceleration_x_earth + $acceleration_x_moon;
    $cmd_ay = $acceleration_y_earth + $acceleration_y_moon;
    $cmd_az = $acceleration_z_earth + $acceleration_z_moon;

    // Update velocities and positions for the CM
    $cmd_vx += $cmd_ax * $time_step;
    $cmd_vy += $cmd_ay * $time_step;
    $cmd_vz += $cmd_az * $time_step;

    $cmd_x += $cmd_vx * $time_step;
    $cmd_y += $cmd_vy * $time_step;
    $cmd_z += $cmd_vz * $time_step;

    // Add prediction to the database
    $currentTime->modify("+1 minute");
    $insertQuery->execute([
        ':timestamp' => $currentTime->format('Y-m-d H:i:s'),
        ':x' => $cmd_x,
        ':y' => $cmd_y,
        ':z' => $cmd_z
    ]);
}


    echo "Command module predictions updated successfully.";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
