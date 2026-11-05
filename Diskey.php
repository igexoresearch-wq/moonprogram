<?php

require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();
// Initialize a flag to check if redirection is needed
$needs_redirect = false;
// Handle button actions (POST request)
if (($_SERVER['REQUEST_METHOD']??'GET') === 'POST') {
    moonSimRequireSameOriginMutation();
    // Process form submission
    if (isset($_POST['noun'])) {
        $conn->query("UPDATE guidance SET noun = '1', verb = 'X' WHERE id = 11");
        $needs_redirect = true;
    } elseif (isset($_POST['verb'])) {
        $conn->query("UPDATE guidance SET noun = 'X', verb = '1' WHERE id = 11");
        $needs_redirect = true;
    } elseif (isset($_POST['key'])) {
        $key = (string)$_POST['key'];
        if (!preg_match('/^[0-9]$/D', $key)) { http_response_code(400); exit('Invalid DSKY key'); }
        $result = $conn->query("SELECT noun, verb FROM guidance WHERE id = 11");
        $row = $result->fetch_assoc();
        if ($row['noun'] === '1') {
            $conn->query("UPDATE guidance SET noun = CONCAT(CASE WHEN noun = 'X' THEN '' ELSE noun END, '{$key}') WHERE id = 13");
        } elseif ($row['verb'] === '1') {
            $conn->query("UPDATE guidance SET verb = CONCAT(CASE WHEN verb = 'X' THEN '' ELSE verb END, '{$key}') WHERE id = 13");
        }
        $needs_redirect = true;
    } elseif (isset($_POST['negative'])) {
        $result = $conn->query("SELECT noun, verb FROM guidance WHERE id = 11");
        $row = $result->fetch_assoc();
        if ($row['noun'] === '1') {
            $conn->query("UPDATE guidance SET noun = CONCAT('-', noun) WHERE id = 13 AND noun NOT LIKE '-%'");
        } elseif ($row['verb'] === '1') {
            $conn->query("UPDATE guidance SET verb = CONCAT('-', verb) WHERE id = 13 AND verb NOT LIKE '-%'");
        }
        $needs_redirect = true;
    } elseif (isset($_POST['clear'])) {
        $result = $conn->query("SELECT noun, verb FROM guidance WHERE id = 11");
        $row = $result->fetch_assoc();
        if ($row['noun'] === '1') {
            $conn->query("UPDATE guidance SET noun = 'X' WHERE id = 13");
        } elseif ($row['verb'] === '1') {
            $conn->query("UPDATE guidance SET verb = 'X' WHERE id = 13");
        }
        $needs_redirect = true;
    } elseif (isset($_POST['enter'])) {
        $result = $conn->query("SELECT noun, verb FROM guidance WHERE id = 13");
        $row = $result->fetch_assoc();
        $target_id = intval($row['noun']);
        $new_verb = (string)$row['verb'];
        if (!preg_match('/^[A-Z0-9-]{1,12}$/D', $new_verb)) { http_response_code(400); exit('Invalid DSKY command'); }
        if ($target_id < 1 || $target_id > 50) { http_response_code(400); exit('Invalid DSKY register'); }
        if ($target_id > 0) {
            $stmt = $conn->prepare('UPDATE guidance SET verb=? WHERE id=?');
            $stmt->bind_param('si', $new_verb, $target_id);
            $stmt->execute();
        }
        $conn->query("UPDATE guidance SET noun = 'X', verb = 'X' WHERE id = 11");
        $conn->query("UPDATE guidance SET noun = 'X', verb = 'X' WHERE id = 13");
        $needs_redirect = true;
    } elseif (isset($_POST['reset'])) {
        $conn->query("UPDATE guidance SET verb = 'X' WHERE id BETWEEN 1 AND 10");
        $conn->query("UPDATE guidance SET verb = 'O' WHERE id = 12");
        $conn->query("UPDATE guidance SET noun = 'X', verb = 'X' WHERE id = 11");
        $conn->query("UPDATE guidance SET noun = 'X', verb = 'X' WHERE id = 13");
        $needs_redirect = true;
    } elseif (isset($_POST['execute'])) {
        $conn->query("UPDATE guidance SET verb = '1' WHERE id = 12");
        $needs_redirect = true;
    }
}
?>



<?php  
 
// Fetch current values for ID 11 (to determine button state)
$result = $conn->query("SELECT noun, verb FROM guidance WHERE id = 11");
$row = $result->fetch_assoc();
$is_noun_active = $row['noun'] === '1';
$is_verb_active = $row['verb'] === '1';

// Fetch current values for noun and verb from the database (ID 13)
$result = $conn->query("SELECT noun, verb FROM guidance WHERE id = 13");
$row = $result->fetch_assoc();
$current_noun = $row['noun'] ?? 'X';
$current_verb = $row['verb'] ?? 'X';
?>

<div class="diskey-container">
    <!-- Display the Noun and Verb -->
    <div class="readout">
        <table>
            <tr>
                <td>
                    <div class="digital-display"><?php echo htmlspecialchars($current_noun); ?></div>
                    <span class="unit-label">Noun</span>
                </td>
                <td>
                    <div class="digital-display"><?php echo htmlspecialchars($current_verb); ?></div>
                    <span class="unit-label">Verb</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Display the Keypad and Controls -->
    <form method="POST">
        <div class="keypad-container">
            <!-- Number Keypad -->
            <div class="keypad">
                <?php foreach (range(1, 9) as $key): ?>
                    <button type="submit" name="key" value="<?php echo $key; ?>"><?php echo $key; ?></button>
                <?php endforeach; ?>
                <button type="submit" name="key" value="0">0</button>
                <button type="submit" name="negative">-</button>
            </div>

            <!-- Other Controls -->
            <div class="controls">
                <button type="submit" name="clear">Clear</button>
                <button type="submit" name="enter">Enter</button>
                <button type="submit" name="noun" class="<?php echo $is_noun_active ? 'depressed' : ''; ?>">Noun</button>
                <button type="submit" name="verb" class="<?php echo $is_verb_active ? 'depressed' : ''; ?>">Verb</button>
                <button type="submit" name="execute">Execute</button>
                <button type="submit" name="reset">Reset</button>
            </div>
        </div>
    </form>
</div>

<style>
    .diskey-container {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .readout {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 10px 0;
    }

    .digital-display {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px;
        border-radius: 5px;
        width: 7ch; /* Matches the width of the screens */
        text-align: right;
    }

    .unit-label {
        font-size: 1rem;
        font-weight: normal;
        color: #ffffff;
    }

    .keypad-container {
        display: flex;
    }

    .keypad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        width: calc(7ch * 3 + 20px); /* Matches the width of two digit screens */
    }

    .controls {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-left: 10px;
    }

    button {
        padding: 10px 20px;
        font-size: 1.2rem;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        background-color: #3a3a3a;
        color: #ffffff;
        font-family: Arial, sans-serif;
        box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    button:active {
        background-color: #555555;
        box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .depressed {
        background-color: #555555;
        box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
</style>
