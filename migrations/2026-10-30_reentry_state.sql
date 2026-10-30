-- Central aerodynamic/reentry state.
-- Safe to run on an existing moonland database.
-- physics/air.php also creates this table automatically if absent.

CREATE TABLE IF NOT EXISTS reentry_state (
    id TINYINT UNSIGNED NOT NULL,
    chute_state ENUM('STOWED','DROGUE','MAIN_REEFED','MAIN','FAILED')
        NOT NULL DEFAULT 'STOWED',
    chute_timer_seconds DOUBLE NOT NULL DEFAULT 0,
    density_kg_m3 DOUBLE NOT NULL DEFAULT 0,
    mach DOUBLE NOT NULL DEFAULT 0,
    dynamic_pressure_pa DOUBLE NOT NULL DEFAULT 0,
    heat_flux_w_m2 DOUBLE NOT NULL DEFAULT 0,
    heat_load_j_m2 DOUBLE NOT NULL DEFAULT 0,
    aero_deceleration_g DOUBLE NOT NULL DEFAULT 0,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

INSERT INTO reentry_state (id)
VALUES (1)
ON DUPLICATE KEY UPDATE id=id;
