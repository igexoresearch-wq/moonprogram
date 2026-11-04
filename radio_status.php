<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
$pw=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){http_response_code(401);exit('{}');}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/radio_routing.php';
$station=(string)($_GET['station']??'FIDO');
if(!in_array($station,['FIDO','CREW'],true)||$station!==($_SESSION['moon_radio_role']??'')){http_response_code(400);exit('{}');}
echo json_encode(['pending'=>moonSimRadioPending($conn,$station)]);
