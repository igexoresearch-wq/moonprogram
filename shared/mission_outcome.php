<?php
declare(strict_types=1);

/**
 * Mission outcome is derived from physical contact and capsule configuration.
 * No scripted faults, artificial rescue, mission reset or propulsion writes.
 */
function moonSimMissionOutcome(
    ?array $cmContact,
    bool $serviceModuleAttached,
    bool $escapeTowerAttached,
    ?string $chuteState,
    ?array $reentryState = null
): array {
    if ($cmContact === null) {
        return ['state'=>'IN_FLIGHT','win'=>false,'reason'=>'Earth recovery has not occurred.'];
    }
    if (($cmContact['body'] ?? '') !== 'EARTH') {
        return ['state'=>'LOSS','win'=>false,'reason'=>'Command Module contacted the Moon instead of returning to Earth.'];
    }
    if (($cmContact['contact_state'] ?? '') !== 'LANDED') {
        return ['state'=>'LOSS','win'=>false,'reason'=>'Earth impact exceeded the simulator safe-contact envelope.'];
    }
    if ($serviceModuleAttached || $escapeTowerAttached) {
        return ['state'=>'LOSS','win'=>false,'reason'=>'Capsule reached Earth with flight hardware still attached.'];
    }
    $impact=(float)($cmContact['impact_speed'] ?? INF);
    if (!is_finite($impact) || $impact>12.0) {
        return ['state'=>'LOSS','win'=>false,'reason'=>'Earth impact speed is not survivable under the simulator envelope.'];
    }
    // Chute-state data may be unavailable for legacy sessions. Require
    // deployed mains for a verified win instead of silently assuming them.
    if ($chuteState !== 'MAIN') {
        return ['state'=>'UNCONFIRMED','win'=>false,'reason'=>'Earth touchdown recorded, but main-parachute deployment was not verified.'];
    }
    if ($reentryState !== null &&
        ((float)($reentryState['shield_integrity'] ?? 0) <= 0 ||
         (int)($reentryState['crew_survives'] ?? 0) !== 1)) {
        return ['state'=>'LOSS','win'=>false,'reason'=>'Reentry survival evidence prevents safe recovery.'];
    }
    return ['state'=>'EARTH_RECOVERY','win'=>true,'reason'=>'Command Module landed safely on Earth with main parachutes deployed.'];
}
