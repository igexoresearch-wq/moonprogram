-- Moon Program Objective 1.3 — Saturn V stage runtime
-- Apply once with a database account authorized to create tables.
-- The physics worker also creates this table on first use if privileges permit.
-- No EngineStatus rows are invented here: existing real engine inventory must
-- be audited on /launch_diagnostics.php before a flight is called validated.
CREATE TABLE IF NOT EXISTS saturn_stage_runtime (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    sic_ignited TINYINT(1) NOT NULL DEFAULT 0,
    sii_ignited TINYINT(1) NOT NULL DEFAULT 0,
    sivb_ignited TINYINT(1) NOT NULL DEFAULT 0,
    sivb_restart_armed TINYINT(1) NOT NULL DEFAULT 1,
    staging_hold TINYINT(1) NOT NULL DEFAULT 0,
    last_seconds INT NULL
) ENGINE=InnoDB;
INSERT IGNORE INTO saturn_stage_runtime (id) VALUES (1);

-- Read-only deployment checks:
SELECT * FROM saturn_stage_runtime WHERE id=1;
SELECT engine_stage,engine_name,status,operational_status
FROM EngineStatus
WHERE engine_stage IN ('First Stage','Second Stage','Third Stage')
  AND component='Combustion Chamber'
ORDER BY engine_stage,engine_name;
SELECT attribute,value FROM command_module
WHERE attribute IN ('stage1','stage2','stage3','staging','thrust',
                    's1fuel','s2fuel','s3fuel','ABORT');
