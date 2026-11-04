<?php
declare(strict_types=1);
session_start();header('Content-Type: application/json');
$pw=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){http_response_code(401);exit('{}');}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!hash_equals((string)($_SESSION['moon_voice_csrf']??''),(string)($_POST['csrf']??''))){http_response_code(403);exit('{}');}
$station=(string)($_POST['station']??'');
if(!in_array($station,['CREW','FIDO'],true)){http_response_code(400);exit('{}');}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/radio_routing.php';
moonSimRadioEnsure($conn);
$conn->begin_transaction();
try{
 $s=$conn->prepare("SELECT id,request_text FROM radio_calls WHERE target_station=? AND status='PENDING' ORDER BY id LIMIT 1 FOR UPDATE");
 $s->bind_param('s',$station);$s->execute();$row=$s->get_result()->fetch_assoc();
 if(!$row){$conn->commit();echo '{"message":null}';exit;}
 $id=(int)$row['id'];
 $conn->query("UPDATE radio_calls SET status='ANSWERED',answered_at=CURRENT_TIMESTAMP WHERE id=$id");
 $conn->commit();
 echo json_encode(['message'=>(string)$row['request_text']]);
}catch(Throwable $e){$conn->rollback();http_response_code(503);echo '{}';}
