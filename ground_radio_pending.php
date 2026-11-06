<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireOperator();

header('Content-Type: application/json');
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/radio_routing.php';
echo json_encode(['pending'=>moonSimRadioPending($conn,'FIDO')]);
