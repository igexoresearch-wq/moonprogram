<?php
declare(strict_types=1);
require_once __DIR__.'/../physics/db.php';
const CONSOLES=['cmc'=>'CM Commander','cmp'=>'CM Pilot','lmc'=>'LM Commander','lmp'=>'LM Pilot','fido'=>'FIDO','flight'=>'Flight Director'];
function moonV2Console():string {
 $id=strtolower((string)($_GET['console']??''));
 if(!array_key_exists($id,CONSOLES)){http_response_code(400);exit('Unknown station');}
 return $id;
}
function moonV2Observer(string $console):int {
 return in_array($console,['lmc','lmp'],true)?6:3;
}
function moonV2InstrumentName(string $name):?string {
 return preg_match('/\A[a-z][a-z0-9_]*\.php\z/i',$name) && is_file(__DIR__.'/instruments/'.$name)?$name:null;
}
