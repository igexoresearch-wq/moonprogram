<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

require __DIR__ . '/physics/db.php';
require_once __DIR__ . '/shared/mission_outcome.php';
require_once __DIR__ . '/shared/orbital_mechanics.php';
require_once __DIR__ . '/shared/physical_constants.php';
header('Content-Type: text/html; charset=utf-8');

$contact=null;
$r=$conn->query("SELECT body,contact_state,impact_speed FROM surface_contact WHERE vehicle='CM' LIMIT 1");
if ($r) $contact=$r->fetch_assoc();
$cm=[]; $r=$conn->query("SELECT attribute,value FROM command_module");
if ($r) while($row=$r->fetch_assoc()) $cm[$row['attribute']]=(float)$row['value'];
$chute=null; $r=$conn->query('SELECT chute_state FROM reentry_state WHERE id=1 LIMIT 1');
if ($r && ($row=$r->fetch_assoc())) $chute=(string)$row['chute_state'];
$outcome=moonSimMissionOutcome(
    $contact,
    (int)($cm['service_module']??1)===1,
    (int)($cm['escape']??1)===1,
    $chute
);
$range=null; $speed=null; $periapsis=null;
if (isset($cm['x'],$cm['y'],$cm['z'],$cm['delta_x'],$cm['delta_y'],$cm['delta_z'])) {
    $radius=sqrt(($cm['x']-$earth_center_x)**2+($cm['y']-$earth_center_y)**2+($cm['z']-$earth_center_z)**2);
    $range=$radius-$earth_radius;
    $speed=sqrt($cm['delta_x']**2+$cm['delta_y']**2+$cm['delta_z']**2);
    $orbit=moonSimOrbitalElements(
        [$cm['x'],$cm['y'],$cm['z']],
        [$cm['delta_x'],$cm['delta_y'],$cm['delta_z']],
        [$earth_center_x,$earth_center_y,$earth_center_z],
        $gravitational_constant*$earth_mass
    );
    if (isset($orbit['periapsis_radius']) && $orbit['periapsis_radius']!==null) {
        $periapsis=(float)$orbit['periapsis_radius']-$earth_radius;
    }
}
function escOutcome(string $value):string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mission Outcome | Moon Simulator</title>
<style>body{font:16px system-ui;background:#101820;color:#eff5fa;margin:0;padding:25px}main{max-width:900px;margin:auto}section{background:#1c2b38;padding:18px;border:1px solid #506273;margin:15px 0}h1{letter-spacing:.08em}.win{color:#95edaa}.warn{color:#ffc777}dt{color:#b5cedd}dd{margin:0 0 10px;font-size:1.15em}</style></head><body><main>
<h1>MISSION CONTROL — EARTH RECOVERY</h1>
<section><h2 class="<?=!empty($outcome['win'])?'win':'warn'?>"><?=escOutcome($outcome['state'])?></h2><p><?=escOutcome($outcome['reason'])?></p>
<p>Only a verified survivable Command Module landing on Earth counts as victory. A lunar landing or partial mission completion does not.</p></section>
<section><h2>Live recovery navigation</h2><dl>
<dt>Earth altitude</dt><dd><?=$range===null?'Unknown':number_format($range/1000,2).' km'?></dd>
<dt>Earth-relative speed</dt><dd><?=$speed===null?'Unknown':number_format($speed,2).' m/s'?></dd>
<dt>Osculating Earth periapsis altitude</dt><dd><?=$periapsis===null?'Unavailable (trajectory may be unbound)':number_format($periapsis/1000,2).' km'?></dd>
<dt>Chute state</dt><dd><?=escOutcome($chute??'Unavailable')?></dd>
</dl><p>These are instantaneous estimates, not a guaranteed reentry corridor. Earth-only osculating periapsis ignores the Moon's future perturbations and atmospheric drag. A negative periapsis alone does not guarantee a survivable landing.</p>
</section><section><h2>Flight operations</h2><p>Manual guidance and original cockpit controls remain available throughout flight. No recovery burn is commanded by this page. Use the current flight data to decide whether to continue, abort, or attempt Earth return.</p></section>
</main></body></html>
