# FINAL CHECKLIST — Moon Program (FROZEN)

**Frozen baseline: 52 mission-level objectives; 14 already implemented in source, 38 not yet complete (26.9%).** This replaces the inflated 110-item list. The full previous checklist and implementation-log history is preserved at [archived checklist](docs/FINAL_CHECKLIST_ARCHIVE_2026-11-03.md).

## Rules — do not expand scope
- **Fixed denominator: 52.** Do not add, split, or subdivide objectives, or change completion criteria without the user's explicit approval. Bug fixes, tests, safety guards, commit counts, and subtasks belong *inside* an existing objective, not as new checkboxes.
- **Two objectives at a time.** On each “Next,” select two unchecked objectives (prioritize prerequisite order); finish and verify their implementation as far as tools allow. If either is not completed, leave it unchecked and explain what's missing.
- **[x] = substantial functionality committed in source, not deployment-tested.** Passing tests and live end-to-end operation are distinct and required before declaring the mission operational. Do not mark an objective complete merely because an advisory approximation exists when the objective asks for real guidance.
- Preserve existing AGC/DSKY, physical motion and manual controls. No scripted/random malfunctions, silent auto-repair, or forced mission success. **The sole win condition is safe Earth landing of the CM**, with lunar objectives optional.
- The source is an evolving simulator, not a flight-validated aerospace navigation package. A completed implementation objective does not imply scientific/operational verification.

## 1. Launch and ascent
- [x] Persistent physics worker, elapsed-time coordination, pad support and physically modeled Saturn V thrust/fuel/staging controls.
- [x] Launch profiles, guarded preliminary engine cutoff events, LES jettison and read-only launch diagnostics.
- [ ] Complete and validate automatic stage engine cutoffs, ignition, jettison and physical S-IC/S-II/S-IVB separation against actual engine database labels.
- [ ] Complete AGC launch-program compatibility, ascent attitude guidance, parking-orbit insertion and cutoff transition.

## 2. Orbital physics and Earth parking orbit
- [x] Earth–Moon gravitational propagation and orbital-element calculations are implemented.
- [x] Live read-only orbit elements, circularization references and estimated propulsion budget are available.
- [ ] Validate orbital propagation, energy behavior and parking-orbit accuracy with executed reproducible tests.
- [ ] Provide cockpit operational orbital navigation, maneuver targets and manual override.

## 3. Translunar injection and coast
- [x] Read-only approximate TLI references, moving-Moon encounter propagation, local search and safety screening.
- [ ] Solve state-specific fuel-feasible finite-burn TLI targeting (direction, departure time, duration, arrival geometry).
- [ ] Integrate TLI guidance and manual execution with AGC/DSKY, attitude and physical engines.
- [ ] Provide and verify midcourse corrections, lunar encounter prediction and free-return alternatives.

## 4. Lunar orbit insertion
- [x] Read-only Moon-relative orbital elements and provisional impulsive lunar-capture estimates.
- [ ] Calculate usable live finite-burn LOI targets and predicted postburn lunar orbit.
- [ ] Execute LOI ignition, attitude, throttle/cutoff and guidance monitoring using physical engine state.
- [ ] Expose LOI orbit predictions, safe manual procedure and recovery controls in cockpit.

## 5. Lunar module landing
- [x] LM descent propulsion/RCS physics, lunar contact detection and optional throttle-only descent assist.
- [ ] Initialize descent from lunar orbit with valid braking and powered-descent targets.
- [ ] Implement Moon-relative horizontal-speed, attitude and final landing guidance with pilot takeover.
- [ ] Integrate complete descent guidance, landing predictions and LM DSKY with verified touchdown behavior.

## 6. Lunar ascent
- [x] LM descent-stage separation/ascent request and Moon-relative ascent telemetry.
- [ ] Calculate safe live ascent pitch, heading, velocity, altitude and orbit-insertion profile.
- [ ] Implement APS ignition, cutoff and orbital insertion with independent guidance executor and manual override.
- [ ] Validate LM launch-to-orbit performance and stage-state persistence.

