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
