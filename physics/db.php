<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$servername=(string)(getenv('MOON_DB_HOST')?:'localhost');
$username=(string)(getenv('MOON_DB_USER')?:'');
$password=(string)(getenv('MOON_DB_PASSWORD')?:'');
$dbname=(string)(getenv('MOON_DB_NAME')?:'moonland');
if($username===''||$password===''){http_response_code(503);throw new RuntimeException('Database not configured');}
$conn=new mysqli($servername,$username,$password,$dbname);
$conn->set_charset('utf8mb4');
