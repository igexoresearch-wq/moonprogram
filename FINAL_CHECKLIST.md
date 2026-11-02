# FINAL CHECKLIST — Moon Program

**Authoritative completion list.** Updated 2026-11-02. Earlier WORK_PLAN.md and PROJECT_STATE.md remain historical reference; update this checklist after every implementation pass.

**Completion rule:** [x] means functionality has been committed to the repository, **not** that it passed a real-flight trial. Where only basic functionality exists, check off that basic capability separately and leave full integration unchecked. Never check off uncommitted work. Maintain existing Apollo cockpit, DSKY, physics, manual fallbacks, and state.

**One win condition:** safe Earth recovery of the CM. Lunar mission objectives are optional; aborting and safely returning counts. Never inject scripted/random malfunctions; never reset or secretly repair real implementation defects midflight. Mission trials can reveal and help us diagnose real bugs.

## 1. Saturn V launch and staging
- [x] Central real-time worker and named lock with elapsed-time accounting.
- [x] Central physical liftoff/pad-support behavior, rather than a T+1 kick.
- [x] Saturn engine/thrust/fuel wiring and independent physical staging commands.
- [x] Generic and Apollo 11 mission-profile data and operator selector.
- [x] Profile-specific Earth parking-orbit cutoff targets in physics/lseq.php.
- [x] Implement guarded S-IC center-engine cutoff at profile reference time (only when five distinct engines and a uniquely labeled center engine exist).
- [x] Implement guarded S-II center-engine cutoff at profile reference time (same explicit center-engine requirement).
- [ ] Verify actual deployed EngineStatus center labels and five-engine groups; otherwise guarded cutoffs intentionally do nothing.
- [x] Add guarded Apollo 11 LES jettison at reference time in S-II flight, inhibited during abort.
- [x] Guard Apollo S-IC/S-II outboard cutoff requests with altitude, remaining propellant, stage configuration and abort state.
- [ ] Complete S-IC/S-II/S-IVB outboard cutoff, separation and ignition sequencing and reference-event handling; avoid forcing physically impossible stage changes.
- [ ] Complete sourced ascent pitch/roll guidance without bypassing physical attitude.
- [ ] Finish reliable guidance-controlled parking-orbit insertion and end-of-launch-program transition.
- [x] Provide read-only operator diagnostics for database launch-program presence, AGC registers and engine-label compatibility.
- [ ] Confirm database-stored AGC launch programs 16–24 are compatible with file-based controller.

## 2. Earth orbit, TLI and lunar transfer
- [x] Velocity-Verlet Earth–Moon gravity and orbital-element helpers.
- [x] S-IVB first-burn cutoff preserving fuel and existing TLI restart support.
- [x] Display approximate fuel-limited maneuver budget using live spacecraft state.
- [x] Add read-only Earth-centered translunar minimum-energy reference speed, direction and transfer duration (not lunar encounter targeting).
- [ ] Calculate state-specific TLI direction, delta-v and burn time with live masses.
- [ ] Connect TLI guidance to existing AGC/DSKY and manual control.
- [ ] Add midcourse-correction calculator and command procedure.
- [x] Display approximate Moon position at reference transfer arrival and departure phase geometry (not a lunar intercept solution).
- [x] Add read-only Earth–Moon gravitational propagation of a reference departure burn and display closest approach.
- [x] Add opt-in local TLI candidate search over prograde impulse magnitude and in-plane direction, displaying estimated closest lunar approach.
- [x] Compare timed TLI candidate departures at 0, 1 and 2 hours using propagated preburn coasting (75 impulse candidates total).
- [x] Extend coarse departure-delay candidates from 0–2h to 0–5h (150 samples).
- [x] Reject local departure candidates that hit Earth in the advisory propagation.
- [x] Report lunar near-pass and surface-clearance classification of preview trajectories.
- [x] Compare the selected departure candidate against estimated usable engine propellant (warning only).
- [ ] Calculate/display lunar intercept, arrival conditions and free-return alternatives.

## 3. Lunar orbit insertion
- [x] Add read-only Earth/Moon two-body circularization vector and delta-v advisor, with near-target warning and ideal burn-time helper.
- [ ] Live LOI vector/delta-v/burn-time calculator.
- [ ] Guidance ignition, attitude and cutoff around the Moon.
- [ ] Lunar periapsis/apoapsis and circularization cockpit displays.
- [ ] Manual LOI procedure and recovery fallback.

