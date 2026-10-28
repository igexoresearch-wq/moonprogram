<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

$pdo = db();
$pageTitle = 'Missions';
$basePath = '../';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_mission') {
            $stmt = $pdo->prepare(
                "INSERT INTO missions (name, status, notes)
                 VALUES (:name, 'draft', :notes)"
            );
            $stmt->execute([
                'name' => trim((string)$_POST['name']),
                'notes' => trim((string)$_POST['notes']) ?: null,
            ]);
            $message = 'Mission created.';
        }

        if ($action === 'set_status') {
            $status = (string)$_POST['status'];
            $allowed = ['draft','active','complete','failed','aborted'];
            if (!in_array($status, $allowed, true)) {
                throw new RuntimeException('Invalid mission status.');
            }

            if ($status === 'active') {
                $stmt = $pdo->prepare(
                    "UPDATE missions
                     SET status=:status,
                         started_at=COALESCE(started_at,NOW(6)),
                         ended_at=NULL
                     WHERE id=:id"
                );
            } elseif (in_array($status, ['complete','failed','aborted'], true)) {
                $stmt = $pdo->prepare(
                    "UPDATE missions
                     SET status=:status,
                         ended_at=NOW(6)
                     WHERE id=:id"
                );
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE missions
                     SET status=:status
                     WHERE id=:id"
                );
            }

            $stmt->execute([
                'status' => $status,
                'id' => (int)$_POST['mission_id'],
            ]);
            $message = 'Mission status updated.';
        }

        if ($action === 'instantiate') {
            $parentId = $_POST['parent_object_id'] !== ''
                ? (int)$_POST['parent_object_id']
                : null;

            $liveId = instantiateDefinition(
                $pdo,
                (int)$_POST['definition_id'],
                (int)$_POST['mission_id'],
                trim((string)$_POST['instance_name']),
                $parentId,
                isset($_POST['attached'])
            );

            $message = 'Live object created as #' . $liveId . '.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$missions = $pdo->query(
    "SELECT
        m.*,
        (SELECT COUNT(*) FROM live_objects o WHERE o.mission_id=m.id) AS object_count
     FROM missions m
     ORDER BY m.id DESC"
)->fetchAll();

$definitions = $pdo->query(
    "SELECT id,name FROM object_definitions ORDER BY name"
)->fetchAll();

$liveObjects = $pdo->query(
    "SELECT id,name,mission_id FROM live_objects ORDER BY id DESC"
)->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<?php if ($message): ?><div class="flash"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= h($error) ?></div><?php endif; ?>

<section class="page-card">
  <h1>Mission Builder</h1>
  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="create_mission">
    <div class="field"><label>Mission Name</label><input name="name" required placeholder="Apollo 12"></div>
    <div class="field full"><label>Notes</label><textarea name="notes"></textarea></div>
    <div class="field full"><button>Create Mission</button></div>
  </form>
</section>

<section class="page-card">
  <h2>Instantiate a Component into a Mission</h2>
  <?php if (!$missions || !$definitions): ?>
    <p class="muted">Create at least one mission and one object definition first.</p>
  <?php else: ?>
  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="instantiate">
    <div class="field"><label>Mission</label><select name="mission_id" required>
      <?php foreach ($missions as $m): ?><option value="<?= (int)$m['id'] ?>"><?= h($m['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label>Definition</label><select name="definition_id" required>
      <?php foreach ($definitions as $d): ?><option value="<?= (int)$d['id'] ?>"><?= h($d['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label>Live Object Name</label><input name="instance_name" required placeholder="Apollo 12 Command Module"></div>
    <div class="field"><label>Attach to Parent (optional)</label><select name="parent_object_id">
      <option value="">Free object</option>
      <?php foreach ($liveObjects as $o): ?><option value="<?= (int)$o['id'] ?>">#<?= (int)$o['id'] ?> <?= h($o['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="field full"><label><input type="checkbox" name="attached"> Lock to selected parent</label></div>
    <div class="field full"><button>Create Live Instance</button></div>
  </form>
  <?php endif; ?>
</section>

<section class="page-card">
  <h2>Missions</h2>
  <table>
    <thead><tr><th>ID</th><th>Mission</th><th>Status</th><th>Objects</th><th>Started</th><th>Change Status</th></tr></thead>
    <tbody>
    <?php foreach ($missions as $m): ?>
      <tr>
        <td><?= (int)$m['id'] ?></td>
        <td><strong><?= h($m['name']) ?></strong></td>
        <td><?= h(strtoupper($m['status'])) ?></td>
        <td><?= (int)$m['object_count'] ?></td>
        <td><?= h($m['started_at'] ?? '—') ?></td>
        <td>
          <form method="post" class="inline compact">
            <input type="hidden" name="action" value="set_status">
            <input type="hidden" name="mission_id" value="<?= (int)$m['id'] ?>">
            <select name="status">
              <?php foreach (['draft','active','complete','failed','aborted'] as $status): ?>
                <option value="<?= h($status) ?>" <?= $m['status']===$status?'selected':'' ?>><?= h(ucfirst($status)) ?></option>
              <?php endforeach; ?>
            </select>
            <button>Set</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
