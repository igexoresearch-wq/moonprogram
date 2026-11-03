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
$b2=moonSimBurnBudget(10000,0,90000,300,0);
check(abs($b2['available_delta_v_m_s'])<1e-9,'zero propellant means zero available delta-v');
check(abs($b2['ideal_burn_seconds'])<1e-9,'zero delta-v implies zero ideal burn time');
$off=moonSimCircularizationEstimate($p,[$orbital*0.1,$orbital,0],$o,$ov,$mu,6371000.0,400000);
check(!$off['applicable_now'],'high radial speed prevents circularization advice');
$delay=moonSimEncounterPreview($p,$v,$o,$moon,$mv,$t['reference_burn_direction'],
    abs($t['reference_signed_delta_v_m_s']),$mu,4.9048695e12,1737400.0,
    $t['reference_coast_seconds']*1.35,240,3600);
check($delay['departure_delay_seconds']===3600.0,'aligned delayed burn is represented');
try {
    moonSimEncounterPreview($p,$v,$o,$moon,$mv,$t['reference_burn_direction'],
        100.0,$mu,4.9048695e12,1737400.0,20000,240,3700);
    throw new RuntimeException('Unaligned delay unexpectedly accepted');
} catch (InvalidArgumentException $ex) {echo "PASS unaligned departure rejected".PHP_EOL;}
try {
    moonSimEncounterLocalSearch($p,$v,$o,$moon,$mv,
        $t['reference_burn_direction'],abs($t['reference_signed_delta_v_m_s']),
        $mu,4.9048695e12,1737400.0,$t['reference_coast_seconds']*1.35,0.0);
    throw new RuntimeException('Zero-fuel search unexpectedly returned a maneuver');
} catch (RuntimeException $ex) {
    if (str_contains($ex->getMessage(),'unexpectedly')) throw $ex;
    echo "PASS zero-fuel search reports no valid candidate".PHP_EOL;
}
try {
    moonSimEncounterPreview($p,$v,$o,$moon,$mv,[0,0,0],100.0,
        $mu,4.9048695e12,1737400.0,20000,240);
    throw new RuntimeException('Zero burn vector accepted');
} catch (InvalidArgumentException $ex) {
    echo "PASS invalid burn vector rejected".PHP_EOL;
}
try {
    moonSimEncounterPreview($p,$v,$o,$moon,$mv,[0,1,0],100.0,
        $mu,4.9048695e12,1737400.0,3600,240,3360);
    throw new RuntimeException('Insufficient coast accepted');
} catch (InvalidArgumentException $ex) {echo "PASS postburn coast required".PHP_EOL;}
try {
    moonSimMoonPhaseReference($o,$o,$moon,$mv,3600);
    throw new RuntimeException('Earth-centered departure accepted');
} catch (InvalidArgumentException $ex) {echo "PASS undefined departure phase rejected".PHP_EOL;}

// Analytic invariances and boundary checks (no database needed).
$opposite=moonSimCircularizationEstimate($p,[0,$orbital*1.1,0],$o,$ov,$mu,6371000,400000);
check($opposite['signed_delta_v_m_s']<0,'overspeed produces retrograde circularization');
check($opposite['burn_direction'][1]<0,'retrograde burn direction sign');
$slow=moonSimCircularizationEstimate($p,[0,$orbital*0.9,0],$o,$ov,$mu,6371000,400000);
check($slow['signed_delta_v_m_s']>0,'underspeed produces prograde circularization');
check($slow['burn_direction'][1]>0,'prograde burn direction sign');
$half=moonSimBurnBudget(10000,500,90000,300,100);
$full=moonSimBurnBudget(10000,1000,90000,300,100);
check($full['available_delta_v_m_s']>$half['available_delta_v_m_s'],'additional fuel increases delta-v capacity');
check($full['fuel_required_kg']===$half['fuel_required_kg'],'requested fuel is independent of onboard reserve');
check($full['ideal_burn_seconds']>0,'positive impulse implies positive burn duration');
try {moonSimBurnBudget(10000,-1,90000,300,100);throw new RuntimeException('Negative fuel accepted');}
catch(InvalidArgumentException $e){echo "PASS negative fuel rejected".PHP_EOL;}
try {moonSimEncounterPreview($p,$v,$o,$moon,$mv,[0,1,0],-1,$mu,4.9048695e12,1737400,20000,240);
    throw new RuntimeException('Negative impulse accepted');
} catch (InvalidArgumentException $e){echo "PASS negative impulse rejected".PHP_EOL;}
$phase=moonSimMoonPhaseReference($p,$o,$moon,$mv,86400);
check($phase['moon_rotation_deg']>0,'Moon progresses in orbit during coast');
check(count($phase['moon_predicted_xyz_m'])===3,'Moon future position has three coordinates');
$radialState=moonSimCircularizationEstimate($p,[$orbital,0,0],$o,$ov,$mu,6371000,400000);
check(!$radialState['applicable_now'],'radial motion blocks circularization recommendation');
echo "All baseline analytical checks passed. Real-flight accuracy NOT proven.".PHP_EOL;
