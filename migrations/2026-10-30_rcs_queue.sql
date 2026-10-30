-- Central body-axis RCS pulse queue.
-- Safe to run on an existing moonland database.
-- The shared RCS helper also creates it automatically if absent.

CREATE TABLE IF NOT EXISTS rcs_pulse_queue (
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
) ENGINE=InnoDB;
