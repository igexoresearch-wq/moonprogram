<table><tr><td>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spacecraft Viewport</title>
    <style>
        body {
            margin: 0;
            background: black;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        #viewport {
            position: relative;
            width: 600px;
            height: 600px;
            background: black;
            overflow: hidden;
            border: 2px solid gray;
        }

        .object {
            position: absolute;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body>
    <div id="viewport"></div>

    <script>
        const viewport = document.getElementById("viewport");

        // Constants
        const FOV = 60; // Field of view in degrees
        const FOV_RADIANS = (FOV * Math.PI) / 180; // Convert to radians
        const VIEWPORT_SIZE = 600; // Viewport dimensions in pixels
        const EARTH_RADIUS = 6371e3; // Earth radius in meters
        const MOON_RADIUS = 1737e3; // Moon radius in meters
        const LUNAR_MODULE_SIZE = 4; // Approx. size of Lunar Module in meters
        const EARTH_POSITION = { x: 1e10, y: 1e10, z: 1e10 }; // Earth position

        // Placeholder for database data
        const databaseData = {
            command_module: { x: 1.002e10, y: 1.001e10, z: 9.998e9, pitch: 0, roll: 0, yaw: 0 },
            moon: { x: 1.003e10, y: 1.002e10, z: 1.004e10 },
            telemetry: { x: 1.0015e10, y: 1.0012e10, z: 1.001e10 }
        };

        // Helper function to convert world coordinates to viewport
        function projectToViewport(object, commandModule, pitch, roll, yaw) {
            const dx = object.x - commandModule.x;
            const dy = object.y - commandModule.y;
            const dz = object.z - commandModule.z;

            // Apply rotation based on pitch, roll, and yaw
            const cosPitch = Math.cos(pitch);
            const sinPitch = Math.sin(pitch);
            const cosYaw = Math.cos(yaw);
            const sinYaw = Math.sin(yaw);

            // Rotate around Y-axis (yaw)
            let xRot = cosYaw * dx + sinYaw * dz;
            let zRot = -sinYaw * dx + cosYaw * dz;

            // Rotate around X-axis (pitch)
            let yRot = cosPitch * dy - sinPitch * zRot;
            zRot = sinPitch * dy + cosPitch * zRot;

            // Convert to screen coordinates
            const distance = Math.abs(zRot);
            if (distance === 0) return null; // Avoid division by zero

            const screenX = (xRot / (zRot * Math.tan(FOV_RADIANS / 2))) * (VIEWPORT_SIZE / 2) + VIEWPORT_SIZE / 2;
            const screenY = (yRot / (zRot * Math.tan(FOV_RADIANS / 2))) * (VIEWPORT_SIZE / 2) + VIEWPORT_SIZE / 2;

            return { screenX, screenY, distance };
        }

        // Calculate angular size
        function calculateAngularSize(realSize, distance) {
            const angularSizeRadians = 2 * Math.atan(realSize / (2 * distance));
            return (angularSizeRadians * VIEWPORT_SIZE) / FOV_RADIANS; // Convert to pixels
        }

        // Create and update objects
        function createOrUpdateObject(id, src, size, x, y) {
            let obj = document.getElementById(id);
            if (!obj) {
                obj = document.createElement("img");
                obj.id = id;
                obj.src = src;
                obj.className = "object";
                viewport.appendChild(obj);
            }

            obj.style.width = size + "px";
            obj.style.height = size + "px";
            obj.style.left = x + "px";
            obj.style.top = y + "px";
        }

        // Update simulation
        function updateSimulation() {
            const { command_module, moon, telemetry } = databaseData;

            // Convert pitch, roll, yaw to radians
            const pitch = (command_module.pitch * Math.PI) / 180;
            const roll = (command_module.roll * Math.PI) / 180;
            const yaw = (command_module.yaw * Math.PI) / 180;

            // Project Earth
            const earthProjection = projectToViewport(EARTH_POSITION, command_module, pitch, roll, yaw);
            if (earthProjection) {
                const earthSize = calculateAngularSize(EARTH_RADIUS, earthProjection.distance);
                createOrUpdateObject("earth", "earth.png", earthSize, earthProjection.screenX, earthProjection.screenY);
            }

            // Project Moon
            const moonProjection = projectToViewport(moon, command_module, pitch, roll, yaw);
            if (moonProjection) {
                const moonSize = calculateAngularSize(MOON_RADIUS, moonProjection.distance);
                createOrUpdateObject("moon", "moon.png", moonSize, moonProjection.screenX, moonProjection.screenY);
            }

            // Project Lunar Module
            const telemetryProjection = projectToViewport(telemetry, command_module, pitch, roll, yaw);
            if (telemetryProjection) {
                const moduleSize = calculateAngularSize(LUNAR_MODULE_SIZE, telemetryProjection.distance);
                createOrUpdateObject("telemetry", "Apollo_11_lunar_module.png", moduleSize, telemetryProjection.screenX, telemetryProjection.screenY);
            }

            requestAnimationFrame(updateSimulation);
        }

        // Start simulation
        updateSimulation();
    </script>
</body>
</html>


</td></tr><tr><td><?php
date_default_timezone_set('America/New_York'); // Set timezone to Eastern Time (ET)

// Database connection
include 'db.php';

// Fetch initial Moon position and velocity from the database
$query = "SELECT attribute, value FROM moon WHERE attribute IN ('x', 'y', 'z', 'delta_x', 'delta_y', 'delta_z')";
$result = mysqli_query($conn, $query);

$moonData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $moonData[$row['attribute']] = $row['value'];
    }
} else {
    die("Database query failed: " . mysqli_error($conn));
}

