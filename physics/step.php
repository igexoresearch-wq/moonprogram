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

// Preserve the current atmosphere/reentry model for now. It produces a
// one-second drag estimate and, for legacy parachutes, may modify current
// velocity before the central integrator receives it.
include 'physics/air.php';

// Propulsion computes main-engine acceleration and one-second propellant flow.
include 'physics/thrust.php';
include 'physics/fuel.php';

$cmNonGravityAcceleration = [
    (float)$thrustAccelerationCMx - (float)$drag_acc_x,
    (float)$thrustAccelerationCMy - (float)$drag_acc_y,
    (float)$thrustAccelerationCMz - (float)$drag_acc_z,
];

$lmNonGravityAcceleration = [
    (float)$thrustAccelerationLMx,
    (float)$thrustAccelerationLMy,
    (float)$thrustAccelerationLMz,
];

// Preserve the old heat-shield wear behavior until the dedicated reentry pass.
if ((float)$altitude < 100000.0 && (float)$temp > 800.0) {
    $newHeat = max(0.0, (float)$heat_shieldCM - (float)$temp);
    $conn->query(
        "UPDATE command_module
         SET value = " . sprintf('%.6f', $newHeat) . "
         WHERE attribute = 'heat_shield'"
    );
}

moonSimAdvanceTranslation(
    $conn,
    1.0,
    0.1,
    $cmNonGravityAcceleration,
    $lmNonGravityAcceleration
);

moonSimAdvanceLegacyAttitude($conn, 1.0);

// Guidance must see the state resulting from the completed physical second.
include 'physics/variables.php';
include 'physics/constants.php';
include 'physics/coordinate.php';
include 'physics/CM_AGC.php';
?>
