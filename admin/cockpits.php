<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

$pdo = db();
$pageTitle = 'Cockpit Builder';
$basePath = '../';
$message = null;
$error = null;

function createBlankCockpitSlots(PDO $pdo, int $cockpitId, int $slotCount): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO cockpit_panels (cockpit_id, slot_number, title, instrument_id)
         VALUES (:cockpit_id, :slot, NULL, NULL)"
    );

    for ($slot = 1; $slot <= $slotCount; $slot++) {
        $stmt->execute([
            'cockpit_id' => $cockpitId,
            'slot' => $slot,
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'create') {
            $name = trim((string)$_POST['name']);
            $slug = slugify((string)($_POST['slug'] ?: $name));
            $columns = max(1, min(8, (int)$_POST['columns_count']));
            $rows = max(1, min(8, (int)$_POST['rows_count']));
            $sourceId = $_POST['copy_from'] !== '' ? (int)$_POST['copy_from'] : null;

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO cockpit_definitions (
                    slug, name, description, columns_count, rows_count
                 ) VALUES (
                    :slug, :name, :description, :columns, :rows
                 )"
            );
            $stmt->execute([
                'slug' => $slug,
                'name' => $name,
                'description' => trim((string)$_POST['description']) ?: null,
                'columns' => $columns,
                'rows' => $rows,
            ]);

            $cockpitId = (int)$pdo->lastInsertId();
            $slotCount = $columns * $rows;

            if ($sourceId !== null) {
                $copy = $pdo->prepare(
                    "INSERT INTO cockpit_panels (
                        cockpit_id, slot_number, title, instrument_id
                     )
                     SELECT
                        :new_id, slot_number, title, instrument_id
                     FROM cockpit_panels
                     WHERE cockpit_id=:source_id
                       AND slot_number <= :slot_count
                     ORDER BY slot_number"
                );
                $copy->bindValue(':new_id', $cockpitId, PDO::PARAM_INT);
                $copy->bindValue(':source_id', $sourceId, PDO::PARAM_INT);
                $copy->bindValue(':slot_count', $slotCount, PDO::PARAM_INT);
                $copy->execute();

                $existing = (int)$pdo->query(
                    "SELECT COUNT(*) FROM cockpit_panels WHERE cockpit_id=" . $cockpitId
                )->fetchColumn();

                if ($existing < $slotCount) {
                    $presentStmt = $pdo->prepare(
                        "SELECT slot_number FROM cockpit_panels WHERE cockpit_id=:id"
                    );
                    $presentStmt->execute(['id' => $cockpitId]);
                    $present = array_map('intval', array_column($presentStmt->fetchAll(), 'slot_number'));

                    $slotStmt = $pdo->prepare(
                        "INSERT INTO cockpit_panels (
                            cockpit_id, slot_number, title, instrument_id
                         ) VALUES (:id,:slot,NULL,NULL)"
                    );

                    for ($slot = 1; $slot <= $slotCount; $slot++) {
                        if (!in_array($slot, $present, true)) {
                            $slotStmt->execute(['id'=>$cockpitId,'slot'=>$slot]);
                        }
                    }
                }
            } else {
                createBlankCockpitSlots($pdo, $cockpitId, $slotCount);
            }

            $pdo->commit();
            $message = 'Cockpit created.';
        }

        if ($action === 'update_slot') {
            $cockpitId = (int)$_POST['cockpit_id'];
            $slot = (int)$_POST['slot_number'];
            $instrumentId = $_POST['instrument_id'] !== ''
                ? (int)$_POST['instrument_id']
                : null;

            $stmt = $pdo->prepare(
                "UPDATE cockpit_panels
                 SET title=:title, instrument_id=:instrument_id
                 WHERE cockpit_id=:cockpit_id
                   AND slot_number=:slot"
            );
            $stmt->execute([
                'title' => trim((string)$_POST['title']) ?: null,
                'instrument_id' => $instrumentId,
                'cockpit_id' => $cockpitId,
                'slot' => $slot,
            ]);
            $message = 'Cockpit panel updated.';
        }

        if ($action === 'rename') {
            $stmt = $pdo->prepare(
                "UPDATE cockpit_definitions
                 SET name=:name, description=:description
                 WHERE id=:id"
            );
            $stmt->execute([
                'name' => trim((string)$_POST['name']),
                'description' => trim((string)$_POST['description']) ?: null,
                'id' => (int)$_POST['cockpit_id'],
            ]);
            $message = 'Cockpit details updated.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$cockpits = $pdo->query(
    "SELECT
        c.*,
        (SELECT COUNT(*) FROM object_definitions d
         WHERE d.default_cockpit_id=c.id) AS assigned_definitions,
        (SELECT COUNT(*) FROM live_objects o
         WHERE o.cockpit_id=c.id) AS live_objects
     FROM cockpit_definitions c
     ORDER BY c.name"
)->fetchAll();

