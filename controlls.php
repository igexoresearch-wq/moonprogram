<?php

require_once __DIR__ . '/shared/docking_commands.php';

$needs_redirect = false;

// Current LM RCS propellant.
$result = $conn->query("SELECT value FROM telemetry WHERE attribute = 'RCS'");
$fuel_level = ($result && $row = $result->fetch_assoc())
    ? (float)$row['value']
    : 0.0;

// System availability.
$systemStatus = [
    'RCS' => 0,
    'thrust' => 0,
    'pitch' => 0,
    'roll' => 0,
    'propulsion' => 0,
];

$result = $conn->query(
    "SELECT system, status
     FROM systems
     WHERE system IN ('RCS', 'thrust', 'pitch', 'roll', 'propulsion')"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $systemStatus[$row['system']] = (int)$row['status'];
    }
}

// Current LM control state.
$telemetry = [
    'pitch_rate' => 0.0,
    'roll_rate' => 0.0,
    'thrust' => 0.0,
    'descent' => 1,
];

$result = $conn->query(
    "SELECT attribute, value
     FROM telemetry
     WHERE attribute IN ('pitch_rate', 'roll_rate', 'thrust', 'descent')"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $telemetry[$row['attribute']] = (float)$row['value'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attitudeCommand =
        isset($_POST['increase_pitch'])
        || isset($_POST['decrease_pitch'])
        || isset($_POST['increase_roll'])
        || isset($_POST['decrease_roll']);

    // The legacy LM attitude controls are still discrete RCS pulses for now.
    // Only an actual RCS attitude command consumes RCS propellant; main-engine
    // throttle/staging must not depend on how much RCS fuel remains.
    if (
        $attitudeCommand
        && $fuel_level >= 1.0
        && $systemStatus['RCS'] === 1
    ) {
        $conn->query(
            "UPDATE telemetry
             SET value = GREATEST(0, value - 1)
             WHERE attribute = 'RCS'"
        );

        if (isset($_POST['increase_pitch']) && $systemStatus['pitch'] === 1) {
            $telemetry['pitch_rate'] += 0.5;
            $conn->query(
                "UPDATE telemetry
                 SET value = {$telemetry['pitch_rate']}
                 WHERE attribute = 'pitch_rate'"
            );
        }

        if (isset($_POST['decrease_pitch']) && $systemStatus['pitch'] === 1) {
            $telemetry['pitch_rate'] -= 0.5;
            $conn->query(
                "UPDATE telemetry
                 SET value = {$telemetry['pitch_rate']}
                 WHERE attribute = 'pitch_rate'"
            );
        }

        if (isset($_POST['increase_roll']) && $systemStatus['roll'] === 1) {
            $telemetry['roll_rate'] += 0.5;
            $conn->query(
                "UPDATE telemetry
                 SET value = {$telemetry['roll_rate']}
                 WHERE attribute = 'roll_rate'"
            );
        }

        if (isset($_POST['decrease_roll']) && $systemStatus['roll'] === 1) {
            $telemetry['roll_rate'] -= 0.5;
            $conn->query(
                "UPDATE telemetry
                 SET value = {$telemetry['roll_rate']}
                 WHERE attribute = 'roll_rate'"
            );
        }

        $needs_redirect = true;
    }

    $mainEngineAvailable =
        $systemStatus['thrust'] === 1
        && $systemStatus['propulsion'] === 1;

    if (isset($_POST['increase_thrust']) && $mainEngineAvailable) {
        // 100 now means 100% commanded throttle.  The old 60% cap was paired
        // with a second hidden reduction in the DPS constant and made the LM
        // incapable of producing the intended full-throttle command.
        $telemetry['thrust'] = min(100.0, $telemetry['thrust'] + 1.0);
        $conn->query(
            "UPDATE telemetry
             SET value = {$telemetry['thrust']}
             WHERE attribute = 'thrust'"
        );
        $needs_redirect = true;
    }

    if (isset($_POST['decrease_thrust']) && $mainEngineAvailable) {
        $telemetry['thrust'] = max(0.0, $telemetry['thrust'] - 1.0);
        $conn->query(
            "UPDATE telemetry
             SET value = {$telemetry['thrust']}
             WHERE attribute = 'thrust'"
        );
        $needs_redirect = true;
    }

    if (isset($_POST['shut_off_engine'])) {
        $telemetry['thrust'] = 0.0;
        $conn->query("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");
        $conn->query(
            "UPDATE EngineStatus
             SET status = 'OFF'
             WHERE engine_stage = 'Lunar Module'"
        );
        $needs_redirect = true;
    }

    if (isset($_POST['stage_ascent']) && (int)$telemetry['descent'] === 1) {
        moonSimQueueDockingCommand($conn, 'LM_ASCENT_STAGE');
        $needs_redirect = true;
    }

    if ($needs_redirect) {
        echo '<script>window.location.href = window.location.href;</script>';
        exit();
    }
}
?>

