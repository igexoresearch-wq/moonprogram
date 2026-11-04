<?php
declare(strict_types=1);
session_start();
$pw=(string)(getenv('MOON_OPERATOR_PASSWORD')?:'');
if($pw===''||!hash_equals($pw,(string)($_SERVER['PHP_AUTH_PW']??''))){header('WWW-Authenticate: Basic realm="Moon Simulator"');http_response_code(401);exit;}
if(!isset($_SESSION['moon_voice_csrf']))$_SESSION['moon_voice_csrf']=bin2hex(random_bytes(16));
?><!doctype html><html><meta charset="utf-8"><title>CREW RADIO / CAPCOM</title>
<body style="background:#1d2422;color:#d1e0d1;font:18px monospace;padding:30px">
<h1>CREW / CAPCOM</h1><p>ALL CREW CALLS GO TO CAPCOM. NO TEXT TRAFFIC.</p>
<button id="send" style="padding:24px">HOLD TO TRANSMIT</button>
<button id="receive" style="padding:24px">ANSWER CAPCOM</button>
<strong id="lamp">RADIO READY</strong>
<script>
const csrf=<?=json_encode($_SESSION['moon_voice_csrf'])?>,lamp=document.getElementById('lamp');
function say(t){if(!t||!('speechSynthesis' in window))return;speechSynthesis.cancel();speechSynthesis.speak(new SpeechSynthesisUtterance(t));}
async function receive(){const f=new FormData();f.append('csrf',csrf);f.append('station','CREW');
const r=await fetch('radio_receive.php',{method:'POST',body:f});if(r.ok){const x=await r.json();say('Apollo, CAPCOM. '+(x.message||'No traffic'));}}
document.getElementById('receive').onclick=receive;
setInterval(async()=>{try{const r=await fetch('radio_status.php?station=CREW');const j=await r.json();lamp.textContent=(j.pending||[]).length?'CAPCOM CALLING':'RADIO READY';}catch(e){}},2000);
let recorder,stream,parts=[];
document.getElementById('send').onpointerdown=async()=>{stream=await navigator.mediaDevices.getUserMedia({audio:true});parts=[];recorder=new MediaRecorder(stream);recorder.ondataavailable=e=>parts.push(e.data);
recorder.onstop=async()=>{stream.getTracks().forEach(t=>t.stop());const f=new FormData();f.append('csrf',csrf);f.append('station','CREW');f.append('specialist','CAPCOM');f.append('audio',new Blob(parts,{type:'audio/webm'}),'crew.webm');
const r=await fetch('ground_voice_api.php',{method:'POST',body:f});const x=await r.json();if(!r.ok){lamp.textContent='RADIO ERROR';return;}say(x.reply);};recorder.start();lamp.textContent='TRANSMITTING';};
document.getElementById('send').onpointerup=()=>{if(recorder&&recorder.state==='recording')recorder.stop();};
</script></body></html>
