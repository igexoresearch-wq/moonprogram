<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/shared/rcs_commands.php';

$queuedPulse = false;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['axis'])) {
    $rcsStatusResult = $conn->query(
        "SELECT status
         FROM systems
         WHERE system = 'RCS'
         LIMIT 1"
    );
    $rcsStatus = ($rcsStatusResult && $row = $rcsStatusResult->fetch_assoc())
        ? (int)$row['status']
        : 0;

    $fuelResult = $conn->query(
        "SELECT value
         FROM telemetry
         WHERE attribute = 'RCS'
         LIMIT 1"
    );
    $fuelLevel = ($fuelResult && $row = $fuelResult->fetch_assoc())
        ? (float)$row['value']
        : 0.0;

    if ($rcsStatus !== 1) {
        $errorMessage = 'LM RCS is switched off.';
    } elseif ($fuelLevel <= 0.0) {
        $errorMessage = 'LM RCS propellant is depleted.';
    } else {
        [$axis, $directionRaw] = array_pad(explode(',', (string)$_POST['axis'], 2), 2, '0');
        $direction = (float)$directionRaw >= 0.0 ? 1.0 : -1.0;

        $vector = [0.0,0.0,0.0];
        if ($axis === 'x') {
            $vector[0] = $direction;
        } elseif ($axis === 'y') {
            $vector[1] = $direction;
        } elseif ($axis === 'z') {
            $vector[2] = $direction;
        } else {
            $errorMessage = 'Unknown LM RCS axis.';
        }

        if ($errorMessage === null) {
            moonSimQueueRcsPulse(
                $conn,
                'LM',
                $vector[0],
                $vector[1],
                $vector[2],
                0.25
            );
            $queuedPulse = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LM RCS Thruster Controls</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #2a2a2a;
            color: #d4af37;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        #rcs-controls {
            display: grid;
            grid-template-columns: repeat(3, 100px);
            gap: 1px;
            margin: 10px auto;
            width: 300px;
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
        }
        button:hover {
            background-color: #d4af37;
            color: #1a1a1a;
        }
        .error {
            color: #ff6666;
            font-weight: bold;
        }
        .pulse-note {
            font-size: 12px;
            color: #c6b56b;
        }
    </style>
</head>
<body>
    <span>LM TRANSLATION RCS</span>
    <div class="pulse-note">Each press queues a 0.25-second body-axis pulse.</div>

    <?php if ($errorMessage): ?>
        <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div id="rcs-controls">
        <form method="POST" action="">
            <table>
                <tr>
                    <td></td>
                    <td><button name="axis" value="x,1">+X</button></td>
                    <td></td>
                    <td width="40px"></td>
                    <td><button name="axis" value="y,-1">-Y</button></td>
                </tr>
                <tr>
                    <td><button name="axis" value="z,1">+Z</button></td>
                    <td></td>
                    <td><button name="axis" value="z,-1">-Z</button></td>
                    <td width="40px"></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td><button name="axis" value="x,-1">-X</button></td>
                    <td></td>
                    <td width="40px"></td>
                    <td><button name="axis" value="y,1">+Y</button></td>
                </tr>
            </table>
        </form>
    </div>

    <?php if ($queuedPulse): ?>
        <audio autoplay>
            <source src="poof-of-smoke-87381.mp3" type="audio/mpeg">
        </audio>
        <script>
            if (history.replaceState) {
                history.replaceState(null, '', window.location.href);
            }
        </script>
    <?php endif; ?>
</body>
</html>
