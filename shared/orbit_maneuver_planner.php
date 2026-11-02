<?php
declare(strict_types=1);
require_once __DIR__.'/orbital_mechanics.php';

/**
 * Instantaneous tangential impulsive maneuver estimate for a two-body orbit.
 * Direction is the current tangential velocity direction; no maneuver is
 * considered valid when radial speed is significant or the target is below
 * the body surface. This is advisory, not a lunar intercept propagator.
 */
function moonSimCircularizationEstimate(
    array $position, array $velocity, array $bodyPosition, array $bodyVelocity,
    float $mu, float $bodyRadius, float $targetAltitude
): array {
    if ($mu<=0 || $bodyRadius<=0 || $targetAltitude<0) {
        throw new InvalidArgumentException('Invalid central body or target altitude.');
    }
    $relative=moonSimRelativeVector($position,$bodyPosition);
    $v=moonSimRelativeVector($velocity,$bodyVelocity);
    $radius=moonSimVectorMagnitude($relative);
    if ($radius<=$bodyRadius) throw new RuntimeException('Vehicle is not above the surface.');
    $up=array_map(static fn($a)=>$a/$radius,$relative);
    $radial=moonSimVectorDot($v,$up);
    $tangent=[$v[0]-$radial*$up[0],$v[1]-$radial*$up[1],$v[2]-$radial*$up[2]];
    $tangentSpeed=moonSimVectorMagnitude($tangent);
    $requiredSpeed=sqrt($mu/$radius);
    $delta=$requiredSpeed-$tangentSpeed;
    $direction=$tangentSpeed>1.0e-5
        ? array_map(static fn($a)=>$a/$tangentSpeed,$tangent)
        : [0.0,0.0,0.0];
    $valid=abs($radial)<50.0 && abs(($radius-$bodyRadius)-$targetAltitude)<20000.0
        && $tangentSpeed>1.0e-5;
    return [
        'altitude_m'=>$radius-$bodyRadius,
        'target_altitude_m'=>$targetAltitude,
        'radial_speed_m_s'=>$radial,
        'tangential_speed_m_s'=>$tangentSpeed,
        'circular_speed_m_s'=>$requiredSpeed,
        'signed_delta_v_m_s'=>$delta,
        'burn_direction'=>$delta>=0 ? $direction : array_map(static fn($a)=>-$a,$direction),
        'applicable_now'=>$valid,
        'warning'=>$valid?'Instantaneous impulse estimate only; finite burns require targeting.':
            'Not near target circularization altitude or radial velocity too high; do not burn from this estimate.',
    ];
}

/** Ideal rocket-equation duration estimate using constant thrust and Isp. */
function moonSimIdealBurnTime(float $massKg,float $thrustN,float $ispSeconds,float $deltaVMs): ?float {
    if ($massKg<=0 || $thrustN<=0 || $ispSeconds<=0 || $deltaVMs<0) return null;
    $ve=$ispSeconds*9.80665;
    $fuel=$massKg*(1-exp(-$deltaVMs/$ve));
    return $fuel*$ve/$thrustN;
}

/** Finite-propellant idealized maneuver budget. Advisory only. */
function moonSimBurnBudget(float $mass, float $fuel, float $thrust, float $isp, float $dv): array {
    if ($mass <= 1.0 || $fuel < 0 || $thrust <= 0 || $isp <= 0 || $dv < 0) {
        throw new InvalidArgumentException('Invalid propulsion inputs');
    }
    $fuel = min($fuel, $mass - 1.0);
    $exhaust = $isp * 9.80665;
    $required = $mass * (1.0 - exp(-$dv / $exhaust));
    return [
        'available_delta_v_m_s' => $exhaust * log($mass / ($mass - $fuel)),
        'fuel_required_kg' => $required,
        'fuel_available_kg' => $fuel,
        'fuel_sufficient' => $required <= $fuel,
        'ideal_burn_seconds' => min($required, $fuel) * $exhaust / $thrust,
    ];
}

