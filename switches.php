<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$needs_redirect = false;

require_once __DIR__ . '/shared/docking_commands.php';

if (!$conn) {
    die("Database connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_id'])) {
        $toggle_id = (int)$_POST['toggle_id'];

        if ($toggle_id >= 1 && $toggle_id <= 10) {
            $result = $conn->query("SELECT status FROM switches WHERE id = $toggle_id");
            if ($result && $row = $result->fetch_assoc()) {
                $new_status = ((int)$row['status'] === 1) ? 0 : 1;

                $conn->query("UPDATE switches SET status = $new_status WHERE id = $toggle_id");
                $conn->query("UPDATE systems SET status = $new_status WHERE id = $toggle_id");

                if ($new_status === 1) {
                    $conn->query(
                        "UPDATE systems
                         SET probability = 10000
                         WHERE id = $toggle_id
                           AND probability < 1"
                    );
                }

                $needs_redirect = true;
            }
        }
    }

    if (isset($_POST['master_off'])) {
        $conn->query("UPDATE switches SET status = 0 WHERE id BETWEEN 1 AND 10");
        $conn->query("UPDATE systems SET status = 0 WHERE id BETWEEN 1 AND 10");
        $needs_redirect = true;
    }

    // Abort the older LM/root guidance entry without moving the spacecraft.
    if (isset($_POST['prog_abort'])) {
        $conn->query("UPDATE guidance SET verb = 'X' WHERE id BETWEEN 1 AND 10");
        $conn->query("UPDATE guidance SET verb = 'O' WHERE id = 12");
        $conn->query("UPDATE guidance SET noun = 'X', verb = 'X' WHERE id IN (11, 13)");
        $needs_redirect = true;
    }

    if (isset($_POST['land_abort'])) {
        /*
         * LM landing abort / ascent command.
         * The old code teleported both vehicles into an obsolete coordinate
         * frame and wrote to system IDs that no longer exist.  Preserve the
         * intended "get off the descent stage and go" behavior instead.
         */
        $result = $conn->query(
            "SELECT value
             FROM telemetry
             WHERE attribute = 'fuel_ascent'
             LIMIT 1"
        );
        $ascentFuel = ($result && $row = $result->fetch_assoc())
            ? (float)$row['value']
            : 0.0;

        $conn->query("UPDATE telemetry SET value = 0 WHERE attribute = 'descent'");
        $conn->query("UPDATE systems SET status = 0 WHERE system = 'landed'");
        $conn->query(
            "UPDATE EngineStatus
             SET status = 'OFF'
             WHERE engine_stage = 'Lunar Module'"
        );

        if ($ascentFuel > 0.0) {
            $conn->query("UPDATE telemetry SET value = 100 WHERE attribute = 'thrust'");
            $conn->query(
                "UPDATE EngineStatus
                 SET status = 'ON'
                 WHERE engine_stage = 'Lunar Module'
                   AND engine_name = 'Ascent Engine'
                   AND operational_status = 'OK'"
            );
        } else {
            $conn->query("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");
        }

        $needs_redirect = true;
    }

    if (isset($_POST['command_module_release'])) {
        moonSimQueueDockingCommand($conn, 'UNDOCK');
        $needs_redirect = true;
    }
}

$switches = [];
$result = $conn->query(
    "SELECT switches.id AS switch_id, switches.status, systems.system
     FROM switches
     JOIN systems ON switches.id = systems.id
     WHERE switches.id BETWEEN 1 AND 10"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $switches[(int)$row['switch_id']] = [
            'status' => (int)$row['status'],
            'name' => ucfirst((string)$row['system']),
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
                        if ($switch_id > 10) {
                            break;
                        }
                        $switch = $switches[$switch_id] ?? null;
                    ?>
                        <?php if ($switch): ?>
                            <td>
                                <button
                                    type="submit"
                                    name="toggle_id"
                                    value="<?php echo $switch_id; ?>"
                                    class="switch-toggle <?php echo $switch['status'] === 1 ? 'switch-on' : 'switch-off'; ?>"
                                ><?php echo htmlspecialchars($switch['name']); ?></button>
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
            <button
                type="submit"
                name="land_abort"
                class="land-abort-button"
                onclick="return confirm('Abort descent and command LM ascent stage?');"
            >Land Abort</button>
            <button
                type="submit"
                name="command_module_release"
                class="command-module-release-button"
            >CSM Dock Release</button>
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
