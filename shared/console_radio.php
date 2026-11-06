<?php
declare(strict_types=1);
/**
 * In-console radio drawer. The dedicated radio endpoint remains an internal
 * transport implementation: operators never navigate away from the cockpit.
 */
function moonSimConsoleRadio(string $station='CREW',string $prefix=''):void {
 $station=strtoupper($station)==='FIDO'?'FIDO':'CREW';
 $path=$prefix.($station==='FIDO'?'ground_voice.php':'crew_radio.php');
 $label=$station==='FIDO'?'CONTROLLERS':'CAPCOM';
 echo '<div id="moon-radio-console" data-moon-radio="1" style="position:fixed;right:12px;bottom:12px;z-index:11000;color:#e8dab2;font:15px monospace">';
 echo '<button type="button" id="moon-radio-toggle" aria-expanded="false" aria-controls="moon-radio-drawer" style="background:#1a2822;color:#f3d995;border:2px solid #b4a47b;padding:12px 18px;cursor:pointer">RADIO / '.htmlspecialchars($label,ENT_QUOTES).'</button>';
 echo '<div id="moon-radio-drawer" hidden style="position:absolute;right:0;bottom:52px;width:min(430px,90vw);height:min(480px,75vh);background:#101b17;border:2px solid #b4a47b;box-shadow:0 0 25px #000">';
 echo '<iframe id="moon-radio-frame" title="'.htmlspecialchars($label,ENT_QUOTES).' radio" data-src="'.htmlspecialchars($path,ENT_QUOTES).'" style="border:0;width:100%;height:100%" allow="microphone"></iframe>';
 echo '</div></div>';
 echo '<script>(function(){let b=document.getElementById("moon-radio-toggle"),d=document.getElementById("moon-radio-drawer"),f=document.getElementById("moon-radio-frame");b.addEventListener("click",function(){d.hidden=!d.hidden;b.setAttribute("aria-expanded",String(!d.hidden));if(!d.hidden&&!f.src)f.src=f.dataset.src;});})();</script>';
}
