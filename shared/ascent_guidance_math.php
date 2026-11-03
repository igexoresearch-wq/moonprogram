<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';

/**
 * Inertial ascent attitude reference. Local +X of the stack is the engine
 * thrust direction. Earth rotation is represented by the actual velocity
 * state, not an invented boost. This is generic launch guidance, not a
 * reproduction of the Apollo 11 guidance coefficients.
 */
function moonSimAscentDirection(
    array $position, array $velocity, array $earthCenter,
    float $earthRadius, float $targetAltitude
): array {
    $r=moonSimRelativeVector($position,$earthCenter);
    $distance=moonSimVectorMagnitude($r);
    if (!is_finite($distance) || $distance<=$earthRadius*0.9
        || $earthRadius<=0.0 || $targetAltitude<=0.0)
        throw new InvalidArgumentException('Invalid Earth-relative launch geometry');
    $up=array_map(static fn(float $x):float=>$x/$distance,$r);
    $radialVelocity=moonSimVectorDot($velocity,$up);
    $east=moonSimVectorCross([0.0,0.0,1.0],$up);
    $eastLength=moonSimVectorMagnitude($east);
    if ($eastLength<1e-8) {
        $east=moonSimVectorCross([0.0,1.0,0.0],$up);
        $eastLength=moonSimVectorMagnitude($east);
    }
    $east=array_map(static fn(float $x):float=>$x/$eastLength,$east);
    $horizontal=[];
    for($i=0;$i<3;$i++) $horizontal[$i]=$velocity[$i]-$radialVelocity*$up[$i];
    $horizontalSpeed=moonSimVectorMagnitude($horizontal);
    // Follow the current prograde orbital plane once the rocket has actually
    // established one; at launch use Earth-east heading as a default.
    if($horizontalSpeed>250.0) {
        $east=array_map(static fn(float $x):float=>$x/$horizontalSpeed,$horizontal);
    }
    $altitude=$distance-$earthRadius;
    if($altitude<1000.0) {
        $elevation=90.0;
    } elseif($altitude<20000.0) {
        $elevation=90.0-45.0*(($altitude-1000.0)/19000.0);
    } elseif($altitude<100000.0) {
        $elevation=45.0-35.0*(($altitude-20000.0)/80000.0);
    } else {
        // Once the rocket approaches the target altitude, drive the radial
        // velocity toward zero rather than continuing a scripted gravity turn.
        $desiredRadialSpeed=max(0.0,min(450.0,0.012*($targetAltitude-$altitude)));
        $elevation=max(-10.0,min(18.0,0.035*($desiredRadialSpeed-$radialVelocity)));
    }
    $angle=deg2rad($elevation);
    $direction=[];
    for($i=0;$i<3;$i++) {
        $direction[$i]=sin($angle)*$up[$i]+cos($angle)*$east[$i];
    }
    $length=moonSimVectorMagnitude($direction);
    if($length<0.5 || !is_finite($length))throw new RuntimeException('Undefined launch thrust direction');
    return [
        'direction'=>array_map(static fn(float $x):float=>$x/$length,$direction),
        'altitude_m'=>$altitude,
        'radial_speed_m_s'=>$radialVelocity,
        'tangential_speed_m_s'=>$horizontalSpeed,
        'elevation_above_local_horizon_deg'=>$elevation,
    ];
}

/** Calculate body-frame pitch/yaw rate commands, never an attitude teleport. */
function moonSimAscentBodyRates(array $quaternion,array $direction,float $maxRate=3.0):array {
    if($maxRate<=0.0 || !is_finite($maxRate))throw new InvalidArgumentException('Invalid rate limit');
    $magnitude=moonSimVectorMagnitude($direction);
    if($magnitude<0.5 || !is_finite($magnitude))throw new InvalidArgumentException('Invalid target direction');
    $direction=array_map(static fn(float $x):float=>$x/$magnitude,$direction);
    // Quaternion matrix columns are the spacecraft's body axes in world XYZ.
    if(!function_exists('moonSimQuaternionMatrix')) {
        throw new RuntimeException('Attitude quaternion functions unavailable');
    }
    $matrix=moonSimQuaternionMatrix($quaternion);
    $local=[];
    for($j=0;$j<3;$j++) {
        $local[$j]=0.0;
        for($i=0;$i<3;$i++) $local[$j]+=$direction[$i]*$matrix[$i][$j];
    }
    $forward=max(-1.0,min(1.0,$local[0]));
    $pitchError=rad2deg(atan2($local[2],max(0.05,$forward)));
    $yawError=-rad2deg(atan2($local[1],max(0.05,$forward)));
    if($forward< -0.98 && abs($local[1])+abs($local[2])<0.1) $yawError=180.0;
    return [
        'pitch_rate'=>max(-$maxRate,min($maxRate,0.35*$pitchError)),
        'yaw_rate'=>max(-$maxRate,min($maxRate,0.35*$yawError)),
        'roll_rate'=>0.0,
        'alignment_error_deg'=>rad2deg(acos($forward)),
    ];
}

/** Only a bound, low-Earth orbit counts as parking-orbit insertion. */
function moonSimParkingOrbitReached(
    array $position,array $velocity,array $earthCenter,
    float $earthMu,float $earthRadius,float $minPeriapsisAltitude,
    float $minApoapsisAltitude,float $maxRadialSpeed
):bool {
    if($earthMu<=0 || $earthRadius<=0)return false;
    $r=moonSimRelativeVector($position,$earthCenter);
    $distance=moonSimVectorMagnitude($r);
    if($distance<=$earthRadius+100000.0 || !is_finite($distance))return false;
    $vr=moonSimVectorDot($r,$velocity)/$distance;
    if(abs($vr)>$maxRadialSpeed || !is_finite($vr))return false;
    $orbit=moonSimOrbitalElements($position,$velocity,$earthCenter,$earthMu);
    if($orbit['specific_energy']>=0
        || $orbit['periapsis_radius']===null || $orbit['apoapsis_radius']===null)
        return false;
    $peri=$orbit['periapsis_radius']-$earthRadius;
    $apo=$orbit['apoapsis_radius']-$earthRadius;
    return $peri>=$minPeriapsisAltitude && $apo>=$minApoapsisAltitude
        && $apo<=$minApoapsisAltitude+120000.0;
}
