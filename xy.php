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

// Handle scale update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['axis']) && isset($_POST['adjustment'])) {
        $axis = $_POST['axis'] === 'x' ? 16 : 17;
        $adjustment = (int)$_POST['adjustment'];

        $updateQuery = "UPDATE telemetry SET value = value + $adjustment WHERE id = $axis";
        $conn->query($updateQuery);
    } elseif (isset($_POST['reset'])) {
        $conn->query("UPDATE telemetry SET value = 0 WHERE id IN (16, 17)");
        $conn->query("UPDATE telemetry SET value = 1 WHERE id = 18");
    } elseif (isset($_POST['zoom']) && isset($_POST['adjustment'])) {
        $zoomAdjustment = (float)$_POST['adjustment'];
        $conn->query("UPDATE telemetry SET value = GREATEST(0.01, LEAST(20.0, value + $zoomAdjustment)) WHERE id = 18");
    }
}

// Fetch telemetry data for x, y, deltaX, deltaY, map_x, map_y, and map_zoom
$xQuery = "SELECT value FROM telemetry WHERE id = 3";
$yQuery = "SELECT value FROM telemetry WHERE id = 4";
$deltaXQuery = "SELECT value FROM telemetry WHERE id = 6";
$deltaYQuery = "SELECT value FROM telemetry WHERE id = 7";
$mapXQuery = "SELECT value FROM telemetry WHERE id = 16";
$mapYQuery = "SELECT value FROM telemetry WHERE id = 17";
$mapZoomQuery = "SELECT value FROM telemetry WHERE id = 18";

$xResult = $conn->query($xQuery);
$yResult = $conn->query($yQuery);
$deltaXResult = $conn->query($deltaXQuery);
$deltaYResult = $conn->query($deltaYQuery);
$mapXResult = $conn->query($mapXQuery);
$mapYResult = $conn->query($mapYQuery);
$mapZoomResult = $conn->query($mapZoomQuery);

