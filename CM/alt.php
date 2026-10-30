
<?php 

// Constants
define("EARTH_RADIUS", 6371000); // Earth radius in meters
define("MOON_RADIUS", 1737100); // Moon radius in meters


$distanceToEarthCenter = $altitude + EARTH_RADIUS;
$distanceToMoonCenter = $altitudemoon + MOON_RADIUS;

// Decision: Target Earth or Moon
if ($altitude < 100000000) {
    // Targeting Earth
    $radius = EARTH_RADIUS;
    $totalDistance = $distanceToEarthCenter;

    // Angular Size (how large Earth appears)
    $angularSize = 2 * rad2deg(atan($radius / $totalDistance));

  // Angle from the Horizon for Target Distance
    $angleFromHorizon = (80000 / $radius) * $angularSize;
   $reentryangle = (360 - $angleorbitCM) + 180 + ($angularSize / 2) + $angleFromHorizon;
   if  ($reentryangle < 0) {$reentryangle = 360 + $reentryangle;}
 if  ($reentryangle >= 360) {$reentryangle = $reentryangle - 360;}
    echo "Targeting Earth:\n";
    echo "Angular Size: " . number_format($angularSize, 8) . " degrees\n";
    echo "Angle from Horizon: " . number_format($angleFromHorizon, 8) . " degrees\n";
} else {
    // Targeting Moon
    $radius = MOON_RADIUS;
    $totalDistance = $distanceToMoonCenter;

    // Angular Size (how large Moon appears)
    $angularSize = 2 * rad2deg(atan($radius / $totalDistance));

    // Angle from the Horizon for Target Distance
    $angleFromHorizon = (100000 / $radius) * $angularSize;
 $reentryangle = (360 - $angleorbitCM) + 180 - ($angularSize / 2) - $angleFromHorizon;
   if  ($reentryangle < 0) {$reentryangle = 360 + $reentryangle;}
 if  ($reentryangle >= 360) {$reentryangle = $reentryangle - 360;}
    echo "Targeting Moon:\n";
    echo "Angular Size: " . number_format($angularSize, 8) . " degrees\n";
    echo "Angle from Horizon: " . number_format($angleFromHorizon, 8) . " degrees\n";
}

echo $reentryangle - $yawVCM;
$query = "SELECT seconds FROM mission_clock WHERE id = 1";
$result = $conn->query($query);

if ($result && $row = $result->fetch_assoc()) {
    $seconds = intval($row['seconds']);

    // Handle negative seconds
    $is_negative = $seconds < 0;
    $absolute_seconds = abs($seconds);

    $hours = floor($absolute_seconds / 3600);
    $minutes = floor(($absolute_seconds % 3600) / 60);
    $remaining_seconds = $absolute_seconds % 60;

    // Reapply negativity if needed
    if ($is_negative) {
        $hours = -$hours;
        $minutes = -$minutes;
        $remaining_seconds = -$remaining_seconds;
    }

  
}
?>

<table class="alt-table">
    <tr>
        <td><h2 class="alt-label">Altitude</h2></td>
        <td><div class="alt-digital-display"><?php if ($Guidance_Computer_SWITCH != 0){ echo round($IMU_altitude,0); }  else {echo ' &nbsp;';}?></div></td>
        <td align="left"> <span class="alt-unit-label">m</span></td>
    </tr>
    <tr>
        <td><h2 class="alt-label">Velocity</h2></td>
        <td><div class="alt-digital-display"><?php if ($Guidance_Computer_SWITCH != 0){ echo $IMU_delta_V;} else {echo ' &nbsp;';} ?></div></td>
        <td><span class="alt-unit-label">m/sec</span></td>
    </tr>
     <tr>
        <td><h2 class="alt-label">Orbital Angle</h2></td>
    
        <td><div class="alt-digital-display"><?php if ($Guidance_Computer_SWITCH != 0){ echo round($angleorbitCM,2); }  else {echo ' &nbsp;';}?></div></td>
        <td><span class="alt-unit-label"></span></td>
    </tr>
    
     <tr>
        <td><h2 class="alt-label">Orbital Horrizon</h2></td>
    
        <td><div class="alt-digital-display"><?php if ($Guidance_Computer_SWITCH != 0){
        
       
        
        echo round($horrizonCM,2); }  else {echo ' &nbsp;';}?></div></td>
        <td><span class="alt-unit-label"></span></td>
    </tr>
    <tr>
        <td><h2 class="alt-label">Retro Horrizon</h2></td>
    
        <td><div class="alt-digital-display"><?php if ($Guidance_Computer_SWITCH != 0){
        
        $horrizonRCM = $horrizonCM - 180;
        if ($horrizonRCM >= 360) {$horrizonRCM = 360 - $horrizonRCM;}
        if ($horrizonRCM < 0) {$horrizonRCM = 360 + $horrizonRCM;}
        
        echo round($horrizonRCM,2); }  else {echo ' &nbsp;';}?></div></td>
        <td><span class="alt-unit-label"></span></td>
    </tr>
     <tr>
        <td><h2 class="alt-label">Insersion <br>Offset</h2></td>
    
        <td><div class="alt-digital-display"><?php if ($Guidance_Computer_SWITCH != 0){
        
        
        
        echo number_format($reentryangle, 2); }  else {echo ' &nbsp;';}?></div></td>
        <td><span class="alt-unit-label"></span></td>
    </tr>
    <tr>
        <td><h2 class="alt-label">Vector Attitude</h2></td>
    
        <td>
            <table><tr><td>
            <div class="alt-digital-display4"><?php  echo $pitchVCM;?></div></td><td>
            <div class="alt-digital-display4"><?php echo $yawVCM;?></div></td></tr><tr><td>Pitch</td><td>Yaw</td></tr></table>
        </td>
        <td></td>
    </tr>
    <tr>
           <tr>
        <td><h2 class="alt-label">Vector moon</h2></td>
    
        <td>
            <table><tr><td>
            <div class="alt-digital-display4"><?php  echo $pitchVCMM;?></div></td><td>
            <div class="alt-digital-display4"><?php echo number_format($yawVCMM,2);?></div></td></tr><tr><td>Pitch</td><td>Yaw</td></tr></table>
