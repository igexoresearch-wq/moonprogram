<?php
/**
 * Fast dependency-free regression checks for contact geometry and mission
 * event timing. Run: php tests/mission_geometry_smoke.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../physics/contact.php';
require_once __DIR__ . '/../shared/mission_profiles.php';

function checkSmoke(bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}

$hit = moonSimSegmentSphereHit([20,0,0],[-20,0,0],[0,0,0],[0,0,0],10.0);
checkSmoke($hit !== null && abs($hit-0.25)<1e-10, 'Fast sphere crossing must be detected.');
$miss = moonSimSegmentSphereHit([20,20,0],[-20,20,0],[0,0,0],[0,0,0],10.0);
checkSmoke($miss === null, 'Non-intersecting trajectory must not collide.');
$moving = moonSimSegmentSphereHit([20,0,0],[20,0,0],[0,0,0],[30,0,0],10.0);
checkSmoke($moving !== null && abs($moving-(1.0/3.0))<1e-10, 'Moving-body collision must be relative.');
$events = ['cutoff'=>['seconds'=>135.2]];
checkSmoke(moonSimMissionEventDue($events,'cutoff',135.0), 'Event should fire on nearest guidance second.');
checkSmoke(!moonSimMissionEventDue($events,'cutoff',134.0), 'Event must not fire early.');
checkSmoke(!moonSimMissionEventDue($events,'missing',135.0), 'Missing events must be ignored.');
echo "PASS: contact and mission-event smoke tests\n";
