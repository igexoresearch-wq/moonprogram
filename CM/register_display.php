<?php 
// Check if the register is full
$registerFullQuery = $conn->query("SELECT COUNT(*) AS count FROM CMprogram_register WHERE program_number != 0 AND seconds IS NOT NULL");
$registerFull = $registerFullQuery && $registerFullQuery->fetch_assoc()['count'] == 5;

// Check if any execute = 1 in id = 10
$busyQuery = $conn->query("SELECT execute FROM CMguidance WHERE id = 10");
$busy = $busyQuery && $busyQuery->fetch_assoc()['execute'] == 1;

// Fetch the register data for display
$registerDataQuery = $conn->query("SELECT id, program_number, seconds FROM CMprogram_register");

// Fetch internal nonmonic values for mapping
$nonmonicQuery = $conn->query("SELECT id, nonmonic FROM CMguidance");
$nonmonics = [];
if ($nonmonicQuery) {
    while ($row = $nonmonicQuery->fetch_assoc()) {
        $nonmonics[$row['id']] = $row['nonmonic'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Display</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
        
            color: #d4af37;
            text-align: center;
            padding: 20px;
        }
        .status-box {
            width: 80px;
            height: 30px;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            border: 2px solid #555;
            border-radius: 5px;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.5);
            background-color: <?php echo $registerFull ? '#FFFF00' : '#D3D3D3'; ?>;
            color: #000;
        }
        .status-box.busy {
            background-color: <?php echo $busy ? '#FFFF00' : '#D3D3D3'; ?>;
        }
        .digital-display, .digital-display2 { font-family: 'DigitTech', monospace; font-size: 2rem; font-weight: bold; color: #FFFF00; background-color: #000000; padding: 5px; border-radius: 5px; text-align: right; }
        .digital-display2 { width: 4ch; }
        .digital-display { width: 12ch; }
        button {
            background-color: #555;
            color: #FFF;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        button:hover {
            background-color: #777;
        }
    </style>
</head>
<body>
    <h1>Register Display</h1>

    <div class="status-box busy">BUSY</div>
    <div class="status-box">REGISTER FULL</div>
 <div class="register-display">
        <table>
            <tr>
                <th>PROGRAM</th>
                <th>SECONDS</th><th></th>
            </tr>
            <?php
            if ($registerDataQuery) {
                while ($row = $registerDataQuery->fetch_assoc()) {
                    $id = $row['id'];
                    $program_number = $row['program_number'];
                    $seconds = $row['seconds'];

                    if ($program_number != 0 && !is_null($seconds)) {
                        $nonmonic = isset($nonmonics[$program_number]) ? $nonmonics[$program_number] : 'EMPTY';
                        $secondsDisplay = $seconds == -1 ? 'ONGN' : $seconds;
                        echo "<tr>
                                <td><div class='digital-display'>" . htmlspecialchars($nonmonic) . "</div></td>
                                <td><div class='digital-display2'>$secondsDisplay</div></td>
                                <td>
                                    <form method='POST'>
                                        <button name='clear_register' value='$id'>Clear</button>
                                    </form>
                                </td>
                              </tr>";
                    } else {
                        echo "<tr>
                                <td><div class='digital-display'>EMPTY</div></td>
                                <td><div class='digital-display2'>--</div></td>
                                <td><form method='POST'>
                                        <button name='clear_register' value='$id'>Clear</button>
                                    </form></td>
                              </tr>";
                    }
                }
            }
            ?>
        </table>
    </div>

    <?php
    // Handle register clearing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_register'])) {
        $idToClear = intval($_POST['clear_register']);
        $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE id = $idToClear");
$needs_redirect = true;
    }
    ?>
</body>
</html>
