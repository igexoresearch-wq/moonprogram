<?php
declare(strict_types=1);
/** Only actual crew/ground workstations, no standalone maneuver destinations. */
function moonSimStationNav(string $active=''):void {
 $links=['CM FLIGHT'=>'index.php','CM COMPUTER'=>'CM/index.php','LM FLIGHT'=>'pilot.php','FIDO'=>'fido.php'];
 echo '<nav style="background:#111b15;color:#c9e4b8;padding:8px;border:1px solid #6c866b;font:13px monospace">';
 foreach($links as $name=>$path){
  $border=($name===$active||($active==='COMMANDER'&&$name==='CM FLIGHT')||($active==='PILOT / LM'&&$name==='LM FLIGHT'))?'2px solid #f3ce79':'1px solid #546951';
  echo '<a href="'.htmlspecialchars($path,ENT_QUOTES).'" style="display:inline-block;padding:5px;color:#dcebbe;border:'.$border.';margin:2px">'.htmlspecialchars($name,ENT_QUOTES).'</a>';
 }
 echo '<form method="post" action="logout.php" style="display:inline-block;margin:2px"><button type="submit" style="padding:5px;background:#5b332f;color:white;border:1px solid #cc9288">LOG OUT</button></form></nav>';
}
