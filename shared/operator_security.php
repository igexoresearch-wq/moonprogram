<?php
declare(strict_types=1);
/** Shared operator gate. Fail closed except on explicitly permitted local development. */
function moonSimRequireOperator(bool $control=true):void{
 $password=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
 if($password===''||!hash_equals($password,(string)($_SERVER['PHP_AUTH_PW']??''))){
  header('WWW-Authenticate: Basic realm="Moon Simulator"');
  http_response_code(401);exit('Operator authentication required');
 }
 $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')
   ||(int)($_SERVER['SERVER_PORT']??0)===443;
 $host=(string)($_SERVER['REMOTE_ADDR']??'');
 $local=in_array($host,['127.0.0.1','::1'],true);
 if($control&&!$https&&!$local){
  http_response_code(403);exit('HTTPS required for operator controls');
 }
 header('Cache-Control: no-store');
}
