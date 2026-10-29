<?php
header('Content-Type: application/json'); // Ensure proper JSON response
include 'db.php'; // Ensure correct database connection

// Fetch current values for ID 1
$query = $conn->query("SELECT roll, pitch, yaw FROM CMguidance WHERE id = 1");
$data = $query->fetch_assoc();

if ($data) {
    // Convert to float
    $COMProll = (float) $data['roll'];
    $COMPpitch = (float) $data['pitch'];
    $COMPyaw = (float) $data['yaw'];

    // Normalize angles
    if ($COMPpitch > 180) {
        $COMPpitch = 0 - (360 - $COMPpitch);
    }
    if ($COMPyaw > 180) {
        $COMPyaw = 0 - (360 - $COMPyaw);
    }

    // Calculate display coordinates
    $skyyaw = -$COMPyaw * 2.5;
    $skypitch = $COMPpitch * 2.5;

    // Return JSON response
    echo json_encode([
        'roll' => $COMProll,
        'pitch' => $COMPpitch,
        'yaw' => $COMPyaw,
        'skyyaw' => $skyyaw,
        'skypitch' => $skypitch
    ]);
} else {
    echo json_encode(['error' => 'No data found']);
}
?>
