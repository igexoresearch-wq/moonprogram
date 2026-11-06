<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireHttps();
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
 http_response_code(405);header('Allow: POST');exit('Use the logout button');
}
moonSimRequireOperator();
moonSimRequireSameOriginMutation();
if(moonSimSwcAuthAvailable() && function_exists('logout_user'))logout_user();
else{
 moonSimSessionStart();
 $_SESSION=[];
 $cp=session_get_cookie_params();
 setcookie(session_name(),'',[
  'expires'=>time()-3600,'path'=>$cp['path'],'domain'=>$cp['domain'],
  'secure'=>$cp['secure'],'httponly'=>$cp['httponly'],'samesite'=>'Lax'
 ]);
 session_destroy();
}
header('Location: '.moonSimLoginUrl(),true,303);exit;
