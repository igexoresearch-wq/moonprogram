<?php
declare(strict_types=1);
$pw=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){http_response_code(401);exit('Operator login required');}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/flight_history.php';
moonSimEnsureFlightHistory($conn);
$id=max(0,(int)($_GET['id']??0));
if($id===0){$latest=$conn->query('SELECT MAX(id) last_id FROM flight_history')->fetch_assoc();$id=(int)($latest['last_id']??0);}
$stmt=$conn->prepare('SELECT * FROM flight_history WHERE id <= ? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('i',$id);$stmt->execute();$sample=$stmt->get_result()->fetch_assoc();
?><!doctype html><html><meta charset="utf-8"><title>Flight Replay</title>
<body style="background:#101713;color:#d4e8bd;font:16px monospace;padding:30px">
<h1>RECORDED FLIGHT / SNAPSHOT REVIEW</h1>
<p>Read-only playback. Selecting a recorded second does not move the simulated spacecraft.</p>
<?php if($sample):?>
<p>Sample #<?=htmlspecialchars((string)$sample['id'])?>; MET <?=htmlspecialchars((string)$sample['met'])?></p>
<pre><?php foreach(['cm_x','cm_y','cm_z','cm_vx','cm_vy','cm_vz','cm_fuel','lm_fuel','burn_status'] as $col)echo htmlspecialchars($col.': '.(string)$sample[$col],ENT_QUOTES)."\n";?></pre>
<p><a href="?id=<?=max(1,(int)$sample['id']-1)?>">PREVIOUS</a> |
<a href="?id=<?=(int)$sample['id']+1?>">NEXT</a></p>
<?php else:?><p>No recorded samples.</p><?php endif;?>
<a href="flight_timeline.php">FULL FLIGHT TIMELINE</a>
</body></html>
