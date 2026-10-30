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

// Fetch telemetry data for x, z, deltaX, deltaZ and display-only map offsets.
$xQuery = "SELECT value FROM telemetry WHERE id = 3";
$zQuery = "SELECT value FROM telemetry WHERE id = 5";
$deltaXQuery = "SELECT value FROM telemetry WHERE id = 6";
$deltaZQuery = "SELECT value FROM telemetry WHERE id = 8";
$mapXQuery = "SELECT value FROM telemetry WHERE id = 16";
// The original X/Z chart accidentally used telemetry id 19, which is LM RCS
// propellant in the current database.  The second legacy map_y slot (id 17)
// is the existing display-only slot paired with map_x id 16.
$mapZQuery = "SELECT value FROM telemetry WHERE id = 17";
$mapZoomQuery = "SELECT value FROM telemetry WHERE id = 18";

$xResult = $conn->query($xQuery);
$zResult = $conn->query($zQuery);
$deltaXResult = $conn->query($deltaXQuery);
$deltaZResult = $conn->query($deltaZQuery);
$mapXResult = $conn->query($mapXQuery);
$mapZResult = $conn->query($mapZQuery);
$mapZoomResult = $conn->query($mapZoomQuery);

$data = [
    "x" => floatval($xResult->fetch_assoc()["value"] ?? 0),
    "z" => floatval($zResult->fetch_assoc()["value"] ?? 0),
    "deltaX" => floatval($deltaXResult->fetch_assoc()["value"] ?? 0),
    "deltaZ" => floatval($deltaZResult->fetch_assoc()["value"] ?? 0),
    "mapX" => floatval($mapXResult->fetch_assoc()["value"] ?? 0),
    "mapZ" => floatval($mapZResult->fetch_assoc()["value"] ?? 0),
    "mapZoom" => floatval($mapZoomResult->fetch_assoc()["value"] ?? 1)
];

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lateral Motion Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let lateralData = <?php echo json_encode($data); ?>;

        function updateScaleLateral(axis, operation) {
            const adjustment = operation === 'increase' ? 50000 : -50000;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ axis, adjustment })
            })
                .then(() => location.reload());
        }

        function resetScaleLateral() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ reset: true })
            })
                .then(() => location.reload());
        }

        function updateZoomLateral(operation) {
            const adjustment = operation === 'increase' ? 0.05 : -0.05;
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ zoom: true, adjustment })
            })
                .then(() => location.reload());
        }

        function renderLateralChart() {
            const ctx = document.getElementById('lateralChart').getContext('2d');
            const zoom = lateralData.mapZoom;

            // Landing site is at z = 0
            const landingSite = { x: lateralData.x, z: 0 };

            // Calculate trajectory path for 1000 seconds
            let trajectory = [];
            let x = lateralData.x;
            let z = lateralData.z;
            let dx = lateralData.deltaX;
            let dz = lateralData.deltaZ;
            const timeStep = 1; // seconds

            for (let t = 0; t < 1000; t += timeStep) {
                x += dx * timeStep;
                z += dz * timeStep;
                trajectory.push({ x, y: z });
            }

            const data = {
                datasets: [
                    {
                        label: 'Lunar Module',
                        data: [{ x: lateralData.x, y: lateralData.z }],
                        borderColor: 'yellow',
                        borderWidth: 2,
                        pointStyle: 'circle',
                        pointRadius: 5
                    },
                    {
                        label: 'Landing Site',
                        data: [{ x: 1740000, y: 0 }],
                        borderColor: 'yellow',
                        borderWidth: 2,
                        pointStyle: 'rectRot',
                        pointRadius: 5
                    },
                    {
                        label: 'Trajectory',
                        data: trajectory,
                        borderColor: 'yellow',
                        borderDash: [5, 5],
                        backgroundColor: 'transparent',
                        showLine: true,
                        pointRadius: 0
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
                            min: (-10000 + lateralData.mapX) / zoom,
                            max: (1900000 + lateralData.mapX) / zoom,
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
                            min: (-80000 + lateralData.mapZ) / zoom,
                            max: (80000 + lateralData.mapZ) / zoom,
                            title: {
                                display: true,
                                text: 'Z Position',
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
            document.getElementById('increaseX').addEventListener('click', () => updateScaleLateral('x', 'increase'));
            document.getElementById('decreaseX').addEventListener('click', () => updateScaleLateral('x', 'decrease'));
            document.getElementById('increaseZ').addEventListener('click', () => updateScaleLateral('z', 'increase'));
            document.getElementById('decreaseZ').addEventListener('click', () => updateScaleLateral('z', 'decrease'));
            document.getElementById('reset').addEventListener('click', resetScaleLateral);
            document.getElementById('zoomIn').addEventListener('click', () => updateZoomLateral('increase'));
            document.getElementById('zoomOut').addEventListener('click', () => updateZoomLateral('decrease'));
            renderLateralChart();
        });
    </script>
</head>
<body style="background-color: black; color: yellow;">
    <div style="width: 600px; height: 200px;">
        <canvas id="lateralChart"></canvas>
    </div>
    <div>
        <button id="increaseX">Increase X</button>
        <button id="decreaseX">Decrease X</button>
        <button id="increaseZ">Increase Z</button>
        <button id="decreaseZ">Decrease Z</button>
        <button id="reset">Reset</button>
        <button id="zoomIn">Zoom In</button>
        <button id="zoomOut">Zoom Out</button>
    </div>
</body>
</html>