$data = [
    "x" => floatval($xResult->fetch_assoc()["value"] ?? 0),
    "y" => floatval($yResult->fetch_assoc()["value"] ?? 0),
    "deltaX" => floatval($deltaXResult->fetch_assoc()["value"] ?? 0),
    "deltaY" => floatval($deltaYResult->fetch_assoc()["value"] ?? 0),
    "mapX" => floatval($mapXResult->fetch_assoc()["value"] ?? 0),
    "mapY" => floatval($mapYResult->fetch_assoc()["value"] ?? 0),
    "mapZoom" => floatval($mapZoomResult->fetch_assoc()["value"] ?? 1)
];

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telemetry Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const moonMass = 7.35e22; // in kg
        const gravitationalConstant = 6.67430e-11; // m^3 kg^-1 s^-2
        const moonRadius = 1740000; // in meters (mean radius)

        let telemetryData = <?php echo json_encode($data); ?>;

        function calculateGravitationalAcceleration(x, y) {
            const distance = Math.sqrt(x ** 2 + y ** 2);
            const forceMagnitude = (gravitationalConstant * moonMass) / (distance ** 2);
            return {
                ax: -forceMagnitude * (x / distance),
                ay: -forceMagnitude * (y / distance)
            };
        }

        function updateScale(axis, operation) {
            const adjustment = operation === 'increase' ? 50000 : -50000;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ axis, adjustment })
            })
                .then(() => location.reload());
        }

        function resetScale() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ reset: true })
            })
                .then(() => location.reload());
        }

        function updateZoom(operation) {
            const adjustment = operation === 'increase' ? 0.05 : -0.05;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ zoom: true, adjustment })
            })
                .then(() => location.reload());
        }

        function renderChart() {
            const ctx = document.getElementById('telemetryChart').getContext('2d');
            const zoom = telemetryData.mapZoom;

            // Calculate path for 1000 seconds
            let path = [];
            let x = telemetryData.x;
            let y = telemetryData.y;
            let dx = telemetryData.deltaX;
            let dy = telemetryData.deltaY;
            const timeStep = 1; // seconds

            for (let t = 0; t < 2000; t += timeStep) {
                const { ax, ay } = calculateGravitationalAcceleration(x, y);
                dx += ax * timeStep;
                dy += ay * timeStep;
                x += dx * timeStep;
                y += dy * timeStep;
                path.push({ x, y });
            }

            const data = {
                datasets: [
                    {
                        label: 'Lunar Module',
                        data: [{ x: telemetryData.x, y: telemetryData.y }],
                        backgroundColor: 'transparent',
                        borderColor: 'yellow',
                        borderWidth: 2,
                        pointStyle: 'circle',
                        pointRadius: 5
                    },
                    {
                        label: 'Trajectory',
                        data: path,
                        borderColor: 'yellow',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        showLine: true,
                        pointRadius: 0
                    },
                    {
                        label: 'Lunar Surface',
                        data: Array.from({ length: 360 }, (_, i) => {
                            const angle = (i * Math.PI) / 180;
                            return {
                                x: moonRadius * Math.cos(angle),
                                y: moonRadius * Math.sin(angle)
                            };
                        }),
                        borderColor: 'yellow',
                        backgroundColor: 'transparent',
                        showLine: true,
                        pointRadius: 0
                    },
                    {
                        label: 'Landing Site',
                        data: [{ x: 1740000, y:0 }],
                        backgroundColor: 'transparent',
                        borderColor: 'yellow',
                        borderWidth: 2,
                        pointStyle: 'rectRot',
                        pointRadius: 5
                    },
                    {
                        label: 'Command Module',
                        data: [{ x: 0, y: 1850000 }],
                        backgroundColor: 'transparent',
                        borderColor: 'yellow',
                        borderWidth: 2,
                        pointStyle: 'triangle',
                        pointRadius: 5
                    }
                ]
            };

            const config = {
                type: 'scatter',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: 'yellow'
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'linear',
                            position: 'bottom',
                            min: (-10000 + telemetryData.mapX) / zoom,
                            max: (1900000 + telemetryData.mapX) / zoom,
                            title: {
                                display: true,
                                text: 'X Position',
                                color: 'yellow'
                            },
                            grid: {
                                color: 'yellow'
                            },
                            ticks: {
                                color: 'yellow'
                            }
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            min: (-20000 + telemetryData.mapY) / zoom,
                            max: (1900000 + telemetryData.mapY) / zoom,
                            title: {
                                display: true,
                                text: 'Y Position',
                                color: 'yellow'
                            },
                            grid: {
                                color: 'yellow'
                            },
                            ticks: {
                                color: 'yellow'
                            }
                        }
                    }
                }
            };

            new Chart(ctx, config);
        }

        document.addEventListener("DOMContentLoaded", () => {
            document.getElementById('increaseX').addEventListener('click', () => updateScale('x', 'increase'));
            document.getElementById('decreaseX').addEventListener('click', () => updateScale('x', 'decrease'));
            document.getElementById('increaseY').addEventListener('click', () => updateScale('y', 'increase'));
            document.getElementById('decreaseY').addEventListener('click', () => updateScale('y', 'decrease'));
            document.getElementById('reset').addEventListener('click', resetScale);
            document.getElementById('zoomIn').addEventListener('click', () => updateZoom('increase'));
            document.getElementById('zoomOut').addEventListener('click', () => updateZoom('decrease'));
            renderChart();
        });
    </script>
</head>
<body style="background-color: black; color: yellow;">
    <div style="width: 600px; height: 600px;">
        <canvas id="telemetryChart"></canvas>
    </div>
    <div>
        <button id="increaseX">Increase X</button>
        <button id="decreaseX">Decrease X</button>
        <button id="increaseY">Increase Y</button>
        <button id="decreaseY">Decrease Y</button>
        <button id="reset">Reset</button>
        <button id="zoomIn">Zoom In</button>
        <button id="zoomOut">Zoom Out</button>
    </div>
</body>
</html>
