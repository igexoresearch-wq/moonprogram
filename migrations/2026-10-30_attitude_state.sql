-- Quaternion attitude state for CSM and LM.
-- Safe to run on an existing moonland database.
-- physics/attitude.php also creates this table automatically if absent.

CREATE TABLE IF NOT EXISTS attitude_state (
    vehicle VARCHAR(8) NOT NULL,
    qw DOUBLE NOT NULL,
    qx DOUBLE NOT NULL,
    qy DOUBLE NOT NULL,
    qz DOUBLE NOT NULL,
    display_pitch DOUBLE NOT NULL DEFAULT 0,
    display_yaw DOUBLE NOT NULL DEFAULT 0,
    display_roll DOUBLE NOT NULL DEFAULT 0,
    updated_at TIMESTAMP(6) NOT NULL
        DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (vehicle)
) ENGINE=InnoDB;