/**
 * Earth-centered Hohmann-style translunar transfer starting at the current
 * radius. This is a *minimum-energy reference*, NOT a Moon intercept solution:
 * departure phase, lunar inclination, lunar gravity and finite burn effects
 * must be solved separately before commanding an engine.
 */
function moonSimTranslunarReference(
    array $craftPosition, array $craftVelocity, array $earthPosition,
    array $earthVelocity, array $moonPosition, float $earthMu
): array {
    $r=moonSimRelativeVector($craftPosition,$earthPosition);
    $v=moonSimRelativeVector($craftVelocity,$earthVelocity);
    $moonR=moonSimVectorMagnitude(moonSimRelativeVector($moonPosition,$earthPosition));
    $radius=moonSimVectorMagnitude($r);
    if ($earthMu<=0 || $radius<=0 || $moonR <= $radius) {
        throw new InvalidArgumentException('Transfer requires an Earth-centered orbit inside the Moon radius.');
    }
    $up=array_map(static fn($x)=>$x/$radius,$r);
    $radial=moonSimVectorDot($v,$up);
    $tangent=array_map(static fn($x,$u)=>$x-$radial*$u,$v,$up);
    $speed=moonSimVectorMagnitude($tangent);
    if ($speed<1e-6) throw new RuntimeException('Tangential reference direction unavailable.');
    $a=($radius+$moonR)/2.0;
    $transferSpeed=sqrt($earthMu*(2.0/$radius-1.0/$a));
    $delta=$transferSpeed-$speed;
    $unit=array_map(static fn($x)=>$x/$speed,$tangent);
    return [
        'earth_radius_m'=>$radius,
        'moon_orbital_radius_m'=>$moonR,
        'departure_radial_speed_m_s'=>$radial,
        'departure_tangential_speed_m_s'=>$speed,
        'reference_departure_speed_m_s'=>$transferSpeed,
        'reference_signed_delta_v_m_s'=>$delta,
        'reference_burn_direction'=>$delta>=0 ? $unit : array_map(static fn($x)=>-$x,$unit),
        'reference_coast_seconds'=>M_PI*sqrt($a*$a*$a/$earthMu),
        'valid_near_circular_departure'=>abs($radial)<50.0,
        'warning'=>'REFERENCE ONLY: does not solve lunar encounter geometry, departure phase or finite burn. Do not treat as an executable TLI command.',
    ];
}

/** Constant-angular-rate lunar ephemeris approximation, advisory only. */
function moonSimMoonPhaseReference(
    array $craftPosition, array $earthPosition, array $moonPosition,
    array $moonVelocity, float $coastSeconds
): array {
    $r=moonSimRelativeVector($moonPosition,$earthPosition);
    $radius=moonSimVectorMagnitude($r);
    if ($radius<1.0 || $coastSeconds<=0) throw new InvalidArgumentException('Invalid lunar ephemeris');
    $normal=array_map(static fn($x)=>$x/$radius,$r);
    $radial=moonSimVectorDot($moonVelocity,$normal);
    $tangent=array_map(static fn($v,$u)=>$v-$radial*$u,$moonVelocity,$normal);
    $speed=moonSimVectorMagnitude($tangent);
    if ($speed<1e-6) throw new RuntimeException('Lunar orbital direction unknown');
    $t=array_map(static fn($v)=>$v/$speed,$tangent);
    $angle=$speed*$coastSeconds/$radius;
    $predicted=array_map(
        static fn($u,$v,$e)=>$e+$radius*($u*cos($angle)+$v*sin($angle)),
        $normal,$t,$earthPosition
    );
    $departure=moonSimRelativeVector($craftPosition,$earthPosition);
    $departRadius=moonSimVectorMagnitude($departure);
    $departUnit=array_map(static fn($x)=>$x/$departRadius,$departure);
    $departurePhase=acos(max(-1.0,min(1.0,moonSimVectorDot($departUnit,$normal))));
    return [
        'moon_predicted_xyz_m'=>$predicted,
        'moon_rotation_deg'=>rad2deg($angle),
        'departure_moon_angle_deg'=>rad2deg($departurePhase),
        'coast_seconds'=>$coastSeconds,
        'warning'=>'Circular-orbit approximation only. Not an encounter solver; Moon gravity, orbital inclination and required lead geometry are unresolved.'
    ];
}

