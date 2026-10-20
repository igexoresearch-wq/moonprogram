SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM initial_object_attachments;
DELETE FROM initial_object_states;
DELETE FROM object_attachments;
DELETE FROM propellant_systems;
DELETE FROM space_objects;
DELETE FROM simulation_state;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO simulation_state (
    id,
    is_running,
    tick_seconds,
    time_scale,
    simulated_time_seconds,
    last_tick_at
) VALUES (
    1,
    0,
    1.000000,
    1.000000,
    0.000000,
    NOW(6)
);

-- ============================================================
-- CELESTIAL OBJECTS
-- ============================================================

INSERT INTO space_objects (
    id, slug, name, object_class, component_type,
    base_mass_kg, gm_m3_s2, radius_m,
    x_cm, y_cm, z_cm,
    vx_cm_s, vy_cm_s, vz_cm_s,
    pitch_deg, yaw_deg, roll_deg,
    pitch_rate_deg_s, yaw_rate_deg_s, roll_rate_deg_s,
    main_engine_count,
    produces_gravity, affected_by_gravity, is_fixed,
    surface_body_id, surface_locked, on_surface,
    source_note
) VALUES
(
    1,
    'earth',
    'Earth',
    'celestial',
    'planet',
    5972170000000000000000000.000000,
    398600435507000.000000,
    6378136.600,
    0, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    NULL, NULL, NULL,
    NULL, NULL, NULL,
    NULL,
    1, 0, 1,
    NULL, 0, 0,
    'JPL Earth mass/GM; equatorial radius used for the equatorial launch surface.'
),
(
    2,
    'moon',
    'Moon',
    'celestial',
    'moon',
    73460000000000000000000.000000,
    4902800118000.000000,
    1737400.000,
    38440000000, 0, 0,
    0.000000000, 101830.340259534, 0.000000000,
    NULL, NULL, NULL,
    NULL, NULL, NULL,
    NULL,
    1, 1, 0,
    NULL, 0, 0,
    'Moon initialized at 384,400 km with circular Earth-centered speed sqrt(GM_earth/r).'
);

-- ============================================================
-- APOLLO / SATURN V LAUNCH STACK
--
-- All point objects begin at the same equatorial surface point.
-- Attachment relationships below make them one rigid stack.
-- ============================================================

