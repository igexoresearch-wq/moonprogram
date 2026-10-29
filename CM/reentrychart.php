<?php
// PHP Variables for Altitude Chart
$earth_radius_alt = 13652279.3; // Earth radius in meters
$arch_thickness_alt = 110000; // Atmosphere extension in meters
$altitude_max_alt = 190000; // Maximum y-axis value
$altitude_min_alt = -10000; // Minimum y-axis value
$canvas_width_alt = 700;
$canvas_height_alt = 300;

// Scaling factor for altitude (y-axis)
$y_scale_alt = $canvas_height_alt / ($altitude_max_alt - $altitude_min_alt);

// Calculate y-axis position of Command Module
$yCM_alt = $canvas_height_alt - (($altitude - $altitude_min_alt) * $y_scale_alt);

// Earth position and radius for drawing arch
$earth_y_alt = $canvas_height_alt + 9541;
$earth_radius_scaled_alt = $earth_radius_alt * ($canvas_width_alt / 1000000);

// Velocity magnitude for the CM
$velocity_magnitude_alt = sqrt(pow($delta_xCM, 2) + pow($delta_yCM, 2) + pow($delta_zCM, 2));

// Calculate the vector line angle
$velocity_angle =  rad2deg(atan2($delta_yCM, $delta_xCM)); // Velocity vector angle
$red_line_angle = 270 - ($velocity_angle - $angleCM_E); // Adjust relative to tangent

// Calculate the altitude range for the red band
$target_temperature_min = 2800; // Min temperature (K)
$target_temperature_max = 3200; // Max temperature (K)
$altitude_band_start = null;
$altitude_band_end = null;

// Function to calculate reentry temperature
function calculateReentryTemperature1($altitude, $airspeed) {
    $rho0 = 1.225; // Sea-level air density (kg/m^3)
    $H = 100000;    // Scale height for atmosphere model (m)
    $kappa = 1.83e-4; // Empirical constant for Apollo (s^2 m^-1)
    $sigma = 5.67e-8; // Stefan-Boltzmann constant (W/m^2 K^4)
    $epsilon = 0.9;   // Emissivity of heat shield material

    if ($altitude > 100000 || $airspeed <= 0) {
        return 0; // Invalid range
    }

    $rho = $rho0 * pow(1 - ($altitude / $H), 4.256);
    $q = $kappa * pow($rho, 0.5) * pow($airspeed, 3);
    return pow($q / ($sigma * $epsilon), 0.25); // Temperature in Kelvin
}

// Iterate to find the range of altitudes
for ($test_altitude = 0; $test_altitude <= 100000; $test_altitude += 100) {
    $temp_at_alt = calculateReentryTemperature1($test_altitude, $velocity_magnitude_alt);
    if ($temp_at_alt >= $target_temperature_min && $temp_at_alt <= $target_temperature_max) {
        if ($altitude_band_start === null) {
            $altitude_band_start = $test_altitude;
        }
        $altitude_band_end = $test_altitude;
    }
}

// Scale the red band positions
$band_start_y = $canvas_height_alt - (($altitude_band_start - $altitude_min_alt) * $y_scale_alt);
$band_end_y = $canvas_height_alt - (($altitude_band_end - $altitude_min_alt) * $y_scale_alt);

// Calculate future position after 1 minute considering velocity and gravity
$time_future = 30; // 1 minute in seconds
$displacement_velocity = $velocity_magnitude_alt * $time_future; // Displacement due to velocity
$gravity_effect = 0.5 * 9.81 * pow($time_future, 2); // Displacement due to gravity

// Total displacement in x and y directions
$future_x = cos(deg2rad($red_line_angle)) * $displacement_velocity;
$future_y = - sin(deg2rad($red_line_angle)) * $displacement_velocity - $gravity_effect;

// Scale future position to canvas
$future_x_scaled = $canvas_width_alt / 2 + ($future_x / 666); // Centered horizontally
$future_y_scaled = $yCM_alt - ($future_y / 666); // Adjust vertical scaling
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Altitude Chart</title>
    <style>
        canvas {
            border: 10px inset #333;
            background-color: #ffffff;
            display: block;
            margin: auto;
        }
    </style>
