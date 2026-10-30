<?php

function moonSimEnsureDockingCommandTables(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS docking_command_queue (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            command_type ENUM('UNDOCK','SIVB_SEPARATE') NOT NULL,
            requested_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            processed_at DATETIME(6) NULL,
            PRIMARY KEY (id),
            KEY idx_docking_pending (processed_at, id)
        ) ENGINE=InnoDB"
    );

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
    if (!in_array($commandType, ['UNDOCK','SIVB_SEPARATE'], true)) {
        throw new InvalidArgumentException('Unknown docking command.');
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
