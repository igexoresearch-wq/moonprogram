<?php
$x = ((float)$controlledObject['x_cm'] + (float)$controlledObject['x_subcm']) / 100.0;
$y = ((float)$controlledObject['y_cm'] + (float)$controlledObject['y_subcm']) / 100.0;
$z = ((float)$controlledObject['z_cm'] + (float)$controlledObject['z_subcm']) / 100.0;
?>
<div class="readout flight-readout">
  <div>X <strong><?= number_format($x, 1) ?> m</strong></div>
  <div>Y <strong><?= number_format($y, 1) ?> m</strong></div>
  <div>Z <strong><?= number_format($z, 1) ?> m</strong></div>
  <div class="small muted" style="margin-top:8px">UNIVERSAL XYZ FRAME</div>
</div>
