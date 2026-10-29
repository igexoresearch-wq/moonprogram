<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Orbital Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        table {
            width: 60%;
            margin: 10px auto;
            border-collapse: collapse;
        }
        td, th {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        h2, h3 {
            margin-top: 20px;
        }
    </style>
    <script>
        function updateOrbitalData() {
            fetch('orbit_update.php')
                .then(response => response.json())
                .then(data => {
                    // Lunar Module Updates
                    document.getElementById('lm_apogee').innerText = data.lm.apogee.toFixed(2) + " m";
                    document.getElementById('lm_perigee').innerText = data.lm.perigee.toFixed(2) + " m";
                    document.getElementById('lm_angle_apogee').innerText = data.lm.angle_apogee.toFixed(2) + "°";
                    document.getElementById('lm_angle_perigee').innerText = data.lm.angle_perigee.toFixed(2) + "°";
                    document.getElementById('lm_eccentricity').innerText = data.lm.eccentricity.toFixed(5);
                    document.getElementById('lm_inclination').innerText = data.lm.inclination.toFixed(2) + "°";
                    document.getElementById('lm_longitude_ascending_node').innerText = data.lm.longitude_ascending_node.toFixed(2) + "°";
                    document.getElementById('lm_argument_periapsis').innerText = data.lm.argument_periapsis.toFixed(2) + "°";

                    // Command Module Updates
                    document.getElementById('cm_apogee').innerText = data.cm.apogee.toFixed(2) + " m";
                    document.getElementById('cm_perigee').innerText = data.cm.perigee.toFixed(2) + " m";
                    document.getElementById('cm_angle_apogee').innerText = data.cm.angle_apogee.toFixed(2) + "°";
                    document.getElementById('cm_angle_perigee').innerText = data.cm.angle_perigee.toFixed(2) + "°";
                    document.getElementById('cm_eccentricity').innerText = data.cm.eccentricity.toFixed(5);
                    document.getElementById('cm_inclination').innerText = data.cm.inclination.toFixed(2) + "°";
                    document.getElementById('cm_longitude_ascending_node').innerText = data.cm.longitude_ascending_node.toFixed(2) + "°";
                    document.getElementById('cm_argument_periapsis').innerText = data.cm.argument_periapsis.toFixed(2) + "°";
                })
                .catch(error => console.error("AJAX Fetch Error:", error));
        }

        setInterval(updateOrbitalData, 1000); // Update every second
        updateOrbitalData(); // Run on page load
    </script>
</head>
<body>
    <h2>Live Orbital Data</h2>

    <h3>Lunar Module</h3>
    <table>
        <tr>
            <td>Apogee:</td>
            <td><span id="lm_apogee">Loading...</span></td>
            <td>Angle:</td>
            <td><span id="lm_angle_apogee">Loading...</span></td>
        </tr>
        <tr>
            <td>Perigee:</td>
            <td><span id="lm_perigee">Loading...</span></td>
            <td>Angle:</td>
            <td><span id="lm_angle_perigee">Loading...</span></td>
        </tr>
        <tr>
            <td>Eccentricity:</td>
            <td><span id="lm_eccentricity">Loading...</span></td>
        </tr>
        <tr>
            <td>Inclination:</td>
            <td><span id="lm_inclination">Loading...</span></td>
        </tr>
        <tr>
            <td>Longitude of Ascending Node (Ω):</td>
            <td><span id="lm_longitude_ascending_node">Loading...</span></td>
        </tr>
        <tr>
            <td>Argument of Periapsis (ω):</td>
            <td><span id="lm_argument_periapsis">Loading...</span></td>
        </tr>
    </table>

    <h3>Command Module</h3>
    <table>
        <tr>
            <td>Apogee:</td>
            <td><span id="cm_apogee">Loading...</span></td>
            <td>Angle:</td>
            <td><span id="cm_angle_apogee">Loading...</span></td>
        </tr>
        <tr>
            <td>Perigee:</td>
            <td><span id="cm_perigee">Loading...</span></td>
            <td>Angle:</td>
            <td><span id="cm_angle_perigee">Loading...</span></td>
        </tr>
        <tr>
            <td>Eccentricity:</td>
            <td><span id="cm_eccentricity">Loading...</span></td>
        </tr>
        <tr>
            <td>Inclination:</td>
            <td><span id="cm_inclination">Loading...</span></td>
        </tr>
        <tr>
            <td>Longitude of Ascending Node (Ω):</td>
            <td><span id="cm_longitude_ascending_node">Loading...</span></td>
        </tr>
        <tr>
            <td>Argument of Periapsis (ω):</td>
            <td><span id="cm_argument_periapsis">Loading...</span></td>
        </tr>
    </table>
<?php
// Assuming $xCM, $yCM, $zCM, and $IMU_delta_V are already declared

// Adjust position relative to Earth's center
$rx = $xCM - 10000000000;
$ry = $yCM - 10000000000;
$rz = $zCM - 10000000000;

// Assign velocity components
$vx = $delta_xCM;
$vy = $delta_yCM;
$vz = $delta_zCM;

// Compute specific angular momentum components (cross product r × v)
$hx = ($ry * $vz) - ($rz * $vy);
$hy = ($rz * $vx) - ($rx * $vz);
$hz = ($rx * $vy) - ($ry * $vx);

// Compute total magnitude of specific angular momentum
$h_total = sqrt(($hx ** 2) + ($hy ** 2) + ($hz ** 2));


// Echo the result
echo "Specific Angular Momentum Vector: ($hx, $hy, $hz)\n";
echo "Total Specific Angular Momentum Magnitude: $h_total";
?>