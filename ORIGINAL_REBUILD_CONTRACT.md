# Moon Program rebuild contract — original ZIP is the visual baseline

## Non-negotiable
- Preserve original `index.php`, `pilot.php`, `CM/index.php` layout, instruments, and Apollo-style presentation from uploaded original `moon (1).zip`. Do not substitute an unrelated dashboard.
- A crew member never needs to navigate to a different page to perform an in-flight maneuver (TLI, LOI, docking, ascent, re-entry, RCS, guidance). Integrate these controls into the relevant flight or computation console.
- Exactly **two operational workstations per spacecraft**: a manual/flight console, a navigation/computational console, with an exterior window view available within the spacecraft experience. Separate operators may use different computers.
- Ground communications are integrated push-to-talk controls, never a required standalone tab. Crew only contacts CAPCOM. FIDO is data-oriented ground flight dynamics console.
- FIDO may have subordinate computation panels but must not create a maze of required navigation destinations.
- Preserve existing SWC root `db.php` and Back Office 5 authentication integration. No new standalone Moon passwords.
- Use the existing `moonland` DB and non-destructive migrations; no implicit reset.

## Technical baseline findings from original archive
- Original `physicsupdate.php` calls gravitational, air, thrust, fuel, telemetry, and AGC includes in a 60-second loop.
- Original `physics/grav.php` uses a velocity update followed by a position update with differing acceleration factors for CM and LM; this is not a consistent symplectic integrator. `physics/gravity.php` also applies ad hoc near-surface gravity exclusions.
- Original coordinates use a large offset (10,000,000,000 units) in some display/physics calculations, degrading numerical behavior and encouraging cancellation errors.
- Current GitHub already contains `physics/integrator.php`, `shared/verlet_kernel.php`, `shared/nbody_gravity.php`, and `tests/orbit_conservation.php`. Audit and improve these; **do not replace working validated physics merely to claim a rewrite**.
- Original archive includes legacy hard-coded DB credentials in PHP. Never commit or re-upload them; rotate any exposed secrets.

## Deliverable acceptance criteria
1. Compare every original cockpit control and instrument with resulting console and track preserved/restored/replaced functionality.
2. Normal non-thrusting orbits conserve specific energy and angular momentum to defined quantitative tolerances over multiple orbits; test Earth-only and Earth/Moon cases, and verify total linear/angular momentum where bodies mutually gravitate.
3. All participating bodies advance coherently with documented time step, SI units, no large arbitrary coordinate offset, and explicit surface contact handling.
4. CM/CSM, LM ascent+descent, docking/undocking, staging, engine/fuel, and re-entry are stateful simulation operations reachable without leaving their consoles.
5. Cockpit optical view uses computed relative position and attitude of targets (not an omniscient exterior camera); supports docking windows and sight-line geometry.
6. Radio button and instrument readouts operate on the same station without requiring a new browser page.
7. Retain physical Apollo-like control panels and navigation instruments; add minimal necessary controls in period-appropriate areas.
8. Test one full flight from launch through Earth return; record known limitations and do not equate code completeness with a flown mission.

## Work sequence
- First secure and snapshot original ZIP privately (without secrets), compare against current repository. Do not destructively overwrite current work.
- Restore cockpit structure and station routing.
- Audit authoritative physics, numerical invariants, and schema mappings.
- Implement LM ascent/descent and cockpit optical view.
- Unify in-console maneuver and radio actions.
- Integrate SWC auth and Moonland DB and then run end-to-end mission verification.
