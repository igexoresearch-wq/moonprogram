<?php
declare(strict_types=1);

/**
 * Mission-profile configuration.
 *
 * Profiles supply targets/events to the existing simulator. They never replace
 * the common physics engine with mission-specific branches.
 */

function moonSimEnsureMissionProfileTables(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS mission_profiles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(80) NOT NULL,
            name VARCHAR(160) NOT NULL,
            description TEXT NULL,
            launch_guidance_mode ENUM('STATE_DRIVEN','TIMED_VALIDATION')
                NOT NULL DEFAULT 'STATE_DRIVEN',
            parking_periapsis_m DOUBLE NOT NULL DEFAULT 160000,
            parking_apoapsis_m DOUBLE NOT NULL DEFAULT 180000,
            parking_max_radial_speed_m_s DOUBLE NOT NULL DEFAULT 200,
            created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (id),
            UNIQUE KEY uq_mission_profile_slug (slug)
        ) ENGINE=InnoDB"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mission_launch_events (
            profile_id INT UNSIGNED NOT NULL,
            event_key VARCHAR(80) NOT NULL,
            event_seconds DOUBLE NOT NULL,
            notes VARCHAR(500) NULL,
            PRIMARY KEY (profile_id,event_key),
            CONSTRAINT fk_mission_launch_profile
                FOREIGN KEY (profile_id)
                REFERENCES mission_profiles(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS mission_runtime (
            id TINYINT UNSIGNED NOT NULL,
            profile_id INT UNSIGNED NOT NULL,
            selected_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (id),
            CONSTRAINT fk_mission_runtime_profile
                FOREIGN KEY (profile_id)
                REFERENCES mission_profiles(id)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB"
    );

    // Generic profile preserves the simulator's state-driven launch style.
    $conn->query(
        "INSERT INTO mission_profiles (
            slug,name,description,launch_guidance_mode,
            parking_periapsis_m,parking_apoapsis_m,
            parking_max_radial_speed_m_s
         ) VALUES (
            'generic-apollo-training',
            'Generic Apollo Lunar Training',
            'State-driven training profile using the common Saturn/CSM/LM simulator.',
            'STATE_DRIVEN',
            160000,180000,200
         )
         ON DUPLICATE KEY UPDATE slug=slug"
    );

    // Historical Apollo 11 validation profile. Times are GET seconds from
    // liftoff, rounded only by the one-second guidance cadence where required.
    $conn->query(
        "INSERT INTO mission_profiles (
            slug,name,description,launch_guidance_mode,
            parking_periapsis_m,parking_apoapsis_m,
            parking_max_radial_speed_m_s
         ) VALUES (
            'apollo-11',
            'Apollo 11 / AS-506',
            'Historical launch-event validation profile for AS-506, CSM-107 and LM-5.',
            'TIMED_VALIDATION',
            183162,185941,200
         )
         ON DUPLICATE KEY UPDATE
            name=VALUES(name),
            description=VALUES(description),
            launch_guidance_mode=VALUES(launch_guidance_mode),
            parking_periapsis_m=VALUES(parking_periapsis_m),
            parking_apoapsis_m=VALUES(parking_apoapsis_m),
            parking_max_radial_speed_m_s=VALUES(parking_max_radial_speed_m_s)"
    );

    $apollo11Result = $conn->query(
        "SELECT id FROM mission_profiles WHERE slug='apollo-11' LIMIT 1"
    );
    $apollo11 = $apollo11Result
        ? (int)($apollo11Result->fetch_assoc()['id'] ?? 0)
        : 0;

    if ($apollo11 > 0) {
        $events = [
            'liftoff' => [0.6, 'First motion/liftoff reference'],
            'pitch_roll_start' => [12.4, 'Pitch and roll maneuver start'],
            'roll_complete' => [31.1, 'Roll maneuver complete'],
            'sic_center_cutoff' => [135.2, 'S-IC center F-1 cutoff'],
            'sic_outboard_cutoff' => [161.6, 'S-IC outboard cutoff'],
            'sic_separation' => [162.3, 'S-IC/S-II separation'],
            'sii_ignition' => [163.0, 'S-II ignition'],
            'les_jettison' => [197.9, 'Launch Escape System jettison'],
            'sii_center_cutoff' => [460.6, 'S-II center J-2 cutoff'],
            'sii_outboard_cutoff' => [548.2, 'S-II outboard cutoff'],
            'sii_separation' => [549.0, 'S-II/S-IVB separation'],
            'sivb_first_ignition' => [552.2, 'S-IVB first ignition'],
            'sivb_first_cutoff' => [699.3, 'S-IVB first cutoff'],
            'parking_orbit_insertion' => [709.3, 'Parking-orbit insertion reference'],
            'sivb_second_ignition' => [9856.2, 'TLI restart ignition'],
            'sivb_second_cutoff' => [10203.0, 'TLI cutoff'],
        ];

        $stmt = $conn->prepare(
            "INSERT INTO mission_launch_events (
                profile_id,event_key,event_seconds,notes
             ) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE
                event_seconds=VALUES(event_seconds),
                notes=VALUES(notes)"
        );

        foreach ($events as $key => [$seconds,$notes]) {
            $stmt->bind_param('isds',$apollo11,$key,$seconds,$notes);
            $stmt->execute();
        }
    }

    $runtime = $conn->query("SELECT profile_id FROM mission_runtime WHERE id=1");
    if (!$runtime || $runtime->num_rows === 0) {
        $generic = $conn->query(
            "SELECT id
             FROM mission_profiles
             WHERE slug='generic-apollo-training'
             LIMIT 1"
        );
        $genericId = $generic
            ? (int)($generic->fetch_assoc()['id'] ?? 0)
            : 0;

        if ($genericId > 0) {
            $conn->query(
                "INSERT INTO mission_runtime (id,profile_id)
                 VALUES (1,$genericId)"
            );
        }
    }
}

function moonSimActiveMissionProfile(mysqli $conn): array
{
    moonSimEnsureMissionProfileTables($conn);

    $result = $conn->query(
        "SELECT p.*
         FROM mission_runtime r
         JOIN mission_profiles p ON p.id=r.profile_id
         WHERE r.id=1
         LIMIT 1"
    );

    return $result && ($row=$result->fetch_assoc())
        ? $row
        : [];
}

function moonSimMissionLaunchEvents(mysqli $conn, int $profileId): array
{
    $events = [];
    $stmt = $conn->prepare(
        "SELECT event_key,event_seconds,notes
         FROM mission_launch_events
         WHERE profile_id=?
         ORDER BY event_seconds,event_key"
    );
    $stmt->bind_param('i',$profileId);
    $stmt->execute();
    $result=$stmt->get_result();

    if($result){
        while($row=$result->fetch_assoc()){
            $events[$row['event_key']]=[
                'seconds'=>(float)$row['event_seconds'],
                'notes'=>(string)($row['notes'] ?? ''),
            ];
        }
    }

    return $events;
}

function moonSimMissionEventDue(
    array $events,
    string $eventKey,
    float $missionSeconds,
    float $cadenceSeconds=1.0
): bool {
    if(!isset($events[$eventKey])){
        return false;
    }

    $event=(float)$events[$eventKey]['seconds'];
    $half=max(0.05,0.5*$cadenceSeconds);

    return $missionSeconds >= $event-$half
        && $missionSeconds < $event+$half;
}
?>