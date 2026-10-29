<?php
// Initialize variables
$current_noun = 0;
$current_verb = 0;
$current_value = 0;

// Verb-to-Column Mapping
$verb_to_column = [
    1 => 'pitch', 2 => 'roll', 3 => 'yaw', 4 => 'pitch_rate', 5 => 'roll_rate',
    6 => 'yaw_rate', 7 => 'thrust', 8 => 'seconds', 9 => 'altitude', 10 => 'delta_V',
    11 => 'delta_x', 12 => 'orbit_angle', 13 => 'velocity'
];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $Guidance_Computer_SWITCH == 1) {  $needs_redirect = true;
    if (isset($_POST['noun'])) {
        // Activate Noun entry mode
        $conn->query("UPDATE CMguidance SET hold = 1 WHERE id = 2");
    } elseif (isset($_POST['verb'])) {
        // Activate Verb entry mode
        $conn->query("UPDATE CMguidance SET hold = 2 WHERE id = 2");
    } elseif (isset($_POST['key'])) {
        // Handle digit input for Noun, Verb, or Value
        $active_state = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 2")->fetch_assoc()['hold']);
        $key = intval($_POST['key']);

        if ($active_state == 1) {
            // Append digit to Noun (id = 4)
            $current_noun = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 4")->fetch_assoc()['hold']);
            $new_noun = ($current_noun * 10) + $key;
            $conn->query("UPDATE CMguidance SET hold = $new_noun WHERE id = 4");
        } elseif ($active_state == 2) {
            // Append digit to Verb (id = 5)
            $current_verb = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 5")->fetch_assoc()['hold']);
            $new_verb = ($current_verb * 10) + $key;
            $conn->query("UPDATE CMguidance SET hold = $new_verb WHERE id = 5");
        } elseif ($active_state == 3) {
            // Append digit to Value (id = 6)
            $current_value = $conn->query("SELECT hold FROM CMguidance WHERE id = 6")->fetch_assoc()['hold'];
            $new_value = $current_value . $key;
            $conn->query("UPDATE CMguidance SET hold = $new_value WHERE id = 6");
        }
    } elseif (isset($_POST['negative'])) {
        // Toggle negative sign for Value (id = 6)
        $active_state = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 2")->fetch_assoc()['hold']);
        if ($active_state == 3) {
            $current_value = $conn->query("SELECT hold FROM CMguidance WHERE id = 6")->fetch_assoc()['hold'];
            $new_value = is_numeric($current_value) ? -1 * floatval($current_value) : -0;
            $conn->query("UPDATE CMguidance SET hold = $new_value WHERE id = 6");
        }
    } elseif (isset($_POST['decimal'])) {
        // Append decimal point to Value (id = 6)
        $active_state = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 2")->fetch_assoc()['hold']);
        if ($active_state == 3) {
            $current_value = $conn->query("SELECT hold FROM CMguidance WHERE id = 6")->fetch_assoc()['hold'];
            if (strpos($current_value, '.') === false) {
                $new_value = $current_value . '.';
                $conn->query("UPDATE CMguidance SET hold = '$new_value' WHERE id = 6");
            }
        }
    } elseif (isset($_POST['clear'])) {
        // Clear Noun, Verb, or Value
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 4"); // Clear Noun
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 5"); // Clear Verb
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 6"); // Clear Value
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 2"); // Reset active state
    } elseif (isset($_POST['enter'])) {
        // Process entry based on the active state
        $active_state = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 2")->fetch_assoc()['hold']);

        if ($active_state == 1) {
            // Save Noun
            $current_noun = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 4")->fetch_assoc()['hold']);
            if ($current_noun == 2) {
                // Transition to Verb Entry Mode
                $conn->query("UPDATE CMguidance SET hold = 2 WHERE id = 2");
            } else {
                $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 2");
            }
        } elseif ($active_state == 2) { 
            $current_noun = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 4")->fetch_assoc()['hold']);
            // Save Verb
            $current_verb = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 5")->fetch_assoc()['hold']);
            if ($current_noun == 2 && isset($verb_to_column[$current_verb])) {
                // Transition to Value Entry Mode
                $conn->query("UPDATE CMguidance SET hold = 3 WHERE id = 2");
            } else {
                $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 2");
            }
        } elseif ($active_state == 3) { 
            $current_noun = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 4")->fetch_assoc()['hold']);
            // Save Value to the respective column in row id = 2
            $current_verb = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 5")->fetch_assoc()['hold']);
            $current_value = floatval($conn->query("SELECT hold FROM CMguidance WHERE id = 6")->fetch_assoc()['hold']);
            if ($current_noun == 2 && isset($verb_to_column[$current_verb])) {
                $column = $verb_to_column[$current_verb];
                $conn->query("UPDATE CMguidance SET $column = $current_value WHERE id = 2");
            }
            // Clear Verb and Value, but keep Noun active for additional input
            $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 5");
            $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 6");
            $conn->query("UPDATE CMguidance SET hold = 2 WHERE id = 2"); // Back to Verb Entry Mode
        }
    } elseif (isset($_POST['execute'])) {
        // Execute action
        $current_noun = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 4")->fetch_assoc()['hold']);
        if ($current_noun == 2) {
             $conn->query("UPDATE CMguidance SET execute = 0");
            $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 2");
        } elseif ($current_noun > 0) {
             $conn->query("UPDATE CMguidance SET execute = 0");
             if ($current_noun != 3) {
            $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = $current_noun");}
            $current_verb = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 5")->fetch_assoc()['hold']);
            $conn->query("UPDATE CMguidance SET hold = $current_verb WHERE id = $current_noun");
        }
        // Clear all states
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 4");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 5");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 6");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 2");
    } elseif (isset($_POST['terminate'])) {
        // Terminate all executions
        $conn->query("UPDATE CMguidance SET execute = 0");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 2"); // Reset state
    } elseif (isset($_POST['reset'])) {
        // Reset all values and states
        $conn->query("UPDATE CMguidance SET " . implode(" = NULL, ", $verb_to_column) . " = NULL WHERE id = 2");
        $conn->query("UPDATE CMguidance SET " . implode(" = NULL, ", $verb_to_column) . " = NULL WHERE id = 3");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 4");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 5");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 6");
        $conn->query("UPDATE CMguidance SET hold = 0 WHERE id = 2");
    }
}

