<?php

// Get 'hold' value from CMguidance where id = 39
$result = $conn->query("SELECT hold FROM CMguidance WHERE id = 39");
$row = $result->fetch_assoc();
$hold_value = $row['hold'];

// Match hold value to corresponding declared variable

if ($hold_value == 1) {$sel_n = 'FUEL'; $selected_v = $fuelCM;}
if ($hold_value == 2) {$sel_n = 'VCTR PITCH MN'; $selected_v = $pitchVCMM;}
if ($hold_value == 3) {$sel_n = 'VCTR YAW MN'; $selected_v = $yawVCMM;}
if ($hold_value == 4) {$sel_n = 'ORBIT ANGLE'; $selected_v = $angleorbitCM;}
if ($hold_value == 5) {$sel_n = 'VCTR YAW'; $selected_v = $yawVCM;}
if ($hold_value == 6) {$sel_n = 'VCTR PITCH'; $selected_v = $pitchVCM;}
if ($hold_value == 7) {$sel_n = 'ORB HRZON'; $selected_v = $horrizonCM;}
if ($hold_value > 7) {$sel_n = 'XXXXX'; $selected_v = 0000000;}




    $conn->query("UPDATE CMguidance SET nonmonic = '$sel_n' WHERE id = 13");
    $conn->query("UPDATE CMguidance SET roll = $selected_v WHERE id = 2");


// Other required updates
$conn->query("UPDATE CMguidance SET roll = 13 WHERE id = 3");
$conn->query("UPDATE CMguidance SET hold = 2 WHERE id = 3");
$conn->query("UPDATE CMguidance SET execute = 0 WHERE id = 39");


?>