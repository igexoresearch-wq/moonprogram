<?php
declare(strict_types=1);
$pw=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($pw)||$pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){http_response_code(401);exit;}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/flight_history.php';
moonSimEnsureFlightHistory($conn);
$first=$conn->query('SELECT * FROM flight_history ORDER BY id ASC LIMIT 1')->fetch_assoc();
$last=$conn->query('SELECT * FROM flight_history ORDER BY id DESC LIMIT 1')->fetch_assoc();
$count=(int)$conn->query('SELECT COUNT(*) n FROM flight_history')->fetch_assoc()['n'];
$label=($last['burn_status']??'NO DATA');
$events=$conn->query("SELECT mission_seconds,event_key,old_value,new_value FROM flight_event_log ORDER BY id DESC LIMIT 100");
?><!doctype html><html><title>Mission Review</title><body style="background:#121c16;color:#d9e7c9;font:17px monospace;padding:30px">
<h1>MISSION REVIEW / TELEMETRY REPORT</h1>
<p>This report summarizes recorded state. It does not assert mission success or diagnose an unrecorded abort.</p>
<p>Recorded samples: <?=htmlspecialchars((string)$count)?>; first MET <?=htmlspecialchars((string)($first['met']??'-'))?>; latest MET <?=htmlspecialchars((string)($last['met']??'-'))?></p>
<p>Latest burn status: <?=htmlspecialchars((string)$label)?></p>
<p>Latest CM fuel: <?=htmlspecialchars((string)($last['cm_fuel']??'-'))?> kg</p>
<h2>RECORDED TRANSITIONS / ABORT EVIDENCE</h2>
<table border="1"><tr><th>MET</th><th>Event</th><th>Previous</th><th>Observed</th></tr>
<?php if($events)while($e=$events->fetch_assoc()):?><tr><?php foreach($e as $value):?><td><?=htmlspecialchars((string)$value,ENT_QUOTES)?></td><?php endforeach;?></tr><?php endwhile;?></table>
<p>Abort or crash state is reported only when an actual recorded transition is present.</p>
<a href="flight_timeline.php" style="color:#ecdc9f">OPEN RECORDED FLIGHT TIMELINE</a>
</body></html>