## 7. Rendezvous and docking
- [x] CM/LM relative state, separation behavior and physical docking/undocking capture conditions.
- [ ] Calculate phasing, rendezvous transfers and correction burns from live vehicle states.
- [ ] Implement docking alignment, closure guidance and cockpit displays.
- [ ] Verify rendezvous guidance, proximity safety and physical docking end-to-end.

## 8. Trans-Earth injection and return
- [x] Basic SPS/TEI hooks, read-only Earth recovery station and Moon-local escape estimate.
- [ ] Calculate Earth-intercepting finite-burn TEI maneuver and connect it to AGC/manual control.
- [ ] Provide and verify return midcourse corrections, arrival times and entry-corridor predictions.
- [ ] Deliver fuel-constrained manual abort/return planning that can still satisfy CM recovery win condition.

## 9. Entry, descent and recovery
- [x] Atmospheric forces/heating estimates, parachute stages, surface contact and conservative CM recovery outcome.
- [ ] Implement Earth-entry corridor targeting, bank-angle guidance and atmospheric-relative speed.
- [ ] Model heat-shield damage and verify crew/CM survivability with chute controls and guidance integration.
- [ ] Validate reentry/reset/contact persistence and safe CM recovery across repeated complete flights.

## 10. Ground Control voice
- [ ] Use secure existing OpenAI API credentials through server-side adapter outside repository.
- [ ] Implement push-to-talk voice transcription, grounded tool responses and optional audio return.
- [ ] Expose verified read-only telemetry/navigation/procedure tools with reference attribution.
- [ ] Share the voice button among Ground/Pilot/Commander/LM with explicit permission for physical actions.

## 11. Cockpit and mission operations
- [x] Preserve original Apollo-style control pages, DSKY and added specialist operator stations.
- [ ] Unify Ground, Pilot, Commander and LM navigation paths and mission status/error displays.
- [ ] Connect calculated targets and guidance status to cockpit/manual controls without replacing original physics.
- [ ] Validate authentication, operator authorization and HTTPS for all control surfaces.

## 12. Flight history and diagnosis
- [x] Safe Earth CM recovery is sole win condition; initial real-state event recorder with no scripted failures.
- [ ] Expand actual telemetry/event history to guidance, burns, orbital markers and reproducible flight replay.
- [ ] Present in-flight event timeline and accessible postflight/abort/failure report.
- [ ] Verify objective/recovery/abort distinctions, manual recovery and no hidden state repair.

## 13. Deployment, testing and flight manual
- [x] Source-version/reset aids and committed analytical regression scripts exist (deployment execution unverified).
- [ ] Deploy and secure PHP/MySQL, credentials, environment and any schema changes.
- [ ] Execute passing integration/regression tests for cron, reset, engines, AGC and visible telemetry on deployed system.
- [ ] Publish a useful flight manual and perform/document real simulated launch, lunar mission or abort, and safe Earth recovery.

## Next two objectives (starting point)
1. **1.3** Validate and complete Saturn V engine cutoff/ignition/separation sequencing against the database.
2. **1.4** Complete launch AGC compatibility, attitude guidance and reliable parking-orbit insertion.

## Archived work and evidence
The previous checklist contains 110 finer-grained entries and implementation logs with commit references. Those changes remain in Git history and the [archived checklist](docs/FINAL_CHECKLIST_ARCHIVE_2026-11-03.md); they were consolidated, not deleted. Previously checked micro-tasks are not automatically equivalent to these larger objectives.

## 2026-11-03 — launch objectives 1.3 and 1.4 in progress
- Added a read-only Program 23 launch-hook audit and AGC program inventory warnings to `launch_diagnostics.php` (commit `4decdc4`).
- Added a stage engine-group and P16–P20 ignition/separation program presence matrix (commit `3f942c4`).
- **Neither objective is complete.** Database AGC program bodies and actual deployed EngineStatus rows have not been examined or flight-tested. These static checks do not establish valid stage sequencing, attitude guidance, or parking-orbit insertion. No new objectives were added; the denominator stays fixed at 52.