## 4. LM descent and landing
- [x] Separate LM dynamics, DPS thrust/propellant and RCS physics.
- [x] Moving-Moon swept surface contact and landed/crashed state.
- [x] Optional throttle-only LM descent assist and separate operator page (not autonomous).
- [ ] Orbital descent braking burn and powered-descent initialization.
- [ ] Attitude/retrograde and horizontal-speed guidance using Moon-relative state.
- [ ] Final descent/landing guidance with pilot manual takeover.
- [ ] Complete LM guidance/DSKY interaction and landing predictions.

## 5. Lunar ascent
- [x] Central queue for descent-stage separation and ascent-abort request.
- [x] LM ascent/rendezvous page with live Moon-relative vectors.
- [ ] Compute safe ascent pitch/heading and altitude/velocity profile.
- [ ] Program APS ignition/cutoff and orbital insertion from live state.
- [ ] Implement independent LM guidance executor and manual override.

## 6. Rendezvous and docking
- [x] Dynamic CM/LM separation, relative telemetry and collision-free rendezvous data.
- [x] Docking capture limits for distance, relative velocity and attitude; undock impulse.
- [ ] Rendezvous transfer, phasing and corrective-burn calculators.
- [ ] Docking alignment and closing-rate guidance exposed in cockpit.
- [ ] Integrate navigation/guidance with existing physical docking capture.

## 7. TEI and Earth return
- [x] Existing SPS and some TEI AGC support.
- [x] Read-only Earth recovery station with instantaneous Earth periapsis.
- [ ] Live TEI direction/delta-v/burn-time calculator and AGC integration.
- [ ] Midcourse return corrections and arrival-time/entry-angle predictions.
- [ ] Manual abort/return burn planning with current fuel/mass limits.

## 8. Reentry and recovery
- [x] Central atmospheric drag/lift, heating estimates and parachute phases.
- [x] Earth surface contact and conservative impact-speed evaluation.
- [x] Earth recovery outcome function requiring safe CM touchdown/configuration.
- [ ] Entry corridor aiming and bank-angle guidance.
- [ ] Improve relative-atmosphere speed and heat-shield condition/damage.
- [ ] Connect guidance displays, chute controls, survivability and final outcome end-to-end.
- [ ] Confirm reset, reentry-state and surface-contact persistence across separate flights.

## 9. Ground Control voice assistant
- [ ] Secure server adapter for existing OpenAI API secret outside repository.
- [ ] Microphone input -> transcription -> grounded tool-based response -> optional audio output.
- [ ] Live read-only telemetry, navigation and calculated burn tools.
- [ ] Ground Control procedures/reference knowledge with accurate source attribution.
- [ ] One voice button shared by Ground, Pilot, Commander and LM pages.
- [ ] Explicit permissions for any AI-proposed physical actions (advice by default).

## 10. Cockpit / operator integration
- [x] Existing original Apollo-style CSM/LM/ground pages and AGC/DSKY model retained.
- [x] Separate profile selector, LM descent, ascent/rendezvous and Earth-recovery pages added.
- [ ] Integrate new stations into four coherent Ground/Pilot/Commander/LM navigation paths.
- [ ] Connect maneuver targets and manual override to existing flight displays.
- [ ] Expose mission status, guidance/error telemetry and voice controls consistently.
- [ ] Protect operator actions with authentication and HTTPS deployment configuration.

## 11. Flight history and outcomes
- [x] Primary outcome rule: safe Earth CM landing only; no automatic simulated faults.
- [x] Record actual spacecraft stage, component and engine-state transitions for postflight diagnosis (initial event recorder).\n- [ ] Extend history to guidance commands, thrust maneuvers, orbital markers and complete mission replay.
- [ ] Distinguish full objectives + recovery from abort/recovery and mission loss.
- [ ] Display accessible in-flight event timeline and after-action flight report.
- [ ] Preserve manual controls and existing state during discovered software defects.

## 12. Deployment, operations, flight manual
- [x] Existing reset snapshot, source-version/status page and several regression scripts.
- [ ] Deploy all files and schema changes onto live PHP/MySQL host.
- [ ] Configure MOON_OPERATOR_PASSWORD, HTTPS and private API credentials.
- [ ] Confirm cron worker, reset, AGC programs and visible telemetry on deployed instance.
- [ ] Write comprehensive flight manual (PDF) with station controls, burn procedures, emergency/manual operations.
- [ ] Attempt a real Earth-orbit / Moon / return flight and document actual issues.

## Implementation order (next unchecked work)
1. Saturn launch engine-group cutoffs and staging guidance (without rewriting existing propulsion).
2. TLI and lunar approach calculators; LOI; descent; ascent; rendezvous.
3. TEI, Earth-return targeting and entry guidance.
4. Ground Control voice/tool backend and 4-station UI.
5. Flight logging, manual and first mission attempt.

