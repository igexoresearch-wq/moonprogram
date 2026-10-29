<?php
// Constants
define('EARTH_RADIUSS', 6371000); // Earth's radius in meters
define('MUU', 3.986004418e14); // Earth's gravitational parameter (m^3/s^2)

// Function to calculate orbital velocity
function calculateVelocityForOrbitChange($currentAltitude, $targetAltitude) {
    // Convert altitudes to orbital radii
    $r_current = EARTH_RADIUSS + $currentAltitude; // Current orbital radius
    $r_target = EARTH_RADIUSS + $targetAltitude;  // Target orbital radius

    // Calculate the semi-major axis of the new orbit
    $a = ($r_current + $r_target) / 2;

    // Calculate the velocity at the current position using the vis-viva equation
    $velocity = sqrt(MUU * (2 / $r_current - 1 / $a));

    return $velocity;
}

// Check if the form is submitted
$requiredVelocity = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentAltitude = isset($_POST['current_altitude']) ? (float)$_POST['current_altitude'] : 0;
    $targetAltitude = isset($_POST['target_altitude']) ? (float)$_POST['target_altitude'] : 0;

    // Calculate the required velocity
    $requiredVelocity = calculateVelocityForOrbitChange($currentAltitude, $targetAltitude);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbital Velocity Calculator</title>
    <style>
        body {
            background-color: black;
            color: yellow;
            font-family: Arial, sans-serif;
        }
        h1 {
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid yellow;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid yellow;
            background-color: black;
            color: yellow;
        }
        button {
            padding: 10px 20px;
            border: 1px solid yellow;
            background-color: black;
            color: yellow;
            cursor: pointer;
        }
        p {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Orbital Velocity Calculator</h1>
        <form method="POST">
            <label for="current_altitude">Current Altitude (meters):</label>
            <input type="number" name="current_altitude" id="current_altitude" step="1" required>

            <label for="target_altitude">Target Altitude (meters):</label>
            <input type="number" name="target_altitude" id="target_altitude" step="1" required>

            <button type="submit">Calculate</button>
        </form>

        <?php if ($requiredVelocity !== null): ?>
            <p><strong>Required Velocity:</strong> <?php echo number_format($requiredVelocity, 2); ?> m/s</p>
        <?php endif; ?>
    </div>
</body>
</html>
