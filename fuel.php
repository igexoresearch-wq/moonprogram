<?php
// Fetch fuel value from the telemetry table
$fuel = "N/A";
$sql = "SELECT value FROM telemetry WHERE attribute = 'fuel' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    echo "<p>Query Error: " . $conn->error . "</p>";
} elseif ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fuel = $row['value'];
} else {
    echo "<p>No fuel value found.</p>";
}

// Fetch thrust value from the telemetry table
$thrust = "N/A";
$sql = "SELECT value FROM telemetry WHERE attribute = 'thrust' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    echo "<p>Query Error: " . $conn->error . "</p>";
} elseif ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $thrust = $row['value'];
} else {
    echo "<p>No thrust value found.</p>";
}

// Calculate fuel burn time
$effective_thrust = ($thrust == 0) ? 25 : $thrust; // Use 25 thrust for calculation if thrust is 0
$burn_time = $fuel / ($effective_thrust * 0.14455);
?>

<table>
    <tr>
        <td><h2>Fuel</h2></td>
        <td><div class="digital-display"><?php echo number_format($fuel, 1, '.', ''); ?></div></td>
        <td><span class="unit-label">kg</span></td>
    </tr>
    <tr>
        <td><h2>Burn Time</h2></td>
        <td><div class="digital-display"><?php echo number_format($burn_time, 0, '.', ''); ?></div></td>
        <td><span class="unit-label">s</span></td>
    </tr>
    <tr>
        <td><h2>Thrust</h2></td>
        <td><div class="digital-display"><?php echo number_format($thrust, 0, '.', ''); ?></div></td>
        <td><span class="unit-label">%</span></td>
    </tr>
</table>
