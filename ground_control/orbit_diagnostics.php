<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/variables.php';
require_once __DIR__ . '/../shared/physical_constants.php';
require_once __DIR__ . '/../shared/orbital_mechanics.php';

$body = strtolower((string)($_GET['body'] ?? 'earth'));
if (!in_array($body, ['earth','moon'], true)) {
    $body = 'earth';
}

$vehicle = strtoupper((string)($_GET['vehicle'] ?? 'CM'));
if (!in_array($vehicle, ['CM','LM'], true)) {
    $vehicle = 'CM';
}

$conn->query(
    "CREATE TABLE IF NOT EXISTS orbit_diagnostic_baseline (
        vehicle ENUM('CM','LM') NOT NULL,
        body ENUM('EARTH','MOON') NOT NULL,
        specific_energy DOUBLE NOT NULL,
        angular_momentum DOUBLE NOT NULL,
        eccentricity DOUBLE NOT NULL,
        semi_major_axis DOUBLE NULL,
        periapsis_radius DOUBLE NULL,
        apoapsis_radius DOUBLE NULL,
        captured_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
        PRIMARY KEY (vehicle, body)
    ) ENGINE=InnoDB"
);

if ($vehicle === 'CM') {
    $position = [(float)$xCM,(float)$yCM,(float)$zCM];
    $velocity = [(float)$delta_xCM,(float)$delta_yCM,(float)$delta_zCM];
} else {
    $position = [(float)$xLM,(float)$yLM,(float)$zLM];
    $velocity = [(float)$delta_xLM,(float)$delta_yLM,(float)$delta_zLM];
}

if ($body === 'moon') {
    $origin = [(float)$xMOON,(float)$yMOON,(float)$zMOON];
    $bodyVelocity = [
        (float)$delta_xMOON,
        (float)$delta_yMOON,
        (float)$delta_zMOON,
    ];
    $mu = (float)$gravitational_constant*(float)$moon_mass;
    $bodyRadius = (float)$moon_radius;
    $bodyDb = 'MOON';
} else {
    $origin = [
        (float)$earth_center_x,
        (float)$earth_center_y,
        (float)$earth_center_z,
    ];
    $bodyVelocity = [0.0,0.0,0.0];
    $mu = (float)$gravitational_constant*(float)$earth_mass;
    $bodyRadius = (float)$earth_radius;
    $bodyDb = 'EARTH';
}

$relativeVelocity = [
    $velocity[0]-$bodyVelocity[0],
    $velocity[1]-$bodyVelocity[1],
    $velocity[2]-$bodyVelocity[2],
];

$elements = moonSimOrbitalElements(
    $position,
    $relativeVelocity,
    $origin,
    $mu
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['capture_baseline'])) {
        $stmt = $conn->prepare(
            "INSERT INTO orbit_diagnostic_baseline (
                vehicle, body,
                specific_energy, angular_momentum, eccentricity,
                semi_major_axis, periapsis_radius, apoapsis_radius,
                captured_at
             ) VALUES (?,?,?,?,?,?,?,?,NOW(6))
             ON DUPLICATE KEY UPDATE
                specific_energy=VALUES(specific_energy),
                angular_momentum=VALUES(angular_momentum),
                eccentricity=VALUES(eccentricity),
                semi_major_axis=VALUES(semi_major_axis),
                periapsis_radius=VALUES(periapsis_radius),
                apoapsis_radius=VALUES(apoapsis_radius),
                captured_at=VALUES(captured_at)"
        );

        $energy=(float)$elements['specific_energy'];
        $h=(float)$elements['angular_momentum'];
        $ecc=(float)$elements['eccentricity'];
        $a=is_finite((float)$elements['semi_major_axis'])
            ? (float)$elements['semi_major_axis']
            : null;
        $rp=$elements['periapsis_radius'] !== null
            ? (float)$elements['periapsis_radius']
            : null;
        $ra=$elements['apoapsis_radius'] !== null
            ? (float)$elements['apoapsis_radius']
            : null;

        $stmt->bind_param(
            'ssdddddd',
            $vehicle,
            $bodyDb,
            $energy,
            $h,
            $ecc,
            $a,
            $rp,
            $ra
        );
        $stmt->execute();
    }

    if (isset($_POST['clear_baseline'])) {
        $stmt=$conn->prepare(
            "DELETE FROM orbit_diagnostic_baseline
             WHERE vehicle=? AND body=?"
        );
        $stmt->bind_param('ss',$vehicle,$bodyDb);
        $stmt->execute();
    }

    $query=http_build_query([
        'vehicle'=>$vehicle,
        'body'=>$body,
    ]);
    header('Location: orbit_diagnostics.php?'.$query);
    exit;
}

$baseline=null;
$stmt=$conn->prepare(
    "SELECT *
     FROM orbit_diagnostic_baseline
     WHERE vehicle=? AND body=?"
);
$stmt->bind_param('ss',$vehicle,$bodyDb);
$stmt->execute();
$result=$stmt->get_result();
if($result){
    $baseline=$result->fetch_assoc() ?: null;
}

function diagNumber(?float $value, int $decimals=6): string
{
    if($value===null || !is_finite($value)){
        return '—';
    }
    return number_format($value,$decimals,'.',',');
}

function diagRelativeDrift(float $value, ?float $baseline): ?float
{
    if($baseline===null){
        return null;
    }
    $scale=max(abs($baseline),1.0e-15);
    return ($value-$baseline)/$scale;
}

$periapsisAltitude=$elements['periapsis_radius'] !== null
    ? (float)$elements['periapsis_radius']-$bodyRadius
    : null;
