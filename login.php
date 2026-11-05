<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimSessionStart();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$secret=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
$operator=(string)(getenv('MOON_OPERATOR_USER')?:'operator');
$error='';
if($secret===''){http_response_code(503);exit('Operator password is not configured on server');}
if(!isset($_SESSION['moon_login_csrf']))$_SESSION['moon_login_csrf']=bin2hex(random_bytes(32));
if(strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'))==='POST'){
 $submitted=(string)($_POST['csrf']??'');
 if(!hash_equals((string)$_SESSION['moon_login_csrf'],$submitted)){
  http_response_code(403);exit('Invalid login request');
 }
 $now=time();
 $failures=(int)($_SESSION['moon_login_failures']??0);
 $last=(int)($_SESSION['moon_login_last_failure']??0);
 if($failures>=5 && $now-$last<300){http_response_code(429);$error='Too many attempts. Try again later.';}
 elseif(hash_equals($operator,(string)($_POST['username']??''))&&hash_equals($secret,(string)($_POST['password']??''))){
  session_regenerate_id(true);
  $_SESSION['moon_authenticated']=true;
  $_SESSION['moon_identity']=$operator;
  $_SESSION['moon_auth_time']=$now;
  unset($_SESSION['moon_login_failures'],$_SESSION['moon_login_last_failure'],$_SESSION['moon_login_csrf']);
  header('Location: '.moonSimCookiePath().'index.php',true,303);exit;
 }else{
  $_SESSION['moon_login_failures']=$failures+1;
  $_SESSION['moon_login_last_failure']=$now;
  $error='Incorrect username or password.';
 }
}
if(!empty($_SESSION['moon_authenticated'])){header('Location: '.moonSimCookiePath().'index.php',true,303);exit;}
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Moon Simulator Login</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#070e0a;color:#d8edcf;font:16px system-ui,Arial,sans-serif;display:grid;place-items:center;min-height:100vh}
main{width:min(420px,94vw);padding:35px;background:#132118;border:1px solid #46674c;border-radius:12px}
h1{font-size:24px;margin:0 0 10px}p{color:#b3cbb1}label{display:block;margin:16px 0 7px}
input{width:100%;padding:12px;background:#fff;color:#111;border:0;border-radius:5px;font-size:16px}
button{margin-top:23px;width:100%;padding:12px;background:#94c78a;color:#082009;font-weight:bold;border:0;border-radius:5px;cursor:pointer}
.error{background:#532221;color:#fff;padding:10px;margin-top:15px;border-radius:5px}
</style></head><body><main><h1>MOON PROGRAM</h1><p>Operator station access</p>
<?php if($error!==''):?><div class="error" role="alert"><?=h($error)?></div><?php endif;?>
<form method="post" action="login.php" autocomplete="on">
<input type="hidden" name="csrf" value="<?=h((string)$_SESSION['moon_login_csrf'])?>">
<label for="username">Username</label><input id="username" name="username" type="text" autocomplete="username" required autofocus>
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
<button type="submit">ENTER MISSION CONTROL</button></form></main></body></html>
