<?php
// Fetch system statuses for IDs 1-10, including radar (ID 5)
$systems = [];
$master_alarm_triggered = false;
$sql = "SELECT id, system, status, last_state FROM systems WHERE id BETWEEN 1 AND 10";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $systems[] = $row;
        // Check if the current status differs from last_state
        if ($row['status'] != $row['last_state']) {
            $master_alarm_triggered = true;
        }
    }
}

// Handle Master Alarm Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_master_alarm'])) {
    // Update last_state to match current status for all systems
    $update_sql = "UPDATE systems SET last_state = status WHERE id BETWEEN 1 AND 10";
    $conn->query($update_sql);
    $master_alarm_triggered = false;
}
?>

<table class="alert-system-table">
    <tr>
        <?php 
        $count = 0;
        foreach ($systems as $system): 
            if ($system['id'] == 5) continue; // Skip radar (ID 5) in visual display
            if ($count > 0 && $count % 3 == 0) echo '</tr><tr>'; // Start a new row every 3 boxes
        ?>
            <td>
                <div class="alert-system-box alert-<?php echo $system['status'] == 1 ? 'green' : 'red'; ?>">
                    <span class="alert-system-label"><?php echo ucwords($system['system']); ?></span>
                </div>
            </td>
        <?php 
            $count++;
        endforeach; 
        ?>
    </tr>
</table>

<!-- Master Alarm -->
<form method="POST" class="master-alarm-container">
    <button type="submit" name="reset_master_alarm" class="master-alarm-button <?php echo $master_alarm_triggered ? 'yellow-box' : 'black-box'; ?>">
        Master Alarm
    </button>
</form>

<?php if ($master_alarm_triggered): ?>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const alarmSound = new Audio('mixkit-classic-alarm-995.wav');
        alarmSound.loop = true; // Enable looping
        alarmSound.play().catch(error => {
            console.error("Sound playback failed: ", error);
        });

        // Stop alarm sound when the reset button is clicked
        const resetButton = document.querySelector(".master-alarm-button");
        resetButton.addEventListener("click", () => {
            alarmSound.pause();
            alarmSound.currentTime = 0; // Reset the sound to the start
        });
    });
</script>
<?php endif; ?>

<style>
    .alert-system-table {
        width: 100%;
        border-collapse: collapse;
    }

    .alert-system-table td {
        padding: 10px;
        text-align: center;
        vertical-align: middle;
    }

    .alert-system-box {
        font-family: Arial, sans-serif;
        font-size: calc(1rem + 0.5vw);
        font-weight: bold;
        color: #888888;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        border: 3px solid #2e2e2e;
        background-color: #000; /* Default black */
        width: 80%;
    }

    .alert-green {
        background-color: #000000; /* Green for Operational */
        color: #000000;
    }

    .alert-red {
        background-color: #ffff00; /* Red for Failed */
        color: #ffffff;
    }

    .alert-system-label {
        display: block;
        color: #888888; /* Grey text for labels */
        font-size: 1rem; /* Ensure consistent text size */
    }

    .master-alarm-container {
        margin-top: 20px;
        text-align: center;
        width: 90%;
    }

    .master-alarm-button {
        font-family: Arial, sans-serif;
        font-size: 1.5rem;
        font-weight: bold;
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        width: 90%;
        text-align: center;
        cursor: pointer;
        background-color: #000; /* Default black */
        color: #fff; /* Default white text */
    }

    .master-alarm-button.yellow-box {
        background-color: #ffff00; /* Yellow for triggered alarm */
        color: #000; /* Black text for yellow button */
    }

    .master-alarm-button.black-box {
        background-color: #000; /* Black for no alarm */
        color: #fff; /* White text for black button */
    }
</style>
