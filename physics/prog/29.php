<?php
$prog = 29;
include 'programshift.php';
//retro vector hold
include '31.php';

//trigger orbit angle
include '32.php';

//#############Initiation

    


        
//#############Trigger 1       
if ($trigger == 1) {
            
       
        if ($IMU_delta_V > $IMUHOLD_delta_V) {
            
            $thrust = round(($IMU_delta_V - $IMUHOLD_delta_V) / 1,0);
            if ($thrust < 100) {$thrust = 100;}
            if ($thrust > 100) {$thrust = 100;}
      $conn->query("UPDATE command_module SET value = $thrust WHERE attribute = 'thrust'"); } 
         else
     { 
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");   
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'yaw_rate'");  
            include 'progclear.php';
    }
    
}

?>