// Extract Moon's initial position and velocity from the database
$xMOON = $moonData['x'];
$yMOON = $moonData['y'];
$zMOON = $moonData['z'];
$delta_xMOON = $moonData['delta_x'];
$delta_yMOON = $moonData['delta_y'];
$delta_zMOON = $moonData['delta_z'];

// Constants
$gravitational_constant = 6.67430e-11; // m^3 kg^-1 s^-2
$earth_mass = 5.972e24; // kg
$moon_mass = 7.348e22; // kg
$tli_velocity = 10.91e3; // m/s (standard TLI velocity)
$moon_orbital_radius = 384400e3; // Average Earth-Moon distance (m)
$moon_orbital_period = 27.32 * 24 * 3600; // Moon's orbital period in seconds (~27.32 days)

// Orbital parameters for parking orbit
$earth_radius = 6.371e6; // m
$orbit_altitude = 200e3; // m (parking orbit altitude)
$orbital_radius = $earth_radius + $orbit_altitude; // Total radius of orbit
$orbital_velocity = sqrt($gravitational_constant * $earth_mass / $orbital_radius); // Orbital velocity (m/s)
$orbital_period = 2 * pi() * sqrt(pow($orbital_radius, 3) / ($gravitational_constant * $earth_mass)); // Orbital period (s)

// Earth's center
$earth_center_x = 1e10;
$earth_center_y = 1e10;
$earth_center_z = 1e10;

// Time steps
$time_step = 3600; // 1 hour in seconds for finer simulation
$max_iterations = 50000; // Increased for accuracy

// Function to simulate Moon's motion
function simulate_moon_motion($xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON, $earth_center_x, $earth_center_y, $earth_center_z, $time_step) {
    // Calculate the distance between the Moon and the Earth
    $distance_to_earth = sqrt(pow($xMOON - $earth_center_x, 2) +
                              pow($yMOON - $earth_center_y, 2) +
                              pow($zMOON - $earth_center_z, 2));

    // Calculate the gravitational force exerted by Earth on the Moon
    $force = $GLOBALS['gravitational_constant'] * $GLOBALS['earth_mass'] * $GLOBALS['moon_mass'] / pow($distance_to_earth, 2);

    // Calculate acceleration components for the Moon
    $acceleration_xMOON = $force * (($earth_center_x - $xMOON) / $distance_to_earth) / $GLOBALS['moon_mass'];
    $acceleration_yMOON = $force * (($earth_center_y - $yMOON) / $distance_to_earth) / $GLOBALS['moon_mass'];
    $acceleration_zMOON = $force * (($earth_center_z - $zMOON) / $distance_to_earth) / $GLOBALS['moon_mass'];

    // Update Moon's velocity based on acceleration
    $delta_xMOONnew = $delta_xMOON + $acceleration_xMOON * $time_step;
    $delta_yMOONnew = $delta_yMOON + $acceleration_yMOON * $time_step;
    $delta_zMOONnew = $delta_zMOON + $acceleration_zMOON * $time_step;

    // Update Moon's position based on velocity
    $newmoonx = $xMOON + $delta_xMOONnew * $time_step;
    $newmoony = $yMOON + $delta_yMOONnew * $time_step;
    $newmoonz = $zMOON + $delta_zMOONnew * $time_step;

    return [
        'x' => $newmoonx,
        'y' => $newmoony,
        'z' => $newmoonz,
        'delta_x' => $delta_xMOONnew,
        'delta_y' => $delta_yMOONnew,
        'delta_z' => $delta_zMOONnew
    ];
}

// Function to find Moon's X-Y plane crossings
function find_plane_crossing($xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON, $earth_center_x, $earth_center_y, $earth_center_z, $time_step, $max_iterations, $offset = 0) {
    $iterations = 0;
    $crossing_found = false;

    while (!$crossing_found && $iterations < $max_iterations) {
        $iterations++;

        // Simulate Moon's motion
        $motion = simulate_moon_motion($xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON, $earth_center_x, $earth_center_y, $earth_center_z, $time_step);

        // Update position and velocity
        $xMOON = $motion['x'];
        $yMOON = $motion['y'];
        $zMOON = $motion['z'];
        $delta_xMOON = $motion['delta_x'];
        $delta_yMOON = $motion['delta_y'];
        $delta_zMOON = $motion['delta_z'];

        // Check if the Moon crosses the X-Y plane
        if (($zMOON - $earth_center_z) * ($zMOON + $delta_zMOON * $time_step - $earth_center_z) < 0) {
            $crossing_found = true;
            $crossing_time = time() + $offset + ($iterations * $time_step);
            return [
                'time' => $crossing_time,
                'x' => $xMOON,
                'y' => $yMOON,
                'z' => $zMOON
            ];
        }
    }

    return null; // Return null if no crossing is found
}

// Find two crossings
$first_crossing = find_plane_crossing($xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON, $earth_center_x, $earth_center_y, $earth_center_z, $time_step, $max_iterations);
$second_crossing = find_plane_crossing($xMOON, $yMOON, $zMOON, $delta_xMOON, $delta_yMOON, $delta_zMOON, $earth_center_x, $earth_center_y, $earth_center_z, $time_step, $max_iterations, $moon_orbital_period);

// Validate crossings
if ($first_crossing === null || $second_crossing === null) {
    die("<p>Error: Unable to find two plane crossings.</p>");
}

// Travel time calculation
$v_start = $tli_velocity;
$v_end = sqrt($gravitational_constant * $earth_mass / $moon_orbital_radius); // Moon orbital velocity
$v_avg = 2 / ((1 / $v_start) + (1 / $v_end)); // Harmonic mean for average velocity
$distance_to_moon = sqrt(pow($first_crossing['x'] - $earth_center_x, 2) +
                         pow($first_crossing['y'] - $earth_center_y, 2) +
                         pow($first_crossing['z'] - $earth_center_z, 2));
$travel_time = $distance_to_moon / $v_avg; // Travel time in seconds (~72 hours)

// Launch, TLI, and Arrival Times
$first_launch_time = $first_crossing['time'] - (2.5 * $orbital_period); // Two full orbits and a partial orbit
$tli_time = $first_crossing['time'];
$arrival_time = $tli_time + $travel_time;

// X-Y Angle and Antipole Angle
$xy_angle_to_moon = rad2deg(atan2($first_crossing['y'] - $earth_center_y, $first_crossing['x'] - $earth_center_x));
if ($xy_angle_to_moon < 0) $xy_angle_to_moon += 360; // Normalize to 0-360°
$antipole_angle = ($xy_angle_to_moon + 180) % 360;

// Output Mission Parameters
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; text-align: left;'>";
echo "<thead><tr><th colspan='2'>Mission Parameters</th></tr></thead><tbody>";
echo "<tr><td>Parking Orbit Altitude</td><td>" . number_format($orbit_altitude, 2) . " m</td></tr>";
echo "<tr><td>Parking Orbit Velocity</td><td>" . number_format($orbital_velocity, 2) . " m/s</td></tr>";
echo "<tr><td>Distance to Moon</td><td>" . number_format($distance_to_moon, 2) . " m</td></tr>";
echo "<tr><td>Travel Time to Moon</td><td>" . gmdate("H:i:s", $travel_time) . " (HH:MM:SS)</td></tr>";
echo "<tr><td>X-Y Angle to Moon</td><td>" . number_format($xy_angle_to_moon, 2) . "°</td></tr>";
echo "<tr><td>Antipole Angle</td><td>" . number_format($antipole_angle, 2) . "°</td></tr>";
echo "<tr><td>Launch Time</td><td>" . date('Y-m-d H:i:s', (int)$first_launch_time) . " (ET)</td></tr>";
echo "<tr><td>TLI Time</td><td>" . date('Y-m-d H:i:s', (int)$tli_time) . " (ET)</td></tr>";
echo "<tr><td>Arrival Time</td><td>" . date('Y-m-d H:i:s', (int)$arrival_time) . " (ET)</td></tr>";
echo "</tbody></table>";
?>


</td></tr></table>