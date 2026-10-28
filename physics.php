<?php

declare(strict_types=1);

require_once __DIR__ . '/physics/tick.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $seconds = physicsAdvance(db(), 300.0);

    echo json_encode([
        'ok' => true,
        'processed_seconds' => $seconds,
        'message' => $seconds > 0
            ? 'Physics advanced.'
            : 'Nothing to process or another worker owns the tick lock.',
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT);
}