$apoapsisAltitude=$elements['apoapsis_radius'] !== null
    ? (float)$elements['apoapsis_radius']-$bodyRadius
    : null;

$drifts=[];
if($baseline){
    $drifts=[
        'energy'=>diagRelativeDrift(
            (float)$elements['specific_energy'],
            (float)$baseline['specific_energy']
        ),
        'h'=>diagRelativeDrift(
            (float)$elements['angular_momentum'],
            (float)$baseline['angular_momentum']
        ),
        'ecc'=>(float)$elements['eccentricity']-(float)$baseline['eccentricity'],
        'a'=>diagRelativeDrift(
            (float)$elements['semi_major_axis'],
            $baseline['semi_major_axis']!==null
                ? (float)$baseline['semi_major_axis']
                : null
        ),
        'rp'=>diagRelativeDrift(
            (float)($elements['periapsis_radius'] ?? 0.0),
            $baseline['periapsis_radius']!==null
                ? (float)$baseline['periapsis_radius']
                : null
        ),
        'ra'=>diagRelativeDrift(
            (float)($elements['apoapsis_radius'] ?? 0.0),
            $baseline['apoapsis_radius']!==null
                ? (float)$baseline['apoapsis_radius']
                : null
        ),
    ];
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orbit Diagnostics</title>
<style>
body{margin:0;padding:20px;background:#101010;color:#f1e6a8;font-family:Consolas,monospace}
main{max-width:1050px;margin:auto}
.panel{background:#252525;border:2px inset #666;padding:16px;margin-bottom:16px}
.controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
a,button,select{background:#3d3d3d;color:#fff;border:1px solid #777;padding:8px 12px;text-decoration:none}
table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #555;text-align:right}
th:first-child,td:first-child{text-align:left}
.good{color:#9be79b}.warn{color:#ffd86b}
small{color:#aaa}
</style>
</head>
<body><main>
<div class="panel">
<h1>Orbit Conservation / Elements</h1>
<div class="controls">
<a href="?vehicle=CM&body=<?= htmlspecialchars($body) ?>">CM</a>
<a href="?vehicle=LM&body=<?= htmlspecialchars($body) ?>">LM</a>
<a href="?vehicle=<?= htmlspecialchars($vehicle) ?>&body=earth">Earth-relative</a>
<a href="?vehicle=<?= htmlspecialchars($vehicle) ?>&body=moon">Moon-relative</a>
<a href="../ground.php">Ground</a>
</div>
<p>
<strong><?= htmlspecialchars($vehicle) ?></strong>
relative to <strong><?= htmlspecialchars($bodyDb) ?></strong>
</p>
</div>

<div class="panel">
<table>
<tr><th>Quantity</th><th>Current</th><th>Drift from captured baseline</th></tr>
<tr>
<td>Radius</td>
<td><?= diagNumber((float)$elements['radius']/1000.0,3) ?> km</td>
<td>—</td>
</tr>
<tr>
<td>Speed</td>
<td><?= diagNumber((float)$elements['speed'],6) ?> m/s</td>
<td>—</td>
</tr>
<tr>
<td>Specific orbital energy</td>
<td><?= diagNumber((float)$elements['specific_energy'],6) ?> J/kg</td>
<td><?= isset($drifts['energy']) ? sprintf('%+.6e',$drifts['energy']) : '—' ?></td>
</tr>
<tr>
<td>Specific angular momentum</td>
<td><?= diagNumber((float)$elements['angular_momentum'],6) ?> m²/s</td>
<td><?= isset($drifts['h']) ? sprintf('%+.6e',$drifts['h']) : '—' ?></td>
</tr>
<tr>
<td>Eccentricity</td>
<td><?= diagNumber((float)$elements['eccentricity'],9) ?></td>
<td><?= isset($drifts['ecc']) ? sprintf('%+.6e',$drifts['ecc']) : '—' ?></td>
</tr>
<tr>
<td>Semi-major axis</td>
<td><?= diagNumber(
    is_finite((float)$elements['semi_major_axis'])
        ? (float)$elements['semi_major_axis']/1000.0
        : null,
    3
) ?> km</td>
<td><?= isset($drifts['a']) ? sprintf('%+.6e',$drifts['a']) : '—' ?></td>
</tr>
<tr>
<td>Periapsis altitude</td>
<td><?= diagNumber($periapsisAltitude!==null?$periapsisAltitude/1000.0:null,3) ?> km</td>
<td><?= isset($drifts['rp']) ? sprintf('%+.6e',$drifts['rp']) : '—' ?></td>
</tr>
<tr>
<td>Apoapsis altitude</td>
<td><?= diagNumber($apoapsisAltitude!==null?$apoapsisAltitude/1000.0:null,3) ?> km</td>
<td><?= isset($drifts['ra']) ? sprintf('%+.6e',$drifts['ra']) : '—' ?></td>
</tr>
</table>
<p><small>
For a controlled Earth-only two-body test, energy and angular momentum should
remain essentially constant. During a real Earth+Moon mission, Earth-relative
osculating elements can legitimately change because lunar gravity perturbs the craft.
</small></p>
</div>

<div class="panel">
<form method="post" class="controls">
<button name="capture_baseline" value="1">Capture Current Baseline</button>
<?php if($baseline): ?>
<button name="clear_baseline" value="1">Clear Baseline</button>
<span>Captured <?= htmlspecialchars((string)$baseline['captured_at']) ?></span>
<?php endif; ?>
</form>
</div>
</main></body>
</html>
