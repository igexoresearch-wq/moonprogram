-- Moon Simulator launch/reset baseline
-- Generated from the active repair baseline on 2026-10-30.
-- This is a recovery/testing snapshot, not a replacement for physics/reset.php.

START TRANSACTION;

UPDATE command_module SET value = CASE attribute
    WHEN 'x' THEN 10006371000
    WHEN 'y' THEN 10000000000
    WHEN 'z' THEN 10000000000
    WHEN 'delta_x' THEN 0
    WHEN 'delta_y' THEN 0
    WHEN 'delta_z' THEN 0
    WHEN 'dock' THEN 0
    WHEN 'stage1' THEN 1
    WHEN 'stage2' THEN 1
    WHEN 'stage3' THEN 1
    WHEN 's1fuel' THEN 2147271.806559880
    WHEN 's2fuel' THEN 444177.153175910
    WHEN 's3fuel' THEN 106831.435759510
    WHEN 'service_module' THEN 1
    WHEN 'fuel' THEN 18507.929473110
    WHEN 'RCS' THEN 608.902397488
    WHEN 'pitch' THEN 0
    WHEN 'roll' THEN 0
    WHEN 'yaw' THEN 0
    WHEN 'pitch_rate' THEN 0
    WHEN 'roll_rate' THEN 0
    WHEN 'yaw_rate' THEN 0
    WHEN 'thrust' THEN 0
    WHEN 'parachutes_deployed' THEN 0
    WHEN 'escape' THEN 1
    WHEN 'ABORT' THEN 0
    WHEN 'staging' THEN 0
    WHEN 'thrusters' THEN 0
    WHEN 'heat_shield' THEN 1420000
    WHEN 'crash' THEN 0
    WHEN 'engine_on' THEN 0
    WHEN 'escape_fuel' THEN 30
    WHEN 'excape_fuel' THEN 30
    ELSE value
END
WHERE attribute IN (
    'x','y','z','delta_x','delta_y','delta_z','dock',
    'stage1','stage2','stage3','s1fuel','s2fuel','s3fuel',
    'service_module','fuel','RCS','pitch','roll','yaw',
    'pitch_rate','roll_rate','yaw_rate','thrust','parachutes_deployed',
    'escape','ABORT','staging','thrusters','heat_shield','crash',
    'engine_on','escape_fuel','excape_fuel'
);

UPDATE telemetry SET value = CASE attribute
    WHEN 'x' THEN 10006370999
    WHEN 'y' THEN 10000000000
    WHEN 'z' THEN 10000000000
    WHEN 'delta_x' THEN 0
    WHEN 'delta_y' THEN 0
    WHEN 'delta_z' THEN 0
    WHEN 'descent' THEN 1
    WHEN 'map_x' THEN 0
    WHEN 'map_y' THEN 0
    WHEN 'map_zoom' THEN 1
    WHEN 'fuel' THEN 8210.021897000
    WHEN 'RCS' THEN 273.969791480
    WHEN 'pitch' THEN 0
    WHEN 'roll' THEN 0
    WHEN 'yaw' THEN 0
    WHEN 'pitch_rate' THEN 0
    WHEN 'roll_rate' THEN 0
    WHEN 'yaw_rate' THEN 0
    WHEN 'thrust' THEN 0
    WHEN 'fuel_ascent' THEN 2365.030617180
    WHEN 'crash' THEN 0
    ELSE value
END
WHERE attribute IN (
    'x','y','z','delta_x','delta_y','delta_z','descent',
    'map_x','map_y','map_zoom','fuel','RCS',
    'pitch','roll','yaw','pitch_rate','roll_rate','yaw_rate',
    'thrust','fuel_ascent','crash'
);

UPDATE systems SET status = 1 WHERE id BETWEEN 1 AND 10;
UPDATE systems SET probability = 10000 WHERE id BETWEEN 1 AND 13;
UPDATE systems SET status = 0 WHERE id BETWEEN 11 AND 13;
UPDATE systems SET status = 0 WHERE id BETWEEN 21 AND 22;

UPDATE CMguidance
SET execute = 0,
    terminate = 0;

UPDATE CMguidance
SET roll = 0,
    pitch = 0,
    yaw = 0,
    roll_rate = 0,
    pitch_rate = 0,
    yaw_rate = 0,
    thrust = 0,
    seconds = 0,
    hold = 0,
    altitude = 0,
    delta_V = 0,
    delta_x = 0,
    delta_y = 0,
    delta_z = 0
WHERE id = 1;

UPDATE CMprogram_register
SET program_number = 0,
    seconds = NULL;

UPDATE mission_clock
SET launch_events = 0,
    minutes = 0,
    seconds = -900
WHERE id = 1;

UPDATE EngineStatus
SET status = 'OFF',
    operational_status = 'OK',
    probability = 10000;

TRUNCATE TABLE CMtelemetry;

-- Optional runtime tables introduced by the repair branch are deliberately
-- omitted here because older database snapshots may not contain them.
-- physics/reset.php safely resets them when present.

COMMIT;
