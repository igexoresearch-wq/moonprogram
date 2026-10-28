<?php
$isCockpitContext = isset($controlledObjectId, $controlledObject);
?>
<div class="readout">
  <div><strong>GROUND CONTROL COMMS</strong></div>
  <?php if ($isCockpitContext): ?>
    <div class="small" style="margin-top:8px">
      LINK: <strong><?= h($controlledObject['name']) ?></strong>
      <?php if (!empty($controlledObject['mission_name'])): ?>
        · <?= h($controlledObject['mission_name']) ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="muted small" style="margin-top:8px">
    OpenAI ground-control routing will attach here. The instrument already receives the active spacecraft context.
  </div>
  <div style="margin-top:10px">
    <button type="button" disabled>PTT / TALK</button>
  </div>
</div>
