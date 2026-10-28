SET NAMES utf8mb4;

-- Reusable celestial definitions
INSERT INTO object_definitions (
    slug, name, object_type, dry_mass_kg, radius_m,
    produces_gravity, affected_by_gravity, has_orientation, notes
) VALUES
(
    'earth',
    'Earth',
    'planet',
    5972170000000000000000000.000000,
    6378136.600000,
    1, 0, 0,
    'Earth reference body. Equatorial radius.'
),
(
    'moon',
    'Moon',
    'moon',
    73460000000000000000000.000000,
    1737400.000000,
    1, 1, 0,
    'Moon initialized at mean lunar distance in a circularized Earth-centered orbit.'
);

-- Live universe
INSERT INTO live_objects (
    definition_id, mission_id, name,
    dry_mass_kg, radius_m,
    x_cm, y_cm, z_cm,
    vx_cm_s, vy_cm_s, vz_cm_s,
    pitch_deg, yaw_deg, roll_deg,
    pitch_rate_deg_s, yaw_rate_deg_s, roll_rate_deg_s,
    produces_gravity, affected_by_gravity,
    surface_body_id, surface_locked
)
SELECT
    id, NULL, 'Earth',
    dry_mass_kg, radius_m,
    0, 0, 0,
    0, 0, 0,
    NULL, NULL, NULL,
    NULL, NULL, NULL,
    produces_gravity, affected_by_gravity,
    NULL, 0
FROM object_definitions
WHERE slug = 'earth';

INSERT INTO live_objects (
    definition_id, mission_id, name,
    dry_mass_kg, radius_m,
    x_cm, y_cm, z_cm,
    vx_cm_s, vy_cm_s, vz_cm_s,
    pitch_deg, yaw_deg, roll_deg,
    pitch_rate_deg_s, yaw_rate_deg_s, roll_rate_deg_s,
    produces_gravity, affected_by_gravity,
    surface_body_id, surface_locked
)
SELECT
    moon.id, NULL, 'Moon',
    moon.dry_mass_kg, moon.radius_m,
    38440000000, 0, 0,
    0, 101830.340260, 0,
    NULL, NULL, NULL,
    NULL, NULL, NULL,
    moon.produces_gravity, moon.affected_by_gravity,
    earth_live.id, 0
FROM object_definitions AS moon
CROSS JOIN live_objects AS earth_live
WHERE moon.slug = 'moon'
  AND earth_live.name = 'Earth'
LIMIT 1;

UPDATE simulation_state
SET last_tick_at = NOW(6)
WHERE id = 1;
