<?php

// Default values for descent rate and alert classes
$descent_rate = 0;
$descent_alert_class = "alt-black-box";

// Fetch radar status from the systems table
$radar_status = 0; // Default to 0
$result = $conn->query("SELECT status FROM systems WHERE system = 'radar' LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $radar_status = intval($row['status']);
}

// Determine radar alert class
$radar_alert_class = $radar_status == 1 ? "alt-green-box" : "alt-red-box";

// Fetch altitude value
$altitude = 0; // Default altitude
$result = $conn->query("SELECT value FROM telemetry WHERE attribute = 'altitude' LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $altitude = floatval($row['value']);
}
// Default values for descent rate and alert classes
$descent_rate = 0;
$descent_alert_class = "alt-black-box";
$radar_alert_class = $radar_status == 1 ? "alt-green-box" : "alt-red-box";

// Fetch current position and deltas from telemetry table
$current_position = ['x' => 0, 'y' => 0, 'z' => 0];
$deltas = ['delta_x' => 0, 'delta_y' => 0, 'delta_z' => 0];

$sql = "SELECT attribute, value FROM telemetry WHERE attribute IN ('x', 'y', 'z', 'delta_x', 'delta_y', 'delta_z')";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['attribute'], $current_position)) {
            $current_position[$row['attribute']] = $row['value'];
        }
        if (array_key_exists($row['attribute'], $deltas)) {
            $deltas[$row['attribute']] = $row['value'];
        }
    }
}

// Calculate descent rate
$previous_origin_distance = sqrt(
    $current_position['x'] ** 2 +
    $current_position['y'] ** 2 +
    $current_position['z'] ** 2
);

$current_origin_distance = sqrt(
    ($current_position['x'] + $deltas['delta_x']) ** 2 +
    ($current_position['y'] + $deltas['delta_y']) ** 2 +
    ($current_position['z'] + $deltas['delta_z']) ** 2
);

$descent_rate = $previous_origin_distance - $current_origin_distance;

// Update alert classes based on altitude and descent rate
if ($altitude < 1000) {
    if (($altitude >= 500 && $descent_rate > 16) ||
        ($altitude >= 100 && $altitude < 500 && $descent_rate > 6) ||
        ($altitude < 100 && $altitude >= 50 && $descent_rate > 3) ||
        ($altitude < 50 && $descent_rate > 2)) {
        $descent_alert_class = "alt-red-box";
    } else {
        $descent_alert_class = "alt-green-box";
    }
}

// Fetch the status of 'landed' and 'eagle' from the systems table
$landed_status = 0;
$eagle_status = 0;

$result = $conn->query("SELECT system, status FROM systems WHERE system IN ('landed', 'eagle')");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['system'] == 'landed') {
            $landed_status = $row['status'];
        } elseif ($row['system'] == 'eagle') {
            $eagle_status = $row['status'];
        }
    }
}

// Determine light color based on the landed status
$light_class = $landed_status == 1 ? 'contact-light-on' : 'contact-light-off';

// Handle sound playback and update 'eagle' if necessary
if ($landed_status == 1 && $eagle_status == 0) {
    if ($descent_rate < 2) {
        echo '<audio autoplay>
                <source src="the-eagle-has-landed.mp3" type="audio/mpeg">
              </audio>';
    } else {
        echo '<audio autoplay>
                <source src="v0001.mp3" type="audio/mpeg">
              </audio>';
    }

    $conn->query("UPDATE systems SET status = 1 WHERE system = 'eagle'");
}
?>

<table class="alt-table">
    <tr>
        <td><h2 class="alt-label">Altitude</h2></td>
        <td><div class="alt-digital-display"><?php echo $radar_status == 1 ? number_format($altitude, 0) : '0'; echo $pitchCM; ?></div></td>
        <td><span class="alt-unit-label">m</span></td>
    </tr>
    <tr>
        <td><h2 class="alt-label">Descent Rate</h2></td>
        <td><div class="alt-digital-display"><?php echo number_format($descent_rate, 1); ?></div></td>
        <td><span class="alt-unit-label">m/s</span></td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="alt-alert-box <?php echo $descent_alert_class; ?>">Dec Rate</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="alt-alert-box <?php echo $radar_alert_class; ?>">Radar</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="contact-container">
                <div class="contact-light <?php echo $light_class; ?>"></div>
                <div class="contact-label">Contact</div>
            </div>
        </td>
    </tr>
</table>

<style>
    /* Altitude Module Independent Styles */
    .alt-table {
        width: 100%;
        border-spacing: 10px;
    }

    .alt-label {
        font-family: Arial, sans-serif;
        font-size: 1.2rem;
        color: #ffffff;
    }

    .alt-digital-display {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00;
        background-color: #000000;
        padding: 5px;
        border-radius: 5px;
        width: 7ch;
        text-align: right;
    }

    .alt-unit-label {
        font-size: 1rem;
        font-weight: normal;
        color: #ffffff;
    }

    .alt-alert-box {
        font-family: Arial, sans-serif;
        font-size: 1rem;
        font-weight: bold;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        width: 50%;
        margin: 0 auto;
        border: 3px solid #888888;
    }

    .alt-black-box {
        background-color: #000000;
        color: #888888;
    }

    .alt-red-box {
        background-color: #ff0000;
        color: #ffffff;
    }

    .alt-green-box {
        background-color: #00ff00;
        color: #000000;
    }

    .contact-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 10px;
    }

    .contact-light {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 2px solid #555;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    }

    .contact-light-on {
        background-color: #FFFF00; /* Yellow */
    }

    .contact-light-off {
        background-color: #000000; /* Black */
    }

    .contact-label {
        font-family: Arial, sans-serif;
        font-size: 1rem;
        color: #ffffff;
    }
</style>
