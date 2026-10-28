<?php
$state = $pdo->query(
    "SELECT
        s.last_tick_at,
        s.physics_enabled,
        s.nominal_tick_seconds,
        (SELECT COUNT(*) FROM live_objects) AS object_count,
        (SELECT COUNT(*) FROM missions WHERE status='active') AS active_missions
     FROM simulation_state AS s
     WHERE s.id = 1"
)->fetch();
?>
<div class="readout">
  <div class="kpi"><?= (int)($state['object_count'] ?? 0) ?> OBJECTS</div>
  <div>Active missions: <strong><?= (int)($state['active_missions'] ?? 0) ?></strong></div>
  <div>Physics: <strong><?= !empty($state['physics_enabled']) ? 'ONLINE' : 'OFFLINE' ?></strong></div>
  <div>Tick target: <strong><?= h((string)($state['nominal_tick_seconds'] ?? '1.0')) ?> s</strong></div>
  <div class="small muted">Last physics: <?= h((string)($state['last_tick_at'] ?? 'n/a')) ?></div>
</div>
