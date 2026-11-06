<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();
require_once __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/station_navigation.php';
$read = static function(string $table)use($conn):array {
 $allowed=['telemetry','moon','command_module','guidance'];
 if(!in_array($table,$allowed,true))return [];
 $out=[];
 try{
  $q=$conn->query('SELECT attribute,value FROM '.$table);
  while($row=$q->fetch_assoc())$out[(string)$row['attribute']]=(string)$row['value'];
 }catch(mysqli_sql_exception $e){return [];}
 return $out;
};
$lm=$read('telemetry');
$moon=$read('moon');
function lmDisplay(array $values,string $key,int $decimals=2):string {
 if(!array_key_exists($key,$values)||!is_numeric($values[$key]))return '---';
 return htmlspecialchars(number_format((float)$values[$key],$decimals,'.',''),ENT_QUOTES);
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>LM | Guidance Computer</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#151a17;color:#ddcc96;font:15px 'Courier New',monospace}
main{padding:16px;max-width:1400px;margin:auto}.console{background:linear-gradient(#292f2a,#151917);border:5px ridge #68695c;padding:14px}
h1{font-size:22px;letter-spacing:2px;margin:5px 0 15px;color:#f4daa3}.grid{display:grid;grid-template-columns:repeat(2,minmax(260px,1fr));gap:12px}
.card{border:2px solid #77765b;padding:12px;background:#202721}h2{font-size:17px;margin:0 0 12px;border-bottom:1px solid #77765b;padding-bottom:8px}
.readout{display:flex;justify-content:space-between;gap:10px;margin:8px 0}.digit{background:#050d07;color:#91e09d;padding:5px 9px;min-width:130px;text-align:right;font-size:19px}
small{color:#b5b5a5}.warn{background:#303121;padding:10px;margin:10px 0}
@media(max-width:650px){.grid{grid-template-columns:1fr}}
</style></head><body><?php moonSimStationNav('LM COMPUTER'); ?>
<main><div class="console"><h1>LUNAR MODULE — GUIDANCE / NAVIGATION</h1>
<div class="grid">
<section class="card"><h2>LM POSITION — INERTIAL</h2>
<?php foreach(['x','y','z'] as $axis): ?><div class="readout"><span><?=strtoupper($axis)?> POSITION</span><span class="digit"><?=lmDisplay($lm,$axis,1)?></span></div><?php endforeach ?>
<small>Coordinates follow the authoritative simulator reference frame.</small></section>
<section class="card"><h2>LM VELOCITY — INERTIAL</h2>
<?php foreach(['delta_x','delta_y','delta_z'] as $axis): ?><div class="readout"><span><?=strtoupper($axis)?></span><span class="digit"><?=lmDisplay($lm,$axis,3)?></span></div><?php endforeach ?>
</section>
<section class="card"><h2>ATTITUDE / RATES</h2>
<?php foreach(['pitch','roll','yaw','pitch_rate','roll_rate','yaw_rate'] as $axis): ?><div class="readout"><span><?=strtoupper(str_replace('_',' ',$axis))?></span><span class="digit"><?=lmDisplay($lm,$axis,2)?></span></div><?php endforeach ?></section>
<section class="card"><h2>LUNAR POSITION REFERENCE</h2>
<?php foreach(['x','y','z'] as $axis): ?><div class="readout"><span>MOON <?=strtoupper($axis)?></span><span class="digit"><?=lmDisplay($moon,$axis,1)?></span></div><?php endforeach ?>
</section>
</div>
<div class="warn">LM computational station. Flight controls remain at LM FLIGHT. Guidance and maneuver requests below stay within this console.</div>
<?php require_once __DIR__.'/shared/console_operations.php';moonSimConsoleOperations('LM'); ?>
</div></main>
<?php require_once __DIR__.'/shared/console_radio.php';moonSimConsoleRadio('CREW'); ?>
</body></html>
