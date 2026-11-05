<?php
declare(strict_types=1);

/** All simulator web stations share one PHP session and login page. */
function moonSimSecureTransport(): bool {
 return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')
   || (int)($_SERVER['SERVER_PORT']??0)===443;
}
function moonSimSessionStart(): void {
 if(PHP_SAPI==='cli'||session_status()===PHP_SESSION_ACTIVE)return;
 $local=in_array((string)($_SERVER['REMOTE_ADDR']??''),['127.0.0.1','::1'],true);
 if(!moonSimSecureTransport()&&!$local){http_response_code(403);exit('HTTPS required');}
 ini_set('session.use_strict_mode','1');
 ini_set('session.use_only_cookies','1');
 ini_set('session.use_trans_sid','0');
 session_name('MOON_SIM_SESSION');
 session_set_cookie_params([
  'lifetime'=>0,'path'=>moonSimCookiePath(),
  'secure'=>moonSimSecureTransport(),'httponly'=>true,'samesite'=>'Strict'
 ]);
 session_start();
}
function moonSimCookiePath():string {
 // operator_security.php resides in moon/shared regardless of entry point.
 $authRoot=realpath(__DIR__.'/..');
 $webRoot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));
 if($authRoot&&$webRoot&&str_starts_with($authRoot,$webRoot)){
  $relative=substr($authRoot,strlen($webRoot));
  return '/'.trim(str_replace('\\','/',$relative),'/').'/';
 }
 // Deployment path does not have to be exactly /moon; current host uses it.
 return '/moon/';
}
function moonSimLoginUrl():string {
 $path=moonSimCookiePath();
 return $path.'login.php';
}
function moonSimRequireOperator(bool $control=true):void {
 if(PHP_SAPI==='cli')return;
 moonSimSessionStart();
 $expected=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
 if($expected===''){http_response_code(503);exit('Operator login not configured');}
 if(empty($_SESSION['moon_authenticated'])||!is_string($_SESSION['moon_identity']??null)
    || (int)($_SESSION['moon_auth_time']??0)<time()-43200) {
  if(strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'))!=='GET'){
   http_response_code(401);exit('Login required before sending controls');
  }
  header('Location: '.moonSimLoginUrl(),true,303);exit;
 }
 header('Cache-Control: no-store');
 header('X-Content-Type-Options: nosniff');
 header('Referrer-Policy: same-origin');
 if($control&&!in_array(strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET')),['GET','HEAD','OPTIONS'],true))
  moonSimRequireSameOriginMutation();
}
function moonSimRequireSameOriginMutation():void {
 if(PHP_SAPI==='cli')return;
 $method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
 if(!in_array($method,['POST','PUT','PATCH','DELETE'],true)){http_response_code(405);exit('Unsupported control mutation method');}
 $site=strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE']??''));
 if(in_array($site,['cross-site','none'],true)){http_response_code(403);exit('Cross-site control denied');}
 $host=strtolower((string)($_SERVER['HTTP_HOST']??''));
 $host=preg_replace('/:\\d+$/','',$host);
 $scheme=moonSimSecureTransport()?'https':'http';
 $source=(string)($_SERVER['HTTP_ORIGIN']??'');
 if($source==='')$source=(string)($_SERVER['HTTP_REFERER']??'');
 $parts=$source!==''?parse_url($source):false;
 if(!is_array($parts)||strtolower((string)($parts['host']??''))!==$host||
  strtolower((string)($parts['scheme']??''))!==$scheme){
  http_response_code(403);exit('Origin verification required for controls');
 }
}
/** Web physics requires its own token; cron/CLI remains separate from operator sessions. */
function moonSimRequireWorker():void {
 if(PHP_SAPI==='cli')return;
 if(!moonSimSecureTransport()){http_response_code(403);exit('Worker HTTPS required');}
 $expected=(string)(getenv('MOON_WORKER_TOKEN')?:'');
 $provided=(string)($_SERVER['HTTP_X_MOON_WORKER_TOKEN']??'');
 if($expected===''||!hash_equals($expected,$provided)){
  http_response_code(403);exit('Worker authorization required');
 }
}
