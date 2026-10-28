<?php
$currentMass = totalLiveObjectMass($pdo, $controlledObjectId);
?>
<div class="readout flight-readout">
  <div><strong><?= h($controlledObject['name']) ?></strong></div>
  <div class="small muted"><?= h($controlledObject['definition_name'] ?? 'Custom object') ?></div>
  <hr>
  <div>MASS <strong><?= number_format($currentMass, 2) ?> kg</strong></div>
  <div>STATE <strong><?= $controlledObject['is_attached'] ? 'ATTACHED' : 'FREE' ?></strong></div>
  <?php if ($controlledObject['mission_name']): ?>
    <div>MISSION <strong><?= h($controlledObject['mission_name']) ?></strong></div>
  <?php endif; ?>
  <?php if ($controlledObject['mission_started_at']): ?>
    <?php
      $start = new DateTimeImmutable($controlledObject['mission_started_at']);
      $now = new DateTimeImmutable('now');
      $elapsed = max(0, $now->getTimestamp() - $start->getTimestamp());
      $days = intdiv($elapsed,86400);
      $hours = intdiv($elapsed%86400,3600);
      $minutes = intdiv($elapsed%3600,60);
      $seconds = $elapsed%60;
    ?>
    <div>MET <strong><?= sprintf('%03d:%02d:%02d:%02d',$days,$hours,$minutes,$seconds) ?></strong></div>
  <?php endif; ?>
</div>