<div class="controls-container">
    <div class="lm-engine-mode">
        MAIN ENGINE:
        <strong><?php echo ((int)$telemetry['descent'] === 1) ? 'DPS / DESCENT' : 'APS / ASCENT'; ?></strong>
    </div>

    <table>
        <tr>
            <td></td>
            <td><button class="control-button" name="increase_pitch" form="control-form">Pitch ↑</button></td>
            <td></td>
        </tr>
        <tr>
            <td><button class="control-button" name="increase_roll" form="control-form">Roll ←</button></td>
            <td>
                <table>
                    <tr>
                        <td>
                            <div class="readout">
                                <span class="unit-label">Pitch Rate</span>
                                <div class="digital-display small"><?php echo str_pad(number_format($telemetry['pitch_rate'], 1), 4, '0', STR_PAD_LEFT); ?></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="readout">
                                <span class="unit-label">Roll Rate</span>
                                <div class="digital-display small"><?php echo str_pad(number_format($telemetry['roll_rate'], 1), 4, '0', STR_PAD_LEFT); ?></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td><button class="control-button" name="decrease_roll" form="control-form">Roll →</button></td>
        </tr>
        <tr>
            <td></td>
            <td><button class="control-button" name="decrease_pitch" form="control-form">Pitch ↓</button></td>
            <td></td>
        </tr>
    </table>

    <div class="thrust-controls">
        <button class="control-button" name="increase_thrust" form="control-form">Thrust ↑</button>
        <div class="digital-display"><?php echo str_pad((int)$telemetry['thrust'], 3, '0', STR_PAD_LEFT); ?>%</div>
        <button class="control-button" name="decrease_thrust" form="control-form">Thrust ↓</button>
    </div>

    <div class="engine-controls">
        <button class="control-button" name="shut_off_engine" form="control-form">Engine Off</button>
        <?php if ((int)$telemetry['descent'] === 1): ?>
            <button
                class="control-button stage-button"
                name="stage_ascent"
                form="control-form"
                onclick="return confirm('Separate the LM descent stage?');"
            >Descent Stage Sep</button>
        <?php endif; ?>
    </div>
</div>

<form method="POST" id="control-form"></form>

<style>
    .controls-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: center;
        align-items: center;
        background-color: #2e2e2e;
        padding: 20px;
    }

    .lm-engine-mode {
        width: 100%;
        text-align: center;
        font-family: Arial, sans-serif;
        letter-spacing: 0.04em;
    }

    .pitch-controls,
    .roll-controls {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .thrust-controls {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .engine-controls {
        margin-top: 10px;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .readout-container {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-top: 20px;
    }

    .readout {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .control-button {
        font-family: Arial, sans-serif;
        font-size: 1.2rem;
        padding: 10px 20px;
        border-radius: 8px;
        border: 1px solid #555;
        background-color: #3a3a3a;
        color: #ffffff;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        text-transform: uppercase;
    }

    .control-button:hover {
        background-color: #555555;
    }

    .control-button:active {
        background-color: #777777;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    .stage-button {
        border-color: #b38a00;
        color: #ffe878;
    }

    .digital-display {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px 10px;
        border-radius: 5px;
        text-align: center;
    }

    .digital-display.small {
        font-size: 1.5rem;
        width: 6ch;
    }

    .unit-label {
        font-size: 1rem;
        color: #ffffff;
    }
</style>
