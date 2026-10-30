<?php
header('Content-Type: application/json');

require 'db.php';
require_once __DIR__ . '/../shared/rcs_commands.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['thruster'])) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

/*
 * Preserve the existing three pulse-size buttons, but make them real SM-RCS
 * pulse durations rather than percentages secretly added to SPS thrust.
 */
$pulses = [
    'axis'   => ['direction'=> 1.0, 'duration'=>0.05],
    'rever'  => ['direction'=>-1.0, 'duration'=>0.05],
    'axis2'  => ['direction'=> 1.0, 'duration'=>0.50],
    'rever2' => ['direction'=>-1.0, 'duration'=>0.50],
    'axis3'  => ['direction'=> 1.0, 'duration'=>5.00],
    'rever3' => ['direction'=>-1.0, 'duration'=>5.00],
];

$key = (string)$_POST['thruster'];
if (!isset($pulses[$key])) {
    echo json_encode(['status'=>'error','message'=>'Unknown RCS pulse']);
    exit;
}

$serviceResult = $conn->query(
    "SELECT value
     FROM command_module
     WHERE attribute = 'service_module'
     LIMIT 1"
);
$serviceModule = ($serviceResult && $row = $serviceResult->fetch_assoc())
    ? (int)$row['value']
    : 0;

$fuelResult = $conn->query(
    "SELECT value
     FROM command_module
     WHERE attribute = 'RCS'
     LIMIT 1"
);
$rcsFuel = ($fuelResult && $row = $fuelResult->fetch_assoc())
    ? (float)$row['value']
    : 0.0;

$switchResult = $conn->query(
    "SELECT status
     FROM CMswitches
     WHERE name = 'RCS_Thrusters'
     LIMIT 1"
);
$rcsEnabled = ($switchResult && $row = $switchResult->fetch_assoc())
    ? (int)$row['status']
    : 1;

if ($serviceModule !== 1) {
    echo json_encode([
        'status'=>'error',
        'message'=>'SM RCS unavailable after service-module separation'
    ]);
    exit;
}

if ($rcsEnabled !== 1 || $rcsFuel <= 0.0) {
    echo json_encode([
        'status'=>'error',
        'message'=>'SM RCS is unavailable or out of propellant'
    ]);
    exit;
}

$pulse = $pulses[$key];

$id = moonSimQueueRcsPulse(
    $conn,
    'CM',
    $pulse['direction'],
    0.0,
    0.0,
    $pulse['duration']
);

echo json_encode([
    'status'=>'success',
    'message'=>sprintf(
        'Queued %.2f s SM-RCS %sX pulse',
        $pulse['duration'],
        $pulse['direction'] > 0 ? '+' : '-'
    ),
    'command_id'=>$id,
    'sound'=>'poof-of-smoke-87381.mp3'
]);
?>