/**
 * Preview an impulsive departure under Earth/Moon gravity using leapfrog.
 * Fixed Earth model; Moon is dynamically propagated around Earth.
 * Advisory approximation, independent of the authoritative flight worker.
 */
function moonSimEncounterPreview(
    array $craft, array $craftVelocity, array $earth, array $moon,
    array $moonVelocity, array $burnDirection, float $deltaV,
    float $earthMu, float $moonMu, float $moonRadius,
    float $duration, float $step=120.0, float $departureDelay=0.0, float $earthRadius=6371000.0
): array {
    if ($duration<=0 || $step<=0 || $earthMu<=0 || $moonMu<=0 || $earthRadius<=0) {
        throw new InvalidArgumentException('Invalid propagation parameters');
    }
    $norm=moonSimVectorMagnitude($burnDirection);
    if ($norm<1e-9) throw new InvalidArgumentException('Burn direction unknown');
    if ($departureDelay<0 || $departureDelay>=$duration) {
        throw new InvalidArgumentException('Departure delay must be inside preview duration');
    }
    $burnApplied=false;
    $accel=static function(array $p,array $origin,float $mu):array {
        $r=moonSimRelativeVector($p,$origin);
        $len=max(1.0,moonSimVectorMagnitude($r));
        $factor=-$mu/($len*$len*$len);
        return [$r[0]*$factor,$r[1]*$factor,$r[2]*$factor];
    };
    $closest=INF;$closestAt=0.0;$relativeSpeed=0.0;
    $earthImpact=false;
    $moonImpact=false;
    $steps=(int)ceil($duration/$step);
    if($steps>10000)throw new InvalidArgumentException('Preview exceeds time step limit');
    for($k=0;$k<=$steps;$k++){
        $now=min($duration,$k*$step);
        if (!$burnApplied && $now >= $departureDelay) {
            for($i=0;$i<3;$i++)$craftVelocity[$i]+=$deltaV*$burnDirection[$i]/$norm;
            $burnApplied=true;
        }
        if (moonSimVectorMagnitude(moonSimRelativeVector($craft,$earth)) < $earthRadius) $earthImpact=true;
        $range=moonSimVectorMagnitude(moonSimRelativeVector($craft,$moon));
        if ($burnApplied && $range <= $moonRadius) $moonImpact=true;
        if($burnApplied && $range<$closest){
            $closest=$range;$closestAt=min($duration,$k*$step);
            $relativeSpeed=moonSimVectorMagnitude(moonSimRelativeVector($craftVelocity,$moonVelocity));
        }
        if($k===$steps)break;
        $h=min($step,$duration-$k*$step);
        $earthCraft=$accel($craft,$earth,$earthMu);
        $moonCraft=$accel($craft,$moon,$moonMu);
        $moonAcc=$accel($moon,$earth,$earthMu);
        for($i=0;$i<3;$i++){
            $craftVelocity[$i]+=0.5*$h*($earthCraft[$i]+$moonCraft[$i]);
            $moonVelocity[$i]+=0.5*$h*$moonAcc[$i];
            $craft[$i]+=$h*$craftVelocity[$i];
            $moon[$i]+=$h*$moonVelocity[$i];
        }
        $earthCraft=$accel($craft,$earth,$earthMu);
        $moonCraft=$accel($craft,$moon,$moonMu);
        $moonAcc=$accel($moon,$earth,$earthMu);
        for($i=0;$i<3;$i++){
            $craftVelocity[$i]+=0.5*$h*($earthCraft[$i]+$moonCraft[$i]);
            $moonVelocity[$i]+=0.5*$h*$moonAcc[$i];
        }
    }
    return [
        'earth_impact_detected'=>$earthImpact,
        'moon_impact_detected'=>$moonImpact,
        'departure_delay_seconds'=>$departureDelay,
        'closest_approach_km'=>$closest/1000.0,
        'closest_surface_altitude_km'=>($closest-$moonRadius)/1000.0,
        'time_of_closest_approach_h'=>$closestAt/3600.0,
        'relative_speed_at_approach_m_s'=>$relativeSpeed,
        'lunar_100km_pass'=>$closest<=$moonRadius+100000.0,
        'warning'=>'Impulsive transfer preview only; fixed Earth, simplified Moon dynamics, finite step and no fuel/attitude effects. Not flight guidance.'
    ];
}

