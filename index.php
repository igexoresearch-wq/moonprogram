<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();
$stations=['cmc'=>'CM Commander','cmp'=>'CM Pilot','lmc'=>'LM Commander','lmp'=>'LM Pilot','fido'=>'FIDO','flight'=>'Flight Director'];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Moon Program</title>
<style>body{margin:0;background:#777;color:white;font:18px Arial,sans-serif}main{max-width:420px;margin:12vh auto;padding:24px;background:#666;border:1px solid #999}h1{font-size:26px;font-weight:normal}a{display:block;padding:13px;margin:8px 0;color:white;text-decoration:none;background:#555;border:1px solid #aaa}a:hover{background:#444}</style></head>
<body><main><h1>Moon Program</h1>
<?php foreach($stations as $id=>$label):?><a href="v2/console.php?console=<?=urlencode($id)?>"><?=htmlspecialchars($label)?></a><?php endforeach;?>
</main></body></html>
