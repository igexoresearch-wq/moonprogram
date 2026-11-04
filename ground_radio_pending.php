<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
$secret=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))){http_response_code(401);exit('{}');}
if(($_SESSION['moon_radio_role']??'')!=='FIDO'){http_response_code(403);exit('{}');}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/radio_routing.php';
echo json_encode(['pending'=>moonSimRadioPending($conn,'FIDO')]);
