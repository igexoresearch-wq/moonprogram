<?php

$earthCenter = [10000000000.0, 10000000000.0, 10000000000.0];
$earthRadius = 6371000.0;
$moonRadius = 1737100.0;

$radar_status = 0;
$result = $conn->query("SELECT status FROM systems WHERE system = 'radar' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $radar_status = (int)$row['status'];
}

$lm = [
    'x' => 0.0,
    'y' => 0.0,
    'z' => 0.0,
    'delta_x' => 0.0,
    'delta_y' => 0.0,
    'delta_z' => 0.0,
];

$result = $conn->query(
    "SELECT attribute, value
     FROM telemetry
     WHERE attribute IN ('x','y','z','delta_x','delta_y','delta_z')"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lm[$row['attribute']] = (float)$row['value'];
    }
}

$moon = [
    'x' => 0.0,
    'y' => 0.0,
    'z' => 0.0,
    'delta_x' => 0.0,
    'delta_y' => 0.0,
    'delta_z' => 0.0,
];

$result = $conn->query(
    "SELECT attribute, value
     FROM moon
     WHERE attribute IN ('x','y','z','delta_x','delta_y','delta_z')"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $moon[$row['attribute']] = (float)$row['value'];
    }
}

$earthRelative = [
    $lm['x'] - $earthCenter[0],
    $lm['y'] - $earthCenter[1],
    $lm['z'] - $earthCenter[2],
];

$moonRelative = [
    $lm['x'] - $moon['x'],
    $lm['y'] - $moon['y'],
    $lm['z'] - $moon['z'],
];

$earthDistance = sqrt(
    $earthRelative[0] ** 2
    + $earthRelative[1] ** 2
    + $earthRelative[2] ** 2
);
$moonDistance = sqrt(
    $moonRelative[0] ** 2
    + $moonRelative[1] ** 2
    + $moonRelative[2] ** 2
);

$earthAltitude = $earthDistance - $earthRadius;
$moonAltitude = $moonDistance - $moonRadius;

// Use the nearer body surface for the landing-radar style display.
$targetIsMoon = ($moonAltitude < $earthAltitude);
$altitude = $targetIsMoon ? $moonAltitude : $earthAltitude;

if ($targetIsMoon) {
    $relativePosition = $moonRelative;
    $relativeVelocity = [
        $lm['delta_x'] - $moon['delta_x'],
        $lm['delta_y'] - $moon['delta_y'],
        $lm['delta_z'] - $moon['delta_z'],
    ];
    $distance = $moonDistance;
    $targetName = 'MOON';
} else {
    $relativePosition = $earthRelative;
    $relativeVelocity = [
        $lm['delta_x'],
        $lm['delta_y'],
        $lm['delta_z'],
    ];
    $distance = $earthDistance;
    $targetName = 'EARTH';
}

// Positive descent_rate means motion toward the selected body surface.
$descent_rate = 0.0;
if ($distance > 0.0) {
    $radialVelocity =
        ($relativePosition[0] * $relativeVelocity[0]
        + $relativePosition[1] * $relativeVelocity[1]
        + $relativePosition[2] * $relativeVelocity[2])
        / $distance;

    $descent_rate = -$radialVelocity;
}

$descent_alert_class = 'alt-black-box';
if ($altitude < 1000.0) {
    $unsafe =
        ($altitude >= 500.0 && $descent_rate > 16.0)
        || ($altitude >= 100.0 && $altitude < 500.0 && $descent_rate > 6.0)
        || ($altitude >= 50.0 && $altitude < 100.0 && $descent_rate > 3.0)
        || ($altitude < 50.0 && $descent_rate > 2.0);

    $descent_alert_class = $unsafe ? 'alt-red-box' : 'alt-green-box';
}

$radar_alert_class = $radar_status === 1 ? 'alt-green-box' : 'alt-red-box';

$landed_status = 0;
$result = $conn->query("SELECT status FROM systems WHERE system = 'landed' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $landed_status = (int)$row['status'];
}

$light_class = $landed_status === 1
    ? 'contact-light-on'
    : 'contact-light-off';

$landingSound = $descent_rate < 2.0
    ? 'the-eagle-has-landed.mp3'
    : 'v0001.mp3';
?>

<?php if ($landed_status === 1): ?>
<audio id="lmLandingAudio" preload="auto">
    <source src="<?php echo htmlspecialchars($landingSound); ?>" type="audio/mpeg">
</audio>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!sessionStorage.getItem('lmLandingSoundPlayed')) {
        const audio = document.getElementById('lmLandingAudio');
        if (audio) {
            audio.play().catch(() => {});
        }
        sessionStorage.setItem('lmLandingSoundPlayed', '1');
    }
});
</script>
<?php else: ?>
<script>
sessionStorage.removeItem('lmLandingSoundPlayed');
</script>
<?php endif; ?>

<table class="alt-table">
    <tr>
        <td><h2 class="alt-label">Altitude</h2></td>
        <td>
            <div class="alt-digital-display">
                <?php echo $radar_status === 1 ? number_format(max(0.0, $altitude), 0) : '0'; ?>
            </div>
        </td>
        <td><span class="alt-unit-label">m</span></td>
    </tr>
    <tr>
        <td><h2 class="alt-label">Target</h2></td>
        <td><div class="alt-digital-display"><?php echo $targetName; ?></div></td>
        <td></td>
    </tr>
    <tr>
        <td><h2 class="alt-label">Descent Rate</h2></td>
        <td><div class="alt-digital-display"><?php echo number_format($descent_rate, 1); ?></div></td>
        <td><span class="alt-unit-label">m/s</span></td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="alt-alert-box <?php echo $descent_alert_class; ?>">DEC RATE</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="alt-alert-box <?php echo $radar_alert_class; ?>">RADAR</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="contact-container">
                <div class="contact-light <?php echo $light_class; ?>"></div>
                <div class="contact-label">CONTACT</div>
            </div>
        </td>
    </tr>
</table>

<style>
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
        min-width: 7ch;
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
        background-color: #FFFF00;
    }

    .contact-light-off {
        background-color: #000000;
    }

    .contact-label {
        font-family: Arial, sans-serif;
        font-size: 1rem;
        color: #ffffff;
    }
</style>
