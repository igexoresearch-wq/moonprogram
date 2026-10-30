<?php
// Fetch the stage statuses from CMguidance
$stage_query = $conn->query("SELECT attribute, value FROM command_module WHERE attribute IN ('stage1', 'stage2', 'stage3')");
$stages = [];
while ($row = $stage_query->fetch_assoc()) {
    $stages[$row['attribute']] = intval($row['value']);
}

// Default ON statuses for engines
$ON1 = 1;
$ON2 = 1;
$ON3 = 1;
$ON4 = 1;
$ON5 = 1;

// Override lights to "OFF" (all 1) if all stages are 0
if ($stages['stage1'] === 0 && $stages['stage2'] === 0 && $stages['stage3'] === 0) {
    $ON1 = 1;
    $ON2 = 1;
    $ON3 = 1;
    $ON4 = 1;
    $ON5 = 1;
} else {
    // Stage 1: Check all F-1 Engines
    if ($stages['stage1'] == 1 && $stages['stage2'] == 1 && $stages['stage3'] == 1) {
        $engine_query = $conn->query("SELECT engine_name, status FROM EngineStatus WHERE engine_stage = 'First Stage'");
        while ($engine = $engine_query->fetch_assoc()) {
            if ($engine['engine_name'] === 'F-1 Engine 1' && $engine['status'] !== 'ON') $ON1 = 0;
            if ($engine['engine_name'] === 'F-1 Engine 2' && $engine['status'] !== 'ON') $ON2 = 0;
            if ($engine['engine_name'] === 'F-1 Engine 3' && $engine['status'] !== 'ON') $ON3 = 0;
            if ($engine['engine_name'] === 'F-1 Engine 4' && $engine['status'] !== 'ON') $ON4 = 0;
            if ($engine['engine_name'] === 'F-1 Engine 5' && $engine['status'] !== 'ON') $ON5 = 0;
        }
    }

    // Stage 2: Check all J-2 Engines
    if ($stages['stage1'] == 0 && $stages['stage2'] == 1 && $stages['stage3'] == 1) {
        $engine_query = $conn->query("SELECT engine_name, status FROM EngineStatus WHERE engine_stage = 'Second Stage'");
        while ($engine = $engine_query->fetch_assoc()) {
            if ($engine['engine_name'] === 'J-2 Engine 1' && $engine['status'] !== 'ON') $ON1 = 0;
            if ($engine['engine_name'] === 'J-2 Engine 2' && $engine['status'] !== 'ON') $ON2 = 0;
            if ($engine['engine_name'] === 'J-2 Engine 3' && $engine['status'] !== 'ON') $ON3 = 0;
            if ($engine['engine_name'] === 'J-2 Engine 4' && $engine['status'] !== 'ON') $ON4 = 0;
            if ($engine['engine_name'] === 'J-2 Engine 5' && $engine['status'] !== 'ON') $ON5 = 0;
        }
    }

    // Stage 3: Check the single S-IVB J-2 engine
    if ($stages['stage1'] == 0 && $stages['stage2'] == 0 && $stages['stage3'] == 1) {
        $engine_query = $conn->query("SELECT engine_name, status FROM EngineStatus WHERE engine_stage = 'Third Stage'");
        while ($engine = $engine_query->fetch_assoc()) {
            if ($engine['engine_name'] === 'J-2 Engine' && $engine['status'] !== 'ON') {
                $ON5 = 0;
            }
        }

        // Ensure other ON statuses are always 1 for Stage 3
        $ON1 = 1;
        $ON2 = 1;
        $ON3 = 1;
        $ON4 = 1;
    }
}

// Determine light classes based on ON status
$light_class_1 = $ON1 === 0 ? 'contact-light-on' : 'contact-light-off';
$light_class_2 = $ON2 === 0 ? 'contact-light-on' : 'contact-light-off';
$light_class_3 = $ON3 === 0 ? 'contact-light-on' : 'contact-light-off';
$light_class_4 = $ON4 === 0 ? 'contact-light-on' : 'contact-light-off';
$light_class_5 = $ON5 === 0 ? 'contact-light-on' : 'contact-light-off';
?>

<table class="engine-status-table">
    <tr>
        <td class="engine-light-cell">
            <div class="engine-light <?php echo $light_class_1; ?>">
                <div class="engine-number">1</div>
            </div>
        </td>
        <td></td>
        <td class="engine-light-cell">
            <div class="engine-light <?php echo $light_class_2; ?>">
                <div class="engine-number">2</div>
            </div>
        </td>
    </tr>
    <tr>
        <td></td>
        <td class="engine-light-cell">
            <div class="engine-light <?php echo $light_class_5; ?>">
                <div class="engine-number">5</div>
            </div>
        </td>
        <td></td>
    </tr>
    <tr>
        <td class="engine-light-cell">
            <div class="engine-light <?php echo $light_class_3; ?>">
                <div class="engine-number">3</div>
            </div>
        </td>
        <td></td>
        <td class="engine-light-cell">
            <div class="engine-light <?php echo $light_class_4; ?>">
                <div class="engine-number">4</div>
            </div>
        </td>
    </tr>
</table>

<h2 class="engine-status-label">ENGINE STATUS</h2>



<style>
    /* Table Layout */
    .engine-status-table {
        width: 100px;
        border-spacing: 1px;
        text-align: center;
    }

    .engine-light-cell {
        width: 33%;
    }

    /* Light Styles */
    .engine-light {
        position: relative;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 2px solid #555;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .contact-light-on {
        background-color: #FFFF00; /* Yellow */
    }

    .contact-light-off {
        background-color: lightgrey; /* Black */
    }

    .engine-number {
        font-family: Arial, sans-serif;
        font-size: 1.2rem;
        font-weight: bold;
        color: #000000;
    }

    /* Label Styles */
    .engine-status-label {
        font-family: Arial, sans-serif;
        font-size: 1.5rem;
        font-weight: bold;
        color: #FFFFFF;
        text-align: center;
        margin-top: 20px;
    }
</style>
