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
    <title>3D Visualization</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            let eightBallData = <?php echo json_encode($data); ?>;

            function renderEightBallChart() {
                const ctx = document.getElementById('eightBallChart').getContext('2d');

                if (!eightBallData) {
                    ctx.font = '16px Arial';
                    ctx.fillStyle = 'yellow';
                    ctx.fillText('No Data Available', 150, 150);
                    return;
                }

                const dbPitch = eightBallData.pitch;
                const dbRoll = eightBallData.roll;

                const calculatedPitch = 90 - Math.atan2(eightBallData.y, eightBallData.x) * (180 / Math.PI) + dbPitch;
                const calculatedRoll = 90 - Math.atan2(eightBallData.y, eightBallData.z) * (180 / Math.PI) + dbRoll;

                ctx.clearRect(0, 0, 200, 200); ctx.canvas.width = 200; ctx.canvas.height = 200;
                ctx.fillStyle = 'black';
                ctx.fillRect(0, 0, 200, 200); ctx.canvas.style.width = '200px'; ctx.canvas.style.height = '200px';

                ctx.strokeStyle = 'yellow';
                ctx.lineWidth = 2;

                ctx.beginPath();
                const horizonY = 200 - (calculatedPitch / 90) * 200;
                ctx.moveTo(0, horizonY);
                ctx.lineTo(200, horizonY);
                ctx.stroke();

                ctx.save();
                ctx.translate(100, 100);
                ctx.rotate((calculatedRoll * Math.PI) / 180);
                ctx.beginPath();
                ctx.moveTo(-50, 0);
                ctx.lineTo(50, 0);
                ctx.stroke();
                ctx.restore();

                ctx.setLineDash([5, 5]);
                ctx.beginPath();
                ctx.moveTo(100, 0);
                ctx.lineTo(100, 200);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(0, 100);
                ctx.lineTo(200, 100);
                ctx.stroke();
                ctx.setLineDash([]);

                ctx.fillStyle = 'yellow';
                ctx.font = '16px Arial';
                ctx.fillText(`Pitch: ${calculatedPitch.toFixed(2)}°`, 10, 20);
                ctx.fillText(`Roll: ${calculatedRoll.toFixed(2)}°`, 10, 40);
            }

            document.addEventListener("DOMContentLoaded", () => {
                renderEightBallChart();
            });
        })();

  (function() {
    let hexTelemetryData = <?php echo json_encode($data); ?>;

    function renderHexWindow() {
        const canvas = document.getElementById('hexWindowCanvas');
        const ctx = canvas.getContext('2d');

        if (!hexTelemetryData) {
            ctx.font = '16px Arial';
            ctx.fillStyle = 'yellow';
            ctx.fillText('No Data Available', 200, 250);
            return;
        }

        // Adjust pitch and roll to match the tangent plane of a sphere centered on the origin
        const adjustedPitch = 90 - Math.atan2(hexTelemetryData.y, hexTelemetryData.x) * (180 / Math.PI) + hexTelemetryData.pitch;
        const adjustedRoll = 90 - Math.atan2(hexTelemetryData.y, hexTelemetryData.z) * (180 / Math.PI) + hexTelemetryData.roll;
        const hexZ = hexTelemetryData.z;
        const hexDistance = Math.sqrt(
            hexTelemetryData.x ** 2 + hexTelemetryData.y ** 2 + hexTelemetryData.z ** 2
        );

        const hexZoom = Math.max(
            1,
            Math.min(2, 1 + ((hexDistance - 1740000) / (1850000 - 1740000)))
        );

        const img = new Image();
        img.src = 'moon.jpg';
        img.onload = () => {
            ctx.fillStyle = 'darkgrey';
            ctx.fillRect(0, 0, 500, 500);
            ctx.clearRect(0, 0, 500, 500);

            ctx.save();
            ctx.beginPath();
            for (let i = 0; i < 6; i++) {
                const angle = (Math.PI / 3) * i - Math.PI / 2;
                const x = 250 + 250 * Math.cos(angle);
                const y = 250 + 250 * Math.sin(angle);
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            }
            ctx.closePath();
            ctx.clip();

            const offsetX = ((adjustedRoll / 90) * 1000) / hexZoom;
            const offsetY = ((adjustedPitch / 90) * 1000) / hexZoom;

            ctx.translate(250, 250);
            ctx.rotate((adjustedRoll * Math.PI) / 180);

            const sourceX = 2048 - (1000 / 2) * hexZoom - offsetX * hexZoom;
            const sourceY = 2048 - (1000 / 2) * hexZoom - offsetY * hexZoom - 200;
            const sourceWidth = 1000 * hexZoom;
            const sourceHeight = 1000 * hexZoom;

            ctx.drawImage(
                img,
                sourceX,
                sourceY,
                sourceWidth,
                sourceHeight,
                -250,
                -250,
                500,
                500
            );

            ctx.restore();

            ctx.strokeStyle = 'lightgrey';
            ctx.lineWidth = 5;
            ctx.beginPath();
            for (let i = 0; i < 6; i++) {
                const angle = (Math.PI / 3) * i - Math.PI / 2;
                const x = 250 + 250 * Math.cos(angle);
                const y = 250 + 250 * Math.sin(angle);
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            }
            ctx.closePath();
            ctx.stroke();

            ctx.strokeStyle = 'white';
            ctx.lineWidth = 1;
            for (let i = -90; i <= 90; i += 5) {
                const y = 250 + (i / 90) * 250;
                ctx.beginPath();
                ctx.moveTo(240, y);
                ctx.lineTo(260, y);
                ctx.stroke();

                if (i % 10 === 0) {
                    ctx.font = '10px Arial';
                    ctx.fillStyle = 'white';
                    ctx.fillText(`${i}°`, 265, y + 3);
                }
            }
        };
    }

    document.addEventListener("DOMContentLoaded", () => {
        renderHexWindow();
    });
})();

    </script>
</head>
<body style="background-color: darkgrey; color: yellow;">
    <div style="display: flex; justify-content: space-around;">
        <table><tr><td><canvas id="eightBallChart" width="200" height="200"></canvas></td><td>
        <canvas id="hexWindowCanvas" width="500" height="500"></canvas></td></tr></table>
    </div>
</body>
</html>
