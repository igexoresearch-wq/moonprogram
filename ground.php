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
$attributes = ['delta_x', 'delta_y', 'delta_z', 'altitude', 'pitch', 'roll', 'fuel'];
$sql = "SELECT attribute, value FROM telemetry WHERE attribute IN ('" . implode("','", $attributes) . "')";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $telemetry[$row['attribute']] = floatval($row['value']);
    }
}

// Calculate vector velocity
$vector_velocity = sqrt(
    ($telemetry['delta_x'] ?? 0) ** 2 +
    ($telemetry['delta_y'] ?? 0) ** 2 +
    ($telemetry['delta_z'] ?? 0) ** 2
);

// Fetch system statuses
$systems = [];
$sql = "SELECT id, system, status FROM systems WHERE id BETWEEN 1 AND 10";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $systems[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ground Control</title>
    <style>
        body {
            margin: 0;
            background-color: #000000;
            color: #FFFF00;
            font-family: Arial, sans-serif;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 2fr 1fr;
            height: 100vh;
            gap: 5px;
        }

        .left-div {
            grid-column: 1 / 2;
            grid-row: 1 / 3;
            padding: 15px;
            border: 2px solid #FFFF00;
            overflow-y: auto;
        }

        .top-right-div {
            grid-column: 2 / 3;
            grid-row: 1 / 2;
            padding: 15px;
            border: 2px solid #FFFF00;
            overflow-y: auto;
        }

        .bottom-right-container {
            grid-column: 2 / 3;
            grid-row: 2 / 3;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
        }

        .bottom-right-div {
            padding: 15px;
            border: 2px solid #FFFF00;
            overflow-y: auto;
        }

        .title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            border-bottom: 2px solid #FFFF00;
            padding-bottom: 5px;
        }

        .content {
            font-size: 1.2rem;
            line-height: 1.5;
        }

        .systems-table {
            width: 100%;
            border-collapse: collapse;
            color: #FFFF00;
        }

        .systems-table th,
        .systems-table td {
            border: 1px solid #FFFF00;
            padding: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
<div style="padding:8px;text-align:center">
  <a href="ground_control/orbit_diagnostics.php" style="color:#ffff66">ORBIT DIAGNOSTICS</a>
</div>
    <div class="container">
        <!-- Left Div -->
        <div class="left-div">
            <?php include 'xy.php'; ?>
        </div>

        <!-- Top Right Div -->
        <div class="top-right-div">
            <?php include 'xz.php'; ?>
        </div>

        <!-- Bottom Right Container -->
        <div class="bottom-right-container">
            <!-- Telemetry Column -->
            <div class="bottom-right-div">
                <div class="title">Telemetry</div>
                <div class="content">
                    <p>Delta X:<?php echo number_format($telemetry['delta_x'] ?? 0, 2); ?> m/s</br>
                    Delta Y:<?php echo number_format($telemetry['delta_y'] ?? 0, 2); ?> m/s</br>
                   Delta Z: <?php echo number_format($telemetry['delta_z'] ?? 0, 2); ?> m/s</br>
                   Vector Velocity:<?php echo number_format($vector_velocity, 2); ?> m/s</br>
                  Altitude:<?php echo number_format($telemetry['altitude'] ?? 0, 2); ?> m</br>
                  Pitch:<?php echo number_format($telemetry['pitch'] ?? 0, 2); ?>°</br>
                   Roll: <?php echo number_format($telemetry['roll'] ?? 0, 2); ?>°</br>
                   Fuel:<?php echo number_format($telemetry['fuel'] ?? 0, 2); ?> kg<br>
                   
                   <?php
// Ensure delta_x, delta_y, delta_z are set and valid
$delta_x = $telemetry['delta_x'] ?? 0;
$delta_y = $telemetry['delta_y'] ?? 0;
$delta_z = $telemetry['delta_z'] ?? 0;

// Calculate the vector pitch and roll
$vector_pitch = rad2deg(atan2($delta_z, sqrt($delta_x**2 + $delta_y**2))) - 90; // Subtract 90° for pitch
$vector_roll = rad2deg(atan2($delta_y, $delta_x));

// Normalize pitch and roll to keep them between -180° and 180°
$vector_pitch = ($vector_pitch > 180) ? $vector_pitch - 360 : $vector_pitch;
$vector_pitch = ($vector_pitch < -180) ? $vector_pitch + 360 : $vector_pitch;

$vector_roll = ($vector_roll > 180) ? $vector_roll - 360 : $vector_roll;
$vector_roll = ($vector_roll < -180) ? $vector_roll + 360 : $vector_roll;

// Output the results
echo "Vector Pitch: " . number_format($vector_pitch, 2) . "°<br>";
echo "Vector Roll: " . number_format($vector_roll, 2) . "°<br>";
?>

                   </p>
                </div>
            </div>

            <!-- Systems Column -->
            <div class="bottom-right-div">
                <div class="title">Systems</div>
                <div class="content">
                   <p>
                            <?php foreach ($systems as $system): ?>
                               
                                    <?php echo htmlspecialchars(ucfirst($system['system'])); ?>: 
                                    <?php echo $system['status'] == 1 ? 'Online' : 'Offline'; ?></br>
                            
                            <?php endforeach; ?></p>
                        
                </div>
            </div>
        </div>
    </div>
    <script>
    setTimeout(() => {
        window.location.reload();
    }, 5000); // Refresh every 5000ms (5 seconds)
    </script>
</body>
</html>
