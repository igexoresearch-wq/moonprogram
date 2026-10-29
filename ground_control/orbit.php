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
    <title>Dynamic Orbit Lengths for Rendezvous</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Ensure the canvas is fixed at 1200x1200 pixels */
        #orbitChart {
            width: 1200px !important;
            height: 1200px !important;
            display: block;
            margin: auto;
        }
    </style>
</head>
<body>

    <canvas id="orbitChart"></canvas>

    <script>
        // PHP-injected variables (Replace with actual PHP values)
        var xCM = <?php echo $xCM; ?>;
        var yCM = <?php echo $yCM; ?>;
        var delta_xCM = <?php echo $delta_xCM; ?>;
        var delta_yCM = <?php echo $delta_yCM; ?>;

        var xLM = <?php echo $xLM; ?>;
        var yLM = <?php echo $yLM; ?>;
        var delta_xLM = <?php echo $delta_xLM; ?>;
        var delta_yLM = <?php echo $delta_yLM; ?>;

        var moonGravity = 4.9028e12; // Moon gravitational parameter (m³/s²)
        var timeStep = 10; // Simulation step in seconds
        var maxTime = 900; // Simulate up to 1 hour into the future
        var stepsCM = maxTime / timeStep; // Number of CM steps
        var stepsLM = maxTime / timeStep; // Number of LM steps

        // Function to compute orbit path with a dynamically adjusted number of steps
        function computeOrbitPath(x, y, delta_x, delta_y, totalSteps) {
            let orbitPath = [];
            let r, acceleration, ax, ay;

            for (let i = 0; i < totalSteps; i++) {
                r = Math.sqrt(x ** 2 + y ** 2);
                acceleration = -moonGravity / (r ** 3);
                ax = acceleration * x;
                ay = acceleration * y;

                // Update velocity
                delta_x += ax * timeStep;
                delta_y += ay * timeStep;

                // Update position
                x += delta_x * timeStep;
                y += delta_y * timeStep;

                // Add the point to the orbit path
                orbitPath.push({ x: x, y: y });

                // Check if CM is close to LM (rendezvous condition)
                let distanceToLM = Math.sqrt((x - xLM) ** 2 + (y - yLM) ** 2);
                if (distanceToLM < 500) { // If within 500 meters, stop simulating
                    break;
                }
            }
            return orbitPath;
        }

        // Generate orbital paths with dynamic length adjustments
        var cmOrbit = computeOrbitPath(xCM, yCM, delta_xCM, delta_yCM, stepsCM);
        var lmOrbit = computeOrbitPath(xLM, yLM, delta_xLM, delta_yLM, stepsLM);

        // Get canvas context
        var ctx = document.getElementById('orbitChart').getContext('2d');

        // Destroy existing chart before creating a new one (prevents multiple instances)
        if (window.orbitChartInstance) {
            window.orbitChartInstance.destroy();
        }

        // Create Chart.js scatter plot
        window.orbitChartInstance = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        label: 'CM Future Orbit (Dynamic Length)',
                        data: cmOrbit,
                        borderColor: 'blue',
                        backgroundColor: 'transparent',
                        showLine: true,
                        pointRadius: 0
                    },
                    {
                        label: 'LM Future Orbit (Dynamic Length)',
                        data: lmOrbit,
                        borderColor: 'red',
                        backgroundColor: 'transparent',
                        showLine: true,
                        pointRadius: 0
                    },
                    {
                        label: 'Command Module (CM)',
                        data: [{ x: xCM, y: yCM }],
                        borderColor: 'blue',
                        backgroundColor: 'blue',
                        pointRadius: 5
                    },
                    {
                        label: 'Lunar Module (LM)',
                        data: [{ x: xLM, y: yLM }],
                        borderColor: 'red',
                        backgroundColor: 'red',
                        pointRadius: 5
                    }
                ]
            },
            options: {
                responsive: false, // Fixed size
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: { display: true, text: 'X Position (m)' }
                    },
                    y: {
                        title: { display: true, text: 'Y Position (m)' }
                    }
                }
            }
        });
    </script>

</body>
</html>
