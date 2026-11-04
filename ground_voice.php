<?php
declare(strict_types=1);
session_start();
$pw=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){
 header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit;
}
if(!isset($_SESSION['moon_voice_csrf']))$_SESSION['moon_voice_csrf']=bin2hex(random_bytes(16));
?><!doctype html><html lang="en"><meta charset="utf-8"><title>Ground Voice</title>
<body style="background:#111b18;color:#e0ecdd;font:17px monospace;max-width:800px;margin:30px auto">
<h1>GROUND / PUSH TO TALK</h1>
<p>Advisory only. Replies include source references. No vehicle actions are available.</p>
<button id="talk">HOLD TO SPEAK</button><label><input type="checkbox" id="speak"> SPEAK REPLY</label><p id="state">READY</p>
<pre id="answer" style="white-space:pre-wrap"></pre>
<script>
const button=document.getElementById('talk'),state=document.getElementById('state'),answer=document.getElementById('answer');
let recorder=null,chunks=[],stream=null;
async function start(){
 try{
  stream=await navigator.mediaDevices.getUserMedia({audio:true});
  chunks=[];recorder=new MediaRecorder(stream);
  recorder.ondataavailable=e=>{if(e.data.size)chunks.push(e.data)};
  recorder.onstop=async()=>{
   stream.getTracks().forEach(t=>t.stop());state.textContent='TRANSMITTING';
   try{
    const fd=new FormData();
    fd.append('csrf',<?=json_encode($_SESSION['moon_voice_csrf'])?>);
    fd.append('audio',new Blob(chunks,{type:'audio/webm'}),'speech.webm');
    const res=await fetch('ground_voice_api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data=await res.json();
    if(!res.ok)throw Error(data.error||'Failed');
    answer.textContent='CREW: '+data.transcript+'\n\nFIDO: '+data.reply;
    if(document.getElementById('speak').checked && 'speechSynthesis' in window){
      speechSynthesis.cancel();speechSynthesis.speak(new SpeechSynthesisUtterance(data.reply));
    }
    state.textContent='READY';
   }catch(e){state.textContent='ERROR: '+e.message;}
  };
  recorder.start();state.textContent='RECORDING';
 }catch(e){state.textContent='MICROPHONE: '+e.message;}
}
function stop(){if(recorder&&recorder.state==='recording')recorder.stop();}
button.addEventListener('pointerdown',start);
button.addEventListener('pointerup',stop);
button.addEventListener('pointercancel',stop);
</script></body></html>
