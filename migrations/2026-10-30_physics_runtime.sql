-- Moon Simulator physics runtime support
-- Safe to run on an existing moonland database.
-- physicsupdate.php also creates this table automatically if it is absent.

CREATE TABLE IF NOT EXISTS simulation_runtime (
    id TINYINT UNSIGNED NOT NULL,
    last_wall_at DATETIME(6) NOT NULL,
    accumulator_seconds DECIMAL(18,6) NOT NULL DEFAULT 0,
    last_tick_at DATETIME(6) NULL,
    updated_at TIMESTAMP(6) NOT NULL
        DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

INSERT INTO simulation_runtime (
    id,
    last_wall_at,
    accumulator_seconds,
    last_tick_at
) VALUES (
    1,
    NOW(6),
    0,
    NULL
)
ON DUPLICATE KEY UPDATE id = id;
