<?php
declare(strict_types=1);
// All web-facing control routes eventually load this central DB connection.
// Apply the authorization boundary here as well as at individual pages so
// a missed legacy PHP endpoint cannot bypass operator/worker permissions.
require_once __DIR__.'/../shared/operator_security.php';
if (PHP_SAPI !== 'cli') {
 if (isset($_SERVER['HTTP_X_MOON_WORKER_TOKEN'])) moonSimRequireWorker();
 else moonSimRequireOperator();
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$servername=(string)(getenv('MOON_DB_HOST')?:'localhost');
$username=(string)(getenv('MOON_DB_USER')?:'');
$password=(string)(getenv('MOON_DB_PASSWORD')?:'');
$dbname=(string)(getenv('MOON_DB_NAME')?:'moonland');
if($username===''||$password===''){http_response_code(503);throw new RuntimeException('Database not configured');}
$conn=new mysqli($servername,$username,$password,$dbname);
$conn->set_charset('utf8mb4');
