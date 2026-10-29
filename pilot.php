<?php error_reporting(E_ALL);
ini_set('display_errors', 1);
// Ensure database connection exists
// Database connection info
$servername = "localhost";
$username = "victorywebproductions";
$password = "CHANGE_ME";
$dbname = "moonland"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch telemetry data
$telemetry_data = [];
$attributes = ['pitch', 'pitch_rate', 'roll', 'roll_rate'];
foreach ($attributes as $attribute) {
    $sql = "SELECT value FROM telemetry WHERE attribute = '$attribute' LIMIT 1";
    $result = $conn->query($sql);
    $telemetry_data[$attribute] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['value'] : "N/A";
}

// Determine if sound should play
$play_sound = ($telemetry_data['pitch_rate'] != 0 || $telemetry_data['roll_rate'] != 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilot Panel</title>
    <style>
        @font-face {
            font-family: 'DigitTech';
            src: url('font/DigitTech16-Regular.ttf') format('truetype');
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #2e2e2e;
            color: #ffffff;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px;
        }

        .grid-item {
            background-color: #3a3a3a;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .digital-display {
            font-family: 'DigitTech', monospace;
            font-size: 2rem;
            font-weight: bold;
            color: #FFFF00;
            background-color: #000000;
            padding: 5px;
            border-radius: 5px;
            width: 100%;
            text-align: center;
        }

        .unit-label {
            font-size: 1rem;
            font-weight: normal;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <!-- Top Grid with 3 Divs -->
    <div class="grid-container">
        <div class="grid-item">
        
             <?php include 'controlls.php'; ?>
        </div>
        <div class="grid-item">
            <?php include 'window.php'; ?>
        </div>
        <div class="grid-item">
           <?php include 'rel_mot.php'; ?>
        </div>
    </div>

    <!-- Bottom Grid with 3 Divs -->
    <div class="grid-container">
        <div class="grid-item">
            <table><tr><td><?php include 'RCS.php'; ?></td><td align="center"><?php include 'rcs_g.php'; ?></td></tr><tr><td><td>RCS<br>PROPELLANT</td></td></tr></table>
        </div>
        <div class="grid-item"><span>DOCKING RADAR</span><table><tr><?php include 'rel_vel.php'; ?><td></td></tr>
        <tr><td>RANGE</td><td>RELATIVE<BR>VELOCITY<BR>
        10M SCALE</td><td>RELATIVE<BR>VELOCITY<BR>
        100M SCALE</td></tr>
        </table>
            
        </div>
        <div class="grid-item">
            <?php include 'abort_launch.php'; ?>
        </div>
    </div>
</body>
    <script>
    setTimeout(() => {
        window.location.reload();
    }, 5000); // Refresh every 5000ms (5 seconds)
    </script>
</html>
