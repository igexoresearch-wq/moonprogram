<?php


// Extract the ones place (4)
$onesPlace = floor($yawCM) % 10;

// Extract the decimal part (0.56)
$decimalPart = $yawCM - floor($yawCM);

// Combine to get the full value (4.56)
$yawDec = $onesPlace + $decimalPart;

// Map yawDec to a position in a 300px-wide div
$centerPosition = ($yawDec / 10) * 300; // Position in pixels
$lineLeftPosition = $centerPosition - 1; // Center 3px line, subtract half width
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yaw Decimal Line</title>
    <style>
        body {
            background: black;
            color: white;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            width: 300px;
            height: 25px;
            background: gray;
            position: relative;
            overflow: hidden;
            border: 1px solid white;
        }

        .line {
            width: 3px;
            height: 25px;
            background: red;
            position: absolute;
        }

        .scale {
            width: 300px;
            position: relative;
            margin-top: 1px;
            font-size: 12px;
        }

        .scale .tick {
            position: absolute;
            width: 1px;
            background: white;
            top: 0;
        }

        .scale .tick.major {
            height: 12px; /* Major tick height for whole numbers */
        }

        .scale .tick.minor {
            height: 6px; /* Minor tick height for smaller increments */
        }

        .scale .label {
            position: absolute;
            top: 15px;
            transform: translateX(-50%);
        }
    </style>
</head>
<body><center><br><br>YAW</center>
    <div class="container">
        <!-- Render the vertical line -->
        <div class="line" style="left: <?php echo $lineLeftPosition; ?>px;"></div>
    </div>

    <!-- Scale -->
    <div class="scale">
        <?php for ($i = 0; $i <= 10; $i++): ?>
            <!-- Major ticks (whole numbers) -->
            <div class="tick major" style="left: <?php echo ($i / 10) * 300; ?>px;"></div>
            <!-- Labels for whole numbers -->
            <div class="label" style="left: <?php echo ($i / 10) * 300; ?>px;"><?php echo $i; ?></div>
        <?php endfor; ?>

        <?php for ($i = 0; $i < 10; $i++): ?>
            <?php for ($j = 1; $j <= 9; $j++): ?>
                <?php $position = (($i + $j / 10) / 10) * 300; ?>
                <!-- Subdivisions -->
                <?php if ($j == 5): ?>
                    <!-- | at .5 -->
                    <div class="tick minor" style="left: <?php echo $position; ?>px; height: 12px;"></div>
                <?php else: ?>
                    <!-- . at .1 increments -->
                    <div class="tick minor" style="left: <?php echo $position; ?>px; height: 6px;"></div>
                <?php endif; ?>
            <?php endfor; ?>
        <?php endfor; ?>
    </div>
</body>
</html>
