<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

header('Content-Type: application/json');
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/radio_routing.php';
$station=(string)($_GET['station']??'FIDO');
if(!in_array($station,['FIDO','CREW'],true)||$station!==($_SESSION['moon_radio_role']??'')){http_response_code(400);exit('{}');}
echo json_encode(['pending'=>moonSimRadioPending($conn,$station)]);
