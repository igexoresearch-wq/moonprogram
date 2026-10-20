<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/physics.php';

try {
    $result = moonRunElapsedPhysics(moonDb());

    echo json_encode(
        $result,
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
}