INSERT INTO space_objects (
    id, slug, name, object_class, component_type,
    base_mass_kg, gm_m3_s2, radius_m,
    x_cm, y_cm, z_cm,
    vx_cm_s, vy_cm_s, vz_cm_s,
    pitch_deg, yaw_deg, roll_deg,
    pitch_rate_deg_s, yaw_rate_deg_s, roll_rate_deg_s,
    main_engine_count,
    produces_gravity, affected_by_gravity, is_fixed,
    surface_body_id, surface_locked, on_surface,
    source_note
) VALUES
(
    10,
    'saturn-v-s-ic',
    'Saturn V S-IC',
    'craft',
    'launch_stage',
    130974.796838,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    5,
    0, 1, 0,
    1, 1, 1,
    'Apollo 11 nominal S-IC dry mass: 288,750 lb. Root of the launch stack at reset.'
),
(
    11,
    'saturn-v-s-ii',
    'Saturn V S-II',
    'craft',
    'launch_stage',
    36250.195026,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    5,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 nominal S-II dry mass: 79,918 lb.'
),
(
    12,
    'saturn-v-s-ivb',
    'Saturn V S-IVB',
    'craft',
    'launch_stage',
    11339.809250,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    1,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 nominal S-IVB dry mass: 25,000 lb.'
),
(
    13,
    'saturn-v-instrument-unit',
    'Saturn V Instrument Unit',
    'structure',
    'instrument_unit',
    1953.168745,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    NULL,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 Instrument Unit mass: 4,306 lb.'
),
(
    14,
    'spacecraft-lm-adapter',
    'Spacecraft-LM Adapter',
    'structure',
    'adapter',
    1814.369480,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    NULL,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 SLA structure mass: 4,000 lb.'
),
(
    15,
    'apollo-service-module',
    'Apollo Service Module',
    'craft',
    'service_module',
    4127.690567,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    1,
    0, 1, 0,
    1, 0, 0,
    'Base mass derived from 51,243 lb Apollo 11 SM launch mass less tracked SPS and SM-RCS propellant.'
),
(
    16,
    'apollo-command-module',
    'Apollo Command Module',
    'craft',
    'command_module',
    5444.968169,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    NULL,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 CM launch mass 12,250 lb less the two tracked CM-RCS propellant loads.'
),
(
    17,
    'launch-escape-system',
    'Launch Escape System',
    'structure',
    'escape_system',
    4041.508017,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    3,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 launch escape system launch mass: 8,910 lb. Solid propellant is not separated from base mass in this first build.'
),
(
    18,
    'lm-descent-stage',
    'Lunar Module Descent Stage',
    'craft',
    'lunar_descent_stage',
    2033.454595,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    1,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 LM descent-stage dry mass: 4,483 lb.'
),
(
    19,
    'lm-ascent-stage',
    'Lunar Module Ascent Stage',
    'craft',
    'lunar_ascent_stage',
    2179.057745,
    NULL,
    NULL,
    637813660, 0, 0,
    0.000000000, 0.000000000, 0.000000000,
    0.000, 0.000, 0.000,
    0.000, 0.000, 0.000,
    1,
    0, 1, 0,
    1, 0, 0,
    'Apollo 11 LM ascent-stage dry mass: 4,804 lb, including water and oxygen but no crew per press kit.'
);

-- ============================================================
-- PROPELLANT LOADS
-- ============================================================

INSERT INTO propellant_systems (
    object_id, slug, name, propellant_type,
    capacity_kg, remaining_kg, source_note
) VALUES
(10, 'rp1', 'S-IC RP-1', 'RP-1 kerosene',
    646854.017493530, 646854.017493530,
    'Apollo 11 press kit: 1,426,069 lb RP-1.'),
(10, 'lox', 'S-IC LOX', 'liquid oxygen',
    1500417.789066350, 1500417.789066350,
    'Apollo 11 press kit: 3,307,855 lb LOX.'),

(11, 'lh2', 'S-II LH2', 'liquid hydrogen',
    71767.838373770, 71767.838373770,
    'Apollo 11 press kit: 158,221 lb LH2.'),
(11, 'lox', 'S-II LOX', 'liquid oxygen',
    372409.314802140, 372409.314802140,
    'Apollo 11 press kit: 821,022 lb LOX.'),

(12, 'lh2', 'S-IVB LH2', 'liquid hydrogen',
    19731.268095000, 19731.268095000,
    'Apollo 11 press kit: 43,500 lb LH2.'),
(12, 'lox', 'S-IVB LOX', 'liquid oxygen',
    87100.167664510, 87100.167664510,
    'Apollo 11 press kit: 192,023 lb LOX.'),

(15, 'sps-fuel', 'Service Propulsion Fuel', 'Aerozine 50',
    7126.843317440, 7126.843317440,
    'Apollo 11 flight plan: 15,712 lb SPS fuel loaded.'),
(15, 'sps-oxidizer', 'Service Propulsion Oxidizer', 'nitrogen tetroxide',
    11381.086155670, 11381.086155670,
    'Apollo 11 flight plan: 25,091 lb SPS oxidizer loaded.'),
(15, 'rcs-fuel', 'Service Module RCS Fuel', 'monomethylhydrazine',
    199.580642800, 199.580642800,
    'Apollo 11 mission report: approximately 440 lb SM-RCS fuel loaded.'),
(15, 'rcs-oxidizer', 'Service Module RCS Oxidizer', 'nitrogen tetroxide',
    408.233133000, 408.233133000,
    'Apollo 11 mission report: approximately 900 lb SM-RCS oxidizer loaded.'),

