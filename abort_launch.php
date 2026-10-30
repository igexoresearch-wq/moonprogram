<?php
// This panel is included from pilot.php, which already owns the database
// connection.  Do not create a second connection or keep credentials here.

$simOn = 0;
$result = $conn->query("SELECT status FROM systems WHERE system = 'sim_on' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $simOn = (int)$row['status'];
}

$cmState = [
    'escape' => 0,
    'ABORT' => 0,
];

$result = $conn->query(
    "SELECT attribute, value
     FROM command_module
     WHERE attribute IN ('escape', 'ABORT')"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cmState[$row['attribute']] = (int)$row['value'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string)$_POST['action'];

    if ($action === 'launch' && $simOn === 0) {
        // Start real-time physics and request the existing COUNTDOWN START
        // guidance program.  The launch sequence itself remains in the
        // original CM guidance system.
        $conn->query("UPDATE systems SET status = 1 WHERE system = 'sim_on'");
        $conn->query("UPDATE CMguidance SET execute = 0");
        $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 24");
    }

    if ($action === 'abort' && $cmState['escape'] === 1) {
        // Request the LES abort. The physics worker owns physical separation
        // of the launch stack and the resulting propulsion state.
        $conn->query("UPDATE command_module SET value = 1 WHERE attribute = 'ABORT'");
        $conn->query("UPDATE command_module SET value = 100 WHERE attribute = 'thrust'");
        $conn->query("UPDATE CMswitches SET status = 1 WHERE id = 76");
    }

    echo '<script>window.location = window.location.pathname;</script>';
    exit;
}

$launch_disabled = ($simOn === 1);
$abort_disabled = ($cmState['escape'] !== 1 || $cmState['ABORT'] === 1);
?>

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
        background: linear-gradient(45deg, yellow 50%, black 50%);
        color: red;
        border: 3px solid black;
    }

    .abort-button {
        background: linear-gradient(45deg, red 50%, black 50%);
        color: white;
        border: 3px solid black;
    }

    .button-container {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 50px;
    }
</style>

<div class="button-container">
    <form method="POST">
        <input type="hidden" name="action" value="launch">
        <button
            type="submit"
            class="button launch-button"
            <?php echo $launch_disabled ? 'disabled' : ''; ?>
        >Launch</button>
    </form>

    <form method="POST">
        <input type="hidden" name="action" value="abort">
        <button
            type="submit"
            class="button abort-button"
            <?php echo $abort_disabled ? 'disabled' : ''; ?>
        >Abort</button>
    </form>
</div>
