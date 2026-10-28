<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/functions.php';

$pdo = db();
$basePath = '';

$objectId = isset($_GET['object_id']) ? (int)$_GET['object_id'] : 0;
if ($objectId <= 0) {
    http_response_code(400);
    exit('A live object_id is required.');
}

$objectStmt = $pdo->prepare(
    "SELECT
        o.*,
        d.name AS definition_name,
        m.name AS mission_name,
        m.started_at AS mission_started_at,
        c.id AS cockpit_definition_id,
        c.name AS cockpit_name,
        c.columns_count,
        c.rows_count
     FROM live_objects o
     LEFT JOIN object_definitions d ON d.id=o.definition_id
     LEFT JOIN missions m ON m.id=o.mission_id
     LEFT JOIN cockpit_definitions c ON c.id=o.cockpit_id
     WHERE o.id=:id"
);
$objectStmt->execute(['id'=>$objectId]);
$controlledObject = $objectStmt->fetch();

if (!$controlledObject) {
    http_response_code(404);
    exit('Live object not found.');
}

if ($controlledObject['cockpit_definition_id'] === null) {
    http_response_code(409);
    exit('This live object has no cockpit assigned. Assign one in Live Universe or its object definition.');
}

$controlledObjectId = (int)$controlledObject['id'];
$pageTitle = $controlledObject['cockpit_name'] . ' — ' . $controlledObject['name'];
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'set_engine') {
            $engineId = (int)$_POST['live_engine_id'];
            $throttle = max(0.0, min(100.0, (float)$_POST['throttle_percent']));

            $verify = $pdo->prepare(
                "SELECT id
                 FROM live_engines
                 WHERE id=:engine_id
                   AND live_object_id=:object_id"
            );
            $verify->execute([
                'engine_id'=>$engineId,
                'object_id'=>$controlledObjectId,
            ]);

            if (!$verify->fetchColumn()) {
                throw new RuntimeException('That engine does not belong to this spacecraft.');
            }

            $stmt = $pdo->prepare(
                "UPDATE live_engines
                 SET throttle_percent=:throttle,
                     enabled=:enabled
                 WHERE id=:id"
            );
            $stmt->execute([
                'throttle'=>number_format($throttle,3,'.',''),
                'enabled'=>isset($_POST['enabled']) ? 1 : 0,
                'id'=>$engineId,
            ]);

            $message = 'Engine command accepted.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    $objectStmt->execute(['id'=>$objectId]);
    $controlledObject = $objectStmt->fetch();
}

$cockpit = [
    'id'=>(int)$controlledObject['cockpit_definition_id'],
    'name'=>$controlledObject['cockpit_name'],
    'columns_count'=>(int)$controlledObject['columns_count'],
    'rows_count'=>(int)$controlledObject['rows_count'],
];

$panelStmt = $pdo->prepare(
    "SELECT
        p.slot_number,
        p.title AS panel_title,
        i.name AS instrument_name,
        i.include_file
     FROM cockpit_panels p
     LEFT JOIN instrument_definitions i ON i.id=p.instrument_id
     WHERE p.cockpit_id=:cockpit_id
     ORDER BY p.slot_number"
);
$panelStmt->execute(['cockpit_id'=>$cockpit['id']]);
$panels = [];
foreach ($panelStmt->fetchAll() as $panel) {
    $panels[(int)$panel['slot_number']] = $panel;
}

require __DIR__ . '/partials/header.php';
?>

<div class="cockpit-banner">
  <div>
    <div class="cockpit-name"><?= h($cockpit['name']) ?></div>
    <div class="muted">
      <?= h($controlledObject['name']) ?>
      <?php if ($controlledObject['mission_name']): ?>
        · <?= h($controlledObject['mission_name']) ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="readout cockpit-id-readout">
    LIVE OBJECT #<?= $controlledObjectId ?>
  </div>
</div>

<?php if ($message): ?><div class="flash"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= h($error) ?></div><?php endif; ?>

<div class="cockpit-grid" style="--cockpit-cols:<?= (int)$cockpit['columns_count'] ?>">
<?php
$slotCount = $cockpit['columns_count'] * $cockpit['rows_count'];
for ($slot = 1; $slot <= $slotCount; $slot++):
    $panel = $panels[$slot] ?? null;
?>
  <section class="instrument-panel cockpit-instrument">
    <div class="panel-title"><?= h($panel['panel_title'] ?? ('Panel ' . $slot)) ?></div>
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
              echo '<div class="empty-panel">Invalid instrument</div>';
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
