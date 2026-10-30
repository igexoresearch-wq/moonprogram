<?php
/*
 * Ground-control constants wrapper.
 * Ground calculations must use the exact same physical values as flight physics.
 */

include_once __DIR__ . '/../shared/physical_constants.php';
include __DIR__ . '/../shared/derived_state.php';

$temp = 0.0;
if ($altitude < 100000.0 && (int)$service_moduleCM === 0) {
    include_once __DIR__ . '/../physics/temp.php';
    $temp = calculateReentryTemperature((float)$altitude, (float)$airspeed);
}
?>
