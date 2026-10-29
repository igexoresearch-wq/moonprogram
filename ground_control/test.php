<?php
include 'db.php';

// Standard deviation and mean values from your previous results
$mean_AM = 51598635145.466026; // Replace with your calculated mean
$std_dev_AM = 99535020.300481; // Replace with your calculated standard deviation

// Fetch AM values from the last 4 hours with timestamps
$sql = "SELECT timestamp, AM FROM CMtelemetry WHERE timestamp >= NOW() - INTERVAL 4 HOUR ORDER BY timestamp ASC";
$result = $conn->query($sql);

$am_data = [];
$time_stamps = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $am_data[] = (float)$row["AM"];
        $time_stamps[] = $row["timestamp"];
    }
}

// Function to compute 15-minute moving average
function moving_average($data, $timestamps, $window_size = 900) { // 900 seconds = 15 minutes
    $count = count($data);
    $averages = [];

    for ($i = 0; $i < $count; $i++) {
        $sum = 0;
        $num_values = 0;
        $current_time = strtotime($timestamps[$i]);

        for ($j = $i; $j >= 0; $j--) {
            $time_diff = $current_time - strtotime($timestamps[$j]);

            if ($time_diff <= $window_size) { // If within 15 minutes
                $sum += $data[$j];
                $num_values++;
            } else {
                break; // Stop checking if it's outside the 15-min window
            }
        }

        $averages[] = ($num_values > 0) ? $sum / $num_values : 0;
    }

    return $averages;
}

// Compute 15-minute moving average
$moving_avg = moving_average($am_data, $time_stamps);

// Function to determine background color based on standard deviation
function get_color($value, $mean, $std_dev) {
    if ($value >= $mean - $std_dev && $value <= $mean + $std_dev) {
        return "background-color: green; color: white;"; // Within ±1 SD
    } elseif ($value >= $mean - (2 * $std_dev) && $value <= $mean + (2 * $std_dev)) {
        return "background-color: yellow; color: black;"; // Within ±2 SD
    } else {
        return "background-color: red; color: white;"; // Beyond ±2 SD
    }
}

// Display the results
echo "<table border='1' style='border-collapse: collapse; text-align: center;'>";
echo "<tr><th>Timestamp</th><th>AM</th><th>15-Min Moving Avg</th></tr>";

for ($i = 0; $i < count($am_data); $i++) {
    $color = get_color($moving_avg[$i], $mean_AM, $std_dev_AM);
    echo "<tr><td>{$time_stamps[$i]}</td><td>{$am_data[$i]}</td><td style='{$color}'>" . number_format($moving_avg[$i], 6) . "</td></tr>";
}

echo "</table>";
?>
