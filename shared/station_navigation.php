<?php
declare(strict_types=1);
/** Shared Apollo station navigation, display only. */
function moonSimStationNav(string $active=''):void{
 $links=['FIDO'=>'fido.php','GROUND RADIO'=>'ground_voice.php','COMMANDER'=>'index.php','PILOT / LM'=>'pilot.php','LM ASCENT'=>'lm_ascent_control.php','LM DOCKING'=>'lm_docking_control.php','CAPCOM'=>'crew_radio.php','CSM BURNS'=>'csm_burn_control.php','EARTH RETURN'=>'fido_earth_return.php'];
 echo '<nav style="background:#111b15;color:#c9e4b8;padding:12px;border:1px solid #6c866b;font:14px monospace">';
 foreach($links as $name=>$path){
  $border=$name===$active?'2px solid #f3ce79':'1px solid #546951';
  echo '<a href="'.htmlspecialchars($path,ENT_QUOTES).'" style="display:inline-block;padding:7px;color:#dcebbe;border:'.$border.';margin:3px">'.htmlspecialchars($name,ENT_QUOTES).'</a>';
 }
 echo '<form method="post" action="logout.php" style="display:inline-block;margin:3px"><button type="submit" style="padding:7px;background:#5b332f;color:white;border:1px solid #cc9288;cursor:pointer">LOG OUT</button></form>';
 echo '</nav>';
}
