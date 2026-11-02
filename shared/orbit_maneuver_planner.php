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
