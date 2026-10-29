<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCS Thruster Controls</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #2a2a2a;
            color: #ffffff;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        h1 {
            margin: 20px 0;
        }
        #rcs-controls {
            display: grid;
            grid-template-columns: repeat(3, 100px);
            gap: 1px;
            margin: 10px auto;
            width: 200px;
            background-color: #1a1a1a;
            padding: 20px;
            border: 2px solid #d4af37;
            border-radius: 10px;
            
        }
     
        button {
            background-color: #333;
            color: #d4af37;
            border: 2px solid #d4af37;
            padding: 10px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }
        footer {
            margin-top: 30px;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
   <span>LATERAL THRUSTERS</span>
    <div id="rcs-controls">
        <table>
            <tr>
                <td><button onclick="activateThruster('axis')">↑</button></td>
                <td>&nbsp;&nbsp;</td>
                <td><button onclick="activateThruster('axis2')">↑</button></td>
                <td>&nbsp;&nbsp;</td>
                <td><button onclick="activateThruster('axis3')">↑</button></td>
            </tr>
            <tr>
                <td><button onclick="activateThruster('rever')">↓</button></td>
                <td>&nbsp;&nbsp;</td>
                <td><button onclick="activateThruster('rever2')">↓</button></td>
                <td>&nbsp;&nbsp;</td>
                <td><button onclick="activateThruster('rever3')">↓</button></td>
            </tr>
            <tr>
                <td>0.05%</td>
                <td>&nbsp;&nbsp;</td>
                <td>0.50%</td>
                <td>&nbsp;&nbsp;</td>
                <td>5.0%</td>
            </tr>
        </table>
    </div>

    <div id="dockStatus" class="status-box" style="background-color: grey;">
        <span class="status-text">DOCKED</span>
    </div>

    <script>
        function activateThruster(thruster) {
            fetch('thrusters.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ "thruster": thruster })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    console.log(data.message);

                    // Play sound
                    let audio = new Audio(data.sound);
                    audio.play();
                } else {
                    console.error("Error:", data.message);
                }
            })
            .catch(error => console.error("Fetch error:", error));
        }
    </script>
    <script>
        function checkDockStatus() {
            fetch('get_dock_status.php')
                .then(response => response.json())
                .then(data => {
                    const dockBox = document.getElementById("dockStatus");

                    if (data.dockCM == 1) {
                        dockBox.style.backgroundColor = "yellow";
                    } else {
                        dockBox.style.backgroundColor = "grey";
                    }

                    console.log("DockCM:", data.dockCM); // Debugging output
                })
                .catch(error => console.error("Error fetching dock status:", error));
        }

        setInterval(checkDockStatus, 1000); // Check every second
        checkDockStatus();
    </script>
</body>
</html>
