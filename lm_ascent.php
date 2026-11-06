<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/lm_flight_computer.php';
require_once __DIR__.'/shared/docking_commands.php';
require_once __DIR__.'/shared/physical_constants.php';
if (!isset($_SESSION['lm_ascent_token'])) $_SESSION['lm_ascent_token']=bin2hex(random_bytes(20));
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['lm_ascent_token'],(string)($_POST['token']??''))) {
        $message='Invalid request';
    } else {
        $command=(string)($_POST['command']??'');
        if (in_array($command,['LM_ASCENT_STAGE','LM_ABORT_ASCENT'],true)) {
            $flags=[];
            $r=$conn->query("SELECT attribute,value FROM command_module WHERE attribute IN ('dock','stage3')");
            if ($r) while($row=$r->fetch_assoc()) $flags[$row['attribute']]=(int)$row['value'];
            $lmStage=$conn->query("SELECT value FROM telemetry WHERE attribute='descent' LIMIT 1");
            $stage=$lmStage? (int)($lmStage->fetch_assoc()['value']??0):0;
            if (!isset($flags['dock'],$flags['stage3']) || $flags['dock']!==0 || $flags['stage3']!==0 || $stage!==1) {
                $message='LM must be independent with descent stage attached.';
            } else {
                moonSimQueueDockingCommand($conn,$command);
                $message=$command==='LM_ABORT_ASCENT'?'Abort ascent requested; APS ignition requested.':'Ascent-stage separation queued; ignite APS using existing LM throttle control.';
            }
        }
    }
}
function readState(mysqli $db,string $table):array {
    $out=[]; $r=$db->query("SELECT attribute,value FROM $table");
    if ($r) while($row=$r->fetch_assoc()) $out[$row['attribute']]=(float)$row['value'];
    return $out;
}
$solution=null;$error='';
try {
    $solution=moonSimLmFlightSolution(readState($conn,'telemetry'),readState($conn,'command_module'),readState($conn,'moon'),$moon_radius,$gravitational_constant*$moon_mass);
} catch(Throwable $e) {$error='Telemetry unavailable or incomplete.';}
function escLm(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LM Ascent and Rendezvous</title><style>
body{background:#151a21;color:#eee;font:16px system-ui;padding:24px}main{max-width:900px;margin:auto}section{border:1px solid #61717e;padding:18px;margin:12px 0}button{background:#36485a;color:white;border:1px solid #9fb4c7;padding:12px;margin:6px}dt{color:#a9bad0}dd{margin:0 0 10px;font-weight:bold}
</style></head><body><main><h1>LM ASCENT / RENDEZVOUS</h1>
<p><?=escLm($message)?> <?=escLm($error)?></p>
<section><h2>Flight solution — live telemetry</h2>
<?php if ($solution): ?><dl><?php foreach($solution as $name=>$value): ?>
<dt><?=escLm(str_replace('_',' ',strtoupper($name)))?></dt>
<dd><?=is_array($value)?escLm(implode(', ',array_map(static fn($n)=>number_format($n,2,'.',''),$value))):($value===null?'unbound':number_format($value,2,'.',''))?></dd>
<?php endforeach; ?></dl><?php endif; ?>
<p>These are measured relative quantities, not an automatic rendezvous burn solution. Match orbit and approach manually; docking still requires safe closure and alignment.</p></section>
<section><h2>Ascent stage</h2><form method="post"><input type="hidden" name="token" value="<?=escLm($_SESSION['lm_ascent_token'])?>">
<button name="command" value="LM_ASCENT_STAGE">Jettison Descent Stage</button>
<button name="command" value="LM_ABORT_ASCENT">Abort / Ignite Ascent Engine</button></form>
<p>Commands go through the existing central physics queue. No repositioning or refueling is performed. The first command separates stages without firing the engine; the second requests APS thrust.</p></section>
</main></body></html>