**Important:** [x] = committed implementation only. No full end-to-end mission has yet been flown successfully.

## 2026-11-01 implementation log
- Checklist created: commit `97fb3c7`.
- Added guarded engine cutoff helper `shared/saturn_engine_groups.php` and connected selected Apollo reference times in `physics/lseq.php`: commits `d9e0553`, `b42c6f1`. Only TIMED_VALIDATION profile; never randomly shuts an ambiguous engine. DB engine names and physical flight remain unverified.

- Apollo profile LES jettison implemented in existing launch controller with abort/stage guards: commit `4bd6204`. Actual deployed flight still unverified.

- Added altitude/fuel/abort-gated Apollo stage outboard cutoff requests, retaining DB AGC separation programs 19/20 and existing depleted-fuel fallback. Commit `9e072b4`. This is not equivalent to completed staging integration or a live flight test.

## 2026-11-02 progress
- Added real state-transition logger `physics/flight_event_log.php`, connected after AGC in `physics/step.php`. Commits `b8ba033`, `e5b7a5a`. This records actual transitions without causing faults. Stage-separation AGC definitions remain to be audited; full launch sequence remains unchecked.

- Added protected, read-only `launch_diagnostics.php` for live AGC program records 16–24, current execute flags/registers, actual engine names/status and center-engine labelling. Commits `300069e`, `ecad45f`. The diagnostics inspect presence only; database program compatibility and flight behavior remain unchecked.

**Checklist progress after this iteration: 29/77 = 37.7% implemented (repository code, not live flight verified).**

## 2026-11-02 maneuver advisor progress
- `shared/orbit_maneuver_planner.php` implements a local circularization direction/delta-v estimate and ideal rocket-equation burn duration; `orbit_maneuver.php` exposes live Earth/Moon and CM/LM advisory telemetry. Commits `82478eb`, `9a7eb62`. It does not calculate LOI capture, rendezvous, TLI, or execute finite burns; those remain unchecked.

**Checklist progress: 30/78 = 38.5% implemented (source committed, not flight verified).**

Fuel budget committed in 9863365 and fc7e948; TLI targeting remains incomplete.

## 2026-11-02 translunar reference
- `shared/orbit_maneuver_planner.php` and `orbit_maneuver.php` expose a state-driven Earth-centered Hohmann reference with a clear warning about unsolved lunar phase, interception and finite burn. Commits `5fefa00`, `b187b67`. Full TLI targeting and guidance remain unchecked.

**Checklist implementation completion: 32/80 = 40.0%.**

## 2026-11-02 lunar ephemeris reference
- Added circular-rate Moon position extrapolation and departure phase reference, then displayed on maneuver advisor; commits `3120fba`, `e5d7526`. This is not reliable lunar intercept guidance and does not actuate the vehicle.

**Current progress: 33/81 = 40.7%.**

## 2026-11-02 encounter preview
- `shared/orbit_maneuver_planner.php` now numerically previews reference-burn closest approach under Earth and Moon gravity. `orbit_maneuver.php` displays distance, encounter time, and relative speed. Commits `a1dfdcc`, `c169baa`. It is an advisory model only, not a validated TLI optimizer, free-return solution, or engine control.

**Current completion: 34/82 = 41.5%.**

## 2026-11-02 local encounter targeting
- Added bounded 25-candidate impulsive departure search in `shared/orbit_maneuver_planner.php`, with opt-in button in `orbit_maneuver.php`; commits `8933c85` and `7b059bb`. Local search does not optimize departure timing, inclination, finite burns, or guarantee lunar intercept. Full TLI remains unchecked.

**Latest checklist completion: 35/83 = 42.2%.**

## 2026-11-02 departure time sampling
- Encounter preview now applies a delayed impulsive burn after coasting to the requested departure time. Local search compares 75 candidates across immediate, +1 h, and +2 h departure windows. UI updated. Commits `9ad2b86`, `47c6848`. Advisory only: no automated ignition, full targeting, or validated live flight.

**Latest checklist completion: 36/84 = 42.9%.**

## 2026-11-02 four-part TLI planner pass
- Extended coarse departure timing, added Earth-impact screening, lunar approach classification, and candidate propellant-warning display. Commits `f11d7fb`, `69ee14d`, `a7e48ca`, `1d52445`. These are advisory heuristics, not a validated TLI solver. 

**Current implementation checklist: 40/88 = 45.5%.**
