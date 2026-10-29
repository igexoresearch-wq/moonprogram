<?php
// Database connection
$host = "localhost";
$db_user = "victorywebproductions";
$db_pass = "CHANGE_ME";
$db_name = "moonland";

$connection = new mysqli($host, $db_user, $db_pass, $db_name);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Fetch docking status from command_module
$dockingStatus = 0;
$dockingQuery = $connection->query("SELECT value FROM command_module WHERE attribute = 'docking' LIMIT 1");
if ($dockingQuery && $dockRow = $dockingQuery->fetch_assoc()) {
    $dockingStatus = (int)$dockRow['value'];
}

// Default metrics
$objectDistance = 0;
$relativeSpeed = 0;

if ($dockingStatus == 1) {
    // Retrieve telemetry and command module data
    $telemetryData = [];
    $moduleData = [];

    $telemetryQuery = $connection->query("SELECT attribute, value FROM telemetry");
    $moduleQuery = $connection->query("SELECT attribute, value FROM command_module");

    if ($telemetryQuery) {
        while ($telemetryRow = $telemetryQuery->fetch_assoc()) {
            $telemetryData[$telemetryRow['attribute']] = (float)$telemetryRow['value'];
        }
    }

    if ($moduleQuery) {
        while ($moduleRow = $moduleQuery->fetch_assoc()) {
            $moduleData[$moduleRow['attribute']] = (float)$moduleRow['value'];
        }
    }

    $deltaX = $telemetryData['x'] - $moduleData['x'];
    $deltaY = $telemetryData['y'] - $moduleData['y'];
    $deltaZ = $telemetryData['z'] - $moduleData['z'];

    $objectDistance = sqrt($deltaX ** 2 + $deltaY ** 2 + $deltaZ ** 2);

    $deltaVX = $telemetryData['delta_x'] - $moduleData['delta_x'];
    $deltaVY = $telemetryData['delta_y'] - $moduleData['delta_y'];
    $deltaVZ = $telemetryData['delta_z'] - $moduleData['delta_z'];

    // Calculate relative velocity, including signs
    $relativeSpeed = sqrt($deltaVX ** 2 + $deltaVY ** 2 + $deltaVZ ** 2);
    if ($deltaVX < 0) {
        $relativeSpeed *= -1;
    }

    $scaledRelativeSpeed = $relativeSpeed * 10; // Scale for -100 to 100 gauge
}

$connection->close();

function computeGaugePercentage($metric, $minimum, $maximum) {
    $percentage = round((($metric - $minimum) / ($maximum - $minimum)) * 100);
    return max(0, min(100, $percentage));
}

// Distance gauge
$distanceGauge = computeGaugePercentage($objectDistance, 0, 6000);
$distanceTicks = range(0, 6000, 1000);

// Velocity gauge (-10 to 10)
$speedGauge = computeGaugePercentage($relativeSpeed, -10, 10);
$velocityTicks = range(-10, 10, 2);
$scaledRelativeSpeeda = $scaledRelativeSpeed / 10;
// Scaled velocity gauge (-100 to 100)
$scaledGauge = computeGaugePercentage($scaledRelativeSpeeda, -100, 100);
$scaledTicks = range(-100, 100, 20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linear Gauges</title>
    <style>
        .gauge-container {
            width: 80px;
            height: 200px;
            border: 2px solid grey;
            border-radius: 10px;
            background: #f0f0f0;
            box-shadow: inset -3px -3px 5px rgba(255, 255, 255, 0.5),
                        inset 3px 3px 5px rgba(0, 0, 0, 0.2);
            position: relative;
            font-family: Arial, sans-serif;
            color: black;
            margin: 0 20px;
        }

        .gauge-ticks {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column-reverse; /* Reverse the direction */
            justify-content: space-between;
            align-items: flex-start;
            padding: 1px;
            box-sizing: border-box;
        }

        .gauge-tick {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .major-tick {
            border-left: 20px solid black;
            height: 1px;
            width: 20px;
            margin-left: 5px;
        }

        .minor-tick {
            border-left: 10px solid black;
            height: 1px;
            width: 10px;
            margin-left: 5px;
        }

        .tick-label {
            margin-left: 10px;
            font-size: 12px;
        }

        .gauge-pointer {
            position: absolute;
            bottom: calc(<?php echo $distanceGauge; ?>% - 2px);
            left: 0;
            width: 50%;
            height: 5px;
            background: black;
        }

        .speed-pointer {
            position: absolute;
            bottom: calc(<?php echo $speedGauge; ?>% - 2px);
            left: 0;
            width: 50%;
            height: 5px;
            background: black;
        }

        .scaled-pointer {
            position: absolute;
            bottom: calc(<?php echo $scaledGauge; ?>% - 2px);
            left: 0;
            width: 50%;
            height: 5px;
            background: black;
        }
    </style>
</head>
<body><td>
    <div style="display: flex; justify-content: center;">
        <!-- Distance Gauge -->
        <div class="gauge-container">
            <div class="gauge-ticks">
                <?php foreach ($distanceTicks as $tick): ?>
                    <div class="gauge-tick">
                        <div class="<?php echo $tick % 1000 === 0 ? 'major-tick' : 'minor-tick'; ?>"></div>
                        <?php if ($tick % 1000 === 0): ?>
                            <div class="tick-label"><?php echo $tick; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="gauge-pointer"></div>
        </div>
</td><td>
        <!-- Velocity Gauge (-10 to 10) -->
        <div class="gauge-container">
            <div class="gauge-ticks">
                <?php foreach ($velocityTicks as $tick): ?>
                    <div class="gauge-tick">
                        <div class="<?php echo $tick % 2 === 0 ? 'major-tick' : 'minor-tick'; ?>"></div>
                        <?php if ($tick % 2 === 0): ?>
                            <div class="tick-label"><?php echo $tick; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="speed-pointer"></div>
        </div>
</td><td>
        <!-- Scaled Velocity Gauge (-100 to 100) -->
        <div class="gauge-container">
            <div class="gauge-ticks">
                <?php foreach ($scaledTicks as $tick): ?>
                    <div class="gauge-tick">
                        <div class="<?php echo $tick % 20 === 0 ? 'major-tick' : 'minor-tick'; ?>"></div>
                        <?php if ($tick % 20 === 0): ?>
                            <div class="tick-label"><?php echo $tick; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="scaled-pointer"></div>
        </div>
    </div>
</body></td>
</html>
