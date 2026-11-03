<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/orbit_maneuver_planner.php';
function check(bool $condition,string $message):void {
    if(!$condition)throw new RuntimeException($message);
    echo "PASS ".$message.PHP_EOL;
}
$mu=3.986004418e14;$radius=6771000.0;$orbital=sqrt($mu/$radius);
$p=[$radius,0,0];$v=[0,$orbital,0];$o=[0,0,0];$ov=[0,0,0];
$c=moonSimCircularizationEstimate($p,$v,$o,$ov,$mu,6371000.0,400000);
check(abs($c['signed_delta_v_m_s'])<0.01,'circular orbit zero delta-v');
check($c['applicable_now'],'valid circularization when at target');
$b=moonSimBurnBudget(10000,1000,90000,300,100);
check($b['fuel_sufficient'],'100 m/s maneuver feasible');
check($b['available_delta_v_m_s']>100,'rocket equation positive capacity');
try {moonSimBurnBudget(INF,100,50000,300,100);throw new RuntimeException('Nonfinite accepted');}
catch(InvalidArgumentException $e){echo "PASS nonfinite input rejected".PHP_EOL;}
$moon=[384400000,0,0];$mv=[0,1018.0,0];
$t=moonSimTranslunarReference($p,$v,$o,$ov,$moon,$mu);
check($t['reference_coast_seconds']>200000,'transfer duration order of magnitude');
$e=moonSimEncounterPreview($p,$v,$o,$moon,$mv,$t['reference_burn_direction'],
    abs($t['reference_signed_delta_v_m_s']),$mu,4.9048695e12,1737400.0,
    $t['reference_coast_seconds']*1.35,240);
check(is_finite($e['closest_approach_km']),'finite lunar range estimate');
echo "All baseline analytical checks passed. Real-flight accuracy NOT proven.".PHP_EOL;
