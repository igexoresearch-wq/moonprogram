<?php
require_once __DIR__.'/bootstrap.php';
$console=moonV2Console();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $name=(string)($_POST['file_name']??'');
 if(!preg_match('/\A[a-z][a-z0-9_]*\.php\z/i',$name)){http_response_code(400);exit('Invalid instrument name');}
 $action=(string)($_POST['action']??'');
 if($action==='add'){
  if(moonV2InstrumentName($name)===null){http_response_code(400);exit('File not found');}
  $stmt=$conn->prepare('INSERT INTO console_instruments(console_id,file_name,pos_x,pos_y) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE pos_x=VALUES(pos_x),pos_y=VALUES(pos_y)');
  $x=(int)($_POST['x']??20);$y=(int)($_POST['y']??70);
  $stmt->bind_param('ssii',$console,$name,$x,$y);$stmt->execute();
 }elseif(in_array($action,['up','down','left','right','remove'],true)){
  $stmt=$conn->prepare('SELECT id,pos_x,pos_y FROM console_instruments WHERE console_id=? AND file_name=?');
  $stmt->bind_param('ss',$console,$name);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
  if($row){
   if($action==='remove'){$stmt=$conn->prepare('DELETE FROM console_instruments WHERE id=?');$stmt->bind_param('i',$row['id']);$stmt->execute();}
   else{$dx=['left'=>-10,'right'=>10,'up'=>0,'down'=>0][$action];$dy=['left'=>0,'right'=>0,'up'=>-10,'down'=>10][$action];
    $x=(int)$row['pos_x']+$dx;$y=(int)$row['pos_y']+$dy;$id=(int)$row['id'];
    $stmt=$conn->prepare('UPDATE console_instruments SET pos_x=?,pos_y=? WHERE id=?');$stmt->bind_param('iii',$x,$y,$id);$stmt->execute();}
  }
 }
 header('Location: editor.php?console='.urlencode($console));exit;
}
$stmt=$conn->prepare('SELECT file_name,pos_x,pos_y FROM console_instruments WHERE console_id=? ORDER BY id');
$stmt->bind_param('s',$console);$stmt->execute();$items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?><!doctype html><html><head><meta charset="utf-8"><title>Layout editor</title><style>body{background:#777;color:white;font:16px Arial}button{padding:7px;margin:3px}input{padding:7px}a{color:white}</style></head><body>
<h2><?=htmlspecialchars(CONSOLES[$console])?> instrument layout</h2><p><a href="console.php?console=<?=urlencode($console)?>">Back to console</a></p>
<?php foreach($items as $item):?><form method="post"><input type="hidden" name="file_name" value="<?=htmlspecialchars($item['file_name'])?>">
<?=htmlspecialchars($item['file_name'])?> (<?=(int)$item['pos_x']?>, <?=(int)$item['pos_y']?>)
<?php foreach(['up'=>'↑','down'=>'↓','left'=>'←','right'=>'→','remove'=>'Remove'] as $action=>$label):?><button name="action" value="<?=$action?>"><?=$label?></button><?php endforeach;?></form><?php endforeach;?>
<h3>Add existing instrument</h3><form method="post"><input name="file_name" placeholder="window.php" required><input name="x" type="number" value="20"><input name="y" type="number" value="70"><button name="action" value="add">Add</button></form>
<p>For a new instrument, create a file in <code>v2/instruments/</code> then add its filename here. Existing instruments can be reused on any console.</p>
</body></html>
