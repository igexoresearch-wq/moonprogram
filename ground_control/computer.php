<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Calculators</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #1a1a1a;
            color: #f0f0f0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #292929;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        h1, h2 {
            text-align: center;
        }
        form {
            margin-bottom: 20px;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 10px;
            padding: 10px;
            background-color: #444;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Space Calculators</h1>

        <!-- Calculator 1: Distance to Earth -->
        <h2>Distance to Earth</h2>
        <form method="POST">
            <label for="yaw1">Yaw 1 (degrees):</label>
            <input type="number" step="0.01" name="yaw1" id="yaw1" required>
            <label for="yaw2">Yaw 2 (degrees):</label>
            <input type="number" step="0.01" name="yaw2" id="yaw2" required>
            <button type="submit" name="calc_earth">Calculate Distance to Earth</button>
        </form>
        <?php
        if (isset($_POST['calc_earth'])) {
            $yaw1 = $_POST['yaw1'];
            $yaw2 = $_POST['yaw2'];
            $angular_size = abs($yaw1 - $yaw2); // Angular size in degrees
            $earth_diameter = 12742e3; // Earth diameter in meters
            $distance = ($earth_diameter / 2) / tan(deg2rad($angular_size / 2));
            echo "<div class='result'>Distance to Earth: " . number_format($distance, 2) . " meters</div>";
        }
        ?>

        <!-- Calculator 2: Distance to Moon -->
        <h2>Distance to Moon</h2>
        <form method="POST">
            <label for="moon_yaw1">Yaw 1 (degrees):</label>
            <input type="number" step="0.01" name="moon_yaw1" id="moon_yaw1" required>
            <label for="moon_yaw2">Yaw 2 (degrees):</label>
            <input type="number" step="0.01" name="moon_yaw2" id="moon_yaw2" required>
            <button type="submit" name="calc_moon">Calculate Distance to Moon</button>
        </form>
        <?php
        if (isset($_POST['calc_moon'])) {
            $moon_yaw1 = $_POST['moon_yaw1'];
            $moon_yaw2 = $_POST['moon_yaw2'];
            $angular_size = abs($moon_yaw1 - $moon_yaw2); // Angular size in degrees
            $moon_diameter = 3474e3; // Moon diameter in meters
            $distance = ($moon_diameter / 2) / tan(deg2rad($angular_size / 2));
            echo "<div class='result'>Distance to Moon: " . number_format($distance, 2) . " meters</div>";
        }
        ?>

        <!-- Calculator 3: Angle K -->
        <h2>Angle K</h2>
        <form method="POST">
            <label for="distance_e">E (distance from Earth to Moon in meters):</label>
            <input type="number" step="0.01" name="distance_e" id="distance_e" required>
            <label for="angle_M">Moon (angle in degrees):</label>
            <input type="number" step="0.01" name="angle_M" id="angle_M" required>
            <label for="angle_CM">Command Module (angle in degrees):</label>
            <input type="number" step="0.01" name="angle_CM" id="angle_CM" required>
            <label for="distance_f">F (distance from Earth to CM in meters):</label>
            <input type="number" step="0.01" name="distance_f" id="distance_f" required>
            <button type="submit" name="calc_k">Calculate K</button>
        </form>
        <?php
        if (isset($_POST['calc_k'])) {
            $e = $_POST['distance_e'];
            $x = $_POST['angle_M'];
            $y = $_POST['angle_CM'];
            $f = $_POST['distance_f'];
            $z = $y - $x;
            $x_radians = deg2rad(90 - $z);

            // Calculate numerator and denominator for atan
            $numerator = sqrt(pow($e, 2) - pow(sin($x_radians) * $e, 2));
            $denominator = (sin($x_radians) * $e) - $f;

            // Calculate K in radians, then convert to degrees
            $k_radians = atan($numerator / $denominator);
            $k_degrees = rad2deg($k_radians);
            $k_degrees = (360 - ($y - $k_degrees));
            echo "<div class='result'>Angle K: " . number_format($k_degrees, 2) . "¡Æ</div>";
        }
        ?>

        <!-- Calculator 4: Apparent Motion of the Moon -->
        <h2>Apparent Motion of the Moon</h2>
        <form method="POST">
            <label for="motion_earth">Moon's Motion as Seen from Earth (degrees):</label>
            <input type="number" step="0.01" name="motion_earth" id="motion_earth" required>
            <label for="distance_spacecraft">Distance from Spacecraft to Moon (meters):</label>
            <input type="number" step="0.01" name="distance_spacecraft" id="distance_spacecraft" required>
            <label for="distance_earth_to_moon">Distance from Earth to Moon (meters):</label>
            <input type="number" step="0.01" name="distance_earth_to_moon" id="distance_earth_to_moon" value="384400000" required>
            <button type="submit" name="calc_motion">Calculate Apparent Motion</button>
        </form>
        <?php
        if (isset($_POST['calc_motion'])) {
            $motion_earth = $_POST['motion_earth']; // Moon's motion as seen from Earth (degrees)
            $distance_spacecraft = $_POST['distance_spacecraft']; // Distance to spacecraft (meters)
            $distance_earth_to_moon = $_POST['distance_earth_to_moon']; // Earth-Moon distance (meters)

            // Calculate the apparent motion
            $motion_spacecraft = $motion_earth * ($distance_earth_to_moon / $distance_spacecraft);

            echo "<div class='result'>Apparent Motion of the Moon from Spacecraft: " . number_format($motion_spacecraft, 2) . " degrees</div>";
        }
        ?>
    </div>
</body>
</html>
