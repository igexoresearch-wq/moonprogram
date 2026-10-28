<?php

declare(strict_types=1);

require_once __DIR__ . '/physics/tick.php';

set_time_limit(0);
ignore_user_abort(true);

$pdo = db();
$started = microtime(true);
$runForSeconds = 58.5;

do {
    try {
        physicsAdvance($pdo, 300.0);
    } catch (Throwable $e) {
        error_log('[Moon Program physics] ' . $e->getMessage());
    }

    usleep(1000000);
} while ((microtime(true) - $started) < $runForSeconds);
