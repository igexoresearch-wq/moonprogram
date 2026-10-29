<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Launch and Abort Buttons</title>
    <style>
        .button {
            width: 100px;
            height: 100px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.3);
            cursor: pointer;
        }

        .button:disabled {
            background-color: gray;
            color: white;
            cursor: not-allowed;
            box-shadow: none;
        }

        .launch-button {
            background: yellow;
            color: red;
            border: 3px solid black;
            background: linear-gradient(45deg, yellow 50%, black 50%);
        }

        .abort-button {
            background: red;
            color: white;
            border: 3px solid black;
            background: linear-gradient(45deg, red 50%, black 50%);
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <?php
    $servername = "localhost";
    $username = "victorywebproductions";
    $password = "CHANGE_ME";
    $dbname = "moonland";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $launch_disabled = false;
    $abort_disabled = false;

    $result = $conn->query("SELECT id, status FROM switches WHERE id IN (11, 12)");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['id'] == 11 && $row['status'] == 1) {
                $launch_disabled = true;
            }
            if ($row['id'] == 12 && $row['status'] == 1) {
                $abort_disabled = true;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];
        $system_id = $action === 'launch' ? 11 : ($action === 'abort' ? 12 : null);
        $system_name = $action === 'launch' ? 'launch' : ($action === 'abort' ? 'abort' : null);

        if ($system_id !== null && $system_name !== null) {
            // Update switches table
            $stmt = $conn->prepare("UPDATE switches SET status = 1 WHERE id = ?");
            $stmt->bind_param("i", $system_id);
            $stmt->execute();
            $stmt->close();

            // Update systems table
            $stmt = $conn->prepare("UPDATE systems SET status = 1 WHERE system = ?");
            $stmt->bind_param("s", $system_name);
            $stmt->execute();
            $stmt->close();

            // Refresh the page to prevent resubmission
            echo "<script>window.location = window.location.pathname;</script>";
            exit;
        }
    }

    $conn->close();
    ?>

    <div class="button-container">
        <form method="POST">
            <input type="hidden" name="action" value="launch">
            <button type="submit" class="button launch-button" <?php echo $launch_disabled ? 'disabled' : ''; ?>>Launch</button>
        </form>
        <form method="POST">
            <input type="hidden" name="action" value="abort">
            <button type="submit" class="button abort-button" <?php echo $abort_disabled ? 'disabled' : ''; ?>>Abort</button>
        </form>
    </div>
</body>
</html>
