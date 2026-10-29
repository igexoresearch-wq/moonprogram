<?php
// Handle button actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    if (isset($_POST['docking_release'])) {
        
        
        
        
        
        
                        $velocity_magnitude = sqrt(pow($delta_xLM, 2) + pow($delta_yLM, 2) + pow($delta_zLM, 2));
                
                            if ($velocity_magnitude > 0) {
                    // Normalize the velocity vector to get direction
                    $unit_x = $delta_xLM / $velocity_magnitude;
                    $unit_y = $delta_yLM / $velocity_magnitude;
                    $unit_z = $delta_zLM / $velocity_magnitude;
                
                    // Scale by 20 meters
                    $shift_x = $unit_x * 20;
                    $shift_y = $unit_y * 20;
                    $shift_z = $unit_z * 20;
                
                    // Apply the shift to position
                    $xLM += $shift_x;
                    $yLM += $shift_y;
                    $zLM += $shift_z;
                }
        
        
        
        
        
        // Docking Release: Set dock to 0 and add 2 to x in telemetry
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'dock'");
        $x_new = $xLM - 2; // Add 2 to the current x value
        $conn->query("UPDATE telemetry SET value = $xLM WHERE attribute = 'x'");
        $conn->query("UPDATE telemetry SET value = $yLM WHERE attribute = 'y'");
        $conn->query("UPDATE telemetry SET value = $zLM WHERE attribute = 'z'");

    }

    if (isset($_POST['sm_release'])) {
        // Service Module Release: Set service_module to 0
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'service_module'");
    } 
        if (isset($_POST['st3_release'])) {
            
          
                        $velocity_magnitude = sqrt(pow($delta_xLM, 2) + pow($delta_yLM, 2) + pow($delta_zLM, 2));
                
                            if ($velocity_magnitude > 0) {
                    // Normalize the velocity vector to get direction
                    $unit_x = $delta_xLM / $velocity_magnitude;
                    $unit_y = $delta_yLM / $velocity_magnitude;
                    $unit_z = $delta_zLM / $velocity_magnitude;
                
                    // Scale by 20 meters
                    $shift_x = -$unit_x * 20;
                    $shift_y = -$unit_y * 20;
                    $shift_z = -$unit_z * 20;
                
                    // Apply the shift to position
                    $xLM += $shift_x;
                    $yLM += $shift_y;
                    $zLM += $shift_z;
                }
          
            
            
            
            
            
             
        // Docking Release: Set dock to 0 and add 2 to x in telemetry

        $conn->query("UPDATE telemetry SET value = $xLM WHERE attribute = 'x'");
        $conn->query("UPDATE telemetry SET value = $yLM WHERE attribute = 'y'");
        $conn->query("UPDATE telemetry SET value = $zLM WHERE attribute = 'z'");
            
            
            
            
            
        // Service Module Release: Set service_module to 0
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'stage3'");

    }
    
    
    $needs_redirect = true;
}

// Determine parachute indicator class
$parachute_class = $parachutes_deployedCM == 1 ? 'indicator-on' : 'indicator-off';
?>

<!-- Indicator and Button Table -->
<table class="control-table">
    <!-- Indicators/Buttons Row -->
    <tr>
        <!-- Parachute Deployed Indicator -->
        <td class="control-cell">
            <div class="indicator <?php echo $parachute_class; ?>">DEPLOYED</div>
        </td>
        <td class="spacer"></td>

        <!-- Docking Release Button -->
        <td class="control-cell">
            <form method="POST">
                <button type="submit" name="docking_release" class="control-button"></button>
            </form>
        </td>
        <td class="spacer"></td>

        <!-- SM Release Button -->
        <td class="control-cell">
            <form method="POST">
                <button type="submit" name="sm_release" class="control-button"></button>
            </form>
        </td>
            <td class="spacer"></td>

        <!-- SM Release Button -->
        <td class="control-cell">
            <form method="POST">
                <button type="submit" name="st3_release" class="control-button"></button>
            </form>
        </td>
    </tr>

    <!-- Labels Row -->
    <tr>
        <td class="label-cell">PARACHUTE DEPLOYED</td>
        <td class="spacer"></td>
        <td class="label-cell">DOCKING RELEASE</td>
        <td class="spacer"></td>
        <td class="label-cell">SM RELEASE</td>
                <td class="spacer"></td>
        <td class="label-cell">STAGE RELEASE</td>
        
    </tr>
</table>

<style>
    /* Table Layout */
    .control-table {
        width: 100%;
        border-spacing: 15px;
        text-align: center;
    }

    .control-cell {
        width: 20%;
    }

    .spacer {
        width: 10px;
    }

    /* Indicator Styles */
    .indicator {
        width: 120px;
        height: 40px;
        line-height: 40px;
        font-family: Arial, sans-serif;
        font-size: 1rem;
        font-weight: bold;
        text-align: center;
        color: #000000;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }

    .indicator-on {
        background-color: #FFFF00; /* Yellow */
    }

    .indicator-off {
        background-color: #D3D3D3; /* Grey */
    }

    /* Button Styles */
    .control-button {
        width: 150px;
        height: 50px;
        background: linear-gradient(to bottom, #d3d3d3, #a9a9a9);
        border: 2px solid #555;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        font-family: Arial, sans-serif;
        font-size: 1rem;
        font-weight: bold;
        color: #000;
        text-align: center;
        cursor: pointer;
        outline: none;
    }

    .control-button:active {
        background: linear-gradient(to bottom, #a9a9a9, #d3d3d3);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    /* Label Styles */
    .label-cell {
        font-family: Arial, sans-serif;
        font-size: 1.2rem;
        font-weight: bold;
        color: #FFFFFF;
    }
</style>
