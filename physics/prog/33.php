<?php
$prog = 33;
include 'programshift.php';
//retro vector hold
include '31.php';
$max = 0;
$rolldelta = 0;
$descrate = $IMUHOLD_altitude - $altitude;
if ($altitude >= 100000) { 
    if ($rollCM < 10) { $rolldelta = 30;}
    if ($rollCM > 10) { $rolldelta = 30;}
    if ($rollCM == 10) { $rolldelta = 30;}
}

elseif ($altitude < 100000 && $altitude >= 15000 ) {
   
// Step 1: Determine roll position value
if (($rollCM <= 20 || $rollCM >= 340)) {
    $value = 1;
} elseif ($rollCM >= 150 && $rollCM <= 210) {
    $value = -1;
} else {
    $value = 0;
}

// Step 2: Set default roll rate
$rolldelta = 30;

// Step 3: Adjust roll rate based on temperature
if ($temp < 1300 && $value == -3) {
    $rolldelta = 1; // Slower roll to increase lift & heat up
} elseif ($temp > 1800 && $value == 1) { 
    $rolldelta = .1; // Slower roll to decrease lift & cool down
}



}

elseif ($altitude < 15000 && $rollCM == 90) {$rolldelta = 0;} elseif  ($altitude < 15000 && $rollCM != 90) {$rolldelta = 1;} 

$conn->query("UPDATE CMguidance SET altitude = $altitude WHERE  id = 2"); 
$conn->query("UPDATE CMguidance SET orbit_angle = $descrate WHERE  id = 2"); 
$conn->query("UPDATE command_module SET value = $rolldelta WHERE attribute = 'roll_rate'");
if ($altitude < 10000 ) {
    include 'progclear.php';
}