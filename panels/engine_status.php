<?php
$rows = $pdo->query(
    "SELECT
        le.id, o.name AS object_name, e.name AS engine_name,
        le.quantity, le.throttle_percent, le.enabled
     FROM live_engines AS le
     JOIN live_objects AS o ON o.id = le.live_object_id
     JOIN engine_definitions AS e ON e.id = le.engine_definition_id
     ORDER BY le.id DESC
     LIMIT 8"
)->fetchAll();
?>
<?php if (!$rows): ?>
  <div class="empty-panel">No live engines</div>
<?php else: ?>
  <?php foreach ($rows as $row): ?>
    <div class="readout" style="margin-bottom:6px">
      <strong><?= h($row['engine_name']) ?></strong> ×<?= (int)$row['quantity'] ?><br>
      <?= h($row['object_name']) ?> — <?= h((string)$row['throttle_percent']) ?>%
      <?= $row['enabled'] ? '' : ' [DISABLED]' ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
