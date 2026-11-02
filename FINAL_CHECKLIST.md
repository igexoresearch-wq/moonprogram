# FINAL CHECKLIST — Moon Program

**Authoritative completion list.** Updated 2026-11-01. Earlier WORK_PLAN.md and PROJECT_STATE.md remain historical reference; update this checklist after every implementation pass.

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
- [ ] Complete S-IC/S-II/S-IVB outboard cutoff, separation and ignition sequencing and reference-event handling; avoid forcing physically impossible stage changes.
- [ ] Complete sourced ascent pitch/roll guidance without bypassing physical attitude.
- [ ] Finish reliable guidance-controlled parking-orbit insertion and end-of-launch-program transition.
- [ ] Confirm database-stored AGC launch programs 16–24 are compatible with file-based controller.

## 2. Earth orbit, TLI and lunar transfer
- [x] Velocity-Verlet Earth–Moon gravity and orbital-element helpers.
- [x] S-IVB first-burn cutoff preserving fuel and existing TLI restart support.
- [ ] Calculate state-specific TLI direction, delta-v and burn time with live masses.
- [ ] Connect TLI guidance to existing AGC/DSKY and manual control.
- [ ] Add midcourse-correction calculator and command procedure.
- [ ] Calculate/display lunar intercept, arrival conditions and free-return alternatives.

## 3. Lunar orbit insertion
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
- [ ] Persist genuine mission-event/command/state history for postflight diagnosis.
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
