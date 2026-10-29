<?php
// Database connection
$servername = "localhost";
$username = "victorywebproductions";
$password = "CHANGE_ME";
$dbname = "moonland";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch RCS amount
$rcsQuery = "SELECT value FROM telemetry WHERE id = 19"; // Assuming RCS amount is ID 20
$rcsResult = $conn->query($rcsQuery);
$rcsAmount = floatval($rcsResult->fetch_assoc()["value"] ?? 0);

// Calculate percentage
$rcsPercentage = round(($rcsAmount / 3368) * 100);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCS Vertical Dial</title>
    <style>
        .dial-container {
            width: 80px;
            height: 200px;
            border: 2px solid grey;
            border-radius: 10px;
            background: #f0f0f0;
            box-shadow: inset -3px -3px 5px rgba(255, 255, 255, 0.5),
                        inset 3px 3px 5px rgba(0, 0, 0, 0.2);
            position: relative;
            font-family: Arial, sans-serif;
            color: black;
        }

        .ticks {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            padding: 5px;
            box-sizing: border-box;
        }

        .tick {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .tick-10 {
            border-left: 20px solid black;
            height: 1px;
            width: 20px;
            margin-left: 5px;
        }

        .tick-5 {
            border-left: 10px solid black;
            height: 1px;
            width: 10px;
            margin-left: 5px;
        }

        .tick-label {
            margin-left: 10px;
            font-size: 12px;
        }

        .needle {
            position: absolute;
            bottom: calc(<?php echo $rcsPercentage; ?>% - 2px);
            left: 0;
            width: 50%;
            height: 5px;
            background: black;
        }
    </style>
</head>
<body>
    <div class="dial-container">
        <div class="ticks">
            <?php for ($i = 100; $i >= 0; $i -= 5): ?>
                <div class="tick">
                    <div class="<?php echo $i % 10 === 0 ? 'tick-10' : 'tick-5'; ?>"></div>
                    <?php if ($i % 10 === 0): ?>
                        <div class="tick-label"><?php echo $i; ?></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        <div class="needle"></div>
    </div>
</body>
</html>
