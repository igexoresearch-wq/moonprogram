<?php
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

// Database connection info
require __DIR__.'/physics/db.php';

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
    <title>Grid Layout Page</title>
    <?php include 'thrust.php'; ?>
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

        .main-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 20px;
        }

        .secondary-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 10px;
            margin: 1px auto;
            padding: 5px;
            width: 99%;
        }

        .grid-item {
            background-color: #3a3a3a;
            padding: 5px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td {
            padding: 5px;
            text-align: left;
        }

        .digital-display {
            font-family: 'DigitTech', monospace;
            font-size: 2rem;
            font-weight: bold;
            color: #FFFF00;
            background-color: #000000;
            padding: 5px;
            border-radius: 5px;
            width: 7ch;
            text-align: right;
        }

        .unit-label {
            font-size: 1rem;
            font-weight: normal;
            color: #ffffff;
        }
    </style>
</head>
<body><?php require_once __DIR__.'/shared/station_navigation.php'; moonSimStationNav('COMMANDER'); ?>
<div style="background:#14273b;padding:9px 18px;text-align:center"><a href="csm_burn_control.php" style="color:#ffd4a1">CSM BURN CONTROL</a> &nbsp; <a href="orbit_navigation.php" style="color:#9edcff;font-weight:bold">ORBITAL NAVIGATION / MANUAL MANEUVER</a></div>

    <!-- Play sound if needed -->
    <?php if ($play_sound): ?>
        <audio autoplay>
            <source src="poof-of-smoke-87381.mp3" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>
    <?php endif; ?>

    <!-- Main 4-Across Grid -->
    <div class="main-grid">
        <div class="grid-item">
            <?php include 'alt.php'; ?>
        </div>
        <div class="grid-item">
            <table>
                <tr>
                    <td><h2>Pitch</h2></td>
                    <td><div class="digital-display"><?php echo htmlspecialchars($telemetry_data['pitch']); ?></div></td>
                    <td><span class="unit-label">°</span></td>
                </tr>
                <tr>
                    <td><h2>Pitch Rate</h2></td>
                    <td><div class="digital-display"><?php echo htmlspecialchars($telemetry_data['pitch_rate']); ?></div></td>
                    <td><span class="unit-label">°/s</span></td>
                </tr>
                <tr>
                    <td><h2>Roll</h2></td>
                    <td><div class="digital-display"><?php echo htmlspecialchars($telemetry_data['roll']); ?></div></td>
                    <td><span class="unit-label">°</span></td>
                </tr>
                <tr>
                    <td><h2>Roll Rate</h2></td>
                    <td><div class="digital-display"><?php echo htmlspecialchars($telemetry_data['roll_rate']); ?></div></td>
                    <td><span class="unit-label">°/s</span></td>
                </tr>
            </table>
        </div>
        <div class="grid-item">
            <?php include 'fuel.php'; ?>
        </div>
        <div class="grid-item">
            <?php include 'alert.php'; ?>
        </div>
    </div>

    <!-- New 2-Across Grid -->
    <div class="secondary-grid">
        <div class="grid-item">
            <!-- Include the first additional grid content here -->
            <table><tr><td><?php include 'Diskey.php'; ?><?php include 'shared/burn_status_instrument.php'; ?></td><td><?php include 'readout.php'; ?><?php include 'switches.php'; ?></td></tr></table>
        </div>
    </div>

    <script>
    setTimeout(() => {
        if(document.getElementById('moon-radio-toggle')?.getAttribute('aria-expanded')!=='true') window.location.reload();
    }, 5000); // Refresh every 5000ms (5 seconds)
    </script>
<?php require_once __DIR__.'/shared/console_operations.php'; moonSimConsoleOperations('CM'); ?>
<?php require_once __DIR__.'/shared/console_radio.php'; moonSimConsoleRadio('CREW'); ?></body>
</html>
