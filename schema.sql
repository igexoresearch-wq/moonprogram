SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS initial_object_attachments;
DROP TABLE IF EXISTS initial_object_states;
DROP TABLE IF EXISTS object_attachments;
DROP TABLE IF EXISTS propellant_systems;
DROP TABLE IF EXISTS space_objects;
DROP TABLE IF EXISTS simulation_state;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE simulation_state (
    id TINYINT UNSIGNED NOT NULL,
    is_running TINYINT(1) NOT NULL DEFAULT 0,
    tick_seconds DECIMAL(12,6) NOT NULL DEFAULT 1.000000,
    time_scale DECIMAL(12,6) NOT NULL DEFAULT 1.000000,
    simulated_time_seconds DECIMAL(30,6) NOT NULL DEFAULT 0.000000,
    last_tick_at DATETIME(6) NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE space_objects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    object_class VARCHAR(32) NOT NULL,
    component_type VARCHAR(48) NOT NULL,

    base_mass_kg DECIMAL(36,6) NOT NULL,
    gm_m3_s2 DECIMAL(30,6) NULL,
    radius_m DECIMAL(20,3) NULL,

    x_cm BIGINT NOT NULL DEFAULT 0,
    y_cm BIGINT NOT NULL DEFAULT 0,
    z_cm BIGINT NOT NULL DEFAULT 0,
    x_subcm DECIMAL(10,9) NOT NULL DEFAULT 0.000000000,
    y_subcm DECIMAL(10,9) NOT NULL DEFAULT 0.000000000,
    z_subcm DECIMAL(10,9) NOT NULL DEFAULT 0.000000000,

    vx_cm_s DECIMAL(24,9) NOT NULL DEFAULT 0.000000000,
    vy_cm_s DECIMAL(24,9) NOT NULL DEFAULT 0.000000000,
    vz_cm_s DECIMAL(24,9) NOT NULL DEFAULT 0.000000000,

    pitch_deg DECIMAL(7,3) NULL,
    yaw_deg DECIMAL(7,3) NULL,
    roll_deg DECIMAL(7,3) NULL,
    pitch_rate_deg_s DECIMAL(8,3) NULL,
    yaw_rate_deg_s DECIMAL(8,3) NULL,
    roll_rate_deg_s DECIMAL(8,3) NULL,

    main_engine_count TINYINT UNSIGNED NULL,

    produces_gravity TINYINT(1) NOT NULL DEFAULT 0,
    affected_by_gravity TINYINT(1) NOT NULL DEFAULT 1,
    is_fixed TINYINT(1) NOT NULL DEFAULT 0,

    surface_body_id INT UNSIGNED NULL,
    surface_locked TINYINT(1) NOT NULL DEFAULT 0,
    on_surface TINYINT(1) NOT NULL DEFAULT 0,

    source_note VARCHAR(500) NULL,

    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),

    PRIMARY KEY (id),
    UNIQUE KEY uq_space_objects_slug (slug),
    KEY idx_space_objects_class (object_class),
    KEY idx_space_objects_surface_body (surface_body_id),

    CONSTRAINT fk_space_objects_surface_body
        FOREIGN KEY (surface_body_id)
        REFERENCES space_objects(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE propellant_systems (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    object_id INT UNSIGNED NOT NULL,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(140) NOT NULL,
    propellant_type VARCHAR(80) NOT NULL,
    capacity_kg DECIMAL(24,9) NOT NULL,
    remaining_kg DECIMAL(24,9) NOT NULL,
    source_note VARCHAR(500) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),

    PRIMARY KEY (id),
    UNIQUE KEY uq_propellant_object_slug (object_id, slug),
    KEY idx_propellant_object (object_id),

    CONSTRAINT fk_propellant_object
        FOREIGN KEY (object_id)
        REFERENCES space_objects(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE object_attachments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_object_id INT UNSIGNED NOT NULL,
    child_object_id INT UNSIGNED NOT NULL,
    attachment_type VARCHAR(40) NOT NULL DEFAULT 'stack',
    attached_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    PRIMARY KEY (id),
    UNIQUE KEY uq_attachment_child (child_object_id),
    KEY idx_attachment_parent (parent_object_id),

    CONSTRAINT fk_attachment_parent
        FOREIGN KEY (parent_object_id)
        REFERENCES space_objects(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_attachment_child
        FOREIGN KEY (child_object_id)
        REFERENCES space_objects(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE initial_object_states (
    object_id INT UNSIGNED NOT NULL,

    x_cm BIGINT NOT NULL,
    y_cm BIGINT NOT NULL,
    z_cm BIGINT NOT NULL,
    x_subcm DECIMAL(10,9) NOT NULL,
    y_subcm DECIMAL(10,9) NOT NULL,
    z_subcm DECIMAL(10,9) NOT NULL,

    vx_cm_s DECIMAL(24,9) NOT NULL,
    vy_cm_s DECIMAL(24,9) NOT NULL,
    vz_cm_s DECIMAL(24,9) NOT NULL,

    pitch_deg DECIMAL(7,3) NULL,
    yaw_deg DECIMAL(7,3) NULL,
    roll_deg DECIMAL(7,3) NULL,
    pitch_rate_deg_s DECIMAL(8,3) NULL,
    yaw_rate_deg_s DECIMAL(8,3) NULL,
    roll_rate_deg_s DECIMAL(8,3) NULL,

    surface_locked TINYINT(1) NOT NULL DEFAULT 0,
    on_surface TINYINT(1) NOT NULL DEFAULT 0,

    PRIMARY KEY (object_id),

    CONSTRAINT fk_initial_state_object
        FOREIGN KEY (object_id)
        REFERENCES space_objects(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE initial_object_attachments (
    parent_object_id INT UNSIGNED NOT NULL,
    child_object_id INT UNSIGNED NOT NULL,
    attachment_type VARCHAR(40) NOT NULL DEFAULT 'stack',

    PRIMARY KEY (child_object_id),
    KEY idx_initial_attachment_parent (parent_object_id),

    CONSTRAINT fk_initial_attachment_parent
        FOREIGN KEY (parent_object_id)
        REFERENCES space_objects(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_initial_attachment_child
        FOREIGN KEY (child_object_id)
        REFERENCES space_objects(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
