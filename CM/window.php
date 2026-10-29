<?php
// Database connection details
$dbHost = 'localhost';
$dbName = 'moonland';
$dbUser = 'victorywebproductions';
$dbPass = 'CHANGE_ME';

try {
    // Establish database connection
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch x, y, z values for command_module
    $queryCommandModule = "SELECT attribute, value FROM command_module WHERE attribute IN ('x', 'y', 'z','pitch','roll','yaw')";
    $stmtCommandModule = $pdo->query($queryCommandModule);
    $commandModule = [];
    while ($row = $stmtCommandModule->fetch(PDO::FETCH_ASSOC)) {
        $commandModule[$row['attribute']] = $row['value'];
    }

    // Fetch x, y, z values for telemetry
    $queryTelemetry = "SELECT attribute, value FROM telemetry WHERE attribute IN ('x', 'y', 'z')";
    $stmtTelemetry = $pdo->query($queryTelemetry);
    $telemetry = [];
    while ($row = $stmtTelemetry->fetch(PDO::FETCH_ASSOC)) {
        $telemetry[$row['attribute']] = $row['value'];
    }

    // Fetch x, y, z values for the moon
    $queryMoon = "SELECT attribute, value FROM moon WHERE attribute IN ('x', 'y', 'z')";
    $stmtMoon = $pdo->query($queryMoon);
    $moon = [];
    while ($row = $stmtMoon->fetch(PDO::FETCH_ASSOC)) {
        $moon[$row['attribute']] = $row['value'];
    }

    // Define Earth coordinates
    $earth = ['x' => 10000000000, 'y' => 10000000000, 'z' => 10000000000];
    $earthRadius = 6371000; // Earth's radius in meters

    // Calculate distances and angles for telemetry
    $dxTelemetry = $telemetry['x'] - $commandModule['x'];
    $dyTelemetry = $telemetry['y'] - $commandModule['y'];
    $dzTelemetry = $telemetry['z'] - $commandModule['z'];

    $distanceTelemetry = sqrt($dxTelemetry ** 2 + $dyTelemetry ** 2 + $dzTelemetry ** 2);
    $pitchTelemetry = rad2deg(atan2($dzTelemetry, sqrt($dxTelemetry ** 2 + $dyTelemetry ** 2)));
    $yawTelemetry = rad2deg(atan2($dyTelemetry, $dxTelemetry));

    // Calculate distances and angles for the moon
    $dxMoon = $moon['x'] - $commandModule['x'];
    $dyMoon = $moon['y'] - $commandModule['y'];
    $dzMoon = $moon['z'] - $commandModule['z'];

    $distanceMoon = sqrt($dxMoon ** 2 + $dyMoon ** 2 + $dzMoon ** 2);
    $pitchMoon = rad2deg(atan2($dzMoon, sqrt($dxMoon ** 2 + $dyMoon ** 2)));
    $yawMoon = rad2deg(atan2($dyMoon, $dxMoon));

    // Calculate distances and angles for Earth
    $dxEarth = $earth['x'] - $commandModule['x'];
    $dyEarth = $earth['y'] - $commandModule['y'];
    $dzEarth = $earth['z'] - $commandModule['z'];

    $distanceEarth = sqrt($dxEarth ** 2 + $dyEarth ** 2 + $dzEarth ** 2) - $earthRadius;
    $pitchEarth = rad2deg(atan2($dzEarth, sqrt($dxEarth ** 2 + $dyEarth ** 2)));
    $yawEarth = rad2deg(atan2($dyEarth, $dxEarth));
if ($distanceMoon < 1) {$distanceMoon = 1;}
if ($distanceEarth < 1) {$distanceEarth = 1;}
if ($distanceTelemetry < .01) {$distanceTelemetry = .01;}
$CMroll = $commandModule['roll'];
if ($yawTelemetry < 0) {$yawTelemetry = 360 + $yawTelemetry;}
if ($pitchTelemetry < 0) {$pitchTelemetry = 360 + $pitchTelemetry;}
    $LEMyaw = $yawTelemetry - $commandModule['yaw'];
    $LEMpitch = $pitchTelemetry - $commandModule['pitch'];
if ($LEMpitch > 180) {$LEMpitch = (0 - (360 - $LEMpitch));}
if ($LEMyaw > 180) {$LEMyaw = (0 - (360 - $LEMyaw));}
$LEMpitch = ((0 - $LEMpitch) * 10);
$LEMyaw = ((0 + $LEMyaw) * 10);

if ($yawMoon < 0) {$yawMoon = 360 + $yawMoon;}
if ($pitchMoon < 0) {$pitchMoon = 360 + $pitchMoon;}
$MOONyaw = $yawMoon - $commandModule['yaw'];
$MOONpitch =  $pitchMoon - $commandModule['pitch'];
if ($MOONpitch > 180) {$MOONpitch = (0 - (360 - $MOONpitch));}
if ($MOONyaw > 180) { $MOONyaw = (0 - (360 - $MOONyaw));}
$MOONpitch = ((0 - $MOONpitch) * 10);
$MOONyaw = ((0 + $MOONyaw) * 10);
if ($yawEarth < 0) {$yawEarth = 360 + $yawEarth;}
if ($pitchEarth < 0) {$pitchEarth = 360 + $pitchEarth;}
    $EARTHyaw = $yawEarth - $commandModule['yaw'];
    $EARTHpitch = $pitchEarth - $commandModule['pitch'];
    if ($EARTHpitch > 180) {$EARTHpitch = (0 - ((360 - $EARTHpitch)));}
    if ($EARTHyaw > 180) {$EARTHyaw = (0 - (360 - $EARTHyaw));}
    $EARTHpitch = ((0 - $EARTHpitch) * 10);
    $EARTHyaw = ((0 + $EARTHyaw) * 10);
    $moonsize = (rad2deg(2 *(atan(1740000 / $distanceMoon)))* 10);
    $earthsize = (rad2deg(2 *(atan(6378000 / $distanceEarth)))* 10);
     $lemsize = (rad2deg(2 *(atan(2.5 / $distanceTelemetry)))* 10);

    $skyyaw =  $commandModule['yaw'] * 10;
    $skypitch = ($commandModule['pitch']) * 10;
    if ($skypitch > 1800) {$skypitch = (0 - (3600 - $skypitch));}
if ($skyyaw > 1800) { $skyyaw = (0 + (3600 - $skyyaw));}
    
    

} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
    die();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Positioning</title>
    <style>
        .space-container {
            background-color:black;
            position: relative;
            width: 2000px;
            height: 2000px;
            border: 1px solid #000;
            border-radius: 50%; /* Makes the div circular */
            overflow: hidden; /* Ensures only visible parts of images are shown */
            margin: auto; /* Centers the div horizontally */
            transform: rotate(<?php echo $CMroll; ?>deg); /* Rotate the entire container */
            transform-origin: center; /* Ensure rotation occurs around the center */
 left: calc(-400px); top: calc(-1000px);
        }
 .overlay {
            position: center;
            width: 350px;
            height: 350px;
            border: 10px solid grey; /* Grey border */
    
            box-sizing: border-box; /* Includes border in the sizing */
            background: transparent; /* Transparent inside */
            top: 0;
            left: 0;  overflow: hidden; /* Ensures only visible parts of images are shown */
        }
        .space-center {
            position: absolute;
            width: 0; /* No dimensions for the center point */
            height: 0;
            left: 50%; /* Center relative to container */
            top: 50%;
        }

        .space-container img {
            position: absolute;
            transform: translate(-50%, -50%); /* Position relative to the image center */
        }


        .moon {
            left: calc(50% + <?php echo $MOONyaw; ?>px);
            top: calc(50% + <?php echo $MOONpitch; ?>px);
        }
