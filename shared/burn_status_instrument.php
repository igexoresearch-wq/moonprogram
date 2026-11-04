<?php
/* Crew-visible burn information only; the physics worker owns all execution. */
$burnPlan=null;$burnClock=null;
$burnResult=$conn->query("SELECT phase,status,ignition_met,cutoff_met FROM mission_burn_plan WHERE id=1 LIMIT 1");
if($burnResult)$burnPlan=$burnResult->fetch_assoc();
$timeResult=$conn->query("SELECT seconds FROM mission_clock WHERE id=1 LIMIT 1");
if($timeResult)$burnClock=$timeResult->fetch_assoc();
$met=(int)($burnClock['seconds']??0);
function moonBurnEsc($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<div style="border:2px solid #737e65;background:#0b100d;color:#c8dfa4;font-family:monospace;padding:12px;margin:8px 0">
 <strong>CM AGC — MANEUVER STATUS</strong><br>
 MODE: <?=moonBurnEsc($burnPlan['phase']??'---')?> / <?=moonBurnEsc($burnPlan['status']??'NO PLAN')?><br>
 MET: <?=moonBurnEsc($met)?><br>
 IGN: <?=moonBurnEsc($burnPlan['ignition_met']??'---')?> &nbsp;
 CUT: <?=moonBurnEsc($burnPlan['cutoff_met']??'---')?><br>
 T-IGN: <?=moonBurnEsc(isset($burnPlan['ignition_met'])?(int)$burnPlan['ignition_met']-$met:'---')?><br>
 <small>DISPLAY ONLY: actual steering, thrust and cutoff controlled by physics worker.</small><br>
 <a href="csm_burn_control.php" style="color:#a8f0b1">REVIEW / ARM / ABORT MANEUVER</a>
</div>
