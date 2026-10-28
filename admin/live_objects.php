<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

$pdo = db();
$pageTitle = 'Live Universe';
$basePath = '../';
$message = null;
$error = null;

function metersToStoredCm(float $meters): array
{
    $cmFloat = $meters * 100.0;
    $whole = (int)floor($cmFloat);
    return [$whole, $cmFloat - $whole];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'state') {
            [$xCm,$xSub] = metersToStoredCm((float)$_POST['x_m']);
            [$yCm,$ySub] = metersToStoredCm((float)$_POST['y_m']);
            [$zCm,$zSub] = metersToStoredCm((float)$_POST['z_m']);

            $stmt = $pdo->prepare(
                "UPDATE live_objects SET
                    x_cm=:x_cm, x_subcm=:x_sub,
                    y_cm=:y_cm, y_subcm=:y_sub,
                    z_cm=:z_cm, z_subcm=:z_sub,
                    vx_cm_s=:vx, vy_cm_s=:vy, vz_cm_s=:vz,
                    pitch_deg=:pitch, yaw_deg=:yaw, roll_deg=:roll,
                    pitch_rate_deg_s=:pitch_rate,
                    yaw_rate_deg_s=:yaw_rate,
                    roll_rate_deg_s=:roll_rate,
                    surface_locked=:surface_locked
                 WHERE id=:id"
            );
            $stmt->execute([
                'x_cm'=>$xCm, 'x_sub'=>number_format($xSub,9,'.',''),
                'y_cm'=>$yCm, 'y_sub'=>number_format($ySub,9,'.',''),
                'z_cm'=>$zCm, 'z_sub'=>number_format($zSub,9,'.',''),
                'vx'=>number_format((float)$_POST['vx_m_s']*100.0,9,'.',''),
                'vy'=>number_format((float)$_POST['vy_m_s']*100.0,9,'.',''),
                'vz'=>number_format((float)$_POST['vz_m_s']*100.0,9,'.',''),
                'pitch'=>$_POST['pitch_deg'] !== '' ? (string)$_POST['pitch_deg'] : null,
                'yaw'=>$_POST['yaw_deg'] !== '' ? (string)$_POST['yaw_deg'] : null,
                'roll'=>$_POST['roll_deg'] !== '' ? (string)$_POST['roll_deg'] : null,
                'pitch_rate'=>$_POST['pitch_rate_deg_s'] !== '' ? (string)$_POST['pitch_rate_deg_s'] : null,
                'yaw_rate'=>$_POST['yaw_rate_deg_s'] !== '' ? (string)$_POST['yaw_rate_deg_s'] : null,
                'roll_rate'=>$_POST['roll_rate_deg_s'] !== '' ? (string)$_POST['roll_rate_deg_s'] : null,
                'surface_locked'=>isset($_POST['surface_locked']) ? 1 : 0,
                'id'=>(int)$_POST['object_id'],
            ]);
            $message = 'Object state updated.';
        }

        if ($action === 'detach') {
            $stmt = $pdo->prepare(
                "UPDATE live_objects
                 SET is_attached=0, parent_object_id=NULL
                 WHERE id=:id"
            );
            $stmt->execute(['id'=>(int)$_POST['object_id']]);
            $message = 'Object detached and is now independently simulated.';
        }

        if ($action === 'attach') {
            $childId = (int)$_POST['object_id'];
            $parentId = (int)$_POST['parent_object_id'];

            if ($childId === $parentId) {
                throw new RuntimeException('An object cannot attach to itself.');
            }

            $parentStmt = $pdo->prepare("SELECT * FROM live_objects WHERE id=:id");
            $parentStmt->execute(['id'=>$parentId]);
            $parent = $parentStmt->fetch();

            if (!$parent) {
                throw new RuntimeException('Parent object not found.');
            }

            $stmt = $pdo->prepare(
                "UPDATE live_objects SET
                    parent_object_id=:parent,
                    is_attached=1,
                    x_cm=:x, y_cm=:y, z_cm=:z,
                    x_subcm=:xs, y_subcm=:ys, z_subcm=:zs,
                    vx_cm_s=:vx, vy_cm_s=:vy, vz_cm_s=:vz,
                    pitch_deg=:pitch, yaw_deg=:yaw, roll_deg=:roll
                 WHERE id=:id"
            );
            $stmt->execute([
                'parent'=>$parentId,
                'x'=>$parent['x_cm'],'y'=>$parent['y_cm'],'z'=>$parent['z_cm'],
                'xs'=>$parent['x_subcm'],'ys'=>$parent['y_subcm'],'zs'=>$parent['z_subcm'],
                'vx'=>$parent['vx_cm_s'],'vy'=>$parent['vy_cm_s'],'vz'=>$parent['vz_cm_s'],
                'pitch'=>$parent['pitch_deg'],'yaw'=>$parent['yaw_deg'],'roll'=>$parent['roll_deg'],
                'id'=>$childId,
            ]);
            $message = 'Object attached.';
        }

        if ($action === 'throttle') {
            $stmt = $pdo->prepare(
                "UPDATE live_engines
                 SET throttle_percent=:throttle,
                     enabled=:enabled
                 WHERE id=:id"
            );
            $stmt->execute([
                'throttle'=>(string)max(0,min(100,(float)$_POST['throttle_percent'])),
                'enabled'=>isset($_POST['enabled']) ? 1 : 0,
                'id'=>(int)$_POST['live_engine_id'],
            ]);
            $message = 'Engine state updated.';
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM live_objects WHERE id=:id");
            $stmt->execute(['id'=>(int)$_POST['object_id']]);
            $message = 'Live object deleted.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$objects = $pdo->query(
    "SELECT
        o.*,
        m.name AS mission_name,
        d.name AS definition_name,
        p.name AS parent_name,
        o.dry_mass_kg
          + COALESCE((SELECT SUM(t.remaining_kg) FROM live_fuel_tanks t WHERE t.live_object_id=o.id),0)
          AS current_mass_kg
     FROM live_objects o
     LEFT JOIN missions m ON m.id=o.mission_id
     LEFT JOIN object_definitions d ON d.id=o.definition_id
     LEFT JOIN live_objects p ON p.id=o.parent_object_id
     ORDER BY o.id"
)->fetchAll();

$engineRows = $pdo->query(
    "SELECT
        le.*, ed.name AS engine_name, o.name AS object_name
     FROM live_engines le
     JOIN engine_definitions ed ON ed.id=le.engine_definition_id
     JOIN live_objects o ON o.id=le.live_object_id
     ORDER BY le.live_object_id, le.id"
)->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<?php if ($message): ?><div class="flash"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= h($error) ?></div><?php endif; ?>

<section class="page-card">
<h1>Live Universe</h1>
<p class="muted">Authoritative positions are stored as signed centimeter integers plus sub-centimeter remainder. This page displays and edits them in meters.</p>
<table>
<thead><tr><th>ID</th><th>Object</th><th>Mission</th><th>Mass</th><th>Attachment</th><th>Position (m)</th><th>Velocity (m/s)</th></tr></thead>
<tbody>
<?php foreach ($objects as $o): ?>
<?php
  $x=((float)$o['x_cm']+(float)$o['x_subcm'])/100.0;
  $y=((float)$o['y_cm']+(float)$o['y_subcm'])/100.0;
  $z=((float)$o['z_cm']+(float)$o['z_subcm'])/100.0;
?>
<tr>
<td><?= (int)$o['id'] ?></td>
<td><strong><?= h($o['name']) ?></strong><br><span class="small muted"><?= h($o['definition_name'] ?? 'custom') ?></span></td>
<td><?= h($o['mission_name'] ?? 'Universe') ?></td>
<td><?= number_format((float)$o['current_mass_kg'],3) ?> kg</td>
<td><?= $o['is_attached'] ? '→ #'.(int)$o['parent_object_id'].' '.h($o['parent_name']) : 'Free' ?></td>
<td><?= number_format($x,2) ?>, <?= number_format($y,2) ?>, <?= number_format($z,2) ?></td>
<td><?= number_format((float)$o['vx_cm_s']/100,3) ?>, <?= number_format((float)$o['vy_cm_s']/100,3) ?>, <?= number_format((float)$o['vz_cm_s']/100,3) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<section class="page-card">
<h2>Edit Object State</h2>
<form class="grid-form" method="post">
<input type="hidden" name="action" value="state">
<div class="field"><label>Object</label><select name="object_id" required>
<?php foreach ($objects as $o): ?><option value="<?= (int)$o['id'] ?>">#<?= (int)$o['id'] ?> <?= h($o['name']) ?></option><?php endforeach; ?>
</select></div>
<div class="field"><label>Surface Locked</label><label><input type="checkbox" name="surface_locked"> Hold object fixed at current state</label></div>
<div class="field"><label>X (m)</label><input type="number" step="0.000001" name="x_m" value="0" required></div>
<div class="field"><label>Y (m)</label><input type="number" step="0.000001" name="y_m" value="0" required></div>
<div class="field"><label>Z (m)</label><input type="number" step="0.000001" name="z_m" value="0" required></div>
<div class="field"><label>VX (m/s)</label><input type="number" step="0.000001" name="vx_m_s" value="0" required></div>
<div class="field"><label>VY (m/s)</label><input type="number" step="0.000001" name="vy_m_s" value="0" required></div>
<div class="field"><label>VZ (m/s)</label><input type="number" step="0.000001" name="vz_m_s" value="0" required></div>
<div class="field"><label>Pitch (deg)</label><input type="number" step="0.0001" name="pitch_deg" value="0"></div>
<div class="field"><label>Yaw (deg)</label><input type="number" step="0.0001" name="yaw_deg" value="0"></div>
<div class="field"><label>Roll (deg)</label><input type="number" step="0.0001" name="roll_deg" value="0"></div>
<div class="field"><label>Pitch rate (deg/s)</label><input type="number" step="0.00001" name="pitch_rate_deg_s" value="0"></div>
<div class="field"><label>Yaw rate (deg/s)</label><input type="number" step="0.00001" name="yaw_rate_deg_s" value="0"></div>
<div class="field"><label>Roll rate (deg/s)</label><input type="number" step="0.00001" name="roll_rate_deg_s" value="0"></div>
<div class="field full"><button>Update State</button></div>
</form>
</section>

<section class="page-card">
<h2>Attach / Detach</h2>
<div class="inline">
<form method="post" class="inline compact">
<input type="hidden" name="action" value="attach">
<select name="object_id"><?php foreach ($objects as $o): ?><option value="<?= (int)$o['id'] ?>"><?= h($o['name']) ?></option><?php endforeach; ?></select>
<span>to</span>
<select name="parent_object_id"><?php foreach ($objects as $o): ?><option value="<?= (int)$o['id'] ?>"><?= h($o['name']) ?></option><?php endforeach; ?></select>
<button>Attach</button>
</form>
<form method="post" class="inline compact">
<input type="hidden" name="action" value="detach">
<select name="object_id"><?php foreach ($objects as $o): ?><option value="<?= (int)$o['id'] ?>"><?= h($o['name']) ?></option><?php endforeach; ?></select>
<button>Detach</button>
</form>
</div>
</section>

<section class="page-card">
<h2>Live Engines</h2>
<?php if (!$engineRows): ?><p class="muted">No live engines. Instantiate a definition that has engine mounts.</p><?php endif; ?>
<table>
<thead><tr><th>Object</th><th>Engine</th><th>Qty</th><th>Control</th></tr></thead>
<tbody>
<?php foreach ($engineRows as $e): ?>
<tr>
<td><?= h($e['object_name']) ?></td>
<td><?= h($e['engine_name']) ?></td>
<td><?= (int)$e['quantity'] ?></td>
<td>
<form method="post" class="inline compact">
<input type="hidden" name="action" value="throttle">
<input type="hidden" name="live_engine_id" value="<?= (int)$e['id'] ?>">
<input type="number" name="throttle_percent" min="0" max="100" step="0.1" value="<?= h((string)$e['throttle_percent']) ?>" style="width:85px">
<label><input type="checkbox" name="enabled" <?= $e['enabled']?'checked':'' ?>> enabled</label>
<button>Set</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<section class="page-card">
<h2>Delete Live Object</h2>
<form method="post" class="inline compact">
<input type="hidden" name="action" value="delete">
<select name="object_id"><?php foreach ($objects as $o): ?><option value="<?= (int)$o['id'] ?>">#<?= (int)$o['id'] ?> <?= h($o['name']) ?></option><?php endforeach; ?></select>
<button class="danger" data-confirm="Delete this live object and its telemetry/fuel/engine instances?">Delete</button>
</form>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
