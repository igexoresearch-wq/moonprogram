<?php
include 'db.php';
include 'variables.php';
include 'constants.php';
include 'coordinate.php';
include 'sound.php';
$needs_redirect = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apollo Spacecraft Interface</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #121212; /* Space-like background */
            font-family: Arial, sans-serif;
        }

        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
        }

        .big-div {
            width: 1000px;
            height: 950px;
            background-color: #555; /* Grey panel color */
            border: 3px solid #888; /* Border for vintage look */
            box-shadow: inset 0 0 10px #222, 0 0 10px black; /* Inner and outer shadows for depth */
        }

        .side-div {
            width: 400px; /* Doubled width */
            height: 900px;
            background-color: #444;
            border: 3px solid #888;
            box-shadow: inset 0 0 8px #222, 0 0 8px black;
            margin: 0 10px; /* Space between divs */
        }

        .bottom-div {
            width: 1800px; /* Wider bottom panel */
            height: 350px;
            background-color: #666;
            border: 3px solid #888;
            box-shadow: inset 0 0 8px #222, 0 0 8px black;
            margin-top: 20px; /* Space below the main container */
        }

        .container-text {
            color: #fff;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="side-div">
            <div class="container-text"></div>
            <?php include 'alt.php';?>
            
        </div>
        <div class="big-div">
            <div class="container-text"><?php include 'control_panel.php';?>
            <table><tr><td></td><td>
             <?php include 'reentrychart.php';?></td></tr></table>
            </div>
        </div>
        <div class="side-div">
            <div class="container-text"><?php include 'register_display.php';?><h1>Orbital Telemetry</h1><?php include 'chart_tel.php';?></div><div></div>
        </div>
    </div>
    <div class="bottom-div">
        <div class="container-text"><table><tr><td><?php include 'engine_indicator.php';?></td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><?php include 'emergency_shutdown.php';?></td>
        <td><?php include 'panel_b.php';?></td>
        <td><?php include 'stages.php';?></td>
        </tr></table></div>
    </div><?php
    if ($needs_redirect) {
        echo '<script>window.location.href = window.location.href;</script>';
        exit();
    }
    ?>
</body>
   <script>
    setTimeout(() => {
        window.location.reload();
    }, 5000); // Refresh every 5000ms (5 seconds)
    </script>
</html>
