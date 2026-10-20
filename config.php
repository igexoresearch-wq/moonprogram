<?php

declare(strict_types=1);

/**
 * Moon Program application/database configuration.
 *
 * Secrets are read from environment variables. Do not hard-code
 * production passwords into this public repository.
 */

function moonConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $local = __DIR__ . '/config.local.php';

    if (is_file($local)) {
        $loaded = require $local;

        if (!is_array($loaded)) {
            throw new RuntimeException('config.local.php must return an array.');
        }

        $config = $loaded;
        return $config;
    }

    $config = [
        'db' => [
            'host' => getenv('MOON_DB_HOST') ?: 'localhost',
            'port' => getenv('MOON_DB_PORT') ?: '3306',
            'name' => getenv('MOON_DB_NAME') ?: 'spacegame',
            'user' => getenv('MOON_DB_USER') ?: '',
            'pass' => getenv('MOON_DB_PASS') ?: '',
        ],
        'control_key' => getenv('MOON_CONTROL_KEY') ?: '',
    ];

    return $config;
}

function moonDb(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = moonConfig();
    $db = $config['db'];

    if ($db['user'] === '') {
        throw new RuntimeException(
            'Database credentials are not configured. Set MOON_DB_USER and MOON_DB_PASS.'
        );
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['port'],
        $db['name']
    );

    $pdo = new PDO(
        $dsn,
        $db['user'],
        $db['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]
    );

    return $pdo;
}

function moonControlKey(): string
{
    return (string)(moonConfig()['control_key'] ?? '');
}
