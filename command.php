<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$db_host = 'localhost';
$db_name = 'moonland';
$db_user = 'victorywebproductions';
$db_pass = 'CHANGE_ME';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Constants for gravity calculations
$GRAVITY_CONSTANT = 6.67430e-11; // m^3/kg/s^2
$MOON_MASS = 7.342e22; // kg
$MOON_RADIUS = 1740000; // meters

// Fetch command_module data
$command_module = $pdo->query("SELECT attribute, value FROM command_module")->fetchAll(PDO::FETCH_KEY_PAIR);

// Extract values
$x = $command_module['x'];
$y = $command_module['y'];
$z = $command_module['z'];
$delta_x = $command_module['delta_x'];
$delta_y = $command_module['delta_y'];
$delta_z = $command_module['delta_z'];

// Calculate the distance from the Moon's center
$distance_from_origin = sqrt($x**2 + $y**2 + $z**2);

// Ensure we're above the Moon's surface
if ($distance_from_origin > $MOON_RADIUS) {
    // Calculate gravitational acceleration
    $gravity_acceleration = $GRAVITY_CONSTANT * $MOON_MASS / $distance_from_origin**2;

    // Calculate gravity vector components
    $gravity_x = -($gravity_acceleration * $x / $distance_from_origin);
    $gravity_y = -($gravity_acceleration * $y / $distance_from_origin);
    $gravity_z = -($gravity_acceleration * $z / $distance_from_origin);

    // Update velocity deltas with gravity effect
    $delta_x += $gravity_x;
    $delta_y += $gravity_y;
    $delta_z += $gravity_z;

    // Update positions using deltas (assuming 1 second timestep)
    $x += $delta_x;
    $y += $delta_y;
    $z += $delta_z;

    // Update the command_module table
    $stmt = $pdo->prepare("UPDATE command_module SET value = :value WHERE attribute = :attribute");
    $stmt->execute(['value' => $x, 'attribute' => 'x']);
    $stmt->execute(['value' => $y, 'attribute' => 'y']);
    $stmt->execute(['value' => $z, 'attribute' => 'z']);
    $stmt->execute(['value' => $delta_x, 'attribute' => 'delta_x']);
    $stmt->execute(['value' => $delta_y, 'attribute' => 'delta_y']);
    $stmt->execute(['value' => $delta_z, 'attribute' => 'delta_z']);

    echo "Command module updated successfully.";
} else {
    echo "Command module is on the Moon's surface. No further updates needed.";
}
?>
