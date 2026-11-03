<?php
declare(strict_types=1);
$secret=getenv('MOON_OPERATOR_PASSWORD');
if (!is_string($secret)||$secret===''||!hash_equals($secret,(string)($_SERVER['PHP_AUTH_PW']??''))) {
    header('WWW-Authenticate: Basic realm="Moon Simulator"');
    http_response_code(401);exit('Operator authentication required');
}
require __DIR__.'/physics/db.php';
require_once __DIR__.'/shared/physical_constants.php';
require_once __DIR__.'/shared/orbit_maneuver_planner.php';
header('Content-Type: text/html; charset=utf-8');
function rowsManeuver(mysqli $conn,string $table):array {
    $result=$conn->query("SELECT attribute,value FROM $table");
    $a=[];
    if($result)while($r=$result->fetch_assoc())$a[$r['attribute']]=(float)$r['value'];
    return $a;
}
function v3(array $a,string $prefix=''):array {
    foreach(['x','y','z'] as $axis)if(!isset($a[$prefix.$axis]))throw new RuntimeException('Missing vector telemetry');
    return [$a[$prefix.'x'],$a[$prefix.'y'],$a[$prefix.'z']];
}
function renderV($x):string {
    if(is_bool($x))return $x?'YES':'NO';
    if(is_array($x))return htmlspecialchars((string)json_encode($x,JSON_UNESCAPED_SLASHES),ENT_QUOTES,'UTF-8');
    if(is_numeric($x))return number_format((float)$x,3,'.','');
    return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
}
$vehicle=(string)($_GET['vehicle']??'CM');if(!in_array($vehicle,['CM','LM'],true))$vehicle='CM';
$body=(string)($_GET['body']??'EARTH');if(!in_array($body,['EARTH','MOON'],true))$body='EARTH';
$altitude=filter_input(INPUT_GET,'altitude',FILTER_VALIDATE_FLOAT);
if($altitude===false || $altitude===null)$altitude=$body==='MOON'?100000.0:180000.0;
$altitude=max(0.0,min(5000000.0,$altitude));
$solution=null;$budget=null;$tli=null;$phase=null;$encounter=null;$search=null;$orbitalDetails=null;$error='';
try{
    $craft=rowsManeuver($conn,$vehicle==='CM'?'command_module':'telemetry');
    $moon=rowsManeuver($conn,'moon');
    $center=$body==='EARTH'?[$earth_center_x,$earth_center_y,$earth_center_z]:v3($moon);
    $velocity=$body==='EARTH'?[0,0,0]:v3($moon,'delta_');
    $mu=$gravitational_constant*($body==='EARTH'?$earth_mass:$moon_mass);
    $radius=$body==='EARTH'?$earth_radius:$moon_radius;
    $solution=moonSimCircularizationEstimate(v3($craft),v3($craft,'delta_'),$center,$velocity,$mu,$radius,(float)$altitude);
    $relativeVelocity=moonSimRelativeVector(v3($craft,'delta_'),$velocity);
    $orbitalDetails=moonSimOrbitalElements(v3($craft),$relativeVelocity,$center,$mu);
    $orbitalDetails=[
        'central_body'=>$body,
        'eccentricity'=>$orbitalDetails['eccentricity'],
        'periapsis_altitude_m'=>$orbitalDetails['periapsis_radius']!==null
            ?$orbitalDetails['periapsis_radius']-$radius:null,
        'apoapsis_altitude_m'=>$orbitalDetails['apoapsis_radius']!==null
            ?$orbitalDetails['apoapsis_radius']-$radius:null,
        'specific_orbital_energy_j_kg'=>$orbitalDetails['specific_energy'],
        'orbit_type'=>$orbitalDetails['specific_energy']<0?'BOUND':'OPEN',
    ];
    if ($vehicle==='CM' && $body==='EARTH') {
        $tli=moonSimTranslunarReference(v3($craft),v3($craft,'delta_'),
            [$earth_center_x,$earth_center_y,$earth_center_z],[0.0,0.0,0.0],
            v3($moon),$gravitational_constant*$earth_mass);
        if ($tli['valid_near_circular_departure']) {
            $encounter=moonSimEncounterPreview(v3($craft),v3($craft,'delta_'),
                [$earth_center_x,$earth_center_y,$earth_center_z],
                v3($moon),v3($moon,'delta_'),$tli['reference_burn_direction'],
                abs($tli['reference_signed_delta_v_m_s']),
                $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
                $moon_radius,(float)$tli['reference_coast_seconds']*1.35);

        }
        $phase=moonSimMoonPhaseReference(v3($craft),
            [$earth_center_x,$earth_center_y,$earth_center_z],
            v3($moon),v3($moon,'delta_'),(float)$tli['reference_coast_seconds']);
    }
    // Only quote an engine budget when the stage configuration is known.
    $cm= $vehicle==='CM' ? $craft : rowsManeuver($conn,'command_module');
    if ($vehicle==='CM' && (int)($cm['service_module']??0)===1
        && (int)($cm['stage1']??0)===0 && (int)($cm['stage2']??0)===0
        && (int)($cm['stage3']??0)===0 && (int)($cm['dock']??0)===0) {
        $fuel=max(0.0,(float)($cm['fuel']??0.0));
        $mass=$command_module_empty_mass+$service_module_empty_mass+$fuel
            +max(0.0,(float)($cm['RCS']??0.0));
        $budget=moonSimBurnBudget($mass,$fuel,$service_module_thrust,314.0,abs($solution['signed_delta_v_m_s']));
    } elseif ($vehicle==='LM' && (int)($cm['dock']??0)===0 && (int)($cm['stage3']??0)===0) {
        $ascent=(int)($craft['descent']??0)!==1;
        $fuel=max(0.0,(float)($craft[$ascent?'fuel_ascent':'fuel']??0.0));
        $otherFuel=$ascent?0.0:max(0.0,(float)($craft['fuel_ascent']??0.0));
        $mass=$lander_ascent_empty_mass+$fuel+$otherFuel
            +($ascent?0.0:$lander_descent_empty_mass)+$lm_rcs_initial_propellant;
        $budget=moonSimBurnBudget($mass,$fuel,$ascent?$lander_ascent_thrust:$lander_descent_thrust,
            $ascent?310.0:304.0,abs($solution['signed_delta_v_m_s']));
    }
    if (($_GET['search']??'')==='1' && $vehicle==='CM' && $body==='EARTH'
        && $tli!==null && $tli['valid_near_circular_departure']) {
        $search=moonSimEncounterLocalSearch(v3($craft),v3($craft,'delta_'),
            [$earth_center_x,$earth_center_y,$earth_center_z],
            v3($moon),v3($moon,'delta_'),$tli['reference_burn_direction'],
            abs($tli['reference_signed_delta_v_m_s']),
            $gravitational_constant*$earth_mass,$gravitational_constant*$moon_mass,
            $moon_radius,(float)$tli['reference_coast_seconds']*1.35,
            $budget!==null?(float)$budget['available_delta_v_m_s']:null);
    }
    if ($search!==null && $budget===null) {
        $search['propulsion_feasibility']='UNVERIFIED: no compatible engine/fuel configuration could be established; do not command this burn.';
    }
    if ($search!==null && $budget!==null) {
        $search['candidate_fuel_budget']=moonSimBurnBudget(
            $mass,$fuel,
            $vehicle==='CM'?$service_module_thrust:($ascent?$lander_ascent_thrust:$lander_descent_thrust),
            $vehicle==='CM'?314.0:($ascent?310.0:304.0),
            (float)$search['candidate_delta_v_m_s']
        );
        $search['candidate_fuel_warning']=($search['candidate_fuel_budget']['fuel_sufficient']??false)
            ? 'Ideal propellant estimate appears sufficient, subject to stage configuration and flight validation.'
            : 'WARNING: candidate exceeds estimated usable propellant.';
    }
} catch(Throwable $e){$error='Required telemetry unavailable: '.$e->getMessage();}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Orbit Maneuver Advisor</title>
<style>body{font:16px system-ui;background:#15202c;color:#edf3fc;padding:22px}main{max-width:900px;margin:auto}dt{color:#9fd4ec}dd{margin:0 0 12px}form{padding:18px;border:1px solid #526679}input,select,button{font:inherit;padding:8px;background:#293c4f;color:white}</style></head><body><main><h1>ORBIT MANEUVER ADVISOR</h1>
<form method="get"><label>Craft <select name="vehicle"><option <?=$vehicle==='CM'?'selected':''?>>CM</option><option <?=$vehicle==='LM'?'selected':''?>>LM</option></select></label>
<label>Central body <select name="body"><option <?=$body==='EARTH'?'selected':''?>>EARTH</option><option <?=$body==='MOON'?'selected':''?>>MOON</option></select></label>
<label>Target altitude (m) <input name="altitude" type="number" min="0" max="5000000" value="<?=(int)$altitude?>"></label>
<button>Calculate</button><button name="search" value="1">Search 150 departure timing and burn candidates</button></form>
<?php if($error):?><p><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></p><?php elseif($solution):?>
<h2><?=renderV($solution['applicable_now'])==='YES'?'Circularization estimate':'CAUTION — not at target orbit'?></h2>
<dl><?php foreach($solution as $key=>$value):?><dt><?=htmlspecialchars(str_replace('_',' ',strtoupper($key)),ENT_QUOTES,'UTF-8')?></dt><dd><?=renderV($value)?></dd><?php endforeach;?></dl>
<?php if($orbitalDetails):?><h2>Current <?=htmlspecialchars($body,ENT_QUOTES,'UTF-8')?>-relative orbital elements</h2><dl>
<?php foreach($orbitalDetails as $key=>$value):?><dt><?=htmlspecialchars(str_replace('_',' ',strtoupper($key)),ENT_QUOTES,'UTF-8')?></dt><dd><?=($value===null?'N/A':renderV($value))?></dd><?php endforeach;?></dl><?php endif;?>
<?php if($tli):?><h2>Translunar minimum-energy reference — NOT targeting guidance</h2><dl>
<?php foreach($tli as $key=>$value):?><dt><?=htmlspecialchars(str_replace('_',' ',strtoupper($key)),ENT_QUOTES,'UTF-8')?></dt><dd><?=renderV($value)?></dd><?php endforeach;?></dl>
<p>Departure phase and lunar arrival are not calculated. A burn based solely on this reference may miss the Moon.</p><?php endif;?>
<?php if($phase):?><h2>Approximate lunar arrival geometry — not an intercept</h2><dl>
<?php foreach($phase as $key=>$value):?><dt><?=htmlspecialchars(str_replace('_',' ',strtoupper($key)),ENT_QUOTES,'UTF-8')?></dt><dd><?=renderV($value)?></dd><?php endforeach;?></dl><?php endif;?>
<?php if($encounter):?><h2>Predicted closest lunar approach — ideal impulsive reference</h2><dl>
<?php foreach($encounter as $key=>$value):?><dt><?=htmlspecialchars(str_replace('_',' ',strtoupper($key)),ENT_QUOTES,'UTF-8')?></dt><dd><?=renderV($value)?></dd><?php endforeach;?></dl><?php endif;?>
<?php if($search):?><h2>Closest non-impact candidate — not a flight-ready TLI command</h2>
<p>The search is approximate and may not find any actual Moon intercept. Only evaluated, non-impact candidates with a resolved closest approach can be ranked; engine ignition and burn guidance are not connected.</p>
<p><strong>Arrival energy:</strong> The candidate includes an osculating Moon-relative orbit and an approximate instantaneous lunar-capture impulse. A closest-approach state is not automatically a suitable LOI ignition point. Confirm timing, orientation, fuel, and periapsis before attempting any manual maneuver.</p><dl>
<?php foreach($search as $key=>$value):?><dt><?=htmlspecialchars(str_replace('_',' ',strtoupper($key)),ENT_QUOTES,'UTF-8')?></dt><dd><?=renderV($value)?></dd><?php endforeach;?></dl><?php endif;?>
<?php if($budget):?><h2>Available propulsion budget (ideal approximation)</h2><dl>
<?php foreach($budget as $key=>$value):?><dt><?=htmlspecialchars(str_replace('_',' ',strtoupper($key)),ENT_QUOTES,'UTF-8')?></dt><dd><?=renderV($value)?></dd><?php endforeach;?></dl>
<p>Budget assumes nominal thrust and engine performance. It does not verify ignition, engine damage, throttling, attitude, other attached loads, or safe burn geometry.</p><?php endif;?>
<?php endif;?><p>This is a two-body, instantaneous-impulse advisory calculator only. It does not command burns, predict lunar intercept, or account for a finite engine burn. Use cockpit controls and real propellant limits.</p></main></body></html>