</head>
<body><h2 align="center"><br>Reentry Attitude</h2>
    <canvas id="altitudeChartAlt" width="<?php echo $canvas_width_alt; ?>" height="<?php echo $canvas_height_alt; ?>"></canvas>
    <script>
        // Get canvas context for altitude chart
        const canvasAlt = document.getElementById('altitudeChartAlt');
        const ctxAlt = canvasAlt.getContext('2d');

        // Clear canvas and set background
        ctxAlt.fillStyle = '#ffffff'; // White background
        ctxAlt.fillRect(0, 0, canvasAlt.width, canvasAlt.height);

        // Draw Earth's arch (segment at the bottom)
        const earthRadiusAlt = <?php echo $earth_radius_scaled_alt; ?>;
        const earthYAlt = <?php echo $earth_y_alt; ?>;
        ctxAlt.beginPath();
        ctxAlt.arc(canvasAlt.width / 2, earthYAlt, earthRadiusAlt, Math.PI, 2 * Math.PI);
        ctxAlt.fillStyle = '#000000'; // Black Earth
        ctxAlt.fill();

        // Draw Earth's atmosphere (dotted line above Earth's arch)
        const atmosphereYAlt = 135;
        ctxAlt.beginPath();
        ctxAlt.setLineDash([5, 5]); // Dotted line
        ctxAlt.moveTo(0, atmosphereYAlt);
        ctxAlt.lineTo(canvasAlt.width, atmosphereYAlt);
        ctxAlt.strokeStyle = '#000000';
        ctxAlt.lineWidth = 1;
        ctxAlt.stroke();
        ctxAlt.setLineDash([]);

        // Draw Command Module (small red dot)
        const cmXAlt = canvasAlt.width / 2; // Centered horizontally
        const cmYAlt = <?php echo $yCM_alt; ?>; // Vertical position based on altitude
        const dotSizeAlt = 5;
        ctxAlt.beginPath();
        ctxAlt.arc(cmXAlt, cmYAlt, dotSizeAlt / 2, 0, 2 * Math.PI);
        ctxAlt.fillStyle = '#FF0000'; // Red dot for Command Module
        ctxAlt.fill();

        // Draw Scale Ticks (y-axis, every 10,000 meters)
        const yScaleAlt = <?php echo $y_scale_alt; ?>;
        ctxAlt.strokeStyle = '#000000';
        ctxAlt.lineWidth = 1;
        ctxAlt.font = '10px Arial';
        ctxAlt.fillStyle = '#000000';
        for (let alt = <?php echo $altitude_min_alt; ?>; alt <= <?php echo $altitude_max_alt; ?>; alt += 10000) {
            let yPosAlt = canvasAlt.height - ((alt - <?php echo $altitude_min_alt; ?>) * yScaleAlt);
            // Tick mark
            ctxAlt.beginPath();
            ctxAlt.moveTo(canvasAlt.width - 10, yPosAlt);
            ctxAlt.lineTo(canvasAlt.width, yPosAlt);
            ctxAlt.stroke();

            // Altitude label
            ctxAlt.fillText(alt + " m", canvasAlt.width - 50, yPosAlt + 3);
        }

        // Draw Red Band for Temperature Range
        const bandStartY = <?php echo $band_start_y; ?>;
        const bandEndY = <?php echo $band_end_y; ?>;
        if (bandStartY && bandEndY) {
            ctxAlt.fillStyle = 'rgba(255, 0, 0, 0.5)'; // Semi-transparent red
            ctxAlt.fillRect(0, bandEndY, canvasAlt.width, bandStartY - bandEndY);
        }

        // Add Red Vector Line for Approach Angle
        const velocityMagnitudeAlt = <?php echo $velocity_magnitude_alt / 666; ?>; // Scaled velocity magnitude
        const redLineAngle = <?php echo $red_line_angle; ?>; // Calculated angle of the red line
        const lineLengthAlt = velocityMagnitudeAlt * 30; // Scale the length of the line
        const angleRadiansAlt = redLineAngle * (Math.PI / 180); // Convert angle to radians

        // Calculate Red Line Endpoint
        const lineEndXAlt = cmXAlt + Math.cos(angleRadiansAlt) * lineLengthAlt;
        const lineEndYAlt = cmYAlt + Math.sin(angleRadiansAlt) * lineLengthAlt;

        // Draw Red Line
        ctxAlt.beginPath();
        ctxAlt.moveTo(cmXAlt, cmYAlt); // Start at the Command Module position
        ctxAlt.lineTo(lineEndXAlt, lineEndYAlt); // End of the red line
        ctxAlt.strokeStyle = '#FF0000'; // Red color
        ctxAlt.lineWidth = 2; // Line thickness
        ctxAlt.stroke();

        // Draw Future Position (Black Dot)
        const futureXAlt = <?php echo $future_x_scaled; ?>;
        const futureYAlt = <?php echo $future_y_scaled; ?>;
        if (!isNaN(futureXAlt) && !isNaN(futureYAlt)) {
            ctxAlt.beginPath();
            ctxAlt.arc(futureXAlt, futureYAlt, dotSizeAlt / 2, 0, 2 * Math.PI);
            ctxAlt.fillStyle = '#000000'; // Black dot
            ctxAlt.fill();
        }
    </script>
</body>
</html>
