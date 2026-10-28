<?php
$vx = (float)$controlledObject['vx_cm_s'] / 100.0;
$vy = (float)$controlledObject['vy_cm_s'] / 100.0;
$vz = (float)$controlledObject['vz_cm_s'] / 100.0;
$speed = sqrt($vx*$vx + $vy*$vy + $vz*$vz);
?>
<div class="readout flight-readout">
  <div class="instrument-big"><?= number_format($speed, 2) ?></div>
  <div>M/S TOTAL</div>
  <hr>
  <div>VX <strong><?= number_format($vx, 2) ?></strong></div>
  <div>VY <strong><?= number_format($vy, 2) ?></strong></div>
  <div>VZ <strong><?= number_format($vz, 2) ?></strong></div>
</div>