<?php
            // Display-only calculation. This page must never modify guidance
            // or physical state merely because the Commander screen is open.
            $precess = $reentryangle - $yawVCMM;
            $precess2 = (($precess - $IMUHOLD_pitch_rate) / 5) * 1000;
            ?>
        </td>
        <td></td>
    </tr>
    <tr>
        <td><h2 class="alt-label">Mission Clock</h2></td>
    
        <td>
            <table><tr><td>
            <div class="alt-digital-display3"><?php  echo $hours;?></div></td><td>
            <div class="alt-digital-display2"><?php echo $minutes;?></div></td><td>
            <div class="alt-digital-display2"><?php  echo $remaining_seconds;?></div></td></tr><tr><td>Hr</td><td>Min</td><td>Sec</td></tr></table>
        </td>
        <td></td>
    </tr>
   <tr>
        <td><h2 class="alt-label">Fuel</h2></td>
    
        <td><div class="alt-digital-display"><?php echo round($fuelCM ,2);?></div></td>
        <td><span class="alt-unit-label">Kg</span></td>
    </tr>
       <tr>
        <td><h2 class="alt-label">Precess</h2></td>
    
        <td><div class="alt-digital-display"><?php echo round($precess2 ,6);?></div></td>
        <td><span class="alt-unit-label">Kg</span></td>
    </tr>
</table>

<style>
    /* Altitude Module Independent Styles */
    .alt-table {
        width: 100%;
        border-spacing: 10px;
    }

    .alt-label {
        font-family: Arial, sans-serif;
        font-size: 1.2rem;
        color: #ffffff;
    }

    .alt-digital-display {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px;
        border-radius: 5px;
        width: 12ch;
        text-align: right;
    }
 .alt-digital-display2 {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px;
        border-radius: 5px;
        width: 3ch;
        text-align: right;
    }
     .alt-digital-display3 {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px;
        border-radius: 5px;
        width: 4ch;
        text-align: right;
    }
         .alt-digital-display4 {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px;
        border-radius: 5px;
        width: 6ch;
        text-align: right;
    }
    .alt-unit-label {
        font-size: 1rem;
        font-weight: normal;
        color: #ffffff;
    }

    .alt-alert-box {
        font-family: Arial, sans-serif;
        font-size: 1rem;
        font-weight: bold;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        width: 50%;
        margin: 0 auto;
        border: 3px solid #888888;
    }

    .alt-black-box {
        background-color: #000000;
        color: #888888;
    }

    .alt-red-box {
        background-color: #ff0000;
        color: #ffffff;
    }

    .alt-green-box {
        background-color: #00ff00;
        color: #000000;
    }

    .contact-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 10px;
    }

    .contact-light {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 2px solid #555;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .contact-light-on {
        background-color: #FFFF00; /* Yellow */
    }

    .contact-light-off {
        background-color: #000000; /* Black */
    }

    .contact-label {
        font-family: Arial, sans-serif;
        font-size: 1rem;
        color: #ffffff;
    }
</style>
