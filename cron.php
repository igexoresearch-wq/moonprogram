<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/physics.php';

const MOON_CRON_RUNNER_LOCK = 'moonprogram_cron_runner';
const MOON_CRON_LOOP_SECONDS = 58.0;

$pdo = moonDb();
$once = in_array('--once', $argv, true);

$runnerLock = $pdo->prepare("SELECT GET_LOCK(:name, 0)");
$runnerLock->execute(['name' => MOON_CRON_RUNNER_LOCK]);

if ((int)$runnerLock->fetchColumn() !== 1) {
    echo "Moon cron skipped: another minute runner is active." . PHP_EOL;
    exit(0);
}

$startedAt = microtime(true);
$ticks = 0;
$lastResult = null;

try {
    do {
        $lastResult = moonRunElapsedPhysics($pdo);
        $ticks++;

        if ($once) {
            break;
        }

        /*
         * Aim for one database/physics update per real second.
         * We calculate the next target from the original start time
         * instead of blindly sleeping one second, so PHP execution
         * time does not accumulate into clock drift.
         */
        $nextTarget = $startedAt + $ticks;
        $remaining = $nextTarget - microtime(true);

        if ($remaining > 0.0) {
            usleep((int)round($remaining * 1000000));
        }
    } while ((microtime(true) - $startedAt) < MOON_CRON_LOOP_SECONDS);

    echo json_encode(
        [
            'success' => true,
            'runner_seconds' => microtime(true) - $startedAt,
            'runner_ticks' => $ticks,
            'last_result' => $lastResult,
        ],
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    ) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(
        STDERR,
        '[' . date('c') . '] Moon physics error: '
        . $e->getMessage()
        . PHP_EOL
    );

    exit(1);
} finally {
    try {
        $release = $pdo->prepare("SELECT RELEASE_LOCK(:name)");
        $release->execute(['name' => MOON_CRON_RUNNER_LOCK]);
    } catch (Throwable $ignored) {
    }
}
