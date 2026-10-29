
<?php


// Earth's position
$earthX = 10000000000;
$earthY = 10000000000;
$earthZ = 10000000000;


$xMOOND = $xMOON - $earthX; // Moon position from database
$yMOOND = $yMOON - $earthY;
$zMOOND = $zMOON - $earthZ;
 $anglediff = ($angleorbitCM - $angleMOON_E) / 0.0001525134; // Replace variables with actual values

// Current date and time
$currentDateTime = new DateTime("now", new DateTimeZone("America/New_York")); // Set to EST




$moondistance = sqrt(pow($xMOOND, 2) + pow($yMOOND, 2) + pow($zMOOND, 2));

// Constants
$earthRadius = 6371000; // Earth's radius in meters
$mu = 3.98589196e14; // Gravitational parameter (m^3/s^2)

// Inputs
$currentAltitude = $altitude; // Current altitude in meters
$apogeeAltitude = $moondistance; // Desired apogee altitude in meters

// Calculate radii
$currentRadius = $earthRadius + $currentAltitude; // Current orbital radius
$apogeeRadius = 2000000 + $apogeeAltitude; // Apogee orbital radius

// Calculate semi-major axis
$semiMajorAxis = ($currentRadius + $apogeeRadius) / 2;

// Calculate velocity using vis-viva
$velocity = sqrt($mu * (2 / $currentRadius - 1 / $semiMajorAxis));


// Calculate orbital period using Kepler's Third Law
$orbitalPeriod = 2 * pi() * sqrt(pow($semiMajorAxis, 3) / $mu);

// Calculate time to apogee (half the orbital period)
$timeToApogee = $orbitalPeriod / 2;

// Convert time to hours and minutes
$hours = intval(floor($timeToApogee / 3600)); // Explicitly cast to integer
$minutes = intval(floor(($timeToApogee % 3600) / 60)); // Explicitly cast to integer




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moon Landing Charts</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<table>
    <tr>
        <td>
            <h1>Earth</h1>
            <canvas id="chartEarthLMCM" width="1200" height="1200"></canvas>
        </td>
        <td>
            
    <h2>Predicted Moon Position</h2>
    <p id="predictedMoonPosition"></p>
    <p><?php echo round($moondistance,0); ?></p>
    
    

<?php echo "The velocity required is: " . number_format($velocity, 2) . " m/s\n";?><br><?php



?>
</div>

        </td>
    </tr>
</table>
<div>
    <h2>Current Position of Command Module</h2>
    <p id="currentPosition"></p>
</div>
<script>
    const EARTH_RADIUS = 6371000; // Earth radius in meters
    const MOON_RADIUS = 1737100; // Moon radius in meters
    const GRAVITATIONAL_CONSTANT = 3.98589196e14; // Earth's gravitational parameter (m^3/s^2)
    const TIME_STEP = 1; // Simulation time step in seconds

    // Initial positions from PHP variables
    let xCM = <?php echo $xCM; ?>;
    let yCM = <?php echo $yCM; ?>;
    let zCM = <?php echo $zCM; ?>;
    let xCMP = <?php echo $xCM; ?>;
    let yCMP = <?php echo $yCM; ?>;
    let zCMP = <?php echo $zCM; ?>;
    let xLM = <?php echo $xLM; ?>;
    let yLM = <?php echo $yLM; ?>;
    let zLM = <?php echo $zLM; ?>;
        let xLMP = <?php echo $xLM; ?>;
    let yLMP = <?php echo $yLM; ?>;
    let zLMP = <?php echo $zLM; ?>;
    const xMOON = <?php echo $xMOON; ?>;
    const yMOON = <?php echo $yMOON; ?>;
    const earthX = <?php echo $earthX; ?>;
    const earthY = <?php echo $earthY; ?>;
    const earthZ = <?php echo $earthZ; ?>;

    // Initial velocities from PHP variables
    let deltaX = <?php echo $delta_xCM; ?>;
    let deltaY = <?php echo $delta_yCM; ?>;
    let deltaZ = <?php echo $delta_zCM; ?>;
        let deltaXL = <?php echo $delta_xLM; ?>;
    let deltaYL = <?php echo $delta_yLM; ?>;
    let deltaZL = <?php echo $delta_zLM; ?>;
     let onion = <?php echo $currentRadius +1000000; ?>;


    const cmTrajectory = [];
    let hitEarth = false;

    // Simulate orbital motion with gravity
    for (let t = 0; t < 5400; t += TIME_STEP) { // Simulate for 24 hours
        // Calculate distance to Earth's center
        const distance = Math.sqrt(
            Math.pow(xCM - earthX, 2) +
            Math.pow(yCM - earthY, 2) +
            Math.pow(zCM - earthZ, 2)
        );

        // Check for impact
        if (distance <= EARTH_RADIUS) {
            hitEarth = true;
            break;
        }

        // Gravitational acceleration
        const acceleration = GRAVITATIONAL_CONSTANT / (distance * distance);
        const accX = -acceleration * (xCM - earthX) / distance;
        const accY = -acceleration * (yCM - earthY) / distance;
        const accZ = -acceleration * (zCM - earthZ) / distance;

// Update positions
        xCM += (deltaX + ((accX))) * TIME_STEP;
        yCM += (deltaY + ((accY))) * TIME_STEP;
        zCM += (deltaZ + ((accZ))) * TIME_STEP;

        // Update velocities
        deltaX += accX * TIME_STEP;
        deltaY += accY * TIME_STEP;
        deltaZ += accZ * TIME_STEP;

        

        // Store position for trajectory visualization
        cmTrajectory.push({ x: xCM, y: yCM });
    }
    
    
    
        const lmTrajectory = [];
    let hitEarthx = false;

    // Simulate orbital motion with gravity
    for (let tx = 0; tx < 5400; tx += TIME_STEP) { // Simulate for 24 hours
        // Calculate distance to Earth's center
        const distancex = Math.sqrt(
            Math.pow(xLM - earthX, 2) +
            Math.pow(yLM - earthY, 2) +
            Math.pow(zLM - earthZ, 2)
        );

        // Check for impact
        if (distancex <= EARTH_RADIUS) {//########################
            hitEarthx = true;
            break;
        }

        // Gravitational acceleration
        const accelerationx = GRAVITATIONAL_CONSTANT / (distancex * distancex);
        const accXx = -accelerationx * (xLM - earthX) / distancex;
        const accYx = -accelerationx * (yLM - earthY) / distancex;
        const accZx = -accelerationx * (zLM - earthZ) / distancex;

// Update positions
        xLM += (deltaXL + ((accXx))) * TIME_STEP;
        yLM += (deltaYL + ((accYx))) * TIME_STEP;
        zLM += (deltaZL + ((accZx))) * TIME_STEP;

        // Update velocities
        deltaXL += accXx * TIME_STEP;
        deltaYL += accYx * TIME_STEP;
        deltaZL += accZx * TIME_STEP;

        

        // Store position for trajectory visualization
        lmTrajectory.push({ x: xLM, y: yLM });
    }
    
 // Moon's orbital period and angular velocity
