<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

/**
 * Launch AGC and Saturn engine inventory. Read-only. Never executes stored
 * guidance code or writes simulator state.
 */
$secret=getenv('MOON_OPERATOR_PASSWORD');
if (!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))) {
    header('WWW-Authenticate: Basic realm="Moon Simulator"');
    http_response_code(401);
    exit('Operator authentication required');
}
require __DIR__.'/physics/db.php';
header('Content-Type: text/html; charset=utf-8');
function safeLaunch(string $v):string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
$programs=[]; $result=$conn->query("SELECT id,execute,program FROM CMguidance WHERE id BETWEEN 16 AND 24 ORDER BY id");
if($result) while($row=$result->fetch_assoc()){
    $source=(string)($row['program']??'');
    $programs[(int)$row['id']]=[
        'present'=>trim($source)!=='',
        'execute'=>(int)$row['execute'],
        'includes_lseq'=>strpos($source,'lseq.php')!==false,
        'source_bytes'=>strlen($source),
    ];
}
$stageRuntime=null;
$tableResult=$conn->query("SHOW TABLES LIKE 'saturn_stage_runtime'");
if ($tableResult && $tableResult->num_rows>0) {
    $runtimeResult=$conn->query("SELECT * FROM saturn_stage_runtime WHERE id=1");
    $stageRuntime=$runtimeResult?$runtimeResult->fetch_assoc():null;
}
$stageFlags=[];
$flagRows=$conn->query("SELECT attribute,value FROM command_module
    WHERE attribute IN ('stage1','stage2','stage3','s1fuel','s2fuel','s3fuel','staging','thrust','ABORT')");
if($flagRows)while($r=$flagRows->fetch_assoc())$stageFlags[(string)$r['attribute']]=$r['value'];
$registers=[];$result=$conn->query("SELECT id,program_number,seconds FROM CMprogram_register ORDER BY id LIMIT 5");
if($result) while($row=$result->fetch_assoc())$registers[]=$row;
$engines=[];$result=$conn->query("SELECT engine_stage,engine_name,component,status,operational_status FROM EngineStatus WHERE engine_stage IN ('First Stage','Second Stage','Third Stage') AND component='Combustion Chamber' ORDER BY engine_stage,engine_name");
if($result) while($row=$result->fetch_assoc())$engines[]=$row;
$groups=[];
foreach($engines as $row) {
    $stage=(string)$row['engine_stage'];
    if(!isset($groups[$stage]))$groups[$stage]=[];
    $groups[$stage][(string)$row['engine_name']] = (string)$row['status'];
}
$required=[16=>'S-IC ignition',17=>'S-II ignition',18=>'S-IVB ignition',19=>'S-IC separation',20=>'S-II separation',23=>'Launch sequence',24=>'Countdown'];
$ready=true; // Launch and staging are executed by versioned physics code, not DB P16-P24.
// Inspect program text without executing it. Flag basic incompatibilities,
 // but only a real flight can verify the resulting physical stage behavior.
$programAudit=[];
foreach($required as $id=>$label) {
    $entry=$programs[$id]??null;
    $programAudit[$id]=[
        'present'=>(bool)($entry['present']??false),
        'launch_hook'=>$id===23?(bool)($entry['includes_lseq']??false):null,
        'static_status'=>!($entry['present']??false)?'MISSING':
            ($id===23 && !($entry['includes_lseq']??false)?'NO LAUNCH HOOK':'PRESENT — NOT EXECUTION VERIFIED'),
    ];
}
$center=[];
foreach(['First Stage','Second Stage'] as $stage){
    $names=array_keys($groups[$stage]??[]);
    $centers=array_filter($names,static fn($name)=>(bool)preg_match('/\b(center|centre|central)\b/i',$name));
    $center[$stage]=count($names)===5 && count($centers)===1;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="refresh" content="15"><title>Saturn Launch Readiness</title>
<style>body{font:16px system-ui;background:#101923;color:#eef4fa;padding:25px}main{max-width:1100px;margin:auto}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #51606d;text-align:left}section{padding:16px;border:1px solid #64707c;margin:16px 0}.bad{color:#ffb0a7}.ok{color:#a9edb0}</style></head><body><main><h1>SATURN V — LAUNCH AGC DIAGNOSTICS</h1>
<p>This page is read-only; it never evaluates the guidance code or commands engines. Refreshes every 15 seconds.</p>
<section><h2>Database program inventory</h2><p class="<?= $ready?'ok':'bad'?>"><?= $ready?'Launch uses checked-in physics programs; historic DB source is diagnostic only.':'One or more optional database records are missing.'?></p>
<table><tr><th>Program</th><th>Function</th><th>Source</th><th>Pending execute</th><th>lseq.php reference</th></tr>
<?php foreach($required as $id=>$label):$p=$programs[$id]??null;?><tr><td><?= $id?></td><td><?=safeLaunch($label)?></td><td><?=!empty($p['present'])?'Present ('.(int)$p['source_bytes'].' bytes)':'MISSING'?></td><td><?= (int)($p['execute']??0)?></td><td><?=!empty($p['includes_lseq'])?'Yes':'No'?></td></tr><?php endforeach;?></table>
<p>Physical P16–P20 and launch P23 execute in checked-in PHP physics controllers, not in these legacy program bodies. Missing database code is no longer an ignition/staging blocker. Existing AGC registers continue to count down for cockpit compatibility.</p></section>
<section><h2>Launch-program compatibility warnings</h2>
<?php foreach($required as $id=>$label):$a=$programAudit[$id];?>
<p class="<?=($a['present']&&($a['launch_hook']!==false))?'ok':'bad'?>">P<?= $id ?>: <?=safeLaunch($a['static_status'])?></p>
<?php endforeach;?>
<p>These are read-only static checks. P16–P20 are legacy physical commands now bypassed in favor of the stage worker; P23 is supplied by the repository module. Test physical sequence when the simulator is deployed.</p></section>
<section><h2>Five-engine stage inventory</h2><table><tr><th>Stage</th><th>Modelled engines</th><th>Unique center</th><th>Engine names / current states</th></tr>
<?php foreach(['First Stage','Second Stage','Third Stage'] as $stage):?><tr><td><?=safeLaunch($stage)?></td><td><?=count($groups[$stage]??[])?></td><td><?=isset($center[$stage])?($center[$stage]?'Yes':'No; cutoff withheld'):'N/A'?></td><td><?php foreach($groups[$stage]??[] as $name=>$status) echo safeLaunch($name.' '.$status).' / ';?></td></tr><?php endforeach;?></table></section>
<section><h2>Stage sequencing prerequisites (static inspection)</h2>
<?php
$stageChecks=[
    'S-IC engine records'=>count($groups['First Stage']??[])>0,
    'S-II engine records'=>count($groups['Second Stage']??[])>0,
    'S-IVB engine records'=>count($groups['Third Stage']??[])>0,
    'Repository launch guidance module'=>is_file(__DIR__.'/physics/ascent_guidance.php'),
];
foreach($stageChecks as $label=>$ok):?>
<p class="<?= $ok?'ok':'bad' ?>"><?=safeLaunch($label)?>: <?= $ok?'present':'MISSING' ?></p>
<?php endforeach;?>
<p>Presence alone cannot certify thrust, shutdown, separation timing or successful engine ignition. Actual database engine labels and all stage actions require a complete observed flight.</p></section>
<section><h2>Physical Saturn stage controller — live database state</h2>
<p>P16–P20 physical ignition/separation is owned by <code>physics/saturn_stage_transitions.php</code>. Legacy scripts are bypassed. P23 launch guidance is handled by the repository module.</p>
<?php if($stageRuntime===null):?><p class="bad">Stage ignition latch row is missing. The physics worker must initialize saturn_stage_runtime before automatic ignition is available.</p>
<?php else:?>
<table><tr><th>Runtime field</th><th>Value</th></tr>
<?php foreach($stageRuntime as $key=>$value):?><tr><td><?=safeLaunch((string)$key)?></td><td><?=safeLaunch((string)$value)?></td></tr><?php endforeach;?></table>
<?php endif;?>
<h3>Current stage and fuel telemetry</h3>
<table><tr><th>Attribute</th><th>Value</th></tr>
<?php foreach($stageFlags as $key=>$value):?><tr><td><?=safeLaunch((string)$key)?></td><td><?=safeLaunch((string)$value)?></td></tr><?php endforeach;?></table>
<p>Confirm engine status matches the active stage, the ignition latch stays set after shutdown, and each separation leaves exactly one fewer Saturn stage attached.</p></section>
<section><h2>AGC registers</h2><table><tr><th>Register</th><th>Program</th><th>Remaining seconds</th></tr>
<?php foreach($registers as $row):?><tr><td><?= (int)$row['id']?></td><td><?= (int)$row['program_number']?></td><td><?=safeLaunch((string)$row['seconds'])?></td></tr><?php endforeach;?></table></section>
</main></body></html>
