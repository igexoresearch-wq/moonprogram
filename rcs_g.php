<?php
include_once __DIR__ . '/shared/physical_constants.php';

$result = $conn->query(
    "SELECT value
     FROM telemetry
     WHERE attribute = 'RCS'
     LIMIT 1"
);
$rcsAmount = ($result && $row = $result->fetch_assoc())
    ? max(0.0, (float)$row['value'])
    : 0.0;

$capacity = max(0.001, (float)$lm_rcs_initial_propellant);
$rcsPercentage = max(0.0, min(100.0, ($rcsAmount / $capacity) * 100.0));
?>

<div class="dial-container">
    <div class="ticks">
        <?php for ($i = 100; $i >= 0; $i -= 5): ?>
            <div class="tick">
                <div class="<?php echo $i % 10 === 0 ? 'tick-10' : 'tick-5'; ?>"></div>
                <?php if ($i % 10 === 0): ?>
                    <div class="tick-label"><?php echo $i; ?></div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
    <div class="needle" style="bottom:calc(<?php echo number_format($rcsPercentage, 3, '.', ''); ?>% - 2px)"></div>
</div>

<style>
.dial-container {
    width:80px;height:200px;border:2px solid grey;border-radius:10px;
    background:#f0f0f0;box-shadow:inset -3px -3px 5px rgba(255,255,255,.5),
    inset 3px 3px 5px rgba(0,0,0,.2);position:relative;font-family:Arial,sans-serif;color:black
}
.ticks {position:absolute;width:100%;height:100%;display:flex;flex-direction:column;justify-content:space-between;align-items:flex-start;padding:5px;box-sizing:border-box}
.tick {position:relative;width:100%;display:flex;align-items:center}
.tick-10 {border-left:20px solid black;height:1px;width:20px;margin-left:5px}
.tick-5 {border-left:10px solid black;height:1px;width:10px;margin-left:5px}
.tick-label {margin-left:10px;font-size:12px}
.needle {position:absolute;left:0;width:50%;height:5px;background:black}
</style>
