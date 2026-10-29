<?php
// Fetch stage statuses from command_module
$stage_query = $conn->query("SELECT attribute, value FROM command_module WHERE attribute IN ('stage1', 'stage2', 'stage3')");
$stages = [];
while ($row = $stage_query->fetch_assoc()) {
    $stages[$row['attribute']] = intval($row['value']);
}

// Determine the current stage
$current_stage = null;
if ($stages['stage1'] === 1) {
    $current_stage = 'First Stage';
} elseif ($stages['stage2'] === 1) {
    $current_stage = 'Second Stage';
} elseif ($stages['stage3'] === 1) {
    $current_stage = 'Third Stage';
}

// Handle button actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shutdown'])) {
    $engine_number = intval($_POST['shutdown']); // Button value corresponds to engine number

    // Perform shutdown for the current stage and engine
    if ($current_stage) {
        $conn->query("UPDATE EngineStatus SET status = 'OFF' WHERE engine_stage = '$current_stage' AND engine_name LIKE '%Engine $engine_number%'");

        // If it's the SPS engine for Stage 3, ensure only Engine 5 is affected
        if ($current_stage === 'Third Stage') {
            $conn->query("UPDATE EngineStatus SET status = 'OFF' WHERE engine_stage = 'Third Stage' AND engine_name = 'SPS Engine'");
        }
    }
}
?>

<!-- Emergency Shutdown Buttons in 3x3 Cluster -->
<table class="shutdown-table">
    <tr>
        <td class="shutdown-button-cell">
            <form method="POST">
                <button type="submit" name="shutdown" value="1" class="shutdown-button">Engine 1</button>
            </form>
        </td>
        <td></td>
        <td class="shutdown-button-cell">
            <form method="POST">
                <button type="submit" name="shutdown" value="2" class="shutdown-button">Engine 2</button>
            </form>
        </td>
    </tr>
    <tr>
        <td></td>
        <td class="shutdown-button-cell">
            <form method="POST">
                <button type="submit" name="shutdown" value="5" class="shutdown-button">Engine 5</button>
            </form>
        </td>
        <td></td>
    </tr>
    <tr>
        <td class="shutdown-button-cell">
            <form method="POST">
                <button type="submit" name="shutdown" value="3" class="shutdown-button">Engine 3</button>
            </form>
        </td>
        <td></td>
        <td class="shutdown-button-cell">
            <form method="POST">
                <button type="submit" name="shutdown" value="4" class="shutdown-button">Engine 4</button>
            </form>
        </td>
    </tr>
</table>
<h2 class="shutdown-label">EMERGENCY SHUTDOWN</h2>

<style>
    /* Table Layout */
    .shutdown-table {
        width: 100%;
        border-spacing: 1px;
        text-align: center;
    }

    .shutdown-button-cell {
        width: 33%;
    }

    /* Button Styles */
    .shutdown-button {
        width: 100%;
        height: 60px;
        background: linear-gradient(to bottom, #d3d3d3, #a9a9a9);
        border: 2px solid #555;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        font-family: Arial, sans-serif;
        font-size: 1rem;
        font-weight: bold;
        color: #000;
        text-align: center;
        cursor: pointer;
        outline: none;
    }

    .shutdown-button:active {
        background: linear-gradient(to bottom, #a9a9a9, #d3d3d3);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    /* Label Styles */
    .shutdown-label {
        font-family: Arial, sans-serif;
        font-size: 1.5rem;
        font-weight: bold;
        color: #FFFFFF;
        text-align: center;
        margin-top: 20px;
    }
</style>
