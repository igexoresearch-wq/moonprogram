<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trajectory Calculator</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: black;
            color: #ffff00;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            font-size: 28px;
        }
        form {
            width: 60%;
            margin: 20px auto;
            padding: 20px;
            border: 2px solid #ffff00;
        }
        input, button {
            width: calc(100% - 22px);
            margin: 10px 0;
            padding: 10px;
            border: 2px solid #ffff00;
            background-color: black;
            color: #ffff00;
            font-size: 16px;
        }
        button {
            cursor: pointer;
        }
        button:hover {
            background-color: #ffff00;
            color: black;
        }
    </style>
</head>
<body>
    <h1>Trajectory Calculator</h1>
    <form id="trajectoryForm">
        <label>Target X Coordinate:</label>
        <input type="number" id="targetX" placeholder="Enter target X">
        <label>Target Y Coordinate:</label>
        <input type="number" id="targetY" placeholder="Enter target Y">
        <label>Target Z Coordinate:</label>
        <input type="number" id="targetZ" placeholder="Enter target Z">
        <label>Target Velocity (m/s):</label>
        <input type="number" id="velocity" placeholder="Enter target velocity">
        <button type="button" onclick="calculateTrajectory()">Calculate Trajectory</button>
    </form>
    <div id="output"></div>
    <script>
        function calculateTrajectory() {
            const targetX = parseFloat(document.getElementById('targetX').value);
            const targetY = parseFloat(document.getElementById('targetY').value);
            const targetZ = parseFloat(document.getElementById('targetZ').value);
            const velocity = parseFloat(document.getElementById('velocity').value);

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ targetX, targetY, targetZ, velocity })
            })
            .then(response => response.json())
            .then(data => {
                const outputDiv = document.getElementById('output');
                outputDiv.innerHTML = `<pre>${data.instructions || data.error}</pre>`;
            })
            .catch(error => {
                const outputDiv = document.getElementById('output');
                outputDiv.innerHTML = `<pre>Error: ${error.message}</pre>`;
            });
        }
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['targetX'], $data['targetY'], $data['targetZ'], $data['velocity'])) {
            echo json_encode(["error" => "Invalid input"]);
            exit;
        }

        // Moon constants
        $moon_gravity = 1.62; // m/s^2
        $moon_radius = 1740000; // meters
        $thrust_per_percent = 445; // Newtons
        $max_thrust_percent = 60; // Max thrust percentage

        // Simulate telemetry data
        $currentTelemetry = [
            'x' => 0,
            'y' => 1850000,
            'z' => 0,
            'delta_x' => 1642,
            'delta_y' => 0,
            'delta_z' => 0,
            'fuel' => 8242
        ];

        $base_mass = 10059; // kg (Descent stage included)
        $current_mass = $base_mass + $currentTelemetry['fuel'];

        // Calculate trajectory without intervention
        $dx = $data['targetX'] - $currentTelemetry['x'];
        $dy = $data['targetY'] - $currentTelemetry['y'];
        $dz = $data['targetZ'] - $currentTelemetry['z'];

        $distance = sqrt($dx**2 + $dy**2 + $dz**2);

        // Current velocity vector and magnitude
        $current_velocity_vector = [
            'x' => $currentTelemetry['delta_x'],
            'y' => $currentTelemetry['delta_y'],
            'z' => $currentTelemetry['delta_z']
        ];
        $current_velocity_magnitude = sqrt(
            $current_velocity_vector['x']**2 +
            $current_velocity_vector['y']**2 +
            $current_velocity_vector['z']**2
        );

        // Target velocity vector
        $target_velocity_vector = [
            'x' => ($dx / $distance) * $data['velocity'],
            'y' => ($dy / $distance) * $data['velocity'],
            'z' => ($dz / $distance) * $data['velocity']
        ];

        // Delta velocity vector
        $delta_velocity_vector = [
            'x' => $target_velocity_vector['x'] - $current_velocity_vector['x'],
            'y' => $target_velocity_vector['y'] - $current_velocity_vector['y'],
            'z' => $target_velocity_vector['z'] - $current_velocity_vector['z']
        ];

        $delta_velocity_magnitude = sqrt(
            $delta_velocity_vector['x']**2 +
            $delta_velocity_vector['y']**2 +
            $delta_velocity_vector['z']**2
        );

        // Thrust calculations
        $thrust = $thrust_per_percent * ($max_thrust_percent / 100);
        $burn_time = abs($delta_velocity_magnitude) / ($thrust / $current_mass);

        // Fuel consumption
        $fuel_consumed = $burn_time * ($max_thrust_percent / 100) * 0.1455; // kg

        if ($currentTelemetry['fuel'] < $fuel_consumed) {
            echo json_encode(["error" => "Not enough fuel for this maneuver"]);
            exit;
        }

        // Output instructions
        $instructions = "To reach target:\n";
        $instructions .= "Burn engines at 60% for: " . round($burn_time, 2) . " seconds\n";
        $instructions .= "Estimated fuel consumed: " . round($fuel_consumed, 2) . " kg\n";

        echo json_encode(["instructions" => $instructions]);
    }
    ?>
</body>
</html>
