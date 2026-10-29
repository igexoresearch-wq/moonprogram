<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Image Positioning</title>
    <style>
        .space-containerx {
            background-color: black;
            position: relative;
            width: 250px;
            height: 250px;
            border: 1px solid #000;
            border-radius: 50%;
            overflow: hidden;
            margin: auto;
            transform-origin: center;
            left: calc(.8%);
            top: 21px;
        }

        .space-centerx {
            position: absolute;
            width: 0;
            height: 0;
            left: 50%;
            top: 50%;
        }

        .space-containerx img {
            position: absolute;
            transform: translate(-50%, -50%);
        }

        .skyx {
            position: absolute;
            width: 1129px;
            height: 586px;
        }

        .needle {
            position: absolute;
            width: 15px;
            height: 50px;
            left: calc(50% + 0px);
            top: calc(50% - 97px);
        }
    </style>

    <script>
        function updateDisplay() {
            fetch('AJ8ball.php')
                .then(response => response.json())
                .then(data => {
                    // Update container rotation (roll)
                    document.querySelector('.space-containerx').style.transform = `rotate(${-data.roll}deg)`;

                    // Update sky positioning (pitch & yaw)
                    document.querySelector('.skyx').style.left = `calc(49% + ${data.skyyaw}px)`;
                    document.querySelector('.skyx').style.top = `calc(50% + ${-data.skypitch}px)`;
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        // Update every second
        setInterval(updateDisplay, 1000);
    </script>
</head>
<body>
    <div class="space-containerx">
        <div class="space-centerx"></div>
        <img src="8ball.png" alt="sky" class="skyx">
        <img src="needle.png" alt="sky" class="needle">
    </div>
</body>
</html>
<?php

// Fetch current values for ID 2
$rollResult = $conn->query("SELECT roll FROM CMguidance WHERE id = 1");
$pitchResult = $conn->query("SELECT pitch FROM CMguidance WHERE id = 1");
$yawResult = $conn->query("SELECT yaw FROM CMguidance WHERE id = 1");

// Check if the queries were successful and fetch the data
if ($rollResult && $row = $rollResult->fetch_assoc()) {
    $COMProll = (float)$row['roll'];
} else {
    $COMProll = 0.0; // Default value
}

if ($pitchResult && $row = $pitchResult->fetch_assoc()) {
    $COMPpitch = (float)$row['pitch'];
} else {
    $COMPpitch = 0.0; // Default value
}

if ($yawResult && $row = $yawResult->fetch_assoc()) {
    $COMPyaw = (float)$row['yaw'];
} else {
    $COMPyaw = 0.0; // Default value
}
if ($COMPpitch > 180) {
    $COMPpitch = 0 - (360 - $COMPpitch);
}
if ($COMPyaw > 180) {
    $COMPyaw = 0 - (360 - $COMPyaw);
}
// Perform calculations
$skyyaw = -$COMPyaw * 2.5;
$skypitch = $COMPpitch * 2.5;
$skyyawR = $skyyaw + 2200;
$skypitchR = 1172 - $skypitch;
if ($Inertial_Measurement_Unit_SWITCH == 0) {
    $skyyaw = 0;
$skypitch = 0;
    
}


?>
<!DOCTYPE html>



    <style>
        .space-containerx {
            background-color:black;
            position: relative;
            width: 250px;
            height:250px;
            border: 1px solid #000;
            border-radius: 50%; /* Makes the div circular */
            overflow: hidden; /* Ensures only visible parts of images are shown */
            margin: auto; /* Centers the div horizontally */
            transform: rotate(<?php echo -$COMProll; ?>deg); /* Rotate the entire container */
            transform-origin:  center; /* Ensure rotation occurs around the center */
 left: calc(.8%); top: 21px;
        }
 
        .space-centerx {
            position: absolute;
            width: 0; /* No dimensions for the center point */
            height: 0;
            left: 50%; /* Center relative to container */
            top: 50%;
        }

        .space-containerx img {
            position: absolute;
            transform: translate(-50%, -50%); /* Position relative to the image center */
        }


.skyx {
            left: calc(49% + <?php echo $skyyaw; ?>px);
            top: calc(50% + <?php echo $skypitch; ?>px);
            
        }
    .needle {
            left: calc(50% + 0px);
            top: calc(50% - 97px);
        }   
      
.overlay-imagex {
            position: absolute; /* Overlay the entire container */

            transform: translate(-48.5%, -81.9%); /* Adjust for center alignment */

        }
    </style>


