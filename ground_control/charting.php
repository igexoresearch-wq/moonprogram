<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMtelemetry Data Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .container {
            width: 80%;
            margin: auto;
        }

        .chart-container {
            width: 1200px;
            height: 1000px;
            margin: auto;
        }
        canvas {
            width: 1200px !important;
            height: 1000px !important;
            background: white;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>CMtelemetry Data Chart</h2>

    <div class="controls">
        <label>Start Time:</label>
        <input type="datetime-local" id="startTime">

        <label>End Time:</label>
        <input type="datetime-local" id="endTime">

        <label>Select Attributes:</label>
        <select id="attributes" multiple>
            <option value="stage1">Stage 1</option>
            <option value="stage2">Stage 2</option>
            <option value="stage3">Stage 3</option>
            <option value="s1fuel">S1 Fuel</option>
            <option value="s2fuel">S2 Fuel</option>
            <option value="s3fuel">S3 Fuel</option>
            <option value="fuel">Fuel</option>
            <option value="pitch">Pitch</option>
            <option value="roll">Roll</option>
            <option value="yaw">Yaw</option>
            <option value="x">X</option>
            <option value="y">Y</option>
            <option value="z">Z</option>
            <option value="thrust">Thrust</option>
            <option value="RCS">RCS</option>
            <option value="parachutes_deployed">Parachutes</option>
            <option value="ABORT">Abort</option>
            <option value="staging">Staging</option>
            <option value="temp">Temperature</option>
            <option value="heat_shield">Heat Shield</option>
            <option value="delta_x">Delta X</option>
            <option value="delta_y">Delta Y</option>
            <option value="delta_z">Delta Z</option>
            <option value="dock">Dock</option>
            <option value="velocity">Velocity</option>
            <option value="AM">Angular Momentum</option>
        </select>

        <button onclick="fetchData()">Load Chart</button>
    </div>

    <div class="chart-container">
        <canvas id="telemetryChart"></canvas>
    </div>
</div>

<script>
let chartInstance = null;

function fetchData() {
    const startTime = document.getElementById("startTime").value;
    const endTime = document.getElementById("endTime").value;
    const attributes = Array.from(document.getElementById("attributes").selectedOptions).map(option => option.value);

    if (!startTime || !endTime || attributes.length === 0) {
        alert("Please select a valid date range and at least one attribute.");
        return;
    }

    $.ajax({
        url: "fetch_telemetry.php",
        method: "POST",
        data: { startTime, endTime, attributes },
        dataType: "json",
        success: function(response) {
            updateChart(response);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching data:", error);
        }
    });
}

function updateChart(data) {
    const ctx = document.getElementById("telemetryChart").getContext("2d");

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels: data.timestamps,
            datasets: data.datasets.map(dataset => ({
                label: dataset.label,
                data: dataset.values.map(v => v !== null ? v : null),
                borderColor: getRandomColor(),
                borderWidth: 1,
                pointRadius: 1,
                fill: false,
                tension: 0.1
            }))
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            scales: {
                x: { 
                    type: 'category', 
                    title: { display: true, text: "Timestamp" }
                },
                y: { 
                    beginAtZero: false, 
                    title: { display: true, text: "Values" }
                }
            }
        }
    });
}

function getRandomColor() {
    return `hsl(${Math.random() * 360}, 100%, 50%)`;
}
</script>

</body>
</html>
