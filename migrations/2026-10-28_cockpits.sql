-- Cockpit system migration for an existing Moon Program database.
-- Run this ONCE if schema.sql was already installed before cockpit support.
-- Fresh installs should use schema.sql instead.

SET NAMES utf8mb4;

CREATE TABLE cockpit_definitions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(140) NOT NULL,
    description VARCHAR(500) NULL,
    columns_count TINYINT UNSIGNED NOT NULL DEFAULT 5,
    rows_count TINYINT UNSIGNED NOT NULL DEFAULT 4,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_cockpit_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE instrument_definitions
    ADD COLUMN context ENUM('ground','cockpit','both') NOT NULL DEFAULT 'both'
    AFTER include_file;

UPDATE instrument_definitions
SET context='ground'
WHERE slug IN ('universe-status','live-objects','engines','quick-links');

UPDATE instrument_definitions
SET context='both'
WHERE slug='ground-comms';

INSERT INTO instrument_definitions (slug,name,include_file,context,description) VALUES
('cockpit-attitude','Attitude','panels/cockpit_attitude.php','cockpit','Pitch, yaw and roll for the controlled spacecraft.'),
('cockpit-velocity','Velocity','panels/cockpit_velocity.php','cockpit','Velocity vector and total speed.'),
('cockpit-position','Position','panels/cockpit_position.php','cockpit','World XYZ position.'),
('cockpit-fuel','Propellant','panels/cockpit_fuel.php','cockpit','Propellant tank quantities.'),
('cockpit-engine-control','Engine Control','panels/cockpit_engine_control.php','cockpit','Engine enable and throttle controls.'),
('cockpit-vehicle','Vehicle Status','panels/cockpit_vehicle.php','cockpit','Vehicle, mission, mass and attachment status.');

CREATE TABLE cockpit_panels (
    cockpit_id INT UNSIGNED NOT NULL,
    slot_number TINYINT UNSIGNED NOT NULL,
    title VARCHAR(120) NULL,
    instrument_id INT UNSIGNED NULL,
    PRIMARY KEY (cockpit_id, slot_number),
    KEY idx_cockpit_panel_instrument (instrument_id),
    CONSTRAINT fk_cockpit_panel_cockpit
        FOREIGN KEY (cockpit_id) REFERENCES cockpit_definitions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cockpit_panel_instrument
        FOREIGN KEY (instrument_id) REFERENCES instrument_definitions(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE object_definitions
    ADD COLUMN default_cockpit_id INT UNSIGNED NULL AFTER object_type,
    ADD KEY idx_object_default_cockpit (default_cockpit_id),
    ADD CONSTRAINT fk_object_default_cockpit
        FOREIGN KEY (default_cockpit_id)
        REFERENCES cockpit_definitions(id)
        ON DELETE SET NULL;

ALTER TABLE live_objects
    ADD COLUMN cockpit_id INT UNSIGNED NULL AFTER definition_id,
    ADD KEY idx_live_cockpit (cockpit_id),
    ADD CONSTRAINT fk_live_cockpit
        FOREIGN KEY (cockpit_id)
        REFERENCES cockpit_definitions(id)
        ON DELETE SET NULL;

INSERT INTO cockpit_definitions (slug,name,description,columns_count,rows_count)
VALUES (
    'generic-flight-deck',
    'Generic Flight Deck',
    'Starter cockpit. Copy or reconfigure it for Apollo, Gemini, Mercury, LM, or fictional vehicles.',
    5,4
);

INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,1,'ATTITUDE',i.id FROM cockpit_definitions c JOIN instrument_definitions i ON i.slug='cockpit-attitude' WHERE c.slug='generic-flight-deck';
INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,2,'VELOCITY',i.id FROM cockpit_definitions c JOIN instrument_definitions i ON i.slug='cockpit-velocity' WHERE c.slug='generic-flight-deck';
INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,3,'POSITION',i.id FROM cockpit_definitions c JOIN instrument_definitions i ON i.slug='cockpit-position' WHERE c.slug='generic-flight-deck';
INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,4,'PROPELLANT',i.id FROM cockpit_definitions c JOIN instrument_definitions i ON i.slug='cockpit-fuel' WHERE c.slug='generic-flight-deck';
INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,5,'ENGINE CONTROL',i.id FROM cockpit_definitions c JOIN instrument_definitions i ON i.slug='cockpit-engine-control' WHERE c.slug='generic-flight-deck';
INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,6,'VEHICLE',i.id FROM cockpit_definitions c JOIN instrument_definitions i ON i.slug='cockpit-vehicle' WHERE c.slug='generic-flight-deck';
INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,7,'COMMS',i.id FROM cockpit_definitions c JOIN instrument_definitions i ON i.slug='ground-comms' WHERE c.slug='generic-flight-deck';

INSERT INTO cockpit_panels (cockpit_id,slot_number,title,instrument_id)
SELECT c.id,n.slot_number,NULL,NULL
FROM cockpit_definitions c
JOIN (
  SELECT 8 slot_number UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11
  UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
  UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
  UNION ALL SELECT 20
) n
WHERE c.slug='generic-flight-deck';

-- Existing live instances inherit their definition's cockpit, if one is assigned later.
UPDATE live_objects o
JOIN object_definitions d ON d.id=o.definition_id
SET o.cockpit_id=d.default_cockpit_id
WHERE o.cockpit_id IS NULL;
