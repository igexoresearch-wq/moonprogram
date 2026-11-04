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
<p>SELECT CIRCUIT — PRESS AND HOLD TO TRANSMIT. AUDIO ONLY. USE A PAPER NOTEBOOK.</p>
<div id="circuits">
<?php foreach(['GUIDO','RETRO','EECOM','GNC','TELMU','TRAJECTORY','CAPCOM'] as $ch):?>
<button type="button" class="channel" data-channel="<?=$ch?>"><?=$ch?></button>
<?php endforeach;?>
</div>
<p><label>STATION <select id="station"><option value="FIDO">FIDO</option><option value="CREW">CREW / CAPCOM ONLY</option></select></label></p>
<button id="talk" style="font-size:25px;padding:20px">HOLD TO TRANSMIT</button>
<p id="state">READY</p><p id="alert" style="color:#fbb;display:none">● CAPCOM CALLING FIDO</p>
<style>.channel{background:#1e342a;color:#a7d1ad;padding:15px;margin:5px;border:2px solid #526}.channel.selected{border-color:#ffcf63;color:#ffcf63}</style>
<script>
const button=document.getElementById('talk'),state=document.getElementById('state');
let selected='TRAJECTORY';
document.querySelectorAll('.channel').forEach(b=>b.addEventListener('click',()=>{
 selected=b.dataset.channel;
 document.querySelectorAll('.channel').forEach(x=>x.classList.toggle('selected',x===b));
 if(selected==='CAPCOM')pickUpCapcom();
}));
document.querySelector('[data-channel="TRAJECTORY"]').click();
setInterval(async()=>{
 if(document.getElementById('station').value!=='FIDO')return;
 try{const r=await fetch('ground_radio_pending.php',{cache:'no-store'});const data=await r.json();
 document.getElementById('alert').style.display=(data.pending||[]).length?'block':'none';}catch(e){}
},2500);
async function pickUpCapcom(){
 try{
  const f=new FormData();f.append('csrf',<?=json_encode($_SESSION['moon_voice_csrf'])?>);
  const response=await fetch('ground_radio_answer.php',{method:'POST',body:f,credentials:'same-origin'});
  if(!response.ok)return;
  const call=await response.json();
  if(call.message && 'speechSynthesis' in window){
   speechSynthesis.cancel();speechSynthesis.speak(new SpeechSynthesisUtterance('FIDO, CAPCOM. '+call.message));
  }
 }catch(e){state.textContent='RADIO ERROR';}
}
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
    fd.append('station',document.getElementById('station').value);
    fd.append('specialist',selected);
    fd.append('audio',new Blob(chunks,{type:'audio/webm'}),'speech.webm');
    const res=await fetch('ground_voice_api.php',{method:'POST',body:fd,credentials:'same-origin'});
    const data=await res.json();
    if(!res.ok)throw Error(data.error||'Failed');
    // Radio replies are audio only: no transcript or written AI readout.
    if('speechSynthesis' in window){
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
