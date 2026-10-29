<?php
require 'db.php'; // Database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spacecraft Controls</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background-color: black; color: white; }
        .controls-container { display: inline-block; text-align: center; margin-top: 20px; }
        table { margin: auto; }
        .control-button { width: 100px; height: 50px; font-size: 16px; margin: 5px; cursor: pointer; background-color: #444; color: white; border: 2px solid white; }
        .control-button:hover { background-color: #666; }
        .digital-display { font-size: 24px; background-color: #222; padding: 10px; border-radius: 5px; display: inline-block; min-width: 40px; margin: 5px; }
        .thrust-controls, .engine-controls { margin-top: 20px; }
        .unit-label { display: block; font-size: 14px; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="controls-container">
    <table>
        
        
        
        <tr>
            
            
                <td><div class="readout">
                    <span class="unit-label">Pitch Rate</span>
                    <div class="digital-display pitch-rate">00.0</div>
                </div></td>
               <td> <div class="readout">
                    <span class="unit-label">Yaw Rate</span>
                    <div class="digital-display yaw-rate">00.0</div>
                </div></td>
                <td><div class="readout">
                    <span class="unit-label">Roll Rate</span>
                    <div class="digital-display roll-rate">00.0</div>
                </div>
           
        <tr><td></td>
          
           
            <td><button class="control-button holdable" name="decrease_pitch">Pitch ↑</button></td>
            <td></td>
        </tr>
        <tr>
              <td><button class="control-button holdable" name="decrease_yaw">Yaw ←</button></td>
           <td><button class="control-button" name="trim">Trim All</button></td>
            
            <td><button class="control-button holdable" name="increase_yaw">Yaw →</button></td>
        </tr>
        <tr>
            <td></td>
            <td><button class="control-button holdable" name="increase_pitch">Pitch ↓</button></td>
            <td></td>
        </tr>
        <tr>
            <td><button class="control-button holdable" name="decrease_roll">Roll ←</button></td><td></td>
            
            <td><button class="control-button holdable" name="increase_roll">Roll →</button></td>
        </tr>
    </table>
<table><tr><td> <button class="control-button holdable" name="increase_thrust">Thrust ↑</button></td><td> <div class="digital-display thrust">00</div></td><td>   <button class="control-button" name="100P">100%</button></td><td></td></tr>
<tr><td><button class="control-button holdable" name="decrease_thrust">Thrust ↓</button></td><td></td><td>        <button class="control-button" name="shut_off_engine">Engine Off</button></td></tr>


       
       
        
   </table>
</div>

<script>
$(document).ready(function() {
    function sendCommand(action) {
        console.log("Sending command: " + action); // Debugging log

        $.ajax({
            url: 'update_controls.php',
            type: 'POST',
            data: { action: action },
            dataType: 'json',
            success: function(response) {
                console.log("Response:", response);
                if (response.status !== "success") {
                    alert("Error: " + response.message);
                }
                refreshDisplay();
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error:", error);
            }
        });
    }

    $(".control-button").click(function(e) {
        e.preventDefault();
        let action = $(this).attr("name");
        sendCommand(action);
    });

    $(document).keydown(function(e) {
        let action = "";
        switch(e.key) {
            case "ArrowUp": action = "decrease_pitch"; break;
            case "ArrowDown": action = "increase_pitch"; break;
            case "ArrowLeft": action = "decrease_yaw"; break;
            case "ArrowRight": action = "increase_yaw"; break;
            case "]": action = "increase_thrust"; break;
            case "[": action = "decrease_thrust"; break;
        }
        if (action) {
            sendCommand(action);
        }
    });

    function refreshDisplay() {
        $.ajax({
            url: 'fetch_data.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $(".pitch-rate").text(data.pitch_rate);
                $(".yaw-rate").text(data.yaw_rate);
                $(".roll-rate").text(data.roll_rate);
                $(".thrust").text(data.thrust);
            }
        });
    }

    setInterval(refreshDisplay, 1000);
});

</script>

</body>
</html>
