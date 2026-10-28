-- Generic reusable engine definition template

INSERT INTO engine_definitions (
    slug,
    name,
    engine_type,
    dry_mass_kg,
    thrust_n,
    fuel_rate_kg_s,
    minimum_throttle_percent,
    maximum_throttle_percent,
    restartable,
    notes
) VALUES (
    'example-engine',
    'Example Engine',
    'liquid rocket',
    0.000000,
    1000000.000000,
    250.000000000,
    0.000,
    100.000,
    1,
    'Replace with verified engine data.'
);

-- Example two-propellant mixture.
-- Fractions should sum to 1.0.
INSERT INTO engine_propellant_requirements (
    engine_definition_id,
    propellant_type,
    mass_fraction
)
SELECT id, 'OXIDIZER', 0.700000000
FROM engine_definitions
WHERE slug='example-engine';

INSERT INTO engine_propellant_requirements (
    engine_definition_id,
    propellant_type,
    mass_fraction
)
SELECT id, 'FUEL', 0.300000000
FROM engine_definitions
WHERE slug='example-engine';
