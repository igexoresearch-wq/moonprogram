<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
$pw=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){http_response_code(401);exit('{}');}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!hash_equals((string)($_SESSION['moon_voice_csrf']??''),(string)($_POST['csrf']??''))){http_response_code(403);exit('{}');}
if(($_SESSION['moon_radio_role']??'')!=='FIDO'){http_response_code(403);exit('{}');}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/radio_routing.php';
moonSimRadioEnsure($conn);
$conn->begin_transaction();
try{
 $q=$conn->query("SELECT id,request_text FROM radio_calls WHERE target_station='FIDO' AND status='PENDING' ORDER BY id ASC LIMIT 1 FOR UPDATE");
 $call=$q?$q->fetch_assoc():null;
 if(!$call){$conn->commit();echo json_encode(['message'=>null]);exit;}
 $id=(int)$call['id'];
 $conn->query("UPDATE radio_calls SET status='ANSWERED',answered_at=CURRENT_TIMESTAMP WHERE id=$id");
 $conn->commit();
 echo json_encode(['message'=>(string)$call['request_text']]);
}catch(Throwable $e){$conn->rollback();http_response_code(503);echo '{}';}