$instruments = $pdo->query(
    "SELECT *
     FROM instrument_definitions
     WHERE context IN ('cockpit','both')
     ORDER BY name"
)->fetchAll();

$selectedId = isset($_GET['cockpit_id'])
    ? (int)$_GET['cockpit_id']
    : (int)($cockpits[0]['id'] ?? 0);

$selected = null;
$slots = [];
if ($selectedId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM cockpit_definitions WHERE id=:id");
    $stmt->execute(['id'=>$selectedId]);
    $selected = $stmt->fetch() ?: null;

    if ($selected) {
        $slotStmt = $pdo->prepare(
            "SELECT p.*, i.name AS instrument_name
             FROM cockpit_panels p
             LEFT JOIN instrument_definitions i ON i.id=p.instrument_id
             WHERE p.cockpit_id=:id
             ORDER BY p.slot_number"
        );
        $slotStmt->execute(['id'=>$selectedId]);
        $slots = $slotStmt->fetchAll();
    }
}

require __DIR__ . '/../partials/header.php';
?>
<?php if ($message): ?><div class="flash"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= h($error) ?></div><?php endif; ?>

<section class="page-card">
  <h1>Cockpit Builder</h1>
  <p class="muted">
    A cockpit is a reusable instrument panel. Assign it to an object definition
    such as an Apollo Command Module, and every live instance can open that cockpit.
  </p>

  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="create">
    <div class="field"><label>Cockpit name</label><input name="name" required placeholder="Apollo Command Module"></div>
    <div class="field"><label>Slug</label><input name="slug" placeholder="apollo-command-module"></div>
    <div class="field"><label>Columns</label><input type="number" name="columns_count" min="1" max="8" value="5" required></div>
    <div class="field"><label>Rows</label><input type="number" name="rows_count" min="1" max="8" value="4" required></div>
    <div class="field"><label>Copy layout from</label>
      <select name="copy_from">
        <option value="">Blank cockpit</option>
        <?php foreach ($cockpits as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field full"><label>Description</label><textarea name="description"></textarea></div>
    <div class="field full"><button>Create Cockpit</button></div>
  </form>
</section>

<section class="page-card">
  <h2>Cockpits</h2>
  <table>
    <thead><tr><th>Cockpit</th><th>Grid</th><th>Definitions</th><th>Live craft</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cockpits as $c): ?>
      <tr>
        <td><strong><?= h($c['name']) ?></strong><br><span class="muted small"><?= h($c['slug']) ?></span></td>
        <td><?= (int)$c['columns_count'] ?> × <?= (int)$c['rows_count'] ?></td>
        <td><?= (int)$c['assigned_definitions'] ?></td>
        <td><?= (int)$c['live_objects'] ?></td>
        <td><a class="button" href="?cockpit_id=<?= (int)$c['id'] ?>">Edit Panel</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php if ($selected): ?>
<section class="page-card">
  <h2>Edit <?= h($selected['name']) ?></h2>

  <form class="grid-form" method="post" style="margin-bottom:18px">
    <input type="hidden" name="action" value="rename">
    <input type="hidden" name="cockpit_id" value="<?= (int)$selected['id'] ?>">
    <div class="field"><label>Name</label><input name="name" value="<?= h($selected['name']) ?>" required></div>
    <div class="field full"><label>Description</label><textarea name="description"><?= h($selected['description'] ?? '') ?></textarea></div>
    <div class="field full"><button>Save Cockpit Details</button></div>
  </form>

  <div class="cockpit-editor-grid" style="--cockpit-cols:<?= (int)$selected['columns_count'] ?>">
    <?php foreach ($slots as $slot): ?>
      <div class="instrument-panel cockpit-editor-slot">
        <div class="panel-title">Slot <?= (int)$slot['slot_number'] ?></div>
        <form method="post" class="panel-body">
          <input type="hidden" name="action" value="update_slot">
          <input type="hidden" name="cockpit_id" value="<?= (int)$selected['id'] ?>">
          <input type="hidden" name="slot_number" value="<?= (int)$slot['slot_number'] ?>">
          <div class="field">
            <label>Panel label</label>
            <input name="title" value="<?= h($slot['title'] ?? '') ?>" placeholder="Optional label">
          </div>
          <div class="field" style="margin-top:8px">
            <label>Instrument</label>
            <select name="instrument_id">
              <option value="">Empty</option>
              <?php foreach ($instruments as $i): ?>
                <option value="<?= (int)$i['id'] ?>" <?= (int)$slot['instrument_id']===(int)$i['id']?'selected':'' ?>>
                  <?= h($i['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button style="margin-top:10px">Save Slot</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