.sky {
            left: calc(50% + <?php echo $skyyaw; ?>px);
            top: calc(50% + <?php echo $skypitch; ?>px);
        }
        .earth {
            left: calc(50% + <?php echo $EARTHyaw; ?>px);
            top: calc(50% + <?php echo $EARTHpitch; ?>px);
        }
        .LEM {
            left: calc(50% + <?php echo $LEMyaw; ?>px); /* Adjust horizontally relative to center */
            top: calc(50% + <?php echo $LEMpitch; ?>px); /* Adjust vertically relative to center */
        }
.overlay-image {
            position: absolute; /* Overlay the entire container */

            transform: translate(-51%, -100.2%); /* Adjust for center alignment */

        }
    </style>
</head>
<body>
   <div class="overlay"><div class="space-container">
        <div class="space-center"></div> <!-- Represents (0, 0) -->
        <img src="sky.png" alt="sky" class="sky" width="4000"  height="4000">
       <img src="earth.png" alt="Earth" class="earth" width="<?php echo $earthsize; ?>">
        <img src="moon.png" alt="Moon" class="moon" width="<?php echo $moonsize; ?>">
        <img src="Apollo_11_lunar_module.png" alt="LEM" class="LEM" width="<?php echo $lemsize; ?>">
        
   </div> </div> 
</body>
</html>
