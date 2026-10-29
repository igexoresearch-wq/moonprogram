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
            <h1>Earth or Moon</h1>
            <canvas id="chartEarthLMCM" width="600" height="600"></canvas>
        </td>
        <td>
            <h1>Earth/Moon</h1>
            <canvas id="chartEarthMoonCM" width="700" height="700"></canvas>
        </td>
    </tr>
</table>
<div>
    <h2>Current Position of Command Module</h2>
    <p id="currentPosition"></p>
</div>
<script>
    // PHP Variables (passed into JS)
    const EARTH_RADIUS = 6371000; // Earth's radius in meters
    const MOON_RADIUS = 1737100; // Moon's radius in meters
     const BUFFER = <?php echo $altitudemoon; ?> * 1.5; // Extra buffer for Moon chart scaling
    const altitudemoon = <?php echo $altitudemoon; ?>;
    const earthX = <?php echo $earthX; ?>;
    const earthY = <?php echo $earthY; ?>;
    const xMOON = <?php echo $xMOON; ?>;
    const yMOON = <?php echo $yMOON; ?>;
    const xLM = <?php echo $xLM; ?>;
    const yLM = <?php echo $yLM; ?>;
    const xCM = <?php echo $xCM; ?>;
    const yCM = <?php echo $yCM; ?>;

    // Determine if chart should center on the Earth or Moon
    const useMoon = altitudemoon < 80000000; // Use Moon if closer than 80,000,000m
    const centerX = useMoon ? xMOON : earthX;
    const centerY = useMoon ? yMOON : earthY;
    const radius = useMoon ? MOON_RADIUS : EARTH_RADIUS;
    const rangeBuffer = useMoon ? BUFFER : 0;

    // Chart Ranges
    const rangeXMin = centerX - radius - rangeBuffer;
    const rangeXMax = centerX + radius + rangeBuffer;
    const rangeYMin = centerY - radius - rangeBuffer;
    const rangeYMax = centerY + radius + rangeBuffer;

    // Plugin for Drawing Static Circle (Earth or Moon)
    const backgroundCirclesPlugin = {
        id: 'backgroundCircles',
        beforeDraw(chart, args, options) {
            const ctx = chart.ctx;
            const xScale = chart.scales.x;
            const yScale = chart.scales.y;

            // Draw Earth or Moon
            const centerPixelX = xScale.getPixelForValue(centerX);
            const centerPixelY = yScale.getPixelForValue(centerY);
            const radiusPixels = xScale.getPixelForValue(centerX + radius) - xScale.getPixelForValue(centerX);

            ctx.beginPath();
            ctx.arc(centerPixelX, centerPixelY, radiusPixels, 0, 2 * Math.PI);
            ctx.fillStyle = 'yellow';
            ctx.fill();
        }
    };

    // Chart 1: Earth or Moon, Lunar Module (LM), and Command Module (CM)
    const ctx1 = document.getElementById('chartEarthLMCM').getContext('2d');
    new Chart(ctx1, {
        type: 'scatter',
        data: {
            datasets: [
                { // Lunar Module
                    label: 'Lunar Module',
                    data: [{ x: xLM, y: yLM }],
                    pointRadius: 2,
                    backgroundColor: 'yellow',
                },
                { // Command Module
                    label: 'Command Module',
                    data: [{ x: xCM, y: yCM }],
                    pointRadius: 2,
                    backgroundColor: 'white',
                }
            ]
        },
        options: {
            responsive: false,
            scales: {
                x: {
                    min: rangeXMin,
                    max: rangeXMax,
                    title: { display: true, text: useMoon ? 'Centered on Moon' : 'Centered on Earth' },
                },
                y: {
                    min: rangeYMin,
                    max: rangeYMax,
                }
            },
            plugins: {
                legend: { display: true }
            }
        },
        plugins: [backgroundCirclesPlugin]
    });

    // Chart 2: Earth/Moon and Command Module (CM)
    const ctx2 = document.getElementById('chartEarthMoonCM').getContext('2d');
    new Chart(ctx2, {
        type: 'scatter',
        data: {
            datasets: [
                { // Earth
                    label: 'Earth',
                    data: [{ x: earthX, y: earthY }],
                    pointRadius: EARTH_RADIUS / 500000,
                    backgroundColor: 'green',
                },
                { // Moon
                    label: 'Moon',
                    data: [{ x: xMOON, y: yMOON }],
                    pointRadius: MOON_RADIUS / 500000,
                    backgroundColor: 'yellow',
                },
                { // Command Module
                    label: 'Command Module',
                    data: [{ x: xCM, y: yCM }],
                    pointRadius: 5,
                    backgroundColor: 'white',
                }
            ]
        },
        options: {
            responsive: false,
            scales: {
                x: {
                    min: earthX - 400000000,
                    max: earthX + 400000000,
                    title: { display: true, text: 'Earth/Moon Map' },
                },
                y: {
                    min: earthY - 400000000,
                    max: earthY + 400000000,
                }
            },
            plugins: {
                legend: { display: true }
            }
        }
    });

    // Display current position of Command Module
    document.getElementById('currentPosition').textContent = `X: ${xCM}, Y: ${yCM}`;
</script>
</body>
</html>