const MOON_ORBITAL_PERIOD = 27.32 * 86400; // Moon's orbital period in seconds
const MOON_ANGULAR_VELOCITY = 360 / MOON_ORBITAL_PERIOD; // Degrees per second

// Find time to apogee for the CM
let timeToApogee = null;
for (let t = 0; t < cmTrajectory.length; t++) {
    const { x, y } = cmTrajectory[t];
    const distance = Math.sqrt(Math.pow(x - earthX, 2) + Math.pow(y - earthY, 2));

    // If this is the farthest point (apogee), calculate the time
    if (!timeToApogee || distance > timeToApogee.distance) {
        timeToApogee = { time: t * TIME_STEP, distance };
    }
}
// Find time to apogee for the CM
let timeToApogeeL = null;
for (let t = 0; t < lmTrajectory.length; t++) {
    const { x, y } = lmTrajectory[t];
    const distancez = Math.sqrt(Math.pow(x - earthX, 2) + Math.pow(y - earthY, 2));

    // If this is the farthest point (apogee), calculate the time
    if (!timeToApogeeL || distancez > timeToApogeeL.distancez) {
        timeToApogeeL = { time: t * TIME_STEP, distancez };
    }
}

// Predict Moon's angular position at apogee
const currentMoonAngle1 = Math.atan2(yMOON - earthY, xMOON - earthX) * (180 / Math.PI); // Current angle in degrees

const currentMoonAngle = currentMoonAngle1 < 0 ? currentMoonAngle1 + 360 : currentMoonAngle1;
const futureMoonAngle = (currentMoonAngle + MOON_ANGULAR_VELOCITY * (72 * 60 * 60)) % 360; // Future angle

// Normalize angle to [0, 360]
const normalizedFutureMoonAngle = futureMoonAngle < 0 ? futureMoonAngle + 360 : futureMoonAngle;

// Calculate the antipole (180° opposite)
const antipoleAngle = (normalizedFutureMoonAngle + 180) % 360;

