<?php
// Database connection
$servername = "localhost";
$username = "victorywebproductions";
$password = "CHANGE_ME";
$dbname = "moonland";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check system status
$systemQuery = "SELECT status FROM systems WHERE id = 4";
$systemResult = $conn->query($systemQuery);
$systemStatus = $systemResult->fetch_assoc()["status"] ?? 0;

if ($systemStatus == 1) {
    // Fetch telemetry data for pitch, roll, x, y, z
    $pitchQuery = "SELECT value FROM telemetry WHERE id = 1";
    $rollQuery = "SELECT value FROM telemetry WHERE id = 2";
    $xQuery = "SELECT value FROM telemetry WHERE id = 3";
    $yQuery = "SELECT value FROM telemetry WHERE id = 4";
    $zQuery = "SELECT value FROM telemetry WHERE id = 5";

    $pitchResult = $conn->query($pitchQuery);
    $rollResult = $conn->query($rollQuery);
    $xResult = $conn->query($xQuery);
    $yResult = $conn->query($yQuery);
    $zResult = $conn->query($zQuery);

    $data = [
        "pitch" => floatval($pitchResult->fetch_assoc()["value"] ?? 0),
        "roll" => floatval($rollResult->fetch_assoc()["value"] ?? 0),
        "x" => floatval($xResult->fetch_assoc()["value"] ?? 0),
        "y" => floatval($yResult->fetch_assoc()["value"] ?? 0),
        "z" => floatval($zResult->fetch_assoc()["value"] ?? 0)
    ];
} else {
    $data = null;
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D 8-Ball Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let telemetryData = <?php echo json_encode($data); ?>;

        function renderChart() {
            const ctx = document.getElementById('eightBallChart').getContext('2d');

            if (!telemetryData) {
                // No data available
                ctx.font = '16px Arial';
                ctx.fillStyle = 'yellow';
                ctx.fillText('No Data Available', 150, 150);
                return;
            }

            // Break down calculations for pitch and roll
            const dbPitch = telemetryData.pitch;
            const dbRoll = telemetryData.roll;

            const calculatedPitch = 90 - Math.atan2(telemetryData.y, telemetryData.x) * (180 / Math.PI) + dbPitch;
            const calculatedRoll = 90 - Math.atan2(telemetryData.y, telemetryData.z) * (180 / Math.PI) + dbRoll;

            // Draw 3D 8-ball-like chart
            ctx.clearRect(0, 0, 200, 200);
            ctx.fillStyle = 'black';
            ctx.fillRect(0, 0, 200, 200);

            ctx.strokeStyle = 'yellow';
            ctx.lineWidth = 2;

            // Draw horizon line (calculated pitch)
            ctx.beginPath();
            const horizonY = 200 - (calculatedPitch / 90) * 200;
            ctx.moveTo(0, horizonY);
            ctx.lineTo(200, horizonY);
            ctx.stroke();

            // Draw roll indicator (calculated roll)
            ctx.save();
            ctx.translate(100, 100);
            ctx.rotate((calculatedRoll * Math.PI) / 180);
            ctx.beginPath();
            ctx.moveTo(-50, 0);
            ctx.lineTo(50, 0);
            ctx.stroke();
            ctx.restore();

            // Draw dotted cross lines at center
            ctx.setLineDash([5, 5]);
            ctx.beginPath();
            ctx.moveTo(100, 0);
            ctx.lineTo(100, 200);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(0, 100);
            ctx.lineTo(200, 100);
            ctx.stroke();
            ctx.setLineDash([]); // Reset line dash

            // Draw pitch and roll readouts
            ctx.fillStyle = 'yellow';
            ctx.font = '16px Arial';
            ctx.fillText(`Pitch: ${calculatedPitch.toFixed(2)}°`, 10, 20);
            ctx.fillText(`Roll: ${calculatedRoll.toFixed(2)}°`, 10, 40);
        }

        document.addEventListener("DOMContentLoaded", () => {
            renderChart();
        });
        
    </script>
</head>
<body style="background-color: black; color: yellow;">
    <canvas id="eightBallChart" width="200" height="200"></canvas>
</body>
</html>
