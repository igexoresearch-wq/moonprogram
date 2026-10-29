<?php


// Query for fuel_level
$result = $conn->query("SELECT value FROM telemetry WHERE attribute = 'RCS'");
if ($result && $row = $result->fetch_assoc()) {
    $fuel_level = $row['value'];
} else {
    die("Error retrieving fuel level: " . $conn->error);
}

// Query for rcsStatus
$result = $conn->query("SELECT status FROM systems WHERE system = 'RCS'");
if ($result && $row = $result->fetch_assoc()) {
    $rcsStatus = $row['status'];
} else {
    die("Error retrieving RCS status: " . $conn->error);
}

// Fetch current telemetry values
$telemetry = [];
$attributes = ['pitch_rate', 'roll_rate', 'thrust'];
foreach ($attributes as $attribute) {
    $result = $conn->query("SELECT value FROM telemetry WHERE attribute = '$attribute'");
    $telemetry[$attribute] = ($result && $result->num_rows > 0) ? floatval($result->fetch_assoc()['value']) : 0;
}

// Fetch system statuses
$systems = [];
$result = $conn->query("SELECT system, status FROM systems WHERE system IN ('thrust', 'pitch', 'roll')");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $systems[$row['system']] = intval($row['status']);
    }
}

// Handle button actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fuel_level >= 1 && $rcsStatus == 1) {
        // Deduct 1 unit of RCS fuel
        $stmt = $conn->prepare("UPDATE telemetry SET value = value - 1 WHERE attribute = 'RCS'");
        $stmt->execute();
    if (isset($_POST['increase_pitch']) && $systems['pitch'] === 1) {
        $telemetry['pitch_rate'] += 0.5;
        $conn->query("UPDATE telemetry SET value = {$telemetry['pitch_rate']} WHERE attribute = 'pitch_rate'");
        $needs_redirect = true;
    }

    if (isset($_POST['decrease_pitch']) && $systems['pitch'] === 1) {
        $telemetry['pitch_rate'] -= 0.5;
        $conn->query("UPDATE telemetry SET value = {$telemetry['pitch_rate']} WHERE attribute = 'pitch_rate'");
        $needs_redirect = true;
    }

    if (isset($_POST['increase_roll']) && $systems['roll'] === 1) {
        $telemetry['roll_rate'] += 0.5;
        $conn->query("UPDATE telemetry SET value = {$telemetry['roll_rate']} WHERE attribute = 'roll_rate'");
        $needs_redirect = true;
    }

    if (isset($_POST['decrease_roll']) && $systems['roll'] === 1) {
        $telemetry['roll_rate'] -= 0.5;
        $conn->query("UPDATE telemetry SET value = {$telemetry['roll_rate']} WHERE attribute = 'roll_rate'");
        $needs_redirect = true;
    }

    if (isset($_POST['increase_thrust']) && $systems['thrust'] === 1) {
        $telemetry['thrust'] = min(60, $telemetry['thrust'] + 1);
        $conn->query("UPDATE telemetry SET value = {$telemetry['thrust']} WHERE attribute = 'thrust'");
        $needs_redirect = true;
    }

    if (isset($_POST['decrease_thrust']) && $systems['thrust'] === 1) {
        $telemetry['thrust'] = max(0, $telemetry['thrust'] - 1);
        $conn->query("UPDATE telemetry SET value = {$telemetry['thrust']} WHERE attribute = 'thrust'");
        $needs_redirect = true;
    }

    if (isset($_POST['shut_off_engine'])) {
        $telemetry['thrust'] = 0;
        $conn->query("UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'");
        $needs_redirect = true;
    }

    if ($needs_redirect) {
        echo '<script>window.location.href = window.location.href;</script>';
        exit();
    }
}
?>

<div class="controls-container">
    <!-- Pitch Controls -->
    
    <table><tr><td></td><td><button class="control-button" name="increase_pitch" form="control-form">Pitch ↑</button></td><td></td></tr>
    <tr><td><button class="control-button" name="increase_roll" form="control-form">Roll ←</button></td><td>
        <table><tr><td><div class="readout">
            <span class="unit-label">Pitch Rate</span>
            <div class="digital-display small"><?php echo str_pad(number_format($telemetry['pitch_rate'], 1), 4, '0', STR_PAD_LEFT); ?></div>
        </div></td></tr><tr><td><div class="readout">
            <span class="unit-label">Roll Rate</span>
            <div class="digital-display small"><?php echo str_pad(number_format($telemetry['roll_rate'], 1), 4, '0', STR_PAD_LEFT); ?></div>
        </div></td></tr></table>
        
    </div></td><td>   <button class="control-button" name="decrease_roll" form="control-form">Roll →</button></td></tr>
    <tr><td></td><td><button class="control-button" name="decrease_pitch" form="control-form">Pitch ↓</button></td><td></td></tr></table>
 

    <!-- Thrust Controls -->
    <div class="thrust-controls">
        <button class="control-button" name="increase_thrust" form="control-form">Thrust ↑</button>
        <div class="digital-display"><?php echo str_pad((int)$telemetry['thrust'], 2, '0', STR_PAD_LEFT); ?></div>
        <button class="control-button" name="decrease_thrust" form="control-form">Thrust ↓</button>
    </div>

 

    <!-- Engine Shutoff -->
    <div class="engine-controls">
        <button class="control-button" name="shut_off_engine" form="control-form">Engine Off</button>
    </div>

    <!-- Readouts -->
    
</div>

<!-- Control Form -->
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
