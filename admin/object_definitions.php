<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

$pdo = db();
$pageTitle = 'Object Definitions';
$basePath = '../';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_definition') {
            $name = trim((string)$_POST['name']);
            $slug = slugify((string)($_POST['slug'] ?: $name));

            $stmt = $pdo->prepare(
                "INSERT INTO object_definitions (
                    slug, name, object_type, dry_mass_kg, radius_m,
                    produces_gravity, affected_by_gravity, has_orientation, notes
                 ) VALUES (
                    :slug, :name, :object_type, :dry_mass_kg, :radius_m,
                    :produces_gravity, :affected_by_gravity, :has_orientation, :notes
                 )"
            );
            $stmt->execute([
                'slug' => $slug,
                'name' => $name,
                'object_type' => trim((string)$_POST['object_type']),
                'dry_mass_kg' => (string)$_POST['dry_mass_kg'],
                'radius_m' => $_POST['radius_m'] !== '' ? (string)$_POST['radius_m'] : null,
                'produces_gravity' => isset($_POST['produces_gravity']) ? 1 : 0,
                'affected_by_gravity' => isset($_POST['affected_by_gravity']) ? 1 : 0,
                'has_orientation' => isset($_POST['has_orientation']) ? 1 : 0,
                'notes' => trim((string)$_POST['notes']) ?: null,
            ]);

            $message = 'Object definition created.';
        }

        if ($action === 'add_tank') {
            $stmt = $pdo->prepare(
                "INSERT INTO definition_fuel_tanks (
                    object_definition_id, name, propellant_type, capacity_kg
                 ) VALUES (:object_id, :name, :type, :capacity)"
            );
            $stmt->execute([
                'object_id' => (int)$_POST['object_definition_id'],
                'name' => trim((string)$_POST['tank_name']),
                'type' => trim((string)$_POST['propellant_type']),
                'capacity' => (string)$_POST['capacity_kg'],
            ]);
            $message = 'Fuel tank added to definition.';
        }

        if ($action === 'add_mount') {
            $stmt = $pdo->prepare(
                "INSERT INTO definition_engine_mounts (
                    object_definition_id, engine_definition_id, quantity,
                    local_x, local_y, local_z, notes
                 ) VALUES (
                    :object_id, :engine_id, :quantity,
                    :x, :y, :z, :notes
                 )"
            );
            $stmt->execute([
                'object_id' => (int)$_POST['object_definition_id'],
                'engine_id' => (int)$_POST['engine_definition_id'],
                'quantity' => (int)$_POST['quantity'],
                'x' => (string)$_POST['local_x'],
                'y' => (string)$_POST['local_y'],
                'z' => (string)$_POST['local_z'],
                'notes' => trim((string)$_POST['mount_notes']) ?: null,
            ]);
            $message = 'Engine mount added to definition.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$definitions = $pdo->query(
    "SELECT
        d.*,
        (SELECT COUNT(*) FROM definition_fuel_tanks t
         WHERE t.object_definition_id=d.id) AS tank_count,
        (SELECT COALESCE(SUM(quantity),0) FROM definition_engine_mounts m
         WHERE m.object_definition_id=d.id) AS engine_count
     FROM object_definitions d
     ORDER BY d.name"
)->fetchAll();

$engines = $pdo->query(
    "SELECT id,name FROM engine_definitions ORDER BY name"
)->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<?php if ($message): ?><div class="flash"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= h($error) ?></div><?php endif; ?>

<section class="page-card">
  <h1>Object / Component Library</h1>
  <p class="muted">Definitions are reusable designs. Creating one here does not place it into the live universe.</p>
  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="add_definition">
    <div class="field"><label>Name</label><input name="name" required placeholder="Saturn V S-II"></div>
    <div class="field"><label>Slug</label><input name="slug" placeholder="saturn-v-s-ii"></div>
    <div class="field"><label>Type</label><input name="object_type" required placeholder="stage, spacecraft, planet..."></div>
    <div class="field"><label>Dry Mass (kg)</label><input type="number" step="0.000001" min="0" name="dry_mass_kg" required></div>
    <div class="field"><label>Radius / collision radius (m)</label><input type="number" step="0.000001" min="0" name="radius_m"></div>
    <div class="field">
      <label>Physics flags</label>
      <div class="inline">
        <label><input type="checkbox" name="produces_gravity"> Produces gravity</label>
        <label><input type="checkbox" name="affected_by_gravity" checked> Affected by gravity</label>
        <label><input type="checkbox" name="has_orientation" checked> Orientation</label>
      </div>
    </div>
    <div class="field full"><label>Notes</label><textarea name="notes"></textarea></div>
    <div class="field full"><button>Create Definition</button></div>
  </form>
</section>

<section class="page-card">
  <h2>Definition Library</h2>
  <table>
    <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Dry mass</th><th>Tanks</th><th>Engines</th></tr></thead>
    <tbody>
    <?php foreach ($definitions as $d): ?>
      <tr>
        <td><?= (int)$d['id'] ?></td>
        <td><strong><?= h($d['name']) ?></strong><br><span class="muted small"><?= h($d['slug']) ?></span></td>
        <td><?= h($d['object_type']) ?></td>
        <td><?= h((string)$d['dry_mass_kg']) ?> kg</td>
        <td><?= (int)$d['tank_count'] ?></td>
        <td><?= (int)$d['engine_count'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section class="page-card">
  <h2>Add Fuel Tank to a Definition</h2>
  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="add_tank">
    <div class="field"><label>Object Definition</label><select name="object_definition_id" required>
      <?php foreach ($definitions as $d): ?><option value="<?= (int)$d['id'] ?>"><?= h($d['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label>Tank Name</label><input name="tank_name" required placeholder="LOX Tank"></div>
    <div class="field"><label>Propellant Type</label><input name="propellant_type" required placeholder="LOX"></div>
    <div class="field"><label>Capacity (kg)</label><input type="number" step="0.000001" min="0" name="capacity_kg" required></div>
    <div class="field full"><button>Add Tank</button></div>
  </form>
</section>

<section class="page-card">
  <h2>Mount Engine to a Definition</h2>
  <?php if (!$engines): ?>
    <p class="muted">Create an engine definition first.</p>
  <?php else: ?>
  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="add_mount">
    <div class="field"><label>Object Definition</label><select name="object_definition_id" required>
      <?php foreach ($definitions as $d): ?><option value="<?= (int)$d['id'] ?>"><?= h($d['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label>Engine Definition</label><select name="engine_definition_id" required>
      <?php foreach ($engines as $e): ?><option value="<?= (int)$e['id'] ?>"><?= h($e['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label>Quantity</label><input type="number" min="1" name="quantity" value="1" required></div>
    <div class="field"><label>Local thrust vector X</label><input type="number" step="0.000001" name="local_x" value="1" required></div>
    <div class="field"><label>Local thrust vector Y</label><input type="number" step="0.000001" name="local_y" value="0" required></div>
    <div class="field"><label>Local thrust vector Z</label><input type="number" step="0.000001" name="local_z" value="0" required></div>
    <div class="field full"><label>Notes</label><input name="mount_notes"></div>
    <div class="field full"><button>Add Engine Mount</button></div>
  </form>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
