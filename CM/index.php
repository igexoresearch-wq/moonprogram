<?php
include 'db.php';
include 'variables.php';
include 'constants.php';
include 'coordinate.php';
include 'sound.php';

$opacity = 0;
$needs_redirect = false;
if ($temp < 3000 && $temp > 800 && $altitude < 100000 && $service_moduleCM == 0) { $opacity = ($temp - 800) / 3000;}
if ($temp >= 3000 && $altitude < 100000  && $service_moduleCM == 0) { $opacity = 1;}
if ($altitude >= 100000) { $opacity = 0;}
if ($altitude < 10000) { $opacity = 0;}
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
            width: 650px;
            height: 950px;
            background-color: #555; /* Grey panel color */
            border: 3px solid #888; /* Border for vintage look */
            box-shadow: inset 0 0 10px #222, 0 0 10px black; /* Inner and outer shadows for depth */
        }

        .side-div {
            width: 550px; /* Doubled width */
            height: 1100px;
            background-color: #444;
            border: 3px solid #888;
            box-shadow: inset 0 0 8px #222, 0 0 8px black;
            margin: 0 10px; /* Space between divs */
        }

        .bottom-div {
            width: 1800px; /* Wider bottom panel */
            height: 150px;
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
        .overlay-image {
            position: absolute; /* Overlay the entire container */

            transform: translate(-50.2%, -100.5%); /* Adjust for center alignment */}
         .overlay-imagexx {
            position: absolute; /* Overlay the entire container */
            opacity: <?php echo $opacity; ?>;
            transform: translate(-50.2%, -100.5%); /* Adjust for center alignment */}
    </style>
</head>
<body>
    <link rel="preload" as="image" href="earth.png">
    <div class="main-container">
        <div class="side-div">
            <div class="container-text"><?php include 'disky.php';?></div>
        </div>
        <div class="big-div">
            <div class="container-text"><?php include 'CMViewport.php';?>
             <img src="reenter.png" alt="Overlay" class="overlay-imagexx" width="590" height="590">
             <?php if ($crashCM == 1) {?><img src="crash.png" alt="Overlay" class="overlay-image" width="590"><?php } ?>
            
            <img src="<?php if ($dockCM == 1) { echo 'degree.png';} else { echo 'degree.png';} ?>" alt="Overlay" class="overlay-image" width="615" height="600">
            
           
            
            
            <table><tr><td><table><tr><td><?php include 'RCS.php'; ?></td><td align="center"><?php include 'rcs_g.php'; ?></td></tr><tr><td><td>RCS<br>PROPELLANT</td></td></tr></table></td><td><span>DOCKING RADAR</span><table><tr><?php include 'rel_vel.php'; ?><td></td></tr>
        <tr><td>RANGE</td><td>RELATIVE<BR>VELOCITY<BR>
        10M SCALE</td><td>RELATIVE<BR>VELOCITY<BR>
        100M SCALE</td></tr>
        </table></td></tr>
        </table></div>
        </div>
        <div class="side-div">
            <div class="container-text"><?php include '8ball.php';?><img src="roll.png" alt="Overlayx" class="overlay-imagex" width="330"></div><div><br><br><div align="center"><?php include 'rollguage.php';?></div><br><br></div><div><?php include 'controlls.php';?></div>
        </div>
    </div>
    <div class="bottom-div">
        <div class="container-text"><?php include 'panel_a.php'; ?></div>
    </div><?php
    if ($needs_redirect) {
        echo '<script>window.location.href = window.location.href;</script>';
        exit();
    }
    ?>
</body>

</html>
