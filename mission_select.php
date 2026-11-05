<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

// Mission selection changes the active simulation. Fail closed without an
// operator password provisioned outside this public repository.
$operatorPassword = getenv('MOON_OPERATOR_PASSWORD');
$providedPassword = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
if (!is_string($operatorPassword) || $operatorPassword === ''
    || !hash_equals($operatorPassword, $providedPassword)) {
    header('WWW-Authenticate: Basic realm="Moon Simulator Mission Control"');
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Mission Control authentication required. Configure MOON_OPERATOR_PASSWORD on the server.');
}
session_start();
require __DIR__ . '/physics/db.php';
require_once __DIR__ . '/shared/mission_profiles.php';
moonSimEnsureMissionProfileTables($conn);
if (!isset($_SESSION['mission_token'])) $_SESSION['mission_token'] = bin2hex(random_bytes(16));
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['mission_token'], (string)($_POST['token'] ?? ''))) {
        $message = 'Invalid request.';
    } else {
        $id = filter_input(INPUT_POST, 'profile', FILTER_VALIDATE_INT);
        // Use the same lock as the worker; never change a profile during flight.
        $result = $conn->query("SELECT GET_LOCK('moon_sim_physics_worker', 1) AS held");
        $locked = $result && (int)($result->fetch_assoc()['held'] ?? 0) === 1;
        if (!$locked) {
            $message = 'Physics worker is busy.';
        } else {
            try {
                $state = $conn->query("SELECT status FROM systems WHERE system='sim_on' LIMIT 1");
                $clock = $conn->query("SELECT seconds FROM mission_clock WHERE id=1 LIMIT 1");
                $off = $state && (int)($state->fetch_assoc()['status'] ?? 1) === 0;
                $reset = $clock && (int)($clock->fetch_assoc()['seconds'] ?? 0) === -900;
                if (!$off || !$reset) {
                    $message = 'Reset the simulation before choosing a mission.';
                } elseif (!$id) {
                    $message = 'Invalid mission.';
                } else {
                    $stmt = $conn->prepare('UPDATE mission_runtime SET profile_id=?, selected_at=NOW(6) WHERE id=1 AND EXISTS (SELECT 1 FROM mission_profiles WHERE id=?)');
                    $stmt->bind_param('ii', $id, $id);
                    $message = $stmt->execute() && $stmt->affected_rows === 1 ? 'Mission selected.' : 'No change made.';
                }
            } finally {
                $conn->query("SELECT RELEASE_LOCK('moon_sim_physics_worker')");
            }
        }
    }
}
$current = moonSimActiveMissionProfile($conn);
$all = $conn->query('SELECT id,name FROM mission_profiles ORDER BY id');
function escapeMission(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mission Selection</title>
<style>body{background:#151b23;color:#eee;font:16px Arial;padding:25px}main{max-width:650px;margin:auto}section{border:1px solid #667;padding:20px}select,button{font:inherit;padding:10px;margin:10px;background:#27323c;color:white}</style></head>
<body><main><h1>Mission Selection</h1><section><p>Active mission: <?=escapeMission((string)($current['name'] ?? 'unknown'))?></p><p><?=escapeMission($message)?></p>
<form method="post"><input type="hidden" name="token" value="<?=escapeMission($_SESSION['mission_token'])?>">
<select name="profile" aria-label="Mission">
<?php if ($all) while ($row=$all->fetch_assoc()): ?>
<option value="<?=(int)$row['id']?>" <?=(int)$row['id']===(int)($current['id']??0)?'selected':''?>><?=escapeMission($row['name'])?></option>
<?php endwhile; ?></select><button type="submit">Select while reset</button></form>
<p>Selecting a mission does not automatically change launch engines or staging.</p></section></main></body></html>
