<?php
declare(strict_types=1);
/** Operator authentication, HTTPS required except loopback development. */
function moonSimRequireOperator(bool $control=true):void {
 $https=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')
   || (int)($_SERVER['SERVER_PORT']??0)===443;
 $local=in_array((string)($_SERVER['REMOTE_ADDR']??''),['127.0.0.1','::1'],true);
 if(!$https && !$local) {
  http_response_code(403);
  exit('HTTPS required');
 }
 $password=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
 if($password==='' || !hash_equals($password,(string)($_SERVER['PHP_AUTH_PW']??''))) {
  header('WWW-Authenticate: Basic realm="Moon Simulator"');
  http_response_code(401);
  exit('Operator authentication required');
 }
 header('Cache-Control: no-store');
}
