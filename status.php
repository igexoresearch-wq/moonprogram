<?php
declare(strict_types=1);

require __DIR__ . '/physics/db.php';

header('Content-Type: text/html; charset=utf-8');

function moonStatusHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$buildVersion = 'unknown';
$buildFile = __DIR__ . '/BUILD_VERSION';
if (is_file($buildFile)) {
    $buildVersion = trim((string)file_get_contents($buildFile));
}

$gitCommit = null;
if (is_dir(__DIR__ . '/.git') && function_exists('shell_exec')) {
    $cmd = 'git -C ' . escapeshellarg(__DIR__) . ' rev-parse --short HEAD 2>/dev/null';
    $candidate = trim((string)@shell_exec($cmd));
    if ($candidate !== '') {
        $gitCommit = $candidate;
    }
}

$checks = [];
$requiredTables = [
    'command_module',
    'telemetry',
    'moon',
    'systems',
    'mission_clock',
    'EngineStatus',
    'CMguidance',
    'CMprogram_register',
];

foreach ($requiredTables as $table) {
    $safe = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$safe'");
    $checks[$table] = $result && $result->num_rows > 0;
}

$simOn = null;
$result = $conn->query(
    "SELECT status FROM systems WHERE system='sim_on' LIMIT 1"
);
if ($result && ($row = $result->fetch_assoc())) {
    $simOn = (int)$row['status'];
}

$missionSeconds = null;
$result = $conn->query(
    "SELECT seconds FROM mission_clock WHERE id=1 LIMIT 1"
);
if ($result && ($row = $result->fetch_assoc())) {
    $missionSeconds = (int)$row['seconds'];
}

$runtime = null;
$result = $conn->query(
    "SELECT last_wall_at, accumulator_seconds, last_tick_at
     FROM simulation_runtime
     WHERE id=1"
);
if ($result && ($row = $result->fetch_assoc())) {
    $runtime = $row;
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Moon Simulator Status</title>
<style>
body{font-family:Consolas,monospace;background:#171717;color:#eee;margin:0;padding:24px}
main{max-width:900px;margin:auto}
section{background:#2c2c2c;border:1px solid #666;padding:16px;margin:0 0 16px}
h1,h2{margin-top:0}
.ok{color:#9ee39e}.bad{color:#ff8d8d}.muted{color:#aaa}
table{border-collapse:collapse;width:100%}td,th{padding:6px 8px;border-bottom:1px solid #555;text-align:left}
code{color:#ffe77a}
</style>
</head>
<body><main>
<h1>Moon Simulator Status</h1>
<section>
<h2>Source</h2>
<p><strong>Build:</strong><br><?= nl2br(moonStatusHtml($buildVersion)) ?></p>
<p><strong>Git commit:</strong>
<?= $gitCommit !== null ? '<code>'.moonStatusHtml($gitCommit).'</code>' : '<span class="muted">not available on this deployment</span>' ?>
</p>
</section>
<section>
<h2>Runtime</h2>
<table>
<tr><th>Simulator</th><td><?= $simOn === 1 ? '<span class="ok">RUNNING</span>' : '<span class="muted">RESET / OFF</span>' ?></td></tr>
<tr><th>Mission seconds</th><td><?= $missionSeconds !== null ? (int)$missionSeconds : 'unknown' ?></td></tr>
<tr><th>Last wall timestamp</th><td><?= moonStatusHtml((string)($runtime['last_wall_at'] ?? 'not initialized')) ?></td></tr>
<tr><th>Last physics tick</th><td><?= moonStatusHtml((string)($runtime['last_tick_at'] ?? 'not initialized')) ?></td></tr>
<tr><th>Accumulator</th><td><?= moonStatusHtml((string)($runtime['accumulator_seconds'] ?? '0')) ?> s</td></tr>
</table>
</section>
<section>
<h2>Required database tables</h2>
<table>
<?php foreach ($checks as $table => $ok): ?>
<tr><th><?= moonStatusHtml($table) ?></th><td class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'MISSING' ?></td></tr>
<?php endforeach; ?>
</table>
</section>
</main></body></html>
