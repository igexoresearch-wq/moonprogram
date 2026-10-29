<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velocity Difference Chart</title>
    <style>
        body { text-align: center; font-family: Arial, sans-serif; }
        canvas { border: 2px solid black; background: #f0f0f0; }
    </style>
</head>
<body>


    <canvas id="velocityChart" width="300" height="300"></canvas>
    
    <script>
        const canvas = document.getElementById("velocityChart");
        const ctx = canvas.getContext("2d");
        const scale = 50; // 2 pixels = 1 m/s (1px = 0.5 m/s)

        function fetchAndUpdate() {
            fetch('get_delta.php')
                .then(response => response.json())
                .then(data => {
                    console.log("Received data:", data); // Debugging
                    drawChart(data);
                })
                .catch(error => console.error("Error fetching data:", error));
        }

        function drawChart(data) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw center origin
            ctx.fillStyle = "black";
            ctx.fillRect(149, 149, 2, 2);  

            function plotDot(x, y, z, color) {
                const centerX = 150;
                const centerY = 150;

                let pxX = centerX + (x * scale);
                let pxY = centerY - (y * scale); // Invert Y-axis
                let pxZ = 150 - (z * scale); // Z-axis moves up/down

                console.log(`Plotting ${color} at (${pxX}, ${pxY}), Z=${pxZ}`); // Debugging

                ctx.beginPath();
                ctx.arc(pxX, pxY, 2, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();

                ctx.fillStyle = "black";
                ctx.fillText(`Z: ${parseFloat(z).toFixed(2)} m/s`, pxX + 10, pxZ);
            }

            if (data.delta_xCM !== undefined && data.delta_yCM !== undefined && data.delta_zCM !== undefined &&
                data.delta_xLM !== undefined && data.delta_yLM !== undefined && data.delta_zLM !== undefined) {
                
                // Calculate velocity differences (CM - LM)
                let net_x = data.delta_xCM - data.delta_xLM;
                let net_y = data.delta_yCM - data.delta_yLM;
                let net_z = data.delta_zCM - data.delta_zLM;

                // Plot CM as red (net velocity differences)
                plotDot(net_x, net_y, net_z, "red");
            } else {
                console.warn("Missing velocity data:", data);
            }
        }

        setInterval(fetchAndUpdate, 1000); // Update every second
        fetchAndUpdate();
    </script>

</body>
</html>