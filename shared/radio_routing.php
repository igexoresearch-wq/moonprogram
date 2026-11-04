<?php
declare(strict_types=1);
function moonSimRadioEnsure(mysqli $db):void{
 $db->query("CREATE TABLE IF NOT EXISTS radio_calls(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,source_station VARCHAR(24) NOT NULL,target_station VARCHAR(24) NOT NULL,request_text TEXT NOT NULL,response_text TEXT NULL,status VARCHAR(16) NOT NULL DEFAULT 'PENDING',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,answered_at TIMESTAMP NULL)");
}
function moonSimRadioRoute(string $station,string $utterance):string{
 if($station==='CREW')return 'CAPCOM';
 if($station==='FIDO')return 'CAPCOM';
 throw new InvalidArgumentException('Unknown transmitting station');
}
function moonSimRadioQueue(mysqli $db,string $from,string $to,string $message):int{
 moonSimRadioEnsure($db);
 $allowed=['CREW','FIDO','CAPCOM'];
 if(!in_array($from,$allowed,true)||!in_array($to,$allowed,true)||mb_strlen($message)>3000)throw new InvalidArgumentException('Invalid radio call');
 $stmt=$db->prepare("INSERT INTO radio_calls(source_station,target_station,request_text) VALUES(?,?,?)");
 $stmt->bind_param('sss',$from,$to,$message);$stmt->execute();
 return $db->insert_id;
}
function moonSimRadioPending(mysqli $db,string $station):array{
 moonSimRadioEnsure($db);
 $stmt=$db->prepare("SELECT id,source_station FROM radio_calls WHERE target_station=? AND status='PENDING' ORDER BY id ASC LIMIT 10");
 $stmt->bind_param('s',$station);$stmt->execute();
 return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
