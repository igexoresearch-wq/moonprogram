<?php
$fuelStmt = $pdo->prepare(
    "SELECT name, propellant_type, capacity_kg, remaining_kg
     FROM live_fuel_tanks
     WHERE live_object_id=:id
     ORDER BY id"
);
$fuelStmt->execute(['id'=>$controlledObjectId]);
$tanks = $fuelStmt->fetchAll();
?>
<?php if (!$tanks): ?>
  <div class="empty-panel">No propellant tanks</div>
<?php else: ?>
  <?php foreach ($tanks as $tank): ?>
    <?php
      $capacity = (float)$tank['capacity_kg'];
      $remaining = (float)$tank['remaining_kg'];
      $percent = $capacity > 0 ? max(0,min(100,$remaining/$capacity*100)) : 0;
    ?>
    <div class="tank-row">
      <div><strong><?= h($tank['propellant_type']) ?></strong> <span class="muted small"><?= h($tank['name']) ?></span></div>
      <div class="tank-gauge"><span style="width:<?= number_format($percent,2,'.','') ?>%"></span></div>
      <div class="small"><?= number_format($remaining,1) ?> / <?= number_format($capacity,1) ?> kg · <?= number_format($percent,1) ?>%</div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
