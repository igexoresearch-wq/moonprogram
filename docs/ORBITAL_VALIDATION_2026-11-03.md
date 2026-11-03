# Orbital propagation validation — 2026-11-03

This is source-level validation for frozen checklist Objective 2.3. It is **not** a completed flight or deployment test.

## Reproduce on a PHP 8.0+ checkout

```sh
php tests/orbital_propagation_checks.php
```

The checked-in script imports `shared/nbody_gravity.php`, `shared/verlet_kernel.php`, `shared/orbital_mechanics.php` and `shared/ascent_guidance_math.php`: the same numerical kernels used by `physics/integrator.php` and the parking-orbit guidance. No MySQL or external API is required. It fails with a nonzero exit when an assertion fails.

## Local numerical evidence

An equivalent standalone PHP 8.4 harness of the production gravity/Verlet formulas and parking-orbit acceptance conditions was **executed locally** (all checks passed). Results:

| Check | Observed | Criterion |
|---|---:|---|
| Circular 180-km orbit, two revolutions: max relative specific-energy drift | 9.42e-11 | <1e-8 |
| Circular orbit: max relative angular-momentum drift | 4.71e-11 | <1e-8 |
| Circular orbit: maximum radial drift | 0.047 m | <100 m |
| Elliptical orbit: perigee | 160000.0003 m | 160000 ±100 m |
| Elliptical orbit: apogee | 320001.1660 m | 320000 ±100 m |
| Moon Earth-relative orbit: one-day energy drift | 2.98e-11 | <1e-8 |
| Moon: one-day radius drift | 0.0057 m | <100 m |
| Lunar tidal displacement vs Earth-only LEO after one revolution | 3.34 m | nonzero, <100 m |
| Lunar acceleration at Earth-centered coordinate origin | 0 (within double precision) | <1e-12 m/s² |
| Parking-orbit acceptance and rejection cases | Pass | Valid bound circular orbit only, rejecting stationary craft, excessive radial speed and excessive apoapsis |

The exact repository regression entry point has **not been executed against a deployed PHP/MySQL instance**. The local harness used copies of the production numerical formulas; do not misrepresent that as execution of the checked-in script. In a checkout, run the command above. Operational end-to-end validation remains in checklist Section 13.

## Physical-frame correction

The simulator fixes Earth at its coordinate origin. Therefore the Moon's gravitational acceleration on the spacecraft must be corrected by subtracting the acceleration it would impart to Earth. This is the standard Earth-centered tidal/indirect term. The simulated Moon itself follows the Earth–Moon relative two-body equation using `G(M_E+M_M)` rather than just `GM_E`.

Before the indirect correction, the local harness showed approximately 889 m of spurious Earth-relative displacement during one LEO period. With the correction, the predicted lunar tidal displacement is approximately 3.34 m under the tested geometry.

These tests are numerical consistency checks. They do **not** establish long-term high-precision ephemeris accuracy, lunar encounter accuracy, drag/reentry fidelity, thrust maneuver accuracy, launch guidance accuracy, or that a full mission has succeeded. Real flight trials will expose further defects to repair.
