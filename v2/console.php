<?php
require_once __DIR__.'/bootstrap.php';
$console=moonV2Console();
$observerId=moonV2Observer($console);
$stmt=$conn->prepare('SELECT file_name,pos_x,pos_y FROM console_instruments WHERE console_id=? ORDER BY id');
$stmt->bind_param('s',$console);$stmt->execute();$instruments=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars(CONSOLES[$console])?></title><style>
body{margin:0;background:#777;color:#eee;font-family:Arial,sans-serif}
.station{position:relative;min-height:1200px}
.station>header{padding:9px 15px;background:#555}
.instrument{position:absolute}
a{color:white}
</style></head><body><div class="station"><header><?=htmlspecialchars(CONSOLES[$console])?> · <a href="../index.php">Menu</a> · <a href="editor.php?console=<?=urlencode($console)?>">Edit layout</a></header>
<?php foreach($instruments as $instrument):$file=moonV2InstrumentName($instrument['file_name']);if($file===null)continue;?>
<div class="instrument" style="left:<?=(int)$instrument['pos_x']?>px;top:<?=(int)$instrument['pos_y']?>px">
<?php include __DIR__.'/instruments/'.$file;?>
</div><?php endforeach;?></div></body></html>
