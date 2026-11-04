<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/lm_ascent_targets.php';
require_once __DIR__.'/../shared/lm_landing_estimate.php';
require_once __DIR__.'/../shared/lm_rendezvous_transfer.php';
function assertMoon(bool $ok,string $message):void{
 if(!$ok)throw new RuntimeException($message);
 echo 'PASS '.$message.PHP_EOL;
}
$mu=4.9048695e12;$moonR=1737400.0;$r=$moonR+100000.0;
$targetSpeed=sqrt($mu/$r);
$p=[$r,0.0,0.0];$v=[0.0,$targetSpeed,0.0];$zero=[0.0,0.0,0.0];
$ascent=moonSimLunarAscentTargets($p,$v,$zero,$zero,$mu,$moonR,2500,1000,15600,311,100000);
assertMoon($ascent['cutoff_reference_met'],'Nominal circular orbit satisfies ascent cutoff');
assertMoon($ascent['predicted_perilune_altitude_m']>=30000,'Perilune protected in insertion');
$slow=moonSimLunarAscentTargets($p,[0.0,$targetSpeed*0.7,0.0],$zero,$zero,$mu,$moonR,2500,1000,15600,311,100000);
assertMoon(!$slow['cutoff_reference_met'],'Suborbital speed cannot trigger orbit cutoff');
$soft=moonSimLmLandingEstimate(0.0,-1.0,0.5,$mu,$moonR);
assertMoon($soft['soft_landing_envelope'],'Slow touchdown meets defined envelope');
$hard=moonSimLmLandingEstimate(0.0,-12.0,5.0,$mu,$moonR);
assertMoon(!$hard['soft_landing_envelope'],'Hard contact is not soft landing');
$cm=[$r+20000.0,0.0,0.0];
$transfer=moonSimRendezvousTransferSearch($p,$v,$cm,[0.0,sqrt($mu/($r+20000.0)),0.0],$zero,$zero,$mu,$moonR,20.0);
assertMoon($transfer['candidates_evaluated']>0,'Rendezvous candidate generation');
assertMoon(isset($transfer['best']['relative_speed_at_closest_m_s']),'Encounter speed is available');
echo 'Source checks completed. Does not substitute for full integrated mission tests.'.PHP_EOL;
