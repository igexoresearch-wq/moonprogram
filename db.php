<?php

declare(strict_types=1);

/*
 * Moon Program database connection.
 *
 * Put your real credentials here on the server.
 * Do not commit production credentials if this repository is public.
 */

const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'spacegame';
const DB_USER = 'CHANGE_ME';
const DB_PASS = 'CHANGE_ME';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]
    );

    return $pdo;
}
