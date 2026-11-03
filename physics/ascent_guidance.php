<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/ascent_guidance_math.php';
require_once __DIR__.'/../shared/mission_profiles.php';
require_once __DIR__.'/../shared/saturn_engine_groups.php';
require_once __DIR__.'/attitude.php';
require_once __DIR__.'/../shared/physical_constants.php';

/** State-driven ascent controller; never directly edits velocity or Euler attitude. */
function moonSimAscentGuidanceStep(mysqli $conn,bool $enabled):?array {
    global $earth_radius,$earth_mass,$gravitational_constant,
           $earth_center_x,$earth_center_y,$earth_center_z,
           $stage1_initial_fuel,$stage2_initial_fuel;
    $result=$conn->query("SELECT seconds,launch_events FROM mission_clock WHERE id=1");
    if(!$result || !($time=$result->fetch_assoc()))return null;
    $seconds=(int)$time['seconds'];$events=(int)$time['launch_events'];
    if($seconds===-900) {
        $conn->query("UPDATE mission_clock SET seconds=-15,minutes=0,launch_events=0 WHERE id=1");
        return ['phase'=>'countdown'];
    }
    if($seconds===-1 && $events===0) {
        $conn->query("UPDATE mission_clock SET launch_events=1 WHERE id=1");
        return ['phase'=>'armed'];
    }
    if(!$enabled || $seconds<0 || $events!==1)return null;
    $result=$conn->query("SELECT attribute,value FROM command_module");
    if(!$result)return null;
    $s=[];
    while($row=$result->fetch_assoc())$s[$row['attribute']]=(float)$row['value'];
    if((int)($s['ABORT']??0)!==0 || (int)($s['stage3']??0)!==1)return null;
    foreach(['x','y','z','delta_x','delta_y','delta_z'] as $key) {
        if(!isset($s[$key])||!is_finite($s[$key]))return null;
    }
    $position=[$s['x'],$s['y'],$s['z']];
    $velocity=[$s['delta_x'],$s['delta_y'],$s['delta_z']];
    $earth=[$earth_center_x,$earth_center_y,$earth_center_z];
    $mu=$gravitational_constant*$earth_mass;
    if($mu<=0 || $earth_radius<=0)return null;
    $profile=moonSimActiveMissionProfile($conn);
    $peri=max(100000.0,(float)($profile['parking_periapsis_m']??160000.0));
    $apo=max($peri,(float)($profile['parking_apoapsis_m']??180000.0));
    $radial=max(1.0,(float)($profile['parking_max_radial_speed_m_s']??200.0));
    $first=(int)($s['stage1']??0)===1;$second=(int)($s['stage2']??0)===1;
    $ref=moonSimAscentDirection($position,$velocity,$earth,$earth_radius,($peri+$apo)/2.0);

    if(!$first && !$second && $seconds>300 &&
       moonSimParkingOrbitReached($position,$velocity,$earth,$mu,$earth_radius,$peri,$apo,$radial)) {
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE command_module SET value=0 WHERE attribute IN ('thrust','pitch_rate','yaw_rate','roll_rate')");
            $conn->query("UPDATE EngineStatus SET status='OFF' WHERE engine_stage='Third Stage'");
            $conn->query("UPDATE CMguidance SET thrust=0 WHERE id=1");
            $conn->query("UPDATE CMprogram_register SET program_number=0,seconds=NULL WHERE program_number=23");
            $conn->query("UPDATE CMguidance SET execute=0 WHERE id=23");
            $conn->query("UPDATE mission_clock SET launch_events=2 WHERE id=1");
            $conn->commit();
        }catch(Throwable $error){$conn->rollback();throw $error;}
        return ['phase'=>'parking_orbit','altitude_m'=>$ref['altitude_m']];
    }
    if(($profile['launch_guidance_mode']??'')==='TIMED_VALIDATION' && isset($profile['id'])) {
        $times=moonSimMissionLaunchEvents($conn,(int)$profile['id']);
        if($first && moonSimMissionEventDue($times,'sic_center_cutoff',(float)$seconds))
            moonSimCenterEngineCutoff($conn,'First Stage');
        if(!$first && $second && moonSimMissionEventDue($times,'sii_center_cutoff',(float)$seconds))
            moonSimCenterEngineCutoff($conn,'Second Stage');
        if(!$first && $second && (int)($s['escape']??0)===1
            && moonSimMissionEventDue($times,'les_jettison',(float)$seconds))
            $conn->query("UPDATE command_module SET value=0 WHERE attribute='escape'");
        $event=$first?'sic_outboard_cutoff':($second?'sii_outboard_cutoff':null);
        $fuel=$first?($s['s1fuel']??INF):($s['s2fuel']??INF);
        $initial=$first?$stage1_initial_fuel:$stage2_initial_fuel;
        $alt=$first?10000.0:80000.0;
        if($event!==null && isset($times[$event])
            && $seconds>=$times[$event]['seconds']
            && $ref['altitude_m']>$alt && $fuel<=0.01*$initial)
            $conn->query("UPDATE command_module SET value=1 WHERE attribute='staging'");
    }
    $rates=moonSimAscentBodyRates(moonSimLoadVehicleQuaternion($conn,'CM'),$ref['direction'],3.0);
    $p=sprintf('%.8f',$rates['pitch_rate']);$y=sprintf('%.8f',$rates['yaw_rate']);
    $conn->query("UPDATE command_module SET value=CASE attribute
        WHEN 'pitch_rate' THEN $p WHEN 'yaw_rate' THEN $y
        WHEN 'roll_rate' THEN 0 ELSE value END
        WHERE attribute IN ('pitch_rate','yaw_rate','roll_rate')");
    $ref['phase']=$first?'S-IC':($second?'S-II':'S-IVB');
    $ref['alignment_error_deg']=$rates['alignment_error_deg'];
    return $ref;
}
