<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$needs_redirects = false;

// Database credentials
$db_host = 'localhost';
$db_name = 'moonland';
$db_user = 'victorywebproductions';
$db_pass = 'CHANGE_ME';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if RCS is active
    $rcsStatus = $pdo->query("SELECT status FROM systems WHERE system = 'rcs'")->fetchColumn();

$fuel_level = $pdo->query("SELECT value FROM telemetry WHERE attribute = 'RCS'")->fetchColumn();

    // Get pitch, roll, and fuel values
    $pitch = deg2rad($pdo->query("SELECT value FROM telemetry WHERE attribute = 'pitch'")->fetchColumn());
    $roll = deg2rad($pdo->query("SELECT value FROM telemetry WHERE attribute = 'roll'")->fetchColumn());
    $fuel = $pdo->query("SELECT value FROM telemetry WHERE attribute = 'fuel'")->fetchColumn();
    $rcs_fuel = $pdo->query("SELECT value FROM telemetry WHERE attribute = 'RCS'")->fetchColumn();

    // Calculate mass
    $launch_status = $pdo->query("SELECT status FROM systems WHERE system = 'launch'")->fetchColumn();
    $base_mass = ($launch_status == 0) ? 10059 : 1875;
    $mass = $fuel + ($rcs_fuel / 3329) * 275 + $base_mass;

    // Get axis and direction from the form
    if (isset($_POST['axis']) && $fuel_level >= 1 && $rcsStatus == 1) {
        $needs_redirects = true;
        [$axis, $direction] = explode(',', $_POST['axis']);

        // Realistic thrust force of RCS thrusters in Newtons
        $thrust_force = 444; // Adjusted for real-life value

        // Calculate delta-v
        $delta_v = ($thrust_force / $mass) * (float)$direction;

        // Calculate the changes in delta_x, delta_y, and delta_z
        $delta_x = 0;
        $delta_y = 0;
        $delta_z = 0;

        if ($axis === 'x') {
            // Adjust delta based on roll
            $delta_x = $delta_v * cos($pitch);
            $delta_y = $delta_v * sin($pitch);
        } elseif ($axis === 'y') {
            // Adjust delta based on pitch
            $delta_x = $delta_v * sin($pitch);
            $delta_y = $delta_v * - cos($roll);
            $delta_z = $delta_v * - sin($roll);
        } elseif ($axis === 'z') {
            // Direct delta change along the z-axis
            $delta_y = $delta_v * (0 - sin($roll));
            $delta_z = $delta_v * cos($roll);
        }

        // Update delta_x, delta_y, and delta_z in telemetry
        $stmt = $pdo->prepare("UPDATE telemetry SET value = value + :delta WHERE attribute = :axis");
        $stmt->execute(['delta' => $delta_x, 'axis' => 'delta_x']);
        $stmt->execute(['delta' => $delta_y, 'axis' => 'delta_y']);
        $stmt->execute(['delta' => $delta_z, 'axis' => 'delta_z']);

        // Deduct 1 unit of RCS fuel
        $stmt = $pdo->prepare("UPDATE telemetry SET value = value - 1 WHERE attribute = 'RCS'");
        $stmt->execute();

        // Play sound effect
        echo "<audio autoplay><source src='poof-of-smoke-87381.mp3' type='audio/mpeg'>Your browser does not support the audio element.</audio>";
    } else {
        echo "<p style='color: yellow;'>No thruster action detected.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCS Thruster Controls</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #2a2a2a;
            color: #d4af37;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        h1 {
            margin: 20px 0;
        }
        #rcs-controls {
            display: grid;
            grid-template-columns: repeat(3, 100px);
            gap: 1px;
            margin: 10px auto;
            width: 200px;
            background-color: #1a1a1a;
            padding: 20px;
            border: 2px solid #d4af37;
            border-radius: 10px;
        }
        button {
            background-color: #333;
            color: #d4af37;
            border: 2px solid #d4af37;
            padding: 10px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }
        button:hover {
            background-color: #d4af37;
            color: #1a1a1a;
        }
        footer {
            margin-top: 30px;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
   <span>LATERAL THRUSTERS</span>
    <div id="rcs-controls">
        <!-- Thruster Buttons -->
        <form method="POST" action="">
        <table>
            <tr>
                <td></td>
                <td><button name="axis" value="x,1">↑</button></td>
                <td></td>
                <td width="100px"></td>
                <td><button name="axis" value="y,-1">_↑_</button></td>
            </tr>
            <tr>
                <td><button name="axis" value="z,1">←</button></td>
                <td></td>
                <td><button name="axis" value="z,-1">→</button></td>
                <td width="100px"></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td><button name="axis" value="x,-1">↓</button></td>
                <td></td>
                <td width="100px"></td>
                <td><button name="axis" value="y,1">~↓~</button></td>
            </tr>
        </table>
        </form>
    </div>
    <?php
    if ($needs_redirects) {
        echo '<script>window.location.href = window.location.href;</script>';
        exit();
    }
    ?>
</body>
</html>
