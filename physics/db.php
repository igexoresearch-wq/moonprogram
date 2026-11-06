<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/operator_security.php';
if(PHP_SAPI!=='cli'){
 if(isset($_SERVER['HTTP_X_MOON_WORKER_TOKEN']))moonSimRequireWorker();
 else moonSimRequireOperator();
}
// SWC's production root db.php is the single source of database credentials.
// Both schemas use the same MySQL account, but this connection selects moonland.
$swcDb=moonSimSwcRoot().'/db.php';
if(!is_file($swcDb)){http_response_code(503);throw new RuntimeException('SWC root db.php not found');}
require_once $swcDb;
if(!isset($db_host,$db_user,$db_password)||$db_user===''||$db_password===''){
 http_response_code(503);throw new RuntimeException('SWC database credentials unavailable');
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$conn=new mysqli((string)$db_host,(string)$db_user,(string)$db_password,'moonland');
$conn->set_charset('utf8mb4');
