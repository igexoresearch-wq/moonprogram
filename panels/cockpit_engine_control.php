<?php
$engineStmt = $pdo->prepare(
    "SELECT
        le.id, le.quantity, le.throttle_percent, le.enabled,
        ed.name AS engine_name,
        ed.minimum_throttle_percent,
        ed.maximum_throttle_percent
     FROM live_engines le
     JOIN engine_definitions ed ON ed.id=le.engine_definition_id
     WHERE le.live_object_id=:id
     ORDER BY le.id"
);
$engineStmt->execute(['id'=>$controlledObjectId]);
$cockpitEngines = $engineStmt->fetchAll();
?>
<?php if (!$cockpitEngines): ?>
  <div class="empty-panel">No engines installed</div>
<?php else: ?>
  <?php foreach ($cockpitEngines as $engine): ?>
    <form method="post" action="cockpit.php?object_id=<?= $controlledObjectId ?>" class="engine-control">
      <input type="hidden" name="action" value="set_engine">
      <input type="hidden" name="live_engine_id" value="<?= (int)$engine['id'] ?>">
      <div class="engine-name">
        <strong><?= h($engine['engine_name']) ?></strong>
        <?php if ((int)$engine['quantity'] > 1): ?> ×<?= (int)$engine['quantity'] ?><?php endif; ?>
      </div>
      <div class="inline compact">
        <input
          type="number"
          name="throttle_percent"
          min="0"
          max="100"
          step="0.1"
          value="<?= h((string)$engine['throttle_percent']) ?>"
          aria-label="Throttle percent"
        >
        <span>%</span>
        <label><input type="checkbox" name="enabled" <?= $engine['enabled']?'checked':'' ?>> ARM</label>
        <button>SET</button>
      </div>
    </form>
  <?php endforeach; ?>
<?php endif; ?>
