<?php
$pitch = $controlledObject['pitch_deg'] ?? null;
$yaw = $controlledObject['yaw_deg'] ?? null;
$roll = $controlledObject['roll_deg'] ?? null;
?>
<div class="readout flight-readout">
  <div class="instrument-big"><?= $pitch !== null ? number_format((float)$pitch, 1) . '°' : '—' ?></div>
  <div>PITCH</div>
  <hr>
  <div>YAW&nbsp;&nbsp;&nbsp;<strong><?= $yaw !== null ? number_format((float)$yaw, 1) . '°' : '—' ?></strong></div>
  <div>ROLL&nbsp;&nbsp;<strong><?= $roll !== null ? number_format((float)$roll, 1) . '°' : '—' ?></strong></div>
</div>
