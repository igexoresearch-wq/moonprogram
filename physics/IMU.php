<?php


// Apply the roll factor (offset)
$roll_factor = ($rollCM - $IMU_roll_rate); // The roll factor in radians (45° offset)

// Transform real-world PRY into the IMU frame of reference
$adjusted_pitch = ($pitchCM)  * cos(deg2rad($roll_factor)) - ($yawCM) * sin(deg2rad($roll_factor));
$adjusted_yaw = ($pitchCM) * sin(deg2rad($roll_factor)) + ($yawCM) * cos(deg2rad($roll_factor));

// Update the internal (IMU) pitch, roll, and yaw based on the PRY factor
$IMU_pitchx = $adjusted_pitch  - $IMU_pitch_rate; // Adjust for pitch rate
$IMU_rollx = $roll_factor;          // Adjust for roll rate
$IMU_yawx = $adjusted_yaw - $IMU_yaw_rate;     // Adjust for yaw rate

if ($IMU_pitchx < 0) {$IMU_pitchx = 360 + $IMU_pitchx;}
if ($IMU_rollx < 0) {$IMU_rollx =  360 + $roll_factor;}
if ($IMU_yawx < 0) {$IMU_yawx =  360 + $IMU_yawx;}
if ($IMU_pitchx >= 360) {$IMU_pitchx = 360 - $IMU_pitchx;}
if ($IMU_rollx >= 360) {$IMU_rollx =  360 - $IMU_rollx;}
if ($IMU_yawx >= 360) {$IMU_yawx =  360 - $IMU_yawx;}

// SQL query to update the internal data into CMguidance
$query = "UPDATE CMguidance 
          SET pitch = ?, roll = ?, yaw = ? 
          WHERE id = 1";

$stmt = $conn->prepare($query);

if ($stmt) {
    // Bind parameters
    $stmt->bind_param("ddd", $pitchCM, $rollCM, $yawCM);
    
    // Execute the query
    if ($stmt->execute()) {
      
    } else {
      
    }
} else {

}
?>