<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function voiceFail(int $code,string $reason):never{http_response_code($code);echo json_encode(['error'=>$reason]);exit;}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST')voiceFail(405,'POST required');
$pw=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??'')))voiceFail(401,'Operator required');
if(!hash_equals((string)($_SESSION['moon_voice_csrf']??''),(string)($_POST['csrf']??'')))voiceFail(403,'Invalid session token');
$file=$_FILES['audio']??null;
if(!$file||($file['error']??1)!==UPLOAD_ERR_OK||($file['size']??0)>4000000)voiceFail(400,'Recording rejected');
if(!is_uploaded_file($file['tmp_name']))voiceFail(400,'Invalid audio upload');
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/openai_server_adapter.php';
require_once __DIR__.'/shared/voice_ground_tools.php';
require_once __DIR__.'/shared/radio_routing.php';
try{
 $transcript=moonSimOpenAiRequest('https://api.openai.com/v1/audio/transcriptions',[
  'model'=>'gpt-4o-mini-transcribe','file'=>new CURLFile($file['tmp_name'],'audio/webm','speech.webm')]);
 $utterance=trim((string)($transcript['text']??''));
 if($utterance==='')voiceFail(400,'Speech not recognized');
 $station=strtoupper((string)($_POST['station']??'FIDO'));
 $specialist=strtoupper((string)($_POST['specialist']??'TRAJECTORY'));
 $roles=['GUIDO','RETRO','EECOM','GNC','TELMU','TRAJECTORY','CAPCOM'];
 if(!in_array($station,['FIDO','CREW'],true))voiceFail(400,'Unknown radio station');
 if($station==='CREW')$specialist='CAPCOM';
 if(!in_array($specialist,$roles,true))voiceFail(400,'Unknown controller');
 $context=moonSimVoiceMissionContext($conn);
 $radioId=null;
 if($station==='CREW'){
  $radioId=moonSimRadioQueue($conn,'CREW','CAPCOM',$utterance);
  moonSimRadioQueue($conn,'CAPCOM','FIDO','Crew transmission requires FIDO review: '.$utterance);
}
 $instructions='You are '.$specialist.', an Apollo mission controller speaking over radio. Speak concisely, in natural radio phrasing. Respond only within this role. CAPCOM relays crew requests and never invents trajectory calculations. For technical questions outside your specialty direct FIDO to the proper controller. Never claim you have operated physical controls. Do not guess missing numbers. Say again and read-back requests must be answered clearly.';

 $reply=moonSimOpenAiRequest('https://api.openai.com/v1/responses',[
  'model'=>(string)(getenv('MOON_VOICE_MODEL')?:'gpt-4.1-mini'),
  'instructions'=>$instructions,
  'input'=>'Mission telemetry: '.json_encode($context)."\nPrevious reply from this circuit: ".(string)($_SESSION['radio_previous'][$specialist]??'')."\n".$station.': '.$utterance,
  'max_output_tokens'=>350]);
 $answer=(string)($reply['output_text']??'');
 if($answer===''){
  foreach(($reply['output']??[]) as $o)foreach(($o['content']??[]) as $c)
   if(isset($c['text']))$answer.=$c['text'];
 }
 $_SESSION['radio_previous'][$specialist]=mb_substr($answer,0,1500);
 echo json_encode(['reply'=>$answer,'radio_id'=>$radioId],JSON_THROW_ON_ERROR);
}catch(Throwable $e){voiceFail(503,'Voice service unavailable; check server configuration');}
