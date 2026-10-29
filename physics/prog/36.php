<?php
$prog = 36;
include 'programshift.php';
//retro vector hold
include '31.php';

//trigger orbit angle
include '32.php';

//#############Initiation

    


        
//#############Trigger 1       
if ($trigger == 1) {
            
       
        if ($IMU_delta_V > $IMUHOLD_delta_V) {
            
            $thrustx = round(($IMU_delta_V - $IMUHOLD_delta_V));
            $thrust = 50;
            if ($thrustx < 100) {$thrust = 25;}
            if ($thrustx < 50) {$thrust = 20;}
            if ($thrustx < 25) {$thrust = 15;}
            if ($thrustx < 12) {$thrust = 10;}
            if ($thrustx < 6) {$thrust = 5;}
      $conn->query("UPDATE command_module SET value = $thrust WHERE attribute = 'thrust'"); } 
         else
     { 
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");   
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'yaw_rate'");  
            include 'progclear.php';
    }
    
}

?>