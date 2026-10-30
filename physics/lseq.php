<?php

require_once __DIR__ . '/../shared/orbital_mechanics.php';

/*
 * Original launch-sequence controller, repaired in place.
 *
 * This remains the automatic Saturn launch program, but derives guidance
 * measurements from live simulation state rather than wall-clock temp files.
 */

$clockResult = $conn->query(
    "SELECT seconds, launch_events
     FROM mission_clock
     WHERE id=1"
);
$clockRow = $clockResult ? $clockResult->fetch_assoc() : null;
$secondsx = (int)($clockRow['seconds'] ?? -900);
$launchEvents = (int)($clockRow['launch_events'] ?? 0);

// Earth-relative speed and true radial climb rate.
$rx = (float)$xCM - (float)$earth_center_x;
$ry = (float)$yCM - (float)$earth_center_y;
$rz = (float)$zCM - (float)$earth_center_z;
$rmag = sqrt($rx*$rx + $ry*$ry + $rz*$rz);

$v_total = sqrt(
    (float)$delta_xCM*(float)$delta_xCM
    + (float)$delta_yCM*(float)$delta_yCM
    + (float)$delta_zCM*(float)$delta_zCM
);

$altitude_rate = 0.0;
if ($rmag > 1.0) {
    $altitude_rate = (
        $rx*(float)$delta_xCM
        + $ry*(float)$delta_yCM
        + $rz*(float)$delta_zCM
    ) / $rmag;
}

// Preserve the original shortened simulator countdown.
if ($secondsx === -900) {
    $conn->query(
        "UPDATE mission_clock
         SET seconds=-15, launch_events=0, minutes=0
         WHERE id=1"
    );
    $secondsx = -15;
    $launchEvents = 0;
}

if ($secondsx === -1) {
    $conn->query(
        "UPDATE command_module SET value=2 WHERE attribute='staging'"
    );
    $conn->query(
        "UPDATE mission_clock SET launch_events=1 WHERE id=1"
    );
    $stagingCM = 2;
    $launchEvents = 1;
}

if ($secondsx === 0) {
    $conn->query("UPDATE CMguidance SET execute=1 WHERE id=16");
    $conn->query(
        "UPDATE command_module SET value=0 WHERE attribute='staging'"
    );
    $stagingCM = 0;
}

/*
 * A separation event remains at staging=1 for one physics pass so displays
 * and audio can observe it. Physics, not the cockpit page, clears it.
 */
if ((int)$stagingCM === 1) {
    $conn->query(
        "UPDATE command_module SET value=0 WHERE attribute='staging'"
    );
    $stagingCM = 0;
}

// S-IC separation after propellant depletion in the current stage model.
if ((float)$s1fuelCM <= 0.0 && (int)$stage1CM === 1) {
    $conn->query("UPDATE CMguidance SET execute=1 WHERE id=19");
    $conn->query(
        "UPDATE command_module SET value=1 WHERE attribute='staging'"
    );
}

// S-II ignition after S-IC separation.
if (
    (int)$stage1CM === 0
    && (int)$stage2CM === 1
    && (int)$stage3CM === 1
    && (float)$thrustCM <= 0.0
    && (int)$stagingCM === 0
) {
    $conn->query("UPDATE CMguidance SET execute=1 WHERE id=17");
}

// S-II separation.
if ((float)$s2fuelCM <= 0.0 && (int)$stage2CM === 1) {
    $conn->query("UPDATE CMguidance SET execute=1 WHERE id=20");
    $conn->query(
        "UPDATE command_module SET value=1 WHERE attribute='staging'"
    );
}

// First S-IVB ignition. Once parking orbit is established launch_events=2,
// preventing the launch sequence from automatically restarting the J-2.
if (
    (int)$stage1CM === 0
    && (int)$stage2CM === 0
    && (int)$stage3CM === 1
    && (float)$thrustCM <= 0.0
    && (int)$stagingCM === 0
    && $launchEvents < 2
) {
    $conn->query("UPDATE CMguidance SET execute=1 WHERE id=18");
}

/*
 * Parking-orbit cutoff.
 *
 * The historical launch vehicle cut the first S-IVB burn by guidance command,
 * not by emptying the S-IVB tank. Preserve enough propellant for a later TLI
 * restart. Mission profiles can later tighten these thresholds; these are a
 * generic Apollo-style stable-LEO condition rather than an Apollo-11-only time.
 */