/**
 * Bounded local search around the current impulsive transfer reference.
 * Search only adjusts prograde speed and the direction within the current
 * Earth-centered orbital plane. It cannot solve inclination or departure time.
 */
function moonSimEncounterLocalSearch(
    array $craft, array $velocity, array $earth, array $moon, array $moonVelocity,
    array $referenceDirection, float $referenceDv, float $earthMu, float $moonMu,
    float $moonRadius, float $duration
): array {
    $radial=moonSimRelativeVector($craft,$earth);
    $rmag=moonSimVectorMagnitude($radial);
    if($rmag<1.0) throw new InvalidArgumentException('Missing Earth-relative position');
    $up=array_map(static fn($x)=>$x/$rmag,$radial);
    $forward=$referenceDirection;
    $fmag=moonSimVectorMagnitude($forward);
    if($fmag<1e-9)throw new InvalidArgumentException('Missing reference burn vector');
    $forward=array_map(static fn($x)=>$x/$fmag,$forward);
    $best=null;
    $delays=[0.0,3600.0,7200.0,10800.0,14400.0,18000.0];
    foreach($delays as $delaySeconds) {
    foreach([-8,-4,0,4,8] as $angleDegrees) {
        $a=deg2rad((float)$angleDegrees);
        $direction=[];
        for($i=0;$i<3;$i++)$direction[$i]=$forward[$i]*cos($a)+$up[$i]*sin($a);
        foreach([0.94,0.97,1.0,1.03,1.06] as $factor) {
            $dv=max(0.0,$referenceDv*$factor);
            $preview=moonSimEncounterPreview($craft,$velocity,$earth,$moon,$moonVelocity,
                $direction,$dv,$earthMu,$moonMu,$moonRadius,$duration,240.0,$delaySeconds);
            if ($preview['earth_impact_detected'] || $preview['moon_impact_detected']) continue;
            if($best===null || $preview['closest_approach_km']<$best['closest_approach_km']) {
                $best=$preview;
                $best['candidate_delta_v_m_s']=$dv;
                $best['candidate_pitch_offset_deg']=$angleDegrees;
                $best['candidate_direction']=$direction;
                $best['candidate_departure_delay_seconds']=$delaySeconds;
            }
        }
    }
    }
    if ($best===null) throw new RuntimeException('No candidate avoids both Earth and Moon impact.');
    $best['sampled_candidates']=150;
    $best['postburn_only_comparison']=true;
    $best['lunar_surface_clearance_status']=$best['closest_surface_altitude_km']<0?'PREDICTED LUNAR IMPACT':($best['closest_surface_altitude_km']<100?'LOW PASS':'DISTANT PASS');
    $best['warning']='LOCAL SEARCH ONLY: impulsive burns, fixed Earth, simplified Moon dynamics, no inclination or finite-burn optimization; departure delay is sampled hourly from 0 through 5 hours. Compare independently before any manual burn.';
    return $best;
}
