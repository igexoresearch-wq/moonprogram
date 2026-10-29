<?php error_reporting(E_ALL);
ini_set('display_errors', 1);
$needs_redirect = false;
// Ensure database connection exists
if (!$conn) {
    die("Database connection failed.");
}

// Handle toggle actions for switches
if ($_SERVER['REQUEST_METHOD'] === 'POST') {  $needs_redirect = true;
    // Toggle a specific switch
    if (isset($_POST['toggle_id'])) {
        $toggle_id = intval($_POST['toggle_id']);

        // Fetch current status of the switch
        $result = $conn->query("SELECT status FROM switches WHERE id = $toggle_id");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $new_status = $row['status'] == 1 ? 0 : 1;

            // Update the switch's status in the switches table
            $conn->query("UPDATE switches SET status = $new_status WHERE id = $toggle_id");

            // Update the corresponding status in the systems table
            $conn->query("UPDATE systems SET status = $new_status WHERE id = $toggle_id");

            // Update probability if toggling on
            if ($new_status == 1) {
                $conn->query("UPDATE systems SET probability = FLOOR(1 + (RAND() * 1000)) WHERE id = $toggle_id AND probability = 1");
            }
        }
    }

    // Master Off: Turn off all switches (1–10)
    if (isset($_POST['master_off'])) { $needs_redirect = true;
        $conn->query("UPDATE switches SET status = 0 WHERE id BETWEEN 1 AND 10");
        $conn->query("UPDATE systems SET status = 0 WHERE id BETWEEN 1 AND 10");
    }

// Prog. Abort: Reset guidance table to original state
if (isset($_POST['prog_abort'])) {  $needs_redirect = true;
    $conn->query("UPDATE guidance SET verb = 'X' WHERE id BETWEEN 1 AND 10"); // Reset guidance verbs 1-10
    $conn->query("UPDATE guidance SET verb = 'O' WHERE id = 12"); // Set ID 12's verb to 'O'
    $conn->query("UPDATE guidance SET verb = 'X' WHERE id = 11"); // Reset ID 11 verb
    $conn->query("UPDATE guidance SET verb = 'X' WHERE id >= 13"); // Reset any remaining verbs to 'X'
}

    // Land Abort: Reset telemetry and systems
    if (isset($_POST['land_abort'])) { $needs_redirect = true;
        $conn->query("UPDATE telemetry SET value = CASE
            WHEN attribute = 'x' THEN 0
            WHEN attribute = 'y' THEN 1850000
            WHEN attribute = 'z' THEN 0
            WHEN attribute = 'delta_x' THEN 1642
            WHEN attribute = 'delta_y' THEN 0
            WHEN attribute = 'delta_z' THEN 0
            WHEN attribute = 'map_zoom' THEN 1
            WHEN attribute = 'fuel' THEN 8242
            WHEN attribute = 'altitude' THEN 110000
            WHEN attribute = 'roll_rate' THEN 0
            WHEN attribute = 'pitch_rate' THEN 0
            WHEN attribute = 'thrust' THEN 0
            WHEN attribute = 'rcs' THEN 3368
            ELSE 0
        END");
        $conn->query("UPDATE systems SET status = 1 WHERE id BETWEEN 1 AND 10");
        $conn->query("UPDATE telemetry SET value = 0 WHERE id BETWEEN 14 AND 17");
        $conn->query("UPDATE systems SET status = 0 WHERE id BETWEEN 11 AND 14");
          $conn->query("UPDATE systems SET status = 1 WHERE id = 17");
          $conn->query("UPDATE systems SET status = 0 WHERE id = 18");
                    $conn->query("UPDATE systems SET status = 0 WHERE id = 20");
          $conn->query("UPDATE switches SET status = 0 WHERE id = 11");
                    $conn->query("UPDATE switches SET status = 0 WHERE id = 12");
          // Database credentials
$db_host = 'localhost';
$db_name = 'moonland';
$db_user = 'victorywebproductions';
$db_pass = 'CHANGE_ME';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Default values
$defaults = [
    'x' => 0,
    'y' => 1850000,
    'z' => 0,
    'delta_x' => 1642,
    'delta_y' => 0,
    'delta_z' => 0,
        'docking' => 0
];

// Reset command_module table
$stmt = $pdo->prepare("UPDATE command_module SET value = :value WHERE attribute = :attribute");

foreach ($defaults as $attribute => $value) {
    $stmt->execute(['value' => $value, 'attribute' => $attribute]);
}

          
          
    }

    // Command Module Release: Set system ID 11 to 1
    if (isset($_POST['command_module_release'])) {  $needs_redirect = true;
        $conn->query("UPDATE systems SET status = 1 WHERE id = 11");
    }
}

// Fetch switch statuses and corresponding system names
$switches = [];
$result = $conn->query("SELECT switches.id AS switch_id, switches.status, systems.system 
                        FROM switches 
                        JOIN systems ON switches.id = systems.id 
                        WHERE switches.id BETWEEN 1 AND 10");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $switches[$row['switch_id']] = [
            'status' => $row['status'],
            'name' => ucfirst($row['system'])
        ];
    }
}
?>

<div class="switches-container">
    <form method="POST">
        <table class="switches-table">
            <?php for ($row = 0; $row < 4; $row++): ?>
                <tr>
                    <?php for ($col = 0; $col < 5; $col++): 
                        $switch_id = $row * 5 + $col + 1;
                        if ($switch_id > 10) break; 
                        $switch = $switches[$switch_id] ?? null;
                        if ($switch): ?>
                            <td>
                                <button type="submit" name="toggle_id" value="<?php echo $switch_id; ?>" 
                                        class="switch-toggle <?php echo $switch['status'] == 1 ? 'switch-on' : 'switch-off'; ?>">
                                    <?php echo htmlspecialchars($switch['name']); ?>
                                </button>
                            </td>
                        <?php else: ?>
                            <td></td>
                        <?php endif; ?>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
        <div class="master-controls">
            <button type="submit" name="master_off" class="master-off-button">Master Off</button>
            <button type="submit" name="prog_abort" class="prog-abort-button">Prog. Abort</button>
            <button type="submit" name="land_abort" class="land-abort-button">Land Abort</button>
            <button type="submit" name="command_module_release" class="command-module-release-button">Command Module Release</button>
        </div>
    </form>
</div>
    <?php
    if ($needs_redirect) {
        echo '<script>window.location.href = window.location.href;</script>';
        exit();
    }
    ?>
<style>
    .switches-container {
        padding: 20px;
        background-color: #2e2e2e;
        border-radius: 8px;
    }

    .switches-table {
        width: 100%;
        border-spacing: 15px;
    }

    .switch-toggle {
        width: 100%;
        height: 50px;
        font-size: 1rem;
        text-align: center;
        border-radius: 5px;
        border: none;
        cursor: pointer;
    }

    .switch-on {
        background-color: #00ff00;
        color: #000000;
    }

    .switch-off {
        background-color: #ff0000;
        color: #ffffff;
    }

    .master-controls {
        margin-top: 20px;
        text-align: center;
    }

    .master-off-button,
    .prog-abort-button,
    .land-abort-button,
    .command-module-release-button {
        padding: 10px 20px;
        font-size: 1rem;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        margin: 5px;
    }

    .master-off-button {
        background-color: #000000;
        color: #ffffff;
    }

    .prog-abort-button {
        background-color: #ff4500;
        color: #ffffff;
    }

    .land-abort-button {
        background-color: #008cff;
        color: #ffffff;
    }

    .command-module-release-button {
        background-color: #ffd700;
        color: #000000;
    }
</style>
