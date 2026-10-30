<?php

declare(strict_types=1);

set_time_limit(0);
ignore_user_abort(true);

function calculate_anglex($xs, $ys) {
    $origin = 10000000000;
    $x_adjusted = $xs - $origin;
    $y_adjusted = $ys - $origin;
    $theta_degrees = atan2($y_adjusted, $x_adjusted) * (180 / pi());

    if ($theta_degrees < 0) {
        $theta_degrees += 360;
    }

    return $theta_degrees;
}

function calculate_anglemoonx($xm, $ym) {
    $theta_degrees = atan2($ym, $xm) * (180 / pi());

    if ($theta_degrees < 0) {
        $theta_degrees += 360;
    }

    return $theta_degrees;
}

include 'physics/db.php';
include_once 'physics/integrator.php';
include_once 'physics/attitude_legacy.php';

// Keep the original once-per-minute worker design, but guarantee that only one
// worker can advance the universe at a time.
$lockResult = $conn->query("SELECT GET_LOCK('moon_sim_physics_worker', 0) AS acquired");
$lockRow = $lockResult ? $lockResult->fetch_assoc() : null;

if (!$lockRow || (int)$lockRow['acquired'] !== 1) {
    exit;
}

try {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS simulation_runtime (
            id TINYINT UNSIGNED NOT NULL,
            last_wall_at DATETIME(6) NOT NULL,
            accumulator_seconds DECIMAL(18,6) NOT NULL DEFAULT 0,
            last_tick_at DATETIME(6) NULL,
            updated_at TIMESTAMP(6) NOT NULL
                DEFAULT CURRENT_TIMESTAMP(6)
                ON UPDATE CURRENT_TIMESTAMP(6),
            PRIMARY KEY (id)
        ) ENGINE=InnoDB"
    );

    $conn->query(
        "INSERT INTO simulation_runtime (
            id,
            last_wall_at,
            accumulator_seconds,
            last_tick_at
         ) VALUES (
            1,
            NOW(6),
            0,
            NULL
         )
         ON DUPLICATE KEY UPDATE id = id"
    );

    $workerStarted = microtime(true);
    $workerRunSeconds = 58.0;
    $resetApplied = false;

    while ((microtime(true) - $workerStarted) < $workerRunSeconds) {
        $simResult = $conn->query(
            "SELECT status
             FROM systems
             WHERE system = 'sim_on'
             LIMIT 1"
        );
        $simRow = $simResult ? $simResult->fetch_assoc() : null;
        $simOn = $simRow ? (int)$simRow['status'] : 0;

        if ($simOn !== 1) {
            if (!$resetApplied) {
                include 'physics/reset.php';
                $resetApplied = true;
            }

            // Time spent reset/off is not owed to the next mission.
            $conn->query(
                "UPDATE simulation_runtime
                 SET last_wall_at = NOW(6),
                     accumulator_seconds = 0
                 WHERE id = 1"
            );

            usleep(200000);
            continue;
        }

        $resetApplied = false;

        $runtimeResult = $conn->query(
            "SELECT
                accumulator_seconds,
                GREATEST(
                    0,
                    TIMESTAMPDIFF(MICROSECOND, last_wall_at, NOW(6)) / 1000000.0
                ) AS elapsed_seconds
             FROM simulation_runtime
             WHERE id = 1"
        );
        $runtime = $runtimeResult ? $runtimeResult->fetch_assoc() : null;

        if (!$runtime) {
            usleep(100000);
            continue;
        }

        $accumulator =
            (float)$runtime['accumulator_seconds']
            + (float)$runtime['elapsed_seconds'];

        $accumulatorSql = number_format($accumulator, 6, '.', '');

        $conn->query(
            "UPDATE simulation_runtime
             SET last_wall_at = NOW(6),
                 accumulator_seconds = $accumulatorSql
             WHERE id = 1"
        );

        // Each completed include below represents one simulated second.
        // Translation inside that second is divided into 0.1 s substeps.
        $ticksToRun = min(10, (int)floor($accumulator));

        if ($ticksToRun <= 0) {
            usleep(100000);
            continue;
        }

        for ($tick = 0; $tick < $ticksToRun; $tick++) {
            include 'physics/step.php';

            // Consume time only after a successful physics/guidance second.
            $conn->query(
                "UPDATE simulation_runtime
                 SET accumulator_seconds = GREATEST(0, accumulator_seconds - 1),
                     last_tick_at = NOW(6)
                 WHERE id = 1"
            );

            if ((microtime(true) - $workerStarted) >= $workerRunSeconds) {
                break;
            }
        }
    }
} finally {
    $conn->query("SELECT RELEASE_LOCK('moon_sim_physics_worker')");
}
?>
