<?php
/*
 * Physics-facing constants wrapper.
 *
 * Physical data lives in one place: shared/physical_constants.php.
 * Derived mass/geometry lives in shared/derived_state.php.
 */

include_once __DIR__ . '/../shared/physical_constants.php';
include __DIR__ . '/../shared/derived_state.php';

$temp = 0.0;
if ($altitude < 100000.0 && (int)$service_moduleCM === 0) {
    include_once __DIR__ . '/temp.php';
    $temp = calculateReentryTemperature((float)$altitude, (float)$airspeed);
}
?>
