<?php
declare(strict_types=1);

/**
 * Hard ascent guidance interlock. Ideal delta-v and estimated fuel reserve
 * from the targeting display are advisory, not physical abort conditions.
 * Real zero-fuel, crashed vehicle, attachment and damaged propulsion checks
 * are handled separately by the physical guidance/engine worker.
 */
function moonSimLmAscentMustInhibitForLowThrust(array $target): bool
{
    $altitude = (float)($target['altitude_m'] ?? NAN);
    $twr = (float)($target['initial_thrust_to_weight'] ?? NAN);
    // Fail closed on invalid telemetry rather than commanding ascent.
    if (!is_finite($altitude) || !is_finite($twr) || $twr <= 0.0) {
        return true;
    }
    return $altitude < 10000.0 && $twr <= 1.0;
}