// Fetch current values for Noun, Verb, and Value
$current_noun = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 4")->fetch_assoc()['hold'] ?? 0);
$current_verb = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 5")->fetch_assoc()['hold'] ?? 0);
$current_value = $conn->query("SELECT hold FROM CMguidance WHERE id = 6")->fetch_assoc()['hold'] ?? 0;

// Placeholder for Value
$value_display = ($current_value === 0 && intval($conn->query("SELECT hold FROM CMguidance WHERE id = 2")->fetch_assoc()['hold']) == 3) ? "ENTER" : $current_value;

// Button states
$active_state = intval($conn->query("SELECT hold FROM CMguidance WHERE id = 2")->fetch_assoc()['hold']);
$is_noun_active = $active_state == 1;
$is_verb_active = $active_state == 2;
$is_value_active = $active_state == 3;

// Fetch current values for ID 2
$result = $conn->query("SELECT * FROM CMguidance WHERE id = 2");
$row = $result ? $result->fetch_assoc() : [];

// Initialize displays with columns
$displays = [];
foreach ($verb_to_column as $column) {
    $displays[$column] = $row[$column] ?? 0; // Default to 0 if column is not set
}
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .diskey-container { display: flex; flex-direction: column; align-items: center; }
        .readout { display: flex; flex-direction: column; align-items: center; margin: 10px 0; }
        .digital-display, .digital-display2, .digital-display4, .digital-display44  { font-family: 'DigitTech', monospace; font-size: 2rem; font-weight: bold; color: #FFFF00; background-color: #000000; padding: 5px; border-radius: 5px; text-align: right; }
        .digital-display2 { width: 3ch; }
        .digital-display { width: 8ch; }
          .digital-display4 { width: 8ch; }
          .digital-display44 { width: 23ch; }
        .unit-label { font-size: 1rem; font-weight: normal; color: #ffffff; }
        .keypad-container { display: flex; }
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; width: calc(7ch * 3 + 20px); }
        .controls { display: flex; flex-direction: column; gap: 10px; margin-left: 10px; }
        button { padding: 10px 20px; font-size: 1.2rem; border-radius: 5px; border: none; cursor: pointer; background-color: #3a3a3a; color: #ffffff; font-family: Arial, sans-serif; box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); }
        button:active, .depressed { background-color: #555555; box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateDisplay() {
    $.ajax({
        url: "get_guidance_data.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            $("#noun-display").text(data.noun + "|");
            $("#verb-display").text(data.verb + "|");
            $("#value-display").text(data.value);
            $("#nonmonic-display").text(data.nonmonic);
            $("#data-value").text(data.data_value);
        }
    });
}

// Auto-update every 1 second
setInterval(updateDisplay, 1000);
</script>

<body>
    <div class="diskey-container">
                <div class="readout">
<table>
    <tbody>

   
                <tr>
       
            <td>
                <div class="digital-display44">
                    <div><table>
<tr><td>NOUN</td><td>PROG</td></tr>
<tr><td class="digital-display44" id="noun-display"><?php echo htmlspecialchars($current_noun ?? "0"); ?>|</td>
<td class="digital-display44" id="nonmonic-display"><?php echo htmlspecialchars($nonmonic_display ?? "0000000"); ?></td></tr>

<tr><td>VERB</td><td>DATA</td></tr>
<tr><td class="digital-display44" id="verb-display"><?php echo htmlspecialchars($current_verb ?? "0"); ?>|</td>
<td class="digital-display44" id="value-display"><?php echo htmlspecialchars($value_id2 ?? "0000000"); ?></td></tr>

<tr><td>ENTER DATA</td><td>HOLD DATA</td></tr>
<tr><td class="digital-display44" id="value-display"><?php echo htmlspecialchars($value_display ?? "0000000"); ?>|</td>
<td class="digital-display44" id="data-value"><?php echo htmlspecialchars($delta_V_value ?? "0000000"); ?></td></tr>

                    </table></div>

                </div>
           
   
        
        
                </tr>
       
   
       <tr>
            <?php include 'compstate.php'; ?></tr>
       
    </tbody>
</table>

          
            
            
            
            
        </div>
        <form method="POST">
    <div class="keypad-container">
        <div class="keypad">
            <?php foreach (range(1, 9) as $key): ?>
                <button type="submit" name="key" value="<?php echo $key; ?>" id="key-<?php echo $key; ?>"><?php echo $key; ?></button>
            <?php endforeach; ?>
            <button type="submit" name="key" value="0" id="key-0">0</button>
            <button type="submit" name="negative" id="negative">-</button>
            <button type="submit" name="decimal" id="decimal">.</button>
        </div>
        <div class="controls">
            <button type="submit" name="clear" id="clear">Clear</button>
            <button type="submit" name="enter" id="enter">Enter</button>
            <button type="submit" name="noun" id="noun" class="<?php echo $is_noun_active ? 'depressed' : ''; ?>">Noun</button>
            <button type="submit" name="verb" id="verb" class="<?php echo $is_verb_active ? 'depressed' : ''; ?>">Verb</button>
            <button type="submit" name="execute" id="execute">Execute</button>
            <button type="submit" name="terminate" id="terminate">Terminate</button>
            <button type="submit" name="reset" id="reset">Reset</button>
        </div>
    </div>
</form>

<script>
    // Map keys to button IDs
    const keyMappings = {
        '1': 'key-1',
        '2': 'key-2',
        '3': 'key-3',
        '4': 'key-4',
        '5': 'key-5',
        '6': 'key-6',
        '7': 'key-7',
        '8': 'key-8',
        '9': 'key-9',
        '0': 'key-0',
        'Enter': 'enter',
        '*': 'noun',
        '-': 'verb',
        '+': 'execute',
        '=': 'terminate',
        'Backspace': 'clear'
    };

    // Add keydown event listener
    document.addEventListener('keydown', (event) => {
        const buttonId = keyMappings[event.key]; // Map the key to a button ID
        if (buttonId) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.click(); // Simulate button click
            }
        }
    });
</script>

<?php include 'guide.php'; ?>  
    </div>
</body>
</html>