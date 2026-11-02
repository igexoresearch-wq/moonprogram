<?php
declare(strict_types=1);
$secret=getenv('MOON_OPERATOR_PASSWORD');
if (!is_string($secret) || $secret==='' || !hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW'] ?? ''))) {
    header('WWW-Authenticate: Basic realm="Moon Simulator"');
    http_response_code(401); exit('Operator authentication required');
}
session_start();
require __DIR__.'/physics/db.php';
require_once __DIR__.'/physics/lm_descent_assist.php';
moonSimEnsureLmAssist($conn);
if (!isset($_SESSION['lm_assist_csrf'])) $_SESSION['lm_assist_csrf']=bin2hex(random_bytes(20));
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['lm_assist_csrf'],(string)($_POST['token']??''))) {
        $message='Invalid request';
    } else {
        $mode=(string)($_POST['mode']??'');
        if ($mode==='on' || $mode==='off') {
            // Do not run assisted descent on a docked/attached LM.
            $state=$conn->query("SELECT attribute,value FROM command_module WHERE attribute IN ('dock','stage3')");
            $flags=[];
            if ($state) while($r=$state->fetch_assoc()) $flags[$r['attribute']]=(int)$r['value'];
            if ($mode==='on' && (($flags['dock']??1) || ($flags['stage3']??1))) {
                $message='Undock and separate the LM before activating descent assist.';
            } else {
                $on=$mode==='on'?1:0;
                $stmt=$conn->prepare("UPDATE lm_descent_assist SET enabled=?,status=? WHERE id=1");
                $status=$on?'ARMED':'MANUAL';
                $stmt->bind_param('is',$on,$status);
                $stmt->execute();
                if (!$on) $conn->query("UPDATE telemetry SET value=0 WHERE attribute='thrust'");
                $message=$on?'Descent assist armed. Point LM engine toward lunar up.':'Manual throttle restored (engine idle).';
            }
        }
    }
}
$r=$conn->query('SELECT * FROM lm_descent_assist WHERE id=1');
$data=$r?$r->fetch_assoc():[];
function h(string $v):string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LM Descent Assist</title>
<style>body{background:#16191e;color:#eee;font:16px system-ui;padding:24px}main{max-width:700px;margin:auto;border:1px solid #596673;padding:20px}button{padding:12px 22px;margin:6px;background:#344558;color:white;border:1px solid #abc}strong{color:#bce6a1}</style></head><body><main><h1>LM DESCENT ASSIST</h1>
<p>State: <strong><?=h((string)($data['status']??'UNKNOWN'))?></strong> / <?=!empty($data['enabled'])?'ON':'OFF'?></p>
<p>Altitude: <?=number_format((float)($data['altitude_m']??0),1)?> m |
Radial velocity: <?=number_format((float)($data['radial_speed_m_s']??0),2)?> m/s |
Lateral velocity: <?=number_format((float)($data['tangential_speed_m_s']??0),2)?> m/s</p>
<p><?=h($message)?></p><form method="post"><input type="hidden" name="token" value="<?=h($_SESSION['lm_assist_csrf'])?>">
<button name="mode" value="on">Enable Descent Assist</button><button name="mode" value="off">Manual / Engine Idle</button></form>
<p>Assistance controls throttle only; the pilot must align the LM engine toward local lunar up. It is not a full autonomous landing program. Reload to update telemetry.</p></main></body></html>
