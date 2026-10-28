<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

$pdo = db();
$pageTitle = 'Engine Definitions';
$basePath = '../';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_engine') {
            $name = trim((string)$_POST['name']);
            $slug = slugify((string)($_POST['slug'] ?: $name));

            $stmt = $pdo->prepare(
                "INSERT INTO engine_definitions (
                    slug, name, engine_type, dry_mass_kg, thrust_n,
                    fuel_rate_kg_s, minimum_throttle_percent,
                    maximum_throttle_percent, restartable, notes
                 ) VALUES (
                    :slug, :name, :type, :mass, :thrust,
                    :fuel_rate, :min_throttle, :max_throttle,
                    :restartable, :notes
                 )"
            );
            $stmt->execute([
                'slug' => $slug,
                'name' => $name,
                'type' => trim((string)$_POST['engine_type']),
                'mass' => (string)$_POST['dry_mass_kg'],
                'thrust' => (string)$_POST['thrust_n'],
                'fuel_rate' => (string)$_POST['fuel_rate_kg_s'],
                'min_throttle' => (string)$_POST['minimum_throttle_percent'],
                'max_throttle' => (string)$_POST['maximum_throttle_percent'],
                'restartable' => isset($_POST['restartable']) ? 1 : 0,
                'notes' => trim((string)$_POST['notes']) ?: null,
            ]);
            $message = 'Engine definition created.';
        }

        if ($action === 'add_propellant') {
            $stmt = $pdo->prepare(
                "INSERT INTO engine_propellant_requirements (
                    engine_definition_id, propellant_type, mass_fraction
                 ) VALUES (:engine_id, :type, :fraction)"
            );
            $stmt->execute([
                'engine_id' => (int)$_POST['engine_definition_id'],
                'type' => trim((string)$_POST['propellant_type']),
                'fraction' => (string)$_POST['mass_fraction'],
            ]);
            $message = 'Propellant requirement added.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$engines = $pdo->query(
    "SELECT
        e.*,
        GROUP_CONCAT(CONCAT(r.propellant_type, ' ', ROUND(r.mass_fraction*100,3), '%')
                     ORDER BY r.id SEPARATOR ', ') AS propellants
     FROM engine_definitions e
     LEFT JOIN engine_propellant_requirements r
       ON r.engine_definition_id=e.id
     GROUP BY e.id
     ORDER BY e.name"
)->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<?php if ($message): ?><div class="flash"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= h($error) ?></div><?php endif; ?>

<section class="page-card">
  <h1>Engine Library</h1>
  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="add_engine">
    <div class="field"><label>Name</label><input name="name" required placeholder="J-2"></div>
    <div class="field"><label>Slug</label><input name="slug" placeholder="j-2"></div>
    <div class="field"><label>Engine Type</label><input name="engine_type" required placeholder="liquid rocket"></div>
    <div class="field"><label>Dry Mass (kg)</label><input type="number" step="0.000001" min="0" name="dry_mass_kg" value="0" required></div>
    <div class="field"><label>Vacuum / modeled thrust (N)</label><input type="number" step="0.000001" min="0" name="thrust_n" required></div>
    <div class="field"><label>Total propellant flow (kg/s)</label><input type="number" step="0.000000001" min="0" name="fuel_rate_kg_s" required></div>
    <div class="field"><label>Minimum throttle %</label><input type="number" step="0.001" min="0" max="100" name="minimum_throttle_percent" value="0"></div>
    <div class="field"><label>Maximum throttle %</label><input type="number" step="0.001" min="0" max="100" name="maximum_throttle_percent" value="100"></div>
    <div class="field"><label><input type="checkbox" name="restartable" checked> Restartable</label></div>
    <div class="field full"><label>Notes</label><textarea name="notes"></textarea></div>
    <div class="field full"><button>Create Engine</button></div>
  </form>
</section>

<section class="page-card">
  <h2>Add Propellant Requirement</h2>
  <p class="muted">Fractions for an engine should total 1.0. Example: LOX 0.73 + LH2 0.27.</p>
  <form class="grid-form" method="post">
    <input type="hidden" name="action" value="add_propellant">
    <div class="field"><label>Engine</label><select name="engine_definition_id" required>
      <?php foreach ($engines as $e): ?><option value="<?= (int)$e['id'] ?>"><?= h($e['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="field"><label>Propellant Type</label><input name="propellant_type" required placeholder="LOX"></div>
    <div class="field"><label>Mass Fraction</label><input type="number" step="0.000000001" min="0" max="1" name="mass_fraction" required placeholder="0.73"></div>
    <div class="field full"><button>Add Requirement</button></div>
  </form>
</section>

<section class="page-card">
  <h2>Engines</h2>
  <table>
    <thead><tr><th>ID</th><th>Engine</th><th>Thrust</th><th>Flow</th><th>Throttle</th><th>Propellants</th></tr></thead>
    <tbody>
    <?php foreach ($engines as $e): ?>
      <tr>
        <td><?= (int)$e['id'] ?></td>
        <td><strong><?= h($e['name']) ?></strong><br><span class="muted small"><?= h($e['engine_type']) ?></span></td>
        <td><?= number_format((float)$e['thrust_n'], 1) ?> N</td>
        <td><?= h((string)$e['fuel_rate_kg_s']) ?> kg/s</td>
        <td><?= h((string)$e['minimum_throttle_percent']) ?>–<?= h((string)$e['maximum_throttle_percent']) ?>%</td>
        <td><?= h($e['propellants'] ?? 'none') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
