<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spacecraft Viewport Simulation</title>
    <style>
        body {
            margin: 0;
            background: black;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        #viewport {
            position: relative;
            width: 600px;
            height: 600px;
            background: black;
            overflow: hidden;
            border: 2px solid gray;
            transition: background-color 0.5s linear;
        }

        .object {
            position: absolute;
            transform: translate(-50%, -50%);
            transition: left 0.3s linear, top 0.3s linear, width 0.3s ease, height 0.3s ease;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div id="viewport"></div>

    <script>
        let lastData = {}; // Store last data to compare and reduce redundant updates

        function fetchAndUpdate() {
            $.getJSON("AJwindow.php", function(data) {
                if (JSON.stringify(data) !== JSON.stringify(lastData)) {
                    updateSimulation(data);
                    lastData = data;
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error:", textStatus, errorThrown);
            });
        }

        function createOrUpdateObject(id, src, size, x, y) {
            let obj = document.getElementById(id);
            if (!obj) {
                obj = document.createElement("img");
                obj.id = id;
                obj.src = src;
                obj.className = "object";
                document.getElementById("viewport").appendChild(obj);
            }
            obj.style.width = size + "px";
            obj.style.height = size + "px";
            obj.style.left = x + "px";
            obj.style.top = y + "px";
        }

      function updateSimulation(data) {
    const { pitchCM, rollCM, yawCM, xCM, yCM, zCM, xMOON, yMOON, zMOON, xLM, yLM, zLM, dockCM, stage3CM } = data;
    const commandModule = { x: xCM, y: yCM, z: zCM };

    const earthData = calculateObjectPosition({ x: 1e10, y: 1e10, z: 1e10 }, commandModule, 12742e3);
    updateObject("earth", "earth.png", earthData, pitchCM, yawCM);

    const moonData = calculateObjectPosition({ x: xMOON, y: yMOON, z: zMOON }, commandModule, 3474e3);
    updateObject("moon", "moon.png", moonData, pitchCM, yawCM);

    const lmElement = document.getElementById("lm");

    if (stage3CM != 1) {
        const lmData = calculateObjectPosition({ x: xLM, y: yLM, z: zLM }, commandModule, 2.5);
        updateObject("lm", "Apollo_11_lunar_module.png", lmData, pitchCM, yawCM);
    } else if (lmElement) {
        // REMOVE LM from the DOM when docked
        lmElement.remove();
    }
}


        function updateObject(id, src, objectData, pitchCM, yawCM) {
            const { yaw, pitch, angularSize } = objectData;
            const viewportSize = 1200;
            const fov = 30;

            const xPos = mapToViewport(normalizeYawDifference(yaw, yawCM), fov, viewportSize);
            const yPos = mapToViewport(pitch - pitchCM, fov, viewportSize);
            const size = (angularSize / fov) * viewportSize;

            createOrUpdateObject(id, src, size, xPos, yPos);
        }

        function normalizeYawDifference(yaw1, yaw2) {
            let difference = yaw1 - yaw2;
            if (difference > 180) difference -= 360;
            if (difference < -180) difference += 360;
            return difference;
        }

        function mapToViewport(angle, fov, viewportSize) {
            return ((angle + fov / 2) / fov) * viewportSize / 2;
        }

        function calculateObjectPosition(object, commandModule, diameter) {
            const dx = object.x - commandModule.x;
            const dy = object.y - commandModule.y;
            const dz = object.z - commandModule.z;
            const distance = Math.sqrt(dx ** 2 + dy ** 2 + dz ** 2);

            let pitch = Math.atan2(dz, Math.sqrt(dx ** 2 + dy ** 2)) * (180 / Math.PI);
            let yaw = 360 - (Math.atan2(dy, dx) * (180 / Math.PI));
            if (yaw < 0) yaw += 360;

            const angularSize = Math.atan(diameter / (2 * (distance - 5))) * (180 / Math.PI);
            return { distance, pitch, yaw, angularSize };
        }

        setInterval(fetchAndUpdate, 1000); // Faster updates for smoother real-time effect
        fetchAndUpdate();
    </script>
</body>
</html>
