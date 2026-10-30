<?php





/////alt logging 
$temp_file = "/tmp/altitude_data.json";

// Get the current time
$current_time = time();

// Check if the temp file exists
if (file_exists($temp_file)) {
    $previous_data = json_decode(file_get_contents($temp_file), true);
} else {
    // Initialize file with the first recorded altitude
    $previous_data = [
        "altitude" => $altitude,
        "timestamp" => $current_time
    ];
}

// Get previous altitude and timestamp
$prev_altitude = $previous_data["altitude"];
$prev_time = $previous_data["timestamp"];

// Compute time difference (ensure no division by zero)
$time_diff = $current_time - $prev_time;
if ($time_diff > 0) {
    $altitude_rate = ($altitude - $prev_altitude); // Change in altitude per second
} else {
    $altitude_rate = 0;
}


// Update the temp file with the latest altitude and timestamp
$new_data = [
    "altitude" => $altitude,
    "timestamp" => $current_time
];
file_put_contents($temp_file, json_encode($new_data));







// Launch-sequence state is owned by physics, not by a display page.
$query = "SELECT seconds FROM mission_clock WHERE id = 1";
$result = $conn->query($query);

if ($result && $row = $result->fetch_assoc()) {
    $secondsx = $row['seconds'];
}
    // Update mission clock to -600 if seconds is -900
    if ($secondsx == -900) {
        $conn->query("UPDATE mission_clock SET seconds = -15 WHERE id = 1");
        $conn->query("UPDATE mission_clock SET launch_events = 0 WHERE id = 1");
        $conn->query("UPDATE mission_clock SET minutes = 0 WHERE id = 1");
    }



if ($secondsx == -1) {
    $conn->query("UPDATE command_module SET value = 2 WHERE attribute = 'staging'");
    $conn->query("UPDATE mission_clock SET launch_events = 1 WHERE id = 1");
    $stagingCM = 2;
}

if ($secondsx == 0) {
    $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 16");
    $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'staging'");
    $stagingCM = 0;
}
if ($secondsx == 1) {
     $conn->query("UPDATE command_module SET value = 10006371005 WHERE attribute = 'x'");
     $conn->query("UPDATE command_module SET value = 10 WHERE attribute = 'delta_x'");
}
/*
 * A separation event remains at staging=1 for one physics pass so displays
 * can observe it.  Physics clears it on the following pass; cockpit audio is
 * no longer allowed to advance the launch sequence.
 */
if ((int)$stagingCM === 1) {
    $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'staging'");
    $stagingCM = 0;
}

// stage 1 sep
if ($s1fuelCM == 0 && $stage1CM == 1) {
    $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 19");
   
 $conn->query("UPDATE command_module SET value = 1 WHERE attribute = 'staging'");
   

    
}
// stage 2 ign
if ((int)$stage1CM === 0 && (int)$stage2CM === 1 && (int)$stage3CM === 1 && (float)$thrustCM <= 0.0 && (int)$stagingCM === 0) {
    $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 17");
   
}


// stage 2 sep
if ($s2fuelCM == 0 && $stage2CM == 1) {
    $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 20");
 $conn->query("UPDATE command_module SET value = 1 WHERE attribute = 'staging'");

}
// stage 3 ign
if ((int)$stage1CM === 0 && (int)$stage2CM === 0 && (int)$stage3CM === 1 && (float)$thrustCM <= 0.0 && (int)$stagingCM === 0) {
    $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 18");
    
}


if ($IMU_delta_V >= $IMUHOLD_delta_V) {
    $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
    $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE id BETWEEN 1 AND 5");
    
}
$prate = 0;

if ($altitude > 1000 && $altitude <= 20000) {
$min_altitude = 1000;
$max_altitude = 20000;
$min_prate = 300;
$max_prate = 359;
if ($altitude > $min_altitude && $altitude <= $max_altitude) {
    // Calculate the prate dynamically
    $prate = round($max_prate - (($altitude - $min_altitude) / ($max_altitude - $min_altitude) * ($max_prate - $min_prate)), 1);
    
    
    
} 

    $conn->query("UPDATE command_module SET value = $prate  WHERE attribute = 'yaw'");
  $v_total = sqrt(pow($delta_xCM, 2) + pow($delta_yCM, 2) + pow($delta_zCM, 2));  
    
} $conn->query("UPDATE CMguidance SET velocity = $altitude_rate WHERE id = 2");
if ($altitude > 20000) { $yawratex = 0;
  
  if ($altitude < 90000  && $v_total < 4000) { 
   $opt = (120000 - $altitude) /100;}
    elseif ($altitude < 100000  && $v_total < 4000) { 
   $opt = 30;}
   elseif ($altitude >= 100000   && $v_total < 6500) { 
   $opt = 10;}
  elseif ($altitude >= 100000   && $v_total < 7200) { 
   $opt = 5;}
   if ($altitude >= 100000   && $v_total > 7200) { 
   $opt = 1;}
   if ($altitude_rate > $opt) { $yawratex = -2;}
   elseif ($altitude_rate < $opt) { $yawratex = 2;}
   else { $yawratex = 0;}
   if ($horrizonCM < 265) { $zzz= $horrizonCM + 35;} else { $zzz= 300;}
   if ($stage1CM == 1)  { $zzz= 290;}
   $jxy =  $yawVCM - 3;
    if ($yawCM > $zzz) { $yawratex = -0.5;}
    if ($yawCM <= $jxy) { $yawratex = 0.5;}
    $jxyx =  $yawVCM + 2;
      if ($yawCM >= $jxyx && $v_total > 6000) { $yawratex = -0.5;}
  


  

  
    // **Step 4: Update Yaw in Database**
    $conn->query("UPDATE command_module SET value = $yawratex WHERE attribute = 'yaw_rate'");
    
}









?>

