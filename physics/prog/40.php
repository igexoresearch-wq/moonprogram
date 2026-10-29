<?php 
$prog = 40;
include 'programshift.php';
$yawVCM = $yawVLM; $yawVCMM = $yawVLM; 
if ($IMUHOLD_delta_V > 3) {$direction = -1; include '28.php';} elseif ($IMUHOLD_delta_V < -3){$direction = 1; include '31.php';} else {$direction = 0;}
$yawDIF = abs($yawVCM - $yawCM);
if ($direction != 0 && $yawDIF <= .2) {
      $conn->query("UPDATE command_module SET value = 2 WHERE attribute = 'thrust'"); } 
         elseif ($direction != 0 && $yawDIF > .2) 
         {$conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'"); }
         elseif ($direction != 0) 
     { 
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");   
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'yaw_rate'");  
            include 'progclear.php';
    }
    

?>