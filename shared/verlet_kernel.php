<?php
declare(strict_types=1);

/**
 * Velocity-Verlet free-flight substep kernels in SI units.
 * These are called by the authoritative physics integrator. Keeping the
 * drift/kick arithmetic here lets regression tests exercise the same code.
 * Gravity is evaluated before and after drift by the caller.
 */
function moonSimVerletDrift(
    array $position, array $velocity, array $acceleration, float $dt
): array {
    if (!is_finite($dt) || $dt <= 0.0) {
        throw new InvalidArgumentException('Verlet timestep must be positive and finite');
    }
    return [
        $position[0] + $velocity[0]*$dt + 0.5*$acceleration[0]*$dt*$dt,
        $position[1] + $velocity[1]*$dt + 0.5*$acceleration[1]*$dt*$dt,
        $position[2] + $velocity[2]*$dt + 0.5*$acceleration[2]*$dt*$dt,
    ];
}

function moonSimVerletKick(
    array $velocity, array $acceleration0, array $acceleration1, float $dt
): array {
    if (!is_finite($dt) || $dt <= 0.0) {
        throw new InvalidArgumentException('Verlet timestep must be positive and finite');
    }
    return [
        $velocity[0] + 0.5*($acceleration0[0]+$acceleration1[0])*$dt,
        $velocity[1] + 0.5*($acceleration0[1]+$acceleration1[1])*$dt,
        $velocity[2] + 0.5*($acceleration0[2]+$acceleration1[2])*$dt,
    ];
}
