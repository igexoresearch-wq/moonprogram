<?php

function moonSimEnsureDockingCommandTables(mysqli $conn): void
{
    $allowedEnum =
        "ENUM(" .
        "'UNDOCK'," .
        "'SIVB_SEPARATE'," .
        "'SM_SEPARATE'," .
        "'LM_ASCENT_STAGE'," .
        "'LM_ABORT_ASCENT'" .
        ")";

    $conn->query(
        "CREATE TABLE IF NOT EXISTS docking_command_queue (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            command_type $allowedEnum NOT NULL,
            requested_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            processed_at DATETIME(6) NULL,
            PRIMARY KEY (id),
            KEY idx_docking_pending (processed_at, id)
        ) ENGINE=InnoDB"
    );

    /*
     * Existing deployments can have an older narrower ENUM. Inspect the
     * column before issuing ALTER TABLE so normal cockpit requests do not
     * take a metadata lock on every use.
     */
    $columnResult = $conn->query(
        "SHOW COLUMNS FROM docking_command_queue LIKE 'command_type'"
    );
    $columnRow = $columnResult ? $columnResult->fetch_assoc() : null;
    $columnType = strtolower((string)($columnRow['Type'] ?? ''));

    $requiredValues = [
        'undock',
        'sivb_separate',
        'sm_separate',
        'lm_ascent_stage',
        'lm_abort_ascent',
    ];

    $needsAlter = ($columnType === '');
    foreach ($requiredValues as $requiredValue) {
        if (strpos($columnType, $requiredValue) === false) {
            $needsAlter = true;
            break;
        }
    }

    if ($needsAlter) {
        $conn->query(
            "ALTER TABLE docking_command_queue
             MODIFY command_type $allowedEnum NOT NULL"
        );
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS docking_runtime (
            id TINYINT UNSIGNED NOT NULL,
            redock_inhibit_until DATETIME(6) NULL,
            last_capture_at DATETIME(6) NULL,
            last_release_at DATETIME(6) NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB"
    );

    $conn->query(
        "INSERT INTO docking_runtime (id)
         VALUES (1)
         ON DUPLICATE KEY UPDATE id=id"
    );
}

function moonSimQueueDockingCommand(mysqli $conn, string $commandType): int
{
    moonSimEnsureDockingCommandTables($conn);

    $commandType = strtoupper($commandType);
    $allowed = [
        'UNDOCK',
        'SIVB_SEPARATE',
        'SM_SEPARATE',
        'LM_ASCENT_STAGE',
        'LM_ABORT_ASCENT',
    ];

    if (!in_array($commandType, $allowed, true)) {
        throw new InvalidArgumentException('Unknown docking/separation command.');
    }

    $stmt = $conn->prepare(
        "INSERT INTO docking_command_queue (command_type)
         VALUES (?)"
    );
    $stmt->bind_param('s', $commandType);
    $stmt->execute();

    return (int)$conn->insert_id;
}

?>