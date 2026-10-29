<?php

// Handle toggle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_switch'])) {
        $button_name = $_POST['toggle_switch']; // Get the button name from POST data
        // Get current status of the switch based on the button name
        $result = $conn->query("SELECT status FROM CMswitches WHERE name = '$button_name'");
        if ($result && $row = $result->fetch_assoc()) {
            // Toggle the status
            $newStatus = ($row['status'] == 1) ? 0 : 1;
            $conn->query("UPDATE CMswitches SET status = $newStatus WHERE name = '$button_name'");
            $needs_redirect = true;
        }
    }
}

// Fetch the current status of the switch


// Close the connection

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Module Switch</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: darkgrey;
            color: white;
            text-align: center;
            margin: 0;
            padding: 10px;
        }
        .switch-containerw {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 1px;
        }
        .switch-labelw {
            margin-bottom: 10px;
            font-size: 18px;
        }
        .switchw {
            width: 60px;
            height: 30px;
            background-color: #333;
            border: 2px solid #555;
            border-radius: 15px;
            position: relative;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }
        .switch-circlew {
            width: 26px;
            height: 26px;
            background-color: #555;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            transition: left 0.3s ease, background-color 0.3s ease;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5);
        }
        .status-boxw {
            width: 150px;
            height: 60px;
            margin: 20px auto;
            border: 2px solid #555;
            border-radius: 5px;
            display: flex;
            color:black;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5);
             /* Yellow if 0, Light Grey if 1 */
        }
        .status-box-textw {
            color: #000;
            font-size: 16px;
            font-weight: bold;
        }


    </style>
</head>
<body>
<table style=" vertical-align: bottom">
    <!-- Row 1 -->
    <tr>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'Inertial_Measurement_Unit'; 
            $CSM_SYS = $CSM_SYS_1;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'Guidance_Computer'; 
            $CSM_SYS = $CSM_SYS_3;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'RCS_Thrusters'; 
            $CSM_SYS = $CSM_SYS_4;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'RCS_Fuel_Tank'; 
            $CSM_SYS = $CSM_SYS_5;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'Service_Propulsion_Engine'; 
            $CSM_SYS = $CSM_SYS_6;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
    </tr>
<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>
<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>
    <!-- Row 2 -->
    <tr>
        
        
       <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'High_Gain_Antenna'; 
            $CSM_SYS = $CSM_SYS_13;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'Low_Gain_Antenna'; 
            $CSM_SYS = $CSM_SYS_14;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            

    <!-- Row 3 -->
    
  <!-- Row 4 -->

        
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'Drogue_Parachutes'; 
            $CSM_SYS = $CSM_SYS_15;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'Main_Parachutes'; 
            $CSM_SYS = $CSM_SYS_16;
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'EDS_MODE'; 
            include 'switches/switch_no2.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><tr> 
   
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        
        
        </tr>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style="vertical-align: bottom; color:red; background: url('switches/warn.png') no-repeat center center; background-size: cover; width: 100px; height: 50px;">
            <?php
            $switch_name = 'LES_Jettison'; 
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
       </tr>
  
</table>

    
</body>
</html>

