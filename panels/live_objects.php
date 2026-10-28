<?php
$rows = $pdo->query(
    "SELECT id, name, is_attached, parent_object_id, x_cm, y_cm, z_cm
     FROM live_objects
     ORDER BY id DESC
     LIMIT 8"
)->fetchAll();
?>
<?php if (!$rows): ?>
  <div class="empty-panel">No live objects</div>
<?php else: ?>
  <table>
    <thead><tr><th>ID</th><th>Object</th><th>State</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= (int)$row['id'] ?></td>
        <td><?= h($row['name']) ?></td>
        <td>
          <?= $row['is_attached'] ? 'Attached → #' . (int)$row['parent_object_id'] : 'Free' ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
