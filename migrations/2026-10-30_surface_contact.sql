-- Persistent Earth/Moon surface contact state.
-- Safe to run on an existing moonland database.

CREATE TABLE IF NOT EXISTS surface_contact (
    vehicle ENUM('CM','LM') NOT NULL,
    body ENUM('EARTH','MOON') NOT NULL,
    contact_state ENUM('LANDED','CRASHED') NOT NULL,
    normal_x DOUBLE NOT NULL,
    normal_y DOUBLE NOT NULL,
    normal_z DOUBLE NOT NULL,
    impact_speed DOUBLE NOT NULL DEFAULT 0,
    radial_speed DOUBLE NOT NULL DEFAULT 0,
    tangential_speed DOUBLE NOT NULL DEFAULT 0,
    contacted_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (vehicle)
) ENGINE=InnoDB;
