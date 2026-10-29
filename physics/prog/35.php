<?php
$prog = 35;
include 'programshift.php';


//trigger orbit angle
include '32.php';

//#############Initiation

    


        
//#############Trigger 1       
if ($trigger == 1) {
            $IMUHOLD_orbit_angle = $IMUHOLD_orbit_angle - 10;
       
      
 $conn->query("UPDATE CMguidance SET orbit_angle = $IMUHOLD_orbit_angle WHERE id = 2"); 
   $prog = 36;
include 'programshift.php';
 $prog = 35;
            include 'progclear.php';
    
    
}

?>