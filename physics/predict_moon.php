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
    $earthZ = 10000000000;                // Earth's fixed Z position

    // Orbital parameters
    $semi_major_axis = 384400000;         // Average distance from Earth to Moon in meters
    $eccentricity = 0.0549;               // Eccentricity of the Moon's orbit
    $orbital_period = 27.321661 * 24 * 3600; // Orbital period in seconds (27.3 days)

    // Time settings
    $time_step = 60; // 1 minute in seconds
    $total_steps = 80 * 60; // 80 hours in minutes

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


    // Clear the `moon_predictions` table
    $conn->exec("TRUNCATE TABLE moon_predictions");

    // Prepare the insert query
    $insertQuery = $conn->prepare("INSERT INTO moon_predictions (timestamp, x, y, z) VALUES (:timestamp, :x, :y, :z)");
    $currentTime = new DateTime();

    // Simulation loop
    for ($step = 0; $step <= $total_steps; $step++) {
        // Calculate gravitational force from Earth
        $distance_to_earth = sqrt(pow($moon_x - $earthX, 2) + pow($moon_y - $earthY, 2) + pow($moon_z - $earthZ, 2));
        $force_earth = $gravitational_constant * $earth_mass * $moon_mass / pow($distance_to_earth, 2);

        // Calculate acceleration due to Earth's gravity
        $acceleration_x = $force_earth * (($earthX - $moon_x) / $distance_to_earth) / $moon_mass;
        $acceleration_y = $force_earth * (($earthY - $moon_y) / $distance_to_earth) / $moon_mass;
        $acceleration_z = $force_earth * (($earthZ - $moon_z) / $distance_to_earth) / $moon_mass;

        // Update velocity
        $moon_vx += $acceleration_x * $time_step;
        $moon_vy += $acceleration_y * $time_step;
        $moon_vz += $acceleration_z * $time_step;

        // Update position
        $moon_x += $moon_vx * $time_step;
        $moon_y += $moon_vy * $time_step;
        $moon_z += $moon_vz * $time_step;

        // Add prediction to the database
        $currentTime->modify("+1 minute");
        $insertQuery->execute([
            ':timestamp' => $currentTime->format('Y-m-d H:i:s'),
            ':x' => $moon_x,
            ':y' => $moon_y,
            ':z' => $moon_z
        ]);
    }

    echo "Moon predictions updated successfully.";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
