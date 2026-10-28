<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/functions.php';

$pdo = db();
$pageTitle = 'Command Center';
$basePath = '';

$panels = [];
try {
    $stmt = $pdo->query(
        "SELECT
            p.slot_number,
            p.title AS panel_title,
            i.name AS instrument_name,
            i.include_file
         FROM command_center_panels AS p
         LEFT JOIN instrument_definitions AS i
           ON i.id = p.instrument_id
         ORDER BY p.slot_number"
    );
    foreach ($stmt->fetchAll() as $row) {
        $panels[(int)$row['slot_number']] = $row;
    }
} catch (Throwable $e) {
    $startupError = $e->getMessage();
}

require __DIR__ . '/partials/header.php';
?>

<?php if (isset($startupError)): ?>
<div class="flash error">
  Database is not initialized yet: <?= h($startupError) ?>
  <div class="small">Configure db.php, import schema.sql, then seed_universe.sql.</div>
</div>
<?php endif; ?>

<div class="command-grid">
<?php for ($slot = 1; $slot <= 20; $slot++): ?>
  <?php $panel = $panels[$slot] ?? null; ?>
  <section class="instrument-panel">
    <div class="panel-title">
      <?= h($panel['panel_title'] ?? ('Panel ' . $slot)) ?>
    </div>
    <div class="panel-body">
      <?php
      $includeFile = $panel['include_file'] ?? null;
      if ($includeFile) {
          $candidate = realpath(__DIR__ . '/' . $includeFile);
          $panelRoot = realpath(__DIR__ . '/panels');

          if (
              $candidate !== false
              && $panelRoot !== false
              && str_starts_with($candidate, $panelRoot . DIRECTORY_SEPARATOR)
              && is_file($candidate)
          ) {
              require $candidate;
          } else {
              echo '<div class="empty-panel">Invalid instrument include</div>';
          }
      } else {
          echo '<div class="empty-panel">Unused panel</div>';
      }
      ?>
    </div>
  </section>
<?php endfor; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
