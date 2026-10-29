<?php
$counter = 0;

// Loop 60 times
while ($counter < 60) {
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

// Fetch system status
$systems = $pdo->query("SELECT * FROM systems")->fetchAll(PDO::FETCH_ASSOC);
$sim_on = array_column($systems, 'status', 'system')['sim_on'];
$landed = array_column($systems, 'status', 'system')['landed'];

$descent_stage = array_column($systems, 'status', 'system')['descent_stage '];
if ($sim_on == 1 && $landed == 0) {
    // Fetch telemetry data
$telemetry = $pdo->query("SELECT attribute, value FROM telemetry")->fetchAll(PDO::FETCH_KEY_PAIR);


    // Constants
    if ($descent_stage == 1) {
    $LEM_MASS_EMPTY = 10059; $THRUST_PER_PERCENT = 750.50; $fuel_burn_rate = 0.1455;}
    else {$LEM_MASS_EMPTY = 1875;  $THRUST_PER_PERCENT = 1083.33;   $pdo->exec("UPDATE telemetry SET value = 60 WHERE attribute = 'thrust'"); $fuel_burn_rate = 0.3766;}// Mass in kg
     // Thrust in Newtons per percent
    $GRAVITY_CONSTANT = 6.67430e-11; // m^3/kg/s^2
    $MOON_MASS = 7.342e22; // kg
    $MOON_RADIUS = 1740000; // meters
    
    // Get current state
    $thrust_percent = $telemetry['thrust'];
        $rawrcs = $telemetry['RCS'];
        $rcs = ($rawrcs / 3329) * 275;
    $fuel_mass = $telemetry['fuel'];
    $total_mass = $LEM_MASS_EMPTY + $fuel_mass + $rcs;
   if ($fuel_mass <= 0) { $thrust_percent = 0;  $pdo->exec("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");}

    $pitch = deg2rad($telemetry['pitch']); // Pitch is understood to be between -180 and 180 degrees
    $roll = deg2rad($telemetry['roll']);   // Roll is understood to be between -180 and 180 degrees
    $x = $telemetry['x'];
    $y = $telemetry['y'];
    $z = $telemetry['z'];
    $delta_x = $telemetry['delta_x'];
    $delta_y = $telemetry['delta_y'];
    $delta_z = $telemetry['delta_z'];

    // Check propulsion status
    $propulsion_status = array_column($systems, 'status', 'system')['propulsion'];

    if ($propulsion_status == 1) {
        // Calculate thrust vector
        $thrust_force = $THRUST_PER_PERCENT * $thrust_percent;
        $acceleration = $thrust_force / $total_mass;
        
$thrust_x = $acceleration * (0 - sin($pitch));
$thrust_y = $acceleration * cos($pitch)*cos($roll);
$thrust_z = $acceleration * sin($roll) * sin($pitch);


        // Calculate gravitational acceleration
        $distance_from_origin = sqrt($x**2 + $y**2 + $z**2);
        $gravity_acceleration = $GRAVITY_CONSTANT * $MOON_MASS / $distance_from_origin**2;

        $gravity_x = -($gravity_acceleration * $x / $distance_from_origin);
        $gravity_y = -($gravity_acceleration * $y / $distance_from_origin);
        $gravity_z = -($gravity_acceleration * $z / $distance_from_origin);


// Generate three random decimal numbers between -0.1 and 0.1 with 3 decimal places
$errorx = number_format(mt_rand(-100, 100) / 100000, 4);
$errory = number_format(mt_rand(-100, 100) / 100000, 4);
$errorz = number_format(mt_rand(-100, 100) / 100000, 4);





        // Update deltas
        $delta_x += $thrust_x + $gravity_x + $errorx;
        $delta_y += $thrust_y + $gravity_y + $errory;
        $delta_z += $thrust_z + $gravity_z + $errorz;
    }

    // Update positions
    $x += $delta_x;
    $y += $delta_y;
    $z += $delta_z;

    // Update pitch and roll
    $pitch_rate = $telemetry['pitch_rate'];
    $roll_rate = $telemetry['roll_rate'];
    $pitch += deg2rad($pitch_rate);
    $roll += deg2rad($roll_rate);

    // Ensure pitch and roll stay within -180 to 180 degrees
    $pitch = rad2deg($pitch);
    $roll = rad2deg($roll);
    if ($pitch > 180) $pitch -= 360;
    if ($pitch < -180) $pitch += 360;
    if ($roll > 180) $roll -= 360;
    if ($roll < -180) $roll += 360;

    // Update fuel
    $fuel_burn = $fuel_burn_rate * $thrust_percent;
    $fuel_mass = max(0, $fuel_mass - $fuel_burn);

    // Update altitude
    $altitude = $distance_from_origin - $MOON_RADIUS;
    if ($altitude <= 0) {
        $altitude = 0;
        $pdo->exec("UPDATE systems SET status = 1 WHERE system = 'landed'");
        if ($delta_x >= 2  || $delta_y >= 2  || $delta_z >= 2) { $pdo->exec("UPDATE systems SET status = 1 WHERE system = 'crashed'");}
        $delta_x = 0;   $delta_y = 0;   $delta_z = 0;
        $pdo->exec("UPDATE telemetry SET value = 0 WHERE attribute = 'roll_rate'");
        $pdo->exec("UPDATE telemetry SET value = 0 WHERE attribute = 'pitch_rate'");
                $pdo->exec("UPDATE telemetry SET value = 0 WHERE attribute = 'delta_X'");
                                $pdo->exec("UPDATE telemetry SET value = 0 WHERE attribute = 'delta_Y'");
                                                $pdo->exec("UPDATE telemetry SET value = 0 WHERE attribute = 'delta_Z'");
    }

    // Update telemetry table
    $stmt = $pdo->prepare("UPDATE telemetry SET value = :value WHERE attribute = :attribute");
    $stmt->execute(['value' => $x, 'attribute' => 'x']);
    $stmt->execute(['value' => $y, 'attribute' => 'y']);
    $stmt->execute(['value' => $z, 'attribute' => 'z']);
    $stmt->execute(['value' => $delta_x, 'attribute' => 'delta_x']);
    $stmt->execute(['value' => $delta_y, 'attribute' => 'delta_y']);
    $stmt->execute(['value' => $delta_z, 'attribute' => 'delta_z']);
    $stmt->execute(['value' => $pitch, 'attribute' => 'pitch']);
    $stmt->execute(['value' => $roll, 'attribute' => 'roll']);
    $stmt->execute(['value' => $fuel_mass, 'attribute' => 'fuel']);
    $stmt->execute(['value' => $altitude, 'attribute' => 'altitude']);

    // Check system statuses and probabilities for systems with id between 1 and 10
    $systems_check = $pdo->query("SELECT * FROM systems WHERE id BETWEEN 1 AND 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($systems_check as $system) {
        if ($system['status'] == 1) {
            if ($system['probability'] == 1) {
                // Set status to 0 and update probability
                $last_probability = $system['probability'];
                $new_probability = rand(1, $last_probability);

                $update_stmt = $pdo->prepare("UPDATE systems SET status = 0, probability = :probability WHERE id = :id");
                $update_stmt->execute(['probability' => $new_probability, 'id' => $system['id']]);
            }
        }
    }
  


    

    


if ($fuel_mass <= 0) { $thrust_percent = 0;  $pdo->exec("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");}

}

 sleep(1);

    // Increment the counter
    $counter++;
}include ground_control/radio.php
?>  
