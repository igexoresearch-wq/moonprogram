<?php
// Include required files
include 'db.php';
include 'variables.php';
include 'constants.php';
include 'coordinate.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orbital Parameters</title>
    <style>
        body {
            background-color: black;
            color: yellow;
            font-family: Arial, sans-serif;
        }
        h1, h2 {
            color: yellow;
        }
        p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
<h1>Guidance Console</h1>
<table>
    <tr><td><?php include 'appogee.php';?></td><td><?php include 'positionmaps.php';?></td></tr>
    

     <tr><td><?php include 'CMPOS.php';?></td><td></td></tr>
    
    
    <br><?php echo $IMU_delta_V; ?>
    <br><?php echo $s3fuelCM; ?>
    
    
</table>


<?php

include 'grav.php';
?>
  <script>
    setTimeout(() => {
        window.location.reload();
    }, 30000000); // Refresh every 5000ms (5 seconds)
    </script>