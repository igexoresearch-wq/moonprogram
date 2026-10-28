<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

$pdo = db();
$pageTitle = 'Panel Layout';
$basePath = '../';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $slot = (int)$_POST['slot_number'];
        if ($slot < 1 || $slot > 20) {
            throw new RuntimeException('Panel slot must be 1 through 20.');
        }

        $instrument = $_POST['instrument_id'] !== ''
            ? (int)$_POST['instrument_id']
            : null;

        $stmt = $pdo->prepare(
            "UPDATE command_center_panels
             SET title=:title, instrument_id=:instrument
             WHERE slot_number=:slot"
        );
        $stmt->execute([
            'title'=>trim((string)$_POST['title']) ?: null,
            'instrument'=>$instrument,
            'slot'=>$slot,
        ]);
        $message = 'Panel updated.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$instruments = $pdo->query(
    "SELECT * FROM instrument_definitions
     WHERE context IN ('ground','both')
     ORDER BY name"
)->fetchAll();

$slots = $pdo->query(
    "SELECT p.*, i.name AS instrument_name
     FROM command_center_panels p
     LEFT JOIN instrument_definitions i ON i.id=p.instrument_id
     ORDER BY p.slot_number"
)->fetchAll();

require __DIR__ . '/../partials/header.php';
?>
<?php if ($message): ?><div class="flash"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= h($error) ?></div><?php endif; ?>

<section class="page-card">
<h1>Ground Command Center Panel Layout</h1>
<p class="muted">This edits the ground-control dashboard only. Spacecraft cockpits are built separately under Cockpits.</p>
<table>
<thead><tr><th>Slot</th><th>Current</th><th>Configure</th></tr></thead>
<tbody>
<?php foreach ($slots as $slot): ?>
<tr>
<td><?= (int)$slot['slot_number'] ?></td>
<td><?= h($slot['instrument_name'] ?? 'Empty') ?></td>
<td>
<form method="post" class="inline compact">
<input type="hidden" name="slot_number" value="<?= (int)$slot['slot_number'] ?>">
<input name="title" value="<?= h($slot['title'] ?? '') ?>" placeholder="Panel title" style="width:180px">
<select name="instrument_id">
<option value="">Empty</option>
<?php foreach ($instruments as $i): ?>
<option value="<?= (int)$i['id'] ?>" <?= (int)$slot['instrument_id']===(int)$i['id']?'selected':'' ?>><?= h($i['name']) ?></option>
<?php endforeach; ?>
</select>
<button>Save</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<section class="page-card">
<h2>Installed Instruments</h2>
<table>
<thead><tr><th>Name</th><th>Include</th><th>Description</th></tr></thead>
<tbody>
<?php foreach ($instruments as $i): ?>
<tr><td><?= h($i['name']) ?></td><td><code><?= h($i['include_file']) ?></code></td><td><?= h($i['description'] ?? '') ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
