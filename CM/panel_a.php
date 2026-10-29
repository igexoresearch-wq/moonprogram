<?php
// Handle toggle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_switch'])) {
        $button_id = $_POST['toggle_switch']; // Get the button name from POST data

        // Validate button_id (prevent SQL injection)
        $button_id = $conn->real_escape_string($button_id);

        // Get current status of the switch
        $result = $conn->query("SELECT status FROM CMswitches WHERE name = '$button_id'");
        if ($result && $row = $result->fetch_assoc()) {
            // Toggle the status
            $newStatus = ($row['status'] == 1) ? 0 : 1;
            
            // Update statuses
            $conn->query("UPDATE CMswitches SET status = $newStatus WHERE name = '$button_id'");
            $conn->query("UPDATE CMguidance SET execute = $newStatus WHERE subnonmonic = '$button_id'");

            // Fetch the ID from CMguidance
            $resultxx = $conn->query("SELECT id FROM CMguidance WHERE subnonmonic = '$button_id'");
            if ($resultxx && $rowxx = $resultxx->fetch_assoc()) {
                $idxxx = $rowxx['id'];

                // If new status is 0, clear the register
                if ($newStatus != 1) {
                    $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE program_number = '$idxxx'");
                    $needs_redirect = true;
                }
            }
        }
    }
}
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
   
  <!-- Row 4 -->
  
    <tr>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'AUTO VECTOR';
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
       <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'AUTO LEVEL'; 
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>

        
        
  
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'RETRO VECTOR'; 
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'RENDVOUS RADAR'; 
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style=" vertical-align: bottom">
            <?php
            $switch_name = 'SET ORBIT VELOCITY'; 
            include 'switches/switch_no.php';
            ?>
        </td> 
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td style="vertical-align: bottom; color:red; background: url('switches/warn.png') no-repeat center center; background-size: cover; width: 100px; height: 50px;">
            <?php
            $switch_name = 'ABORT'; 
            include 'switches/switch_no.php';
            ?>
        </td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        </tr>
  
</table>

    
</body>
</html>

