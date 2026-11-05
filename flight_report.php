<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/flight_history.php';
moonSimEnsureFlightHistory($conn);
require_once __DIR__.'/shared/mission_outcome.php';
$first=$conn->query('SELECT * FROM flight_history ORDER BY id ASC LIMIT 1')->fetch_assoc();
$last=$conn->query('SELECT * FROM flight_history ORDER BY id DESC LIMIT 1')->fetch_assoc();
$count=(int)$conn->query('SELECT COUNT(*) n FROM flight_history')->fetch_assoc()['n'];
$label=($last['burn_status']??'NO DATA');
$contactResult=$conn->query("SELECT body,contact_state,impact_speed FROM surface_contact WHERE vehicle='CM' LIMIT 1");
$contact=$contactResult?$contactResult->fetch_assoc():null;
$cmSm=$conn->query("SELECT value FROM command_module WHERE attribute='service_module' LIMIT 1");
$sm=(int)($cmSm?$cmSm->fetch_assoc()['value']??0:1)===1;
$cmEsc=$conn->query("SELECT value FROM command_module WHERE attribute='escape' LIMIT 1");
$escape=(int)($cmEsc?$cmEsc->fetch_assoc()['value']??0:1)===1;
$entry=$conn->query("SELECT chute_state,shield_integrity,crew_survives FROM reentry_state WHERE id=1");
$reentry=$entry?$entry->fetch_assoc():null;
$chute=$reentry['chute_state']??null;
$outcome=moonSimMissionOutcome($contact,$sm,$escape,$chute,$reentry);
$abortEvents=$conn->query("SELECT COUNT(*) AS n FROM flight_event_log WHERE event_key='CM.ABORT' AND new_value='1'");
$abortObserved=(int)($abortEvents?$abortEvents->fetch_assoc()['n']??0:0)>0;
$lmObjectives=$conn->query("SELECT COUNT(*) AS n FROM flight_event_log WHERE event_key='LM.descent' AND new_value='0'");
$lmAscentObserved=(int)($lmObjectives?$lmObjectives->fetch_assoc()['n']??0:0)>0;
$events=$conn->query("SELECT mission_seconds,event_key,old_value,new_value FROM flight_event_log ORDER BY id DESC LIMIT 100");
?><!doctype html><html><title>Mission Review</title><body style="background:#121c16;color:#d9e7c9;font:17px monospace;padding:30px">
<h1>MISSION REVIEW / TELEMETRY REPORT</h1>
<p>This report summarizes recorded state. It does not assert mission success or diagnose an unrecorded abort.</p>
<p>Recorded samples: <?=htmlspecialchars((string)$count)?>; first MET <?=htmlspecialchars((string)($first['met']??'-'))?>; latest MET <?=htmlspecialchars((string)($last['met']??'-'))?></p>
<p>Latest burn status: <?=htmlspecialchars((string)$label)?></p>
<p>Latest CM fuel: <?=htmlspecialchars((string)($last['cm_fuel']??'-'))?> kg</p>
<h2>RECOVERY OUTCOME</h2>
<p><?=htmlspecialchars($outcome['state'],ENT_QUOTES)?> — <?=htmlspecialchars($outcome['reason'],ENT_QUOTES)?></p>
<p>Recorded abort: <?= $abortObserved ? 'YES' : 'NO OBSERVED EVENT' ?>. Recorded LM ascent transition: <?= $lmAscentObserved ? 'YES' : 'NO OBSERVED EVENT' ?>.</p>
<p>Abort and lunar objectives are independent of the win condition. The report never changes spacecraft state.</p>
<h2>RECORDED TRANSITIONS / ABORT EVIDENCE</h2>
<table border="1"><tr><th>MET</th><th>Event</th><th>Previous</th><th>Observed</th></tr>
<?php if($events)while($e=$events->fetch_assoc()):?><tr><?php foreach($e as $value):?><td><?=htmlspecialchars((string)$value,ENT_QUOTES)?></td><?php endforeach;?></tr><?php endwhile;?></table>
<p>Abort or crash state is reported only when an actual recorded transition is present.</p>
<a href="flight_timeline.php" style="color:#ecdc9f">OPEN RECORDED FLIGHT TIMELINE</a>
</body></html>
