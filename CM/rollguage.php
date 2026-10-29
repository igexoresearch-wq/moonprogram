<!DOCTYPE html>

    <style>
        body {
            background: black;
            color: white;
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
        }



        .containerxx {
            width: 300px;
            height: 25px;
            background: gray;
            position: relative;
            overflow: hidden;
            border: 1px solid white;
            margin: auto;
        }

        .linex {
            width: 3px;
            height: 25px;
            background: red;
            position: absolute;
        }

        .scalex {
            width: 300px;
            position: relative;
            margin-top: 5px;
            font-size: 12px;
        }

        .scalex .tick {
            position: absolute;
            width: 1px;
            background: white;
            top: 0;
        }

        .scalex .tick.major {
            height: 12px;
        }

        .scalex .tick.minor {
            height: 6px;
        }

        .scalex .label {
            position: absolute;
            top: 15px;
            transform: translateX(-50%);
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td><b>ROLL</b></td>
    </tr>
    <tr>
        <td>
            <div class="containerxx">
                <div class="linex" id="rollLine" style="left: 0px;"></div>
            </div>
            <div class="scalex" id="rollScale"></div>
        </td>
    </tr>
    <tr>
        <td><b>YAW</b></td>
    </tr>
    <tr>
        <td>
            <div class="containerxx">
                <div class="linex" id="yawLine" style="left: 0px;"></div>
            </div>
            <div class="scalex" id="yawScale"></div>
        </td>
    </tr>
</table>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        generateScale("rollScale");
        generateScale("yawScale");
        updateRollYawPosition();
        setInterval(updateRollYawPosition, 1000);
    });

    // Function to create scale with tick marks and labels
    function generateScale(scaleId) {
        let scaleDiv = document.getElementById(scaleId);
        for (let i = 0; i <= 9; i++) {
            let tickMajor = document.createElement("div");
            tickMajor.className = "tick major";
            tickMajor.style.left = ((i / 10)) * 300 + "px";
            scaleDiv.appendChild(tickMajor);

            let label = document.createElement("div");
            label.className = "label";
            label.style.left = (i / 10) * 300 + "px";
            label.innerText = i;
            scaleDiv.appendChild(label);

            for (let j = 1; j <= 9; j++) {
                let position = ((i + j / 10) / 10) * 300;
                let tickMinor = document.createElement("div");
                tickMinor.className = "tick minor";
                tickMinor.style.left = position + "px";
                tickMinor.style.height = j === 5 ? "12px" : "6px";
                scaleDiv.appendChild(tickMinor);
            }
        }
    }

    // Function to update Roll & Yaw positions in real time
    function updateRollYawPosition() {
        fetch('roll_yaw_data.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById("rollLine").style.left = data.rollPosition + "px";
            document.getElementById("yawLine").style.left = data.yawPosition + "px";
        })
        .catch(error => console.error("Error fetching roll & yaw data:", error));
    }
</script>