(16, 'rcs1-fuel', 'Command Module RCS System 1 Fuel', 'monomethylhydrazine',
    20.320938176, 20.320938176,
    'Apollo 11 CSM RCS report: 44.8 lb.'),
(16, 'rcs1-oxidizer', 'Command Module RCS System 1 Oxidizer', 'nitrogen tetroxide',
    35.561641808, 35.561641808,
    'Apollo 11 CSM RCS report: 78.4 lb.'),
(16, 'rcs2-fuel', 'Command Module RCS System 2 Fuel', 'monomethylhydrazine',
    20.139501228, 20.139501228,
    'Apollo 11 CSM RCS report: 44.4 lb.'),
(16, 'rcs2-oxidizer', 'Command Module RCS System 2 Oxidizer', 'nitrogen tetroxide',
    35.516282571, 35.516282571,
    'Apollo 11 CSM RCS report: 78.3 lb.'),

(18, 'dps-fuel', 'LM Descent Propulsion Fuel', 'Aerozine 50',
    3163.806780750, 3163.806780750,
    'Apollo 11 mission report: 6,975 lb DPS fuel loaded.'),
(18, 'dps-oxidizer', 'LM Descent Propulsion Oxidizer', 'nitrogen tetroxide',
    5084.316875330, 5084.316875330,
    'Apollo 11 mission report: 11,209 lb DPS oxidizer loaded.'),

(19, 'aps-fuel', 'LM Ascent Propulsion Fuel', 'Aerozine 50',
    916.256587400, 916.256587400,
    'Apollo 11 mission report: 2,020 lb APS fuel loaded.'),
(19, 'aps-oxidizer', 'LM Ascent Propulsion Oxidizer', 'nitrogen tetroxide',
    1459.660246660, 1459.660246660,
    'Apollo 11 mission report: 3,218 lb APS oxidizer loaded.'),
(19, 'rcs-total', 'LM RCS Propellant', 'hypergolic bipropellant total',
    287.123970210, 287.123970210,
    'Apollo 11 flight plan: 633.0 lb LM-RCS propellant loaded.');

-- ============================================================
-- INITIAL PHYSICAL ATTACHMENTS
--
-- Parent is the lower/supporting component. Removing an edge
-- creates independent physics roots for staging/docking later.
-- ============================================================

INSERT INTO object_attachments (
    parent_object_id, child_object_id, attachment_type
) VALUES
(10, 11, 'stage'),
(11, 12, 'stage'),
(12, 13, 'stage'),
(13, 14, 'adapter'),
(14, 15, 'stack'),
(15, 16, 'stack'),
(16, 17, 'escape_system'),
(14, 18, 'stowed_lm'),
(18, 19, 'lm_stage');

-- ============================================================
-- RESET SNAPSHOT
-- ============================================================

INSERT INTO initial_object_states (
    object_id,
    x_cm, y_cm, z_cm,
    x_subcm, y_subcm, z_subcm,
    vx_cm_s, vy_cm_s, vz_cm_s,
    pitch_deg, yaw_deg, roll_deg,
    pitch_rate_deg_s, yaw_rate_deg_s, roll_rate_deg_s,
    surface_locked, on_surface
)
SELECT
    id,
    x_cm, y_cm, z_cm,
    x_subcm, y_subcm, z_subcm,
    vx_cm_s, vy_cm_s, vz_cm_s,
    pitch_deg, yaw_deg, roll_deg,
    pitch_rate_deg_s, yaw_rate_deg_s, roll_rate_deg_s,
    surface_locked, on_surface
FROM space_objects;

INSERT INTO initial_object_attachments (
    parent_object_id,
    child_object_id,
    attachment_type
)
SELECT
    parent_object_id,
    child_object_id,
    attachment_type
FROM object_attachments;
