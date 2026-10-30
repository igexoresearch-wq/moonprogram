<?php
declare(strict_types=1);

require __DIR__ . '/../physics/contact.php';

function failContact(string $message): void
{
    fwrite(STDERR, "FAIL: $message\n");
    exit(1);
}

// Fast tunneling case: the craft starts outside the left side, crosses the
// entire moving sphere, and ends outside the right side in the same step.
$radius = 100.0;
$bodyStart = [0.0,0.0,0.0];
$bodyEnd = [10.0,0.0,0.0];
$craftStart = [-250.0,0.0,0.0];
$craftEnd = [260.0,0.0,0.0];

$t = moonSimSegmentSphereHit(
    $craftStart,
    $craftEnd,
    $bodyStart,
    $bodyEnd,
    $radius
);

if ($t === null || $t <= 0.0 || $t >= 1.0) {
    failContact('moving-sphere tunneling intersection was not detected');
}

$bodyAtHit = moonSimInterpolate($bodyStart,$bodyEnd,$t);
$craftAtHit = moonSimInterpolate($craftStart,$craftEnd,$t);
$dx = $craftAtHit[0]-$bodyAtHit[0];
$dy = $craftAtHit[1]-$bodyAtHit[1];
$dz = $craftAtHit[2]-$bodyAtHit[2];
$distance = sqrt($dx*$dx+$dy*$dy+$dz*$dz);

if (abs($distance-$radius) > 1.0e-8) {
    failContact('computed moving-body contact point is not on sphere surface');
}

// Miss case.
$miss = moonSimSegmentSphereHit(
    [-250.0,150.0,0.0],
    [260.0,150.0,0.0],
    $bodyStart,
    $bodyEnd,
    $radius
);

if ($miss !== null) {
    failContact('non-intersecting path was classified as collision');
}

// Starting inside is immediate contact.
$inside = moonSimSegmentSphereHit(
    [0.0,0.0,0.0],
    [20.0,0.0,0.0],
    $bodyStart,
    $bodyEnd,
    $radius
);

if ($inside !== 0.0) {
    failContact('inside-sphere state did not resolve as immediate contact');
}

echo "PASS: moving-body continuous collision regression.\n";
?>
