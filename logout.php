<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimSessionStart();
header('Cache-Control: no-store');
if(strtoupper((string)($_SERVER['REQUEST_METHOD']??''))!=='POST'){
 http_response_code(405);header('Allow: POST');exit('Use the logout button');
}
moonSimRequireSameOriginMutation();
$_SESSION=[];
if(ini_get('session.use_cookies')){
 $params=session_get_cookie_params();
 setcookie(session_name(),'',[
  'expires'=>time()-3600,'path'=>$params['path'],'domain'=>$params['domain'],
  'secure'=>$params['secure'],'httponly'=>$params['httponly'],'samesite'=>'Strict'
 ]);
}
session_destroy();
header('Location: '.moonSimLoginUrl(),true,303);
exit;
