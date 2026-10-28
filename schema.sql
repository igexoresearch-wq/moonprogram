SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS telemetry;
DROP TABLE IF EXISTS command_center_panels;
DROP TABLE IF EXISTS instrument_definitions;
DROP TABLE IF EXISTS live_engines;
DROP TABLE IF EXISTS live_fuel_tanks;
DROP TABLE IF EXISTS live_objects;
DROP TABLE IF EXISTS definition_engine_mounts;
DROP TABLE IF EXISTS definition_fuel_tanks;
DROP TABLE IF EXISTS engine_propellant_requirements;
DROP TABLE IF EXISTS engine_definitions;
DROP TABLE IF EXISTS object_definitions;
DROP TABLE IF EXISTS missions;
DROP TABLE IF EXISTS simulation_state;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE simulation_state (
    id TINYINT UNSIGNED NOT NULL,
    last_tick_at DATETIME(6) NOT NULL,
    nominal_tick_seconds DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
    telemetry_interval_seconds DECIMAL(8,3) NOT NULL DEFAULT 1.000,
    physics_enabled TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE missions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    status ENUM('draft','active','complete','failed','aborted') NOT NULL DEFAULT 'draft',
    started_at DATETIME(6) NULL,
    ended_at DATETIME(6) NULL,
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_mission_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE object_definitions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(140) NOT NULL,
    object_type VARCHAR(60) NOT NULL,
    dry_mass_kg DECIMAL(40,6) NOT NULL,
    radius_m DECIMAL(20,6) NULL,
    produces_gravity TINYINT(1) NOT NULL DEFAULT 0,
    affected_by_gravity TINYINT(1) NOT NULL DEFAULT 1,
    has_orientation TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_object_definition_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE engine_definitions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(140) NOT NULL,
    engine_type VARCHAR(60) NOT NULL,
    dry_mass_kg DECIMAL(20,6) NOT NULL DEFAULT 0,
    thrust_n DECIMAL(24,6) NOT NULL,
    fuel_rate_kg_s DECIMAL(20,9) NOT NULL,
    minimum_throttle_percent DECIMAL(6,3) NOT NULL DEFAULT 0,
    maximum_throttle_percent DECIMAL(6,3) NOT NULL DEFAULT 100,
    restartable TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_engine_definition_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE engine_propellant_requirements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    engine_definition_id INT UNSIGNED NOT NULL,
    propellant_type VARCHAR(100) NOT NULL,
    mass_fraction DECIMAL(12,9) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_epr_engine (engine_definition_id),
    CONSTRAINT fk_epr_engine
        FOREIGN KEY (engine_definition_id)
        REFERENCES engine_definitions(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE definition_fuel_tanks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    object_definition_id INT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    propellant_type VARCHAR(100) NOT NULL,
    capacity_kg DECIMAL(24,9) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_dft_object (object_definition_id),
    CONSTRAINT fk_dft_object
        FOREIGN KEY (object_definition_id)
        REFERENCES object_definitions(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE definition_engine_mounts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    object_definition_id INT UNSIGNED NOT NULL,
    engine_definition_id INT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    local_x DECIMAL(12,9) NOT NULL DEFAULT 1.000000000,
    local_y DECIMAL(12,9) NOT NULL DEFAULT 0.000000000,
    local_z DECIMAL(12,9) NOT NULL DEFAULT 0.000000000,
    notes VARCHAR(500) NULL,
    PRIMARY KEY (id),
    KEY idx_dem_object (object_definition_id),
    KEY idx_dem_engine (engine_definition_id),
    CONSTRAINT fk_dem_object
        FOREIGN KEY (object_definition_id)
        REFERENCES object_definitions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_dem_engine
        FOREIGN KEY (engine_definition_id)
        REFERENCES engine_definitions(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE live_objects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mission_id INT UNSIGNED NULL,
    definition_id INT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,

    parent_object_id BIGINT UNSIGNED NULL,
    is_attached TINYINT(1) NOT NULL DEFAULT 0,

    attach_x_m DECIMAL(18,6) NOT NULL DEFAULT 0,
    attach_y_m DECIMAL(18,6) NOT NULL DEFAULT 0,
    attach_z_m DECIMAL(18,6) NOT NULL DEFAULT 0,
    attach_pitch_deg DECIMAL(9,4) NOT NULL DEFAULT 0,
    attach_yaw_deg DECIMAL(9,4) NOT NULL DEFAULT 0,
    attach_roll_deg DECIMAL(9,4) NOT NULL DEFAULT 0,

    dry_mass_kg DECIMAL(40,6) NOT NULL,
    radius_m DECIMAL(20,6) NULL,

    x_cm BIGINT NOT NULL DEFAULT 0,
    y_cm BIGINT NOT NULL DEFAULT 0,
    z_cm BIGINT NOT NULL DEFAULT 0,
    x_subcm DECIMAL(10,9) NOT NULL DEFAULT 0,
    y_subcm DECIMAL(10,9) NOT NULL DEFAULT 0,
    z_subcm DECIMAL(10,9) NOT NULL DEFAULT 0,

    vx_cm_s DECIMAL(24,9) NOT NULL DEFAULT 0,
    vy_cm_s DECIMAL(24,9) NOT NULL DEFAULT 0,
    vz_cm_s DECIMAL(24,9) NOT NULL DEFAULT 0,

    pitch_deg DECIMAL(9,4) NULL,
    yaw_deg DECIMAL(9,4) NULL,
    roll_deg DECIMAL(9,4) NULL,
    pitch_rate_deg_s DECIMAL(10,5) NULL,
    yaw_rate_deg_s DECIMAL(10,5) NULL,
    roll_rate_deg_s DECIMAL(10,5) NULL,

    produces_gravity TINYINT(1) NOT NULL DEFAULT 0,
    affected_by_gravity TINYINT(1) NOT NULL DEFAULT 1,
    surface_body_id BIGINT UNSIGNED NULL,
    surface_locked TINYINT(1) NOT NULL DEFAULT 0,

    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),

    PRIMARY KEY (id),
    KEY idx_live_mission (mission_id),
    KEY idx_live_parent (parent_object_id),
    KEY idx_live_definition (definition_id),
    KEY idx_live_surface (surface_body_id),

    CONSTRAINT fk_live_mission
        FOREIGN KEY (mission_id) REFERENCES missions(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_live_definition
        FOREIGN KEY (definition_id) REFERENCES object_definitions(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_live_parent
        FOREIGN KEY (parent_object_id) REFERENCES live_objects(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_live_surface
        FOREIGN KEY (surface_body_id) REFERENCES live_objects(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE live_fuel_tanks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    live_object_id BIGINT UNSIGNED NOT NULL,
    definition_tank_id INT UNSIGNED NULL,
    name VARCHAR(140) NOT NULL,
    propellant_type VARCHAR(100) NOT NULL,
    capacity_kg DECIMAL(24,9) NOT NULL,
    remaining_kg DECIMAL(24,9) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lft_object (live_object_id),
    CONSTRAINT fk_lft_object
        FOREIGN KEY (live_object_id) REFERENCES live_objects(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_lft_definition
        FOREIGN KEY (definition_tank_id) REFERENCES definition_fuel_tanks(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE live_engines (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    live_object_id BIGINT UNSIGNED NOT NULL,
    engine_definition_id INT UNSIGNED NOT NULL,
    mount_definition_id INT UNSIGNED NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    throttle_percent DECIMAL(6,3) NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    local_x DECIMAL(12,9) NOT NULL DEFAULT 1,
    local_y DECIMAL(12,9) NOT NULL DEFAULT 0,
    local_z DECIMAL(12,9) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_le_object (live_object_id),
    CONSTRAINT fk_le_object
        FOREIGN KEY (live_object_id) REFERENCES live_objects(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_le_engine
        FOREIGN KEY (engine_definition_id) REFERENCES engine_definitions(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_le_mount
        FOREIGN KEY (mount_definition_id) REFERENCES definition_engine_mounts(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE telemetry (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    live_object_id BIGINT UNSIGNED NOT NULL,
    recorded_at DATETIME(6) NOT NULL,
    x_cm BIGINT NOT NULL,
    y_cm BIGINT NOT NULL,
    z_cm BIGINT NOT NULL,
    vx_cm_s DECIMAL(24,9) NOT NULL,
    vy_cm_s DECIMAL(24,9) NOT NULL,
    vz_cm_s DECIMAL(24,9) NOT NULL,
    pitch_deg DECIMAL(9,4) NULL,
    yaw_deg DECIMAL(9,4) NULL,
    roll_deg DECIMAL(9,4) NULL,
    total_mass_kg DECIMAL(40,6) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_telemetry_object_time (live_object_id, recorded_at),
    CONSTRAINT fk_telemetry_object
        FOREIGN KEY (live_object_id) REFERENCES live_objects(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE instrument_definitions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(140) NOT NULL,
    include_file VARCHAR(255) NOT NULL,
    description VARCHAR(500) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_instrument_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE command_center_panels (
    slot_number TINYINT UNSIGNED NOT NULL,
    title VARCHAR(120) NULL,
    instrument_id INT UNSIGNED NULL,
    PRIMARY KEY (slot_number),
    CONSTRAINT fk_ccp_instrument
        FOREIGN KEY (instrument_id) REFERENCES instrument_definitions(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO simulation_state (
    id, last_tick_at, nominal_tick_seconds,
    telemetry_interval_seconds, physics_enabled
) VALUES (
    1, NOW(6), 1.0000, 1.000, 1
);

INSERT INTO instrument_definitions (slug, name, include_file, description) VALUES
('universe-status', 'Universe Status', 'panels/universe_status.php', 'Physics clock and object count.'),
('live-objects', 'Live Objects', 'panels/live_objects.php', 'Current objects and attachments.'),
('engines', 'Engines', 'panels/engine_status.php', 'Live engine state.'),
('quick-links', 'Build / Configure', 'panels/quick_links.php', 'Definition and mission tools.'),
('ground-comms', 'Ground Control COMMS', 'panels/comms.php', 'Ground-control communications instrument shell.');

INSERT INTO command_center_panels (slot_number, title, instrument_id)
SELECT 1, 'Universe', id FROM instrument_definitions WHERE slug='universe-status';
INSERT INTO command_center_panels (slot_number, title, instrument_id)
SELECT 2, 'Objects', id FROM instrument_definitions WHERE slug='live-objects';
INSERT INTO command_center_panels (slot_number, title, instrument_id)
SELECT 3, 'Engines', id FROM instrument_definitions WHERE slug='engines';
INSERT INTO command_center_panels (slot_number, title, instrument_id)
SELECT 4, 'Build', id FROM instrument_definitions WHERE slug='quick-links';
INSERT INTO command_center_panels (slot_number, title, instrument_id)
SELECT 5, 'COMMS', id FROM instrument_definitions WHERE slug='ground-comms';

INSERT INTO command_center_panels (slot_number, title, instrument_id) VALUES
(6,NULL,NULL),(7,NULL,NULL),(8,NULL,NULL),(9,NULL,NULL),(10,NULL,NULL),
(11,NULL,NULL),(12,NULL,NULL),(13,NULL,NULL),(14,NULL,NULL),(15,NULL,NULL),
(16,NULL,NULL),(17,NULL,NULL),(18,NULL,NULL),(19,NULL,NULL),(20,NULL,NULL);
