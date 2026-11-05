<?php
declare(strict_types=1);
/** Server-only AI credential resolution. Never expose token in HTML or JSON. */
function moonSimOpenAiKey():string{
 $key=trim((string)(getenv('OPENAI_API_KEY')?:''));
 if($key===''){
  $path=(string)(getenv('MOON_OPENAI_KEY_FILE')?:'');
  if($path!==''&&is_file($path)&&is_readable($path))$key=trim((string)file_get_contents($path));
 }
 if($key===''){
  $swcFile=(string)(getenv('SWC_SECRETS_FILE')?:'');
  if($swcFile!==''&&is_file($swcFile)&&is_readable($swcFile)){
   $config=(static function($path){return require $path;})($swcFile);
   if(is_array($config))$key=(string)($config['openai_api_key']??$config['OPENAI_API_KEY']??'');
  }
 }
 if($key===''||strlen($key)<20)throw new RuntimeException('Server AI credentials are not configured');
 return $key;
}
function moonSimOpenAiRequest(string $endpoint,array $payload):array{
 if(!function_exists('curl_init'))throw new RuntimeException('PHP curl extension required');
 $allowed=['https://api.openai.com/v1/responses','https://api.openai.com/v1/audio/transcriptions'];
 if(!in_array($endpoint,$allowed,true))throw new InvalidArgumentException('AI endpoint not permitted');
 $c=curl_init($endpoint);
 $multipart=isset($payload['file']);
 curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
  CURLOPT_POSTFIELDS=>$multipart?$payload:json_encode($payload,JSON_THROW_ON_ERROR),
  CURLOPT_HTTPHEADER=>array_merge(['Authorization: Bearer '.moonSimOpenAiKey()],
    $multipart?[]:['Content-Type: application/json']),
  CURLOPT_TIMEOUT=>40,CURLOPT_CONNECTTIMEOUT=>8]);
 $raw=curl_exec($c);$code=(int)curl_getinfo($c,CURLINFO_HTTP_CODE);
 curl_close($c);
 if(!is_string($raw)||$code<200||$code>=300)
  throw new RuntimeException('AI provider unavailable (HTTP '.$code.')');
 $data=json_decode($raw,true);
 if(!is_array($data))throw new RuntimeException('Unexpected AI response');
 return $data;
}
