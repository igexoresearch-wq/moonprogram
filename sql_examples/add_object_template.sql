-- Generic reusable object/component definition template
-- Replace the values and run in MySQL.

INSERT INTO object_definitions (
    slug,
    name,
    object_type,
    dry_mass_kg,
    radius_m,
    produces_gravity,
    affected_by_gravity,
    has_orientation,
    notes
) VALUES (
    'example-stage',
    'Example Stage',
    'stage',
    10000.000000,
    NULL,
    0,
    1,
    1,
    'Replace with verified values.'
);

-- Optional fuel tank:
INSERT INTO definition_fuel_tanks (
    object_definition_id,
    name,
    propellant_type,
    capacity_kg
)
SELECT
    id,
    'Main Tank',
    'EXAMPLE_PROPELLANT',
    50000.000000000
FROM object_definitions
WHERE slug='example-stage';

-- Optional engine mounting.
-- Requires the engine definition to already exist.
INSERT INTO definition_engine_mounts (
    object_definition_id,
    engine_definition_id,
    quantity,
    local_x,
    local_y,
    local_z,
    notes
)
SELECT
    o.id,
    e.id,
    1,
    1.000000000,
    0.000000000,
    0.000000000,
    'Local +X is forward thrust.'
FROM object_definitions o
JOIN engine_definitions e ON e.slug='example-engine'
WHERE o.slug='example-stage';
