<?php
declare(strict_types=1);

/** Protect both read-only stations and all controls before accessing the DB. */
function moonSimRequireOperator(bool $control=true): void {
 if (PHP_SAPI==='cli') return;
 $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')
   || (int)($_SERVER['SERVER_PORT']??0)===443;
 $local=in_array((string)($_SERVER['REMOTE_ADDR']??''),['127.0.0.1','::1'],true);
 if (!$https&&!$local) {http_response_code(403);exit('HTTPS required');}
 $expected=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
 $actual=(string)($_SERVER['PHP_AUTH_PW']??'');
 if ($expected===''||!hash_equals($expected,$actual)) {
  header('WWW-Authenticate: Basic realm="Moon Simulator"');
  http_response_code(401);exit('Operator authentication required');
 }
 header('Cache-Control: no-store');
 header('X-Content-Type-Options: nosniff');
 header('Referrer-Policy: same-origin');
 if ($control && !in_array((string)($_SERVER['REQUEST_METHOD']??'GET'),['GET','HEAD','OPTIONS'],true)) {
  moonSimRequireSameOriginMutation();
 }
}

/** Defense in depth for legacy cockpit forms and AJAX controls. */
function moonSimRequireSameOriginMutation(): void {
 if (PHP_SAPI==='cli') return;
 $site=strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE']??''));
 if ($site==='cross-site') {http_response_code(403);exit('Cross-site control denied');}
 $host=strtolower((string)($_SERVER['HTTP_HOST']??''));
 $host=preg_replace('/:\\d+$/','',$host);
 $requestScheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
 // A same-origin POST normally carries Origin or Referer. A non-browser
 // client may use the same operator password and an exact Origin header.
 $source=(string)($_SERVER['HTTP_ORIGIN']??'');
 if ($source==='') $source=(string)($_SERVER['HTTP_REFERER']??'');
 $parts=$source!==''?parse_url($source):false;
 if (!is_array($parts)||strtolower((string)($parts['host']??''))!==$host ||
     (!in_array(strtolower((string)($parts['scheme']??'')),[$requestScheme],true))) {
  http_response_code(403);exit('Origin verification required for controls');
 }
}

/** Physics worker requires a separate off-repository token or CLI. */
function moonSimRequireWorker(): void {
 if (PHP_SAPI==='cli') return;
 $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')
   ||(int)($_SERVER['SERVER_PORT']??0)===443;
 if (!$https) {http_response_code(403);exit('Worker HTTPS required');}
 $expected=(string)(getenv('MOON_WORKER_TOKEN')?:'');
 $provided=(string)($_SERVER['HTTP_X_MOON_WORKER_TOKEN']??'');
 if ($expected===''||!hash_equals($expected,$provided)) {
  http_response_code(403);exit('Worker authorization required');
 }
}