if (
    (int)$stage1CM === 0
    && (int)$stage2CM === 0
    && (int)$stage3CM === 1
    && (float)$thrustCM > 0.0
    && $launchEvents < 2
    && $secondsx > 600
) {
    $elements = moonSimOrbitalElements(
        [(float)$xCM,(float)$yCM,(float)$zCM],
        [(float)$delta_xCM,(float)$delta_yCM,(float)$delta_zCM],
        [
            (float)$earth_center_x,
            (float)$earth_center_y,
            (float)$earth_center_z,
        ],
        (float)$gravitational_constant*(float)$earth_mass
    );

    $periapsisAltitude = $elements['periapsis_radius'] !== null
        ? (float)$elements['periapsis_radius']-(float)$earth_radius
        : -INF;
    $apoapsisAltitude = $elements['apoapsis_radius'] !== null
        ? (float)$elements['apoapsis_radius']-(float)$earth_radius
        : -INF;

    if (
        $periapsisAltitude >= 160000.0
        && $apoapsisAltitude >= 170000.0
        && abs($altitude_rate) <= 200.0
    ) {
        $conn->query(
            "UPDATE command_module
             SET value=0
             WHERE attribute IN ('thrust','yaw_rate')"
        );
        $conn->query(
            "UPDATE CMguidance SET thrust=0 WHERE id=1"
        );
        $conn->query(
            "UPDATE EngineStatus
             SET status='OFF'
             WHERE engine_stage='Third Stage'"
        );
        $conn->query(
            "UPDATE mission_clock SET launch_events=2 WHERE id=1"
        );
        $conn->query(
            "UPDATE CMprogram_register
             SET program_number=0, seconds=NULL
             WHERE program_number=23"
        );

        $launchEvents = 2;
    }
}

// Preserve the original simple pitch/heading schedule until the mission-profile
// launch guidance phase replaces it with sourced trajectory targets.
$prate = 0.0;

if ($launchEvents < 2 && $altitude > 1000.0 && $altitude <= 20000.0) {
    $minAltitude = 1000.0;
    $maxAltitude = 20000.0;
    $minPrate = 300.0;
    $maxPrate = 359.0;

    $prate = round(
        $maxPrate
        - (($altitude-$minAltitude)/($maxAltitude-$minAltitude))
        * ($maxPrate-$minPrate),
        1
    );

    // Legacy guidance command. Quaternion state resynchronizes from explicit
    // Euler guidance changes on the next attitude load.
    $conn->query(
        "UPDATE command_module SET value=$prate WHERE attribute='yaw'"
    );
}

$conn->query(
    "UPDATE CMguidance SET velocity=$altitude_rate WHERE id=2"
);

if ($launchEvents < 2 && $altitude > 20000.0) {
    $yawratex = 0.0;

    if ($altitude < 90000.0 && $v_total < 4000.0) {
        $opt = (120000.0-$altitude)/100.0;
    } elseif ($altitude < 100000.0 && $v_total < 4000.0) {
        $opt = 30.0;
    } elseif ($altitude >= 100000.0 && $v_total < 6500.0) {
        $opt = 10.0;
    } elseif ($altitude >= 100000.0 && $v_total < 7200.0) {
        $opt = 5.0;
    } else {
        $opt = 1.0;
    }

    if ($altitude_rate > $opt) {
        $yawratex = -2.0;
    } elseif ($altitude_rate < $opt) {
        $yawratex = 2.0;
    }

    $zzz = $horrizonCM < 265.0 ? $horrizonCM+35.0 : 300.0;
    if ((int)$stage1CM === 1) {
        $zzz = 290.0;
    }

    $lowerVector = $yawVCM-3.0;
    $upperVector = $yawVCM+2.0;

    if ($yawCM > $zzz) {
        $yawratex = -0.5;
    }
    if ($yawCM <= $lowerVector) {
        $yawratex = 0.5;
    }
    if ($yawCM >= $upperVector && $v_total > 6000.0) {
        $yawratex = -0.5;
    }

    $conn->query(
        "UPDATE command_module
         SET value=$yawratex
         WHERE attribute='yaw_rate'"
    );
}

?>