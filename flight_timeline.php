<?php
declare(strict_types=1);
$pw=getenv('MOON_OPERATOR_PASSWORD');
if(!is_string($pw)||$pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){http_response_code(401);exit;}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/flight_history.php';
moonSimEnsureFlightHistory($conn);
$rows=$conn->query('SELECT met,cm_x,cm_y,cm_z,cm_vx,cm_vy,cm_vz,cm_fuel,lm_fuel,burn_status FROM flight_history ORDER BY id DESC LIMIT 200');
?><!doctype html><html><title>Flight Timeline</title><body><h1>Flight Timeline</h1>
<p>Actual recorded flight telemetry, newest first.</p><a href="fido.php">FIDO</a>
<table border="1"><tr><th>MET</th><th>CM X</th><th>CM Y</th><th>CM Z</th><th>VX</th><th>VY</th><th>VZ</th><th>CM Fuel</th><th>LM Fuel</th><th>Burn</th></tr>
<?php while($rows && ($row=$rows->fetch_assoc())): ?><tr><?php foreach($row as $v):?><td><?=htmlspecialchars((string)$v,ENT_QUOTES)?></td><?php endforeach;?></tr><?php endwhile;?>
</table>
<h2>OBSERVED STAGE / ENGINE / ABORT EVENTS</h2>
<?php $events=$conn->query("SELECT mission_seconds,event_key,old_value,new_value FROM flight_event_log ORDER BY id DESC LIMIT 100");?>
<table border="1"><tr><th>MET</th><th>Event</th><th>Previous</th><th>Observed</th></tr>
<?php if($events)while($e=$events->fetch_assoc()):?><tr><?php foreach($e as $value):?><td><?=htmlspecialchars((string)$value,ENT_QUOTES)?></td><?php endforeach;?></tr><?php endwhile;?></table>
<a href="flight_report.php">MISSION REPORT</a>
</body></html>
