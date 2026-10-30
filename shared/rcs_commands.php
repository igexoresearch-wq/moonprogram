<?php

function moonSimEnsureRcsQueue(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS rcs_pulse_queue (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            vehicle ENUM('CM','LM') NOT NULL,
            body_x DOUBLE NOT NULL DEFAULT 0,
            body_y DOUBLE NOT NULL DEFAULT 0,
            body_z DOUBLE NOT NULL DEFAULT 0,
            remaining_seconds DOUBLE NOT NULL,
            created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            completed_at DATETIME(6) NULL,
            PRIMARY KEY (id),
            KEY idx_rcs_pending (vehicle, completed_at, id)
        ) ENGINE=InnoDB"
    );
}

function moonSimQueueRcsPulse(
    mysqli $conn,
    string $vehicle,
    float $bodyX,
    float $bodyY,
    float $bodyZ,
    float $durationSeconds
): int {
    moonSimEnsureRcsQueue($conn);

    $vehicle = strtoupper($vehicle);
    if (!in_array($vehicle, ['CM','LM'], true)) {
        throw new InvalidArgumentException('Invalid RCS vehicle.');
    }

    $length = sqrt($bodyX*$bodyX + $bodyY*$bodyY + $bodyZ*$bodyZ);
    if ($length <= 0.0) {
        throw new InvalidArgumentException('RCS pulse requires a direction.');
    }

    $durationSeconds = max(0.01, min(30.0, $durationSeconds));

    $bodyX /= $length;
    $bodyY /= $length;
    $bodyZ /= $length;

    $stmt = $conn->prepare(
        "INSERT INTO rcs_pulse_queue (
            vehicle, body_x, body_y, body_z, remaining_seconds
         ) VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param(
        'sdddd',
        $vehicle,
        $bodyX,
        $bodyY,
        $bodyZ,
        $durationSeconds
    );
    $stmt->execute();

    return (int)$conn->insert_id;
}

?>
