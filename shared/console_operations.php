<?php
declare(strict_types=1);
/**
 * Legacy calculation/control modules remain functional inside the flight
 * station until their forms are migrated into direct cockpit instruments.
 * Operators stay at one station URL throughout a maneuver.
 */
function moonSimConsoleOperations(string $craft):void {
 $lm=strtoupper($craft)==='LM';
 $tasks=$lm
   ? ['DESCENT'=>'lm_descent_setup.php','LANDING'=>'lm_descent_assist.php','ASCENT'=>'lm_ascent_control.php','RENDEZVOUS'=>'lm_rendezvous_reference.php','DOCKING'=>'lm_docking_control.php']
   : ['CSM BURN'=>'csm_burn_control.php','ORBIT'=>'orbit_navigation.php','LOI'=>'loi_reference.php','RETURN'=>'fido_earth_return.php'];
 $id=$lm?'lm':'cm';
 echo '<section id="moon-operations-'.$id.'" style="background:#111b18;color:#e8d9a2;border:1px solid #7e795d;padding:8px;font:13px monospace;margin:8px 0">';
 echo '<strong>'.($lm?'LUNAR MODULE':'COMMAND / SERVICE MODULE').' — FLIGHT OPERATIONS</strong> ';
 foreach($tasks as $name=>$path){
  echo '<button type="button" data-moon-op="'.htmlspecialchars($path,ENT_QUOTES).'" style="background:#202a23;color:#e7d9a2;border:1px solid #837b5d;padding:5px;margin:3px">'.htmlspecialchars($name,ENT_QUOTES).'</button>';
 }
 echo '<button type="button" data-moon-op-close style="background:#342727;color:#e7d9a2;border:1px solid #837b5d;padding:5px">CLOSE</button>';
 echo '<div data-moon-op-framewrap hidden style="height:540px;max-height:70vh;margin-top:8px"><iframe title="Flight operations" data-moon-op-frame style="width:100%;height:100%;background:#111;border:1px solid #8b876a"></iframe></div>';
 echo '</section>';
 echo '<script>(function(){let host=document.getElementById("moon-operations-'.$id.'");if(!host)return;let wrap=host.querySelector("[data-moon-op-framewrap]"),frame=host.querySelector("[data-moon-op-frame]");for(let btn of host.querySelectorAll("[data-moon-op]"))btn.addEventListener("click",()=>{wrap.hidden=false;frame.src=btn.dataset.moonOp;});host.querySelector("[data-moon-op-close]").addEventListener("click",()=>{wrap.hidden=true;frame.src="about:blank";});})();</script>';
}
