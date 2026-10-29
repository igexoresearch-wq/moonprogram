<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Gauges</title>
    <style>
        .gauge-container {
            width: 63px;
            height: 200px;
            border: 2px solid grey;
            border-radius: 10px;
            background: #f0f0f0;
            box-shadow: inset -3px -3px 5px rgba(255, 255, 255, 0.5),
                        inset 3px 3px 5px rgba(0, 0, 0, 0.2);
            position: relative;
            font-family: Arial, sans-serif;
            color: black;
            margin: 0 10px;
        }

        .gauge-ticks {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column-reverse;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1px;
            box-sizing: border-box;
        }

        .gauge-tick {
            display: flex;
            align-items: center;
        }

        .major-tick {
            border-left: 20px solid black;
            height: 1px;
            width: 20px;
            margin-left: 5px;
        }

        .minor-tick {
            border-left: 10px solid black;
            height: 1px;
            width: 10px;
            margin-left: 5px;
        }

        .tick-label {
            margin-left: 10px;
            font-size: 12px;
        }

        .gauge-pointer {
            position: absolute;
            left: 0;
            width: 50%;
            height: 5px;
            background: black;
            transition: bottom 0.5s ease-in-out;
        }
    </style>
</head>
<body>

<div style="display: flex; justify-content: center;">
    <!-- Distance Gauge -->
    <div class="gauge-container">
        <div class="gauge-ticks" id="distanceTicks"></div>
        <div class="gauge-pointer" id="distancePointer"></div>
    </div>

    <!-- Velocity Gauge -->
    <div class="gauge-container">
        <div class="gauge-ticks" id="speedTicks"></div>
        <div class="gauge-pointer" id="speedPointer"></div>
    </div>

    <!-- Scaled Velocity Gauge -->
    <div class="gauge-container">
        <div class="gauge-ticks" id="scaledTicks"></div>
        <div class="gauge-pointer" id="scaledPointer"></div>
    </div>
</div>

<script>
// Fetch and update gauges every second
function updateGauges() {
    fetch('guage_data.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById("distancePointer").style.bottom = data.distanceGauge + "%";
        document.getElementById("speedPointer").style.bottom = data.speedGauge + "%";
        document.getElementById("scaledPointer").style.bottom = data.scaledGauge + "%";

        updateTicks("distanceTicks", data.distanceTicks);
        updateTicks("speedTicks", data.speedTicks);
        updateTicks("scaledTicks", data.scaledTicks);
    });
}

// Function to update tick marks dynamically
function updateTicks(containerId, ticks) {
    let tickContainer = document.getElementById(containerId);
    tickContainer.innerHTML = "";
    ticks.forEach(tick => {
        let tickDiv = document.createElement("div");
        tickDiv.className = tick % 20 === 0 ? "gauge-tick major-tick" : "gauge-tick minor-tick";
        tickDiv.innerHTML = tick % 20 === 0 ? `<div class="tick-label">${tick}</div>` : "";
        tickContainer.appendChild(tickDiv);
    });
}

// Run every second
setInterval(updateGauges, 2000);
updateGauges();
</script>

</body>
</html>
