<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/flight_replay_store.php';
moonSimEnsureReplayFrames($conn);

function replayEscape(mixed $value):string {
 return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
}
$run=max(0,(int)($_GET['run']??0));
if($run===0){
 $last=$conn->query('SELECT MAX(run_id) AS run_id FROM flight_replay_frames');
 $run=(int)($last?$last->fetch_assoc()['run_id']??0:0);
}
$met=isset($_GET['met'])?max(0,(int)$_GET['met']):PHP_INT_MAX;
$frameRow=null;$prev=null;$next=null;
if($run>0){
 $stmt=$conn->prepare('SELECT run_id,met,frame_json,sha256 FROM flight_replay_frames WHERE run_id=? AND met<=? ORDER BY met DESC LIMIT 1');
 $stmt->bind_param('ii',$run,$met);$stmt->execute();
 $frameRow=$stmt->get_result()->fetch_assoc();
}
$frame=null;$verified=false;
if($frameRow){
 $stored=(string)$frameRow['frame_json'];
 $verified=hash_equals((string)$frameRow['sha256'],hash('sha256',$stored));
 if($verified)$frame=json_decode($stored,true,512,JSON_THROW_ON_ERROR);
 $actualMet=(int)$frameRow['met'];
 $nav=$conn->prepare('SELECT met FROM flight_replay_frames WHERE run_id=? AND met<? ORDER BY met DESC LIMIT 1');
 $nav->bind_param('ii',$run,$actualMet);$nav->execute();$prev=$nav->get_result()->fetch_assoc();
 $nav=$conn->prepare('SELECT met FROM flight_replay_frames WHERE run_id=? AND met>? ORDER BY met ASC LIMIT 1');
 $nav->bind_param('ii',$run,$actualMet);$nav->execute();$next=$nav->get_result()->fetch_assoc();
}
if(($_GET['format']??'')==='json'){
 header('Content-Type: application/json; charset=utf-8');
 if(!$frameRow){http_response_code(404);echo '{"error":"No recorded frame"}';exit;}
 if(!$verified){http_response_code(409);echo '{"error":"Recorded frame checksum mismatch"}';exit;}
 header('X-Moon-Replay-SHA256: '.$frameRow['sha256']);
 echo $frameRow['frame_json'];
 exit;
}
$allRuns=$conn->query('SELECT run_id,MIN(met) first_met,MAX(met) last_met,COUNT(*) frames FROM flight_replay_frames GROUP BY run_id ORDER BY run_id DESC LIMIT 100');
?><!doctype html><html lang="en"><head><meta charset="utf-8">
<title>Moon Program | Immutable Flight Replay</title>
<style>
body{margin:0;padding:24px;background:#101712;color:#cce0c7;font:15px ui-monospace,monospace}
a{color:#a4dfc4}main{max-width:1180px;margin:auto}section{border:1px solid #415d4b;padding:15px;margin:13px 0}
table{border-collapse:collapse;width:100%}td,th{padding:6px;border:1px solid #36503d;text-align:left}
pre{white-space:pre-wrap;word-break:break-all;max-height:420px;overflow:auto}
.error{color:#ffb6ae} .ok{color:#aaf0be}nav{display:flex;gap:20px}
</style></head><body><main><h1>RECORDED MISSION REPLAY</h1>
<p>Read-only, deterministic playback of authoritative captured frames. This
does not execute a new flight, replace control inputs, or change physics state.</p>
<section><h2>Recorded runs</h2><table>
<tr><th>Run</th><th>First MET</th><th>Last MET</th><th>Frames</th></tr>
<?php while($allRuns && ($record=$allRuns->fetch_assoc())): ?>
<tr><td><a href="?run=<?=(int)$record['run_id']?>"><?=replayEscape($record['run_id'])?></a></td>
<td><?=replayEscape($record['first_met'])?></td><td><?=replayEscape($record['last_met'])?></td>
<td><?=replayEscape($record['frames'])?></td></tr>
<?php endwhile;?></table></section>
<?php if(!$frameRow):?><section><p>No saved frames for this run.
Historical 30-second snapshots remain in <a href="flight_timeline.php">the timeline</a>,
but full replay begins with frames recorded by the updated worker.</p></section>
<?php elseif(!$verified):?><section class="error"><h2>RECORD CORRUPTION</h2>
<p>SHA-256 verification failed. This frame will not be displayed as authoritative.</p></section>
<?php else:?>
<section><h2>Run <?=replayEscape($run)?> / MET <?=replayEscape($frameRow['met'])?></h2>
<p class="ok">Frame checksum verified: <?=replayEscape($frameRow['sha256'])?></p>
<nav>
<?php if($prev):?><a href="?run=<?=$run?>&amp;met=<?=(int)$prev['met']?>">← Previous second</a><?php endif;?>
<?php if($next):?><a href="?run=<?=$run?>&amp;met=<?=(int)$next['met']?>">Next second →</a><?php endif;?>
<a href="?run=<?=$run?>&amp;met=<?=(int)$frameRow['met']?>&amp;format=json">Raw immutable frame</a>
</nav></section>
<section><h2>Recorded orbital markers</h2><table>
<tr><th>Vehicle</th><th>Altitude (m)</th><th>Periapsis (m)</th><th>Apoapsis (m)</th></tr>
<?php foreach(($frame['orbital_markers']??[]) as $vehicle=>$orbit):?>
<tr><td><?=replayEscape($vehicle)?></td>
<td><?=replayEscape($orbit['altitude_m']??'N/A')?></td>
<td><?=replayEscape($orbit['periapsis_altitude_m']??'N/A')?></td>
<td><?=replayEscape($orbit['apoapsis_altitude_m']??'N/A')?></td></tr>
<?php endforeach;?></table></section>
<section><h2>Actual observed transitions at this second</h2>
<?php if(empty($frame['events'])):?><p>No recorded transition.</p><?php else:?>
<table><tr><th>System</th><th>Old state</th><th>New state</th></tr>
<?php foreach($frame['events'] as $event):?>
<tr><td><?=replayEscape($event['event_key']??'')?></td>
<td><?=replayEscape($event['old_value']??'')?></td>
<td><?=replayEscape($event['new_value']??'')?></td></tr><?php endforeach;?>
</table><?php endif;?></section>
<section><h2>Complete authoritative state at MET <?=replayEscape($frameRow['met'])?></h2>
<?php foreach(($frame['state']??[]) as $table=>$rows):?>
<details><summary><?=replayEscape($table)?> (<?=count($rows)?> rows)</summary>
<pre><?=replayEscape(json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR))?></pre></details>
<?php endforeach;?></section>
<?php endif;?>
<p><a href="flight_timeline.php">Flight timeline</a> ·
<a href="flight_report.php">Mission report</a> ·
<a href="fido.php">FIDO</a></p>
</main></body></html>
