<?php
/*
 * One simulated second.
 *
 * Translation is integrated in 0.1 s velocity-Verlet substeps.
 * Guidance/program timing is still evaluated once per simulated second,
 * matching the original AGC/program-register behavior.
 */

include 'physics/variables.php';
include 'physics/constants.php';
include 'physics/coordinate.php';

// Load authoritative orientation before propulsion is evaluated. Guidance may
// still write Euler targets directly; moonSimLoadVehicleQuaternion() detects
// those writes and resynchronizes the internal quaternion automatically.
$cmQuaternion = moonSimLoadVehicleQuaternion($conn, 'CM');
$lmQuaternion = moonSimLoadVehicleQuaternion($conn, 'LM');

if ((int)$dockCM === 1 || (int)$stage3CM === 1) {
    $lmQuaternion = $cmQuaternion;
    moonSimStoreVehicleQuaternion($conn, 'LM', $lmQuaternion);
}

// Separation and undocking are requested by the cockpit but applied here.
if (moonSimConsumeDockingCommands($conn, $cmQuaternion, $lmQuaternion)) {
    include 'physics/variables.php';
    include 'physics/constants.php';
    include 'physics/coordinate.php';

    $cmQuaternion = moonSimLoadVehicleQuaternion($conn, 'CM');
    $lmQuaternion = moonSimLoadVehicleQuaternion($conn, 'LM');
}

// Preserve the current atmosphere/reentry model for now. It produces a
// one-second drag estimate and, for legacy parachutes, may modify current
// velocity before the central integrator receives it.
include 'physics/air.php';

// Propulsion computes main-engine acceleration and one-second propellant flow.
include 'physics/thrust.php';
include 'physics/fuel.php';

// Translational RCS commands are queued by the cockpit pages and consumed only
// here, so clicking/refreshing a UI can no longer edit velocity directly.
$rcsAcceleration = moonSimConsumeRcsPulses(
    $conn,
    $cmQuaternion,
    $lmQuaternion,
    1.0
);

$cmNonGravityAcceleration = [
    (float)$thrustAccelerationCMx
        + (float)$drag_acc_x
        + (float)$lift_acc_x
        + $rcsAcceleration['CM'][0],
    (float)$thrustAccelerationCMy
        + (float)$drag_acc_y
        + (float)$lift_acc_y
        + $rcsAcceleration['CM'][1],
    (float)$thrustAccelerationCMz
        + (float)$drag_acc_z
        + (float)$lift_acc_z
        + $rcsAcceleration['CM'][2],
];

$lmNonGravityAcceleration = [
    (float)$thrustAccelerationLMx + $rcsAcceleration['LM'][0],
    (float)$thrustAccelerationLMy + $rcsAcceleration['LM'][1],
    (float)$thrustAccelerationLMz + $rcsAcceleration['LM'][2],
];

moonSimAdvanceTranslation(
    $conn,
    1.0,
    0.1,
    $cmNonGravityAcceleration,
    $lmNonGravityAcceleration
);

moonSimAdvanceAttitude($conn, 1.0);

// Guidance must see the state resulting from the completed physical second.
include 'physics/variables.php';
include 'physics/constants.php';
include 'physics/coordinate.php';
include 'physics/CM_AGC.php';
?>