// Calculate the date and time at apogee in EST
const now = new Date(); // Current date and time
const apogeeTime = new Date(now.getTime() + (timeToApogee?.time || 0) * 1000); // Add time to apogee in milliseconds
const options = { timeZone: 'America/New_York', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
const apogeeDateTimeEST = apogeeTime.toLocaleString('en-US', options);

// Display the predicted angular position, time, and antipole below the second map
document.getElementById('predictedMoonPosition').innerHTML =
    `Predicted LM and CM Apogee: ${normalizedFutureMoonAngle.toFixed(2)}°<br>` +
    `Date and Time at cross (EST): ${apogeeDateTimeEST}<br>` +
    `Current Angle: ${currentMoonAngle.toFixed(2)}°<br>` +
    `72 hr Angle: ${normalizedFutureMoonAngle.toFixed(2)}°<br>` +
    `72 hr Antipole: ${antipoleAngle.toFixed(2)}°`;

 

    // Display current position of CM
    document.getElementById('currentPosition').textContent = `X: ${xCM.toFixed(2)}, Y: ${yCM.toFixed(2)}, Z: ${zCM.toFixed(2)}`;

    // Plugin for drawing circles
    const backgroundCirclesPlugin = {
        id: 'backgroundCircles',
        beforeDraw(chart, args, options) {
            const ctx = chart.ctx;
            const xScale = chart.scales.x;
            const yScale = chart.scales.y;

            // Earth
            const earthXPixel = xScale.getPixelForValue(earthX);
            const earthYPixel = yScale.getPixelForValue(earthY);
            const earthRadiusPixels = xScale.getPixelForValue(earthX + EARTH_RADIUS) - xScale.getPixelForValue(earthX);

            ctx.beginPath();
            ctx.arc(earthXPixel, earthYPixel, earthRadiusPixels, 0, 2 * Math.PI);
            ctx.fillStyle = 'yellow';
            ctx.fill();

            // Moon
            const moonXPixel = xScale.getPixelForValue(xMOON);
            const moonYPixel = yScale.getPixelForValue(yMOON);
            const moonRadiusPixels = xScale.getPixelForValue(xMOON + MOON_RADIUS) - xScale.getPixelForValue(xMOON);

            ctx.beginPath();
            ctx.arc(moonXPixel, moonYPixel, moonRadiusPixels, 0, 2 * Math.PI);
            ctx.fillStyle = 'yellow';
            ctx.fill();
        }
    };

   // Chart 1: Earth, Lunar Module (LM), and Command Module (CM)
const ctx1 = document.getElementById('chartEarthLMCM').getContext('2d');
new Chart(ctx1, {
    type: 'scatter',
    data: {
        datasets: [{ // Lunar Module trajectory
                label: 'Lunar Module Trajectory',
                data: lmTrajectory,
                pointRadius: 1,
                backgroundColor: hitEarthx ? 'yellow' : 'white',
            },
            { // Lunar Module
                label: 'Lunar Module',
                data: [{ x: xLMP, y: yLMP }],
                pointRadius: 3,
                backgroundColor: 'yellow',
            },
            { // Command Module trajectory
                label: 'Command Module Trajectory',
                data: cmTrajectory,
                pointRadius: 1,
                backgroundColor: hitEarth ? 'red' : 'pink',
            },
             
            { // Current Position of Command Module
                label: 'Current Position of CM',
                data: [{ x: xCMP, y: yCMP }], // Current position
                pointRadius: 3, // Larger point size for emphasis
                backgroundColor: 'red', // Unique color for current position
            },
        ]
    },
    options: {
        responsive: false,
        scales: {
            x: { min: earthX - onion, max: earthX + onion },
            y: { min: earthY - onion, max: earthY + onion }
        },
        plugins: {
            legend: { display: true }
        }
    },
    plugins: [backgroundCirclesPlugin]
});

  // Chart 2: Earth, Moon, and Command Module (CM)
const ctx2 = document.getElementById('chartEarthMoonCM').getContext('2d');
new Chart(ctx2, {
    type: 'scatter',
    data: {
        datasets: [
           
            { // Command Module trajectory
                label: 'Command Module Trajectory',
                data: cmTrajectory,
                pointRadius: 1,
                backgroundColor: hitEarth ? 'red' : 'white',
            },
            { // Current Position of Command Module
                label: 'Current Position of CM',
                data: [{ x: xCMP, y: yCMP }], // Current position
                pointRadius: 2, // Larger point size for emphasis
                backgroundColor: 'white', // Unique color for current position
            },
        ]
    },
    options: {
        responsive: false,
        scales: {
            x: { min: earthX - 400000000, max: earthX + 400000000 },
            y: { min: earthY - 400000000, max: earthY + 400000000 }
        },
        plugins: {
            legend: { display: true }
        }
    },
    plugins: [backgroundCirclesPlugin]
});

</script><?php
echo "<h2>Orbital Position and Velocity Data</h2>";

echo "<strong>Command Module (CM):</strong><br>";
echo "X: " . number_format($xCM, 2) . " m<br>";
echo "Y: " . number_format($yCM, 2) . " m<br>";
echo "Z: " . number_format($zCM, 2) . " m<br>";
echo "Velocity X: " . number_format($delta_xCM, 2) . " m/s<br>";
echo "Velocity Y: " . number_format($delta_yCM, 2) . " m/s<br>";
echo "Velocity Z: " . number_format($delta_zCM, 2) . " m/s<br>";
echo "<br>";

echo "<strong>Lunar Module (LM):</strong><br>";
echo "X: " . number_format($xLM, 2) . " m<br>";
echo "Y: " . number_format($yLM, 2) . " m<br>";
echo "Z: " . number_format($zLM, 2) . " m<br>";
echo "Velocity X: " . number_format($delta_xLM, 2) . " m/s<br>";
echo "Velocity Y: " . number_format($delta_yLM, 2) . " m/s<br>";
echo "Velocity Z: " . number_format($delta_zLM, 2) . " m/s<br>";
echo "<br>";?>
</body>
</html>
