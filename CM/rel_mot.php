<?php
// Database connection
$servername = "localhost";
$username = "victorywebproductions";
$password = "CHANGE_ME";
$dbname = "moonland";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch telemetry data
$telemetry = [];
$attributes = ['pitch', 'roll', 'delta_x', 'delta_y', 'delta_z'];
$sql_telemetry = "SELECT attribute, value FROM telemetry WHERE attribute IN ('" . implode("','", $attributes) . "')";
$result_telemetry = $conn->query($sql_telemetry);

if ($result_telemetry && $result_telemetry->num_rows > 0) {
    while ($row = $result_telemetry->fetch_assoc()) {
        $telemetry[$row['attribute']] = floatval($row['value']);
    }
} else {
    die("Failed to fetch telemetry data.");
}

// Assign variables
$pitch = $telemetry['pitch'] ?? 0; // Default to 0 if not found
$roll = $telemetry['roll'] ?? 0; // Default to 0 if not found
$delta_x = $telemetry['delta_x'] ?? 0;
$delta_y = $telemetry['delta_y'] ?? 0;
$delta_z = $telemetry['delta_z'] ?? 0;

// Calculate speed
$speed = sqrt($delta_x**2 + $delta_y**2 + $delta_z**2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attitude Visualization</title>
    <style>
        /* Ensure the canvas and its container do not affect other elements */
        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 200px; /* Fixed width */
            height: 200px; /* Fixed height */
            margin: 0 auto; /* Center within its container */
            background-color: black; /* Matches chart background */
            border: 1px solid #888888; /* Optional border to match styling */
        }

        canvas {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Chart Container -->
    <div class="chart-container">
        <canvas id="motionChart" width="200" height="200"></canvas>
    </div>

    <script>
        // Initialize canvas and context
        const canvas = document.getElementById('motionChart');
        const ctx = canvas.getContext('2d');

        // Data fetched from PHP
        const pitch = <?php echo json_encode($pitch); ?>;
        const roll = <?php echo json_encode($roll); ?>;
        const speed = <?php echo json_encode($speed); ?>;

        // Draw static chart elements
        function drawChart() {
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Set border and background
            ctx.fillStyle = '#000000'; // Black background
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.strokeStyle = '#888888'; // Grey border
            ctx.lineWidth = 2;
            ctx.strokeRect(0, 0, canvas.width, canvas.height);

            // Draw cross in the center
            ctx.strokeStyle = '#888888';
            ctx.lineWidth = 1;

            // Vertical line
            ctx.beginPath();
            ctx.moveTo(canvas.width / 2, 0);
            ctx.lineTo(canvas.width / 2, canvas.height);
            ctx.stroke();

            // Horizontal line
            ctx.beginPath();
            ctx.moveTo(0, canvas.height / 2);
            ctx.lineTo(canvas.width, canvas.height / 2);
            ctx.stroke();
        }

        // Draw the lateral motion dot and ascent/descent needle
        function updateMotion(pitch, roll, speed) {
            drawChart(); // Redraw the base chart

            // Calculate lateral motion relative to the craft
            const forwardMotion = speed * Math.cos((pitch * Math.PI) / 180);
            const lateralMotion = speed * Math.sin((roll * Math.PI) / 180);

            // Scale for the chart (10 m/s max range)
            const scale = canvas.width / 20;

            // Dot position
            const dotX = canvas.width / 2 + lateralMotion * scale;
            const dotY = canvas.height / 2 - forwardMotion * scale;

            // Draw the dot
            ctx.fillStyle = '#FFFF00'; // Red dot
            ctx.beginPath();
            ctx.arc(dotX, dotY, 3, 0, Math.PI * 2);
            ctx.fill();

            // Calculate ascent/descent component
            const ascentDescent = speed * Math.sin((-pitch * Math.PI) / 180);

            // Draw needle on the right side
            const needleCenterY = canvas.height / 2 - ascentDescent * scale;
            ctx.strokeStyle = '#FFFF00'; // Red needle
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(canvas.width - 15, needleCenterY);
            ctx.lineTo(canvas.width - 5, needleCenterY);
            ctx.stroke();
        }

        // Update the chart with telemetry data
        updateMotion(pitch, roll, speed);
    </script>
</body>
</html>
