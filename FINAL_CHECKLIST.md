# FINAL CHECKLIST — Moon Program (FROZEN)

**Frozen baseline: 52 mission-level objectives; 17 implemented in source, 35 remaining (32.7%).** This replaces the inflated 110-item list. The full previous checklist and implementation-log history is preserved at [archived checklist](docs/FINAL_CHECKLIST_ARCHIVE_2026-11-03.md).

## Rules — do not expand scope
- **Fixed denominator: 52.** Do not add, split, or subdivide objectives, or change completion criteria without the user's explicit approval. Bug fixes, tests, safety guards, commit counts, and subtasks belong *inside* an existing objective, not as new checkboxes.
- **One objective at a time.** On each “Next,” continue the first unfinished objective; avoid switching to unrelated work until its source implementation is complete.
- **[x] = implemented and connected to the repository's simulation pathways, ready to attempt in the future integrated simulator.** It does NOT mean bug-free, deployed, or live flight-validated. Deployment and end-to-end flight testing are tracked under the existing Section 13 objective, not a gate for completing every feature. Do not check a merely advisory approximation as full guidance.
- Preserve existing AGC/DSKY, physical motion and manual controls. No scripted/random malfunctions, silent auto-repair, or forced mission success. **The sole win condition is safe Earth landing of the CM**, with lunar objectives optional.
- The source is an evolving simulator, not a flight-validated aerospace navigation package. A completed implementation objective does not imply scientific/operational verification.

## 1. Launch and ascent
- [x] Persistent physics worker, elapsed-time coordination, pad support and physically modeled Saturn V thrust/fuel/staging controls.
- [x] Launch profiles, guarded preliminary engine cutoff events, LES jettison and read-only launch diagnostics.
- [x] Complete and validate automatic stage engine cutoffs, ignition, jettison and physical S-IC/S-II/S-IVB separation against actual engine database labels. **Source implementation complete; live engine inventory and flight validation deferred to Section 13.**
- [x] Complete AGC launch-program compatibility, ascent attitude guidance, parking-orbit insertion and cutoff transition. **Repository-controlled rate guidance and orbital cutoff; integrated-flight verification deferred to Section 13.**

## 2. Orbital physics and Earth parking orbit
- [x] Earth–Moon gravitational propagation and orbital-element calculations are implemented.
- [x] Live read-only orbit elements, circularization references and estimated propulsion budget are available.
- [x] Validate orbital propagation, energy behavior and parking-orbit accuracy with executed reproducible tests. **Production numerical kernels now have committed regression coverage; equivalent local PHP checks passed; deployed flight validation remains in Section 13.**
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

## Next objective
**2.4** Provide cockpit operational orbital navigation, maneuver targets and manual override.

## Archived work and evidence
The previous checklist contains 110 finer-grained entries and implementation logs with commit references. Those changes remain in Git history and the [archived checklist](docs/FINAL_CHECKLIST_ARCHIVE_2026-11-03.md); they were consolidated, not deleted. Previously checked micro-tasks are not automatically equivalent to these larger objectives.

## 2026-11-03 — launch objectives 1.3 and 1.4 in progress
- Added a read-only Program 23 launch-hook audit and AGC program inventory warnings to `launch_diagnostics.php` (commit `4decdc4`).
- Added a stage engine-group and P16–P20 ignition/separation program presence matrix (commit `3f942c4`).
- **Neither objective is complete.** Database AGC program bodies and actual deployed EngineStatus rows have not been examined or flight-tested. These static checks do not establish valid stage sequencing, attitude guidance, or parking-orbit insertion. No new objectives were added; the denominator stays fixed at 52.

## 2026-11-03 — direct staging fixes; objectives 1.3 and 1.4 remain open
- `physics/lseq.php`: on propellant depletion, automatic S-IC/S-II separation requests are inhibited during abort, and the departing stage engine group is commanded OFF first (commit `ef2bcb4`).
- S-II and S-IVB ignition requests now require positive remaining fuel, no abort and launch mode; S-II ignition additionally requires unfinished launch state (commit `6fc779d`).
- **Outstanding for 1.3:** Verify deployed EngineStatus rows and inspect actual P16–P20 stored program bodies; run a complete staged launch against physical engine state. **Outstanding for 1.4:** Verify P23 guidance source, implement/validate ascent attitude targeting and reliable orbital insertion. Source changes have not been tested on the deployed simulator. No objectives marked complete and the frozen total stays 52.

## 2026-11-03 — continued objectives 1.3 / 1.4
- Fixed repeated timed engine-shutdown queries, and ensured fuel-empty fallback cuts off departing S-IC/S-II engines before scheduling their AGC separation programs (commit `7509e28`).
- Disabled automated ascent yaw updates and parking-orbit cutoff during abort so recovery commands are not overridden by launch guidance (commit `8185bd4`).
- **Neither objective fully completed:** actual P16–P24 program contents, engine inventory and successful staged orbit flight cannot be verified from repository-only inspection. No new checkbox added. Denominator fixed at 52.

## 2026-11-03 — Objective 1.3 physical staging implementation
- `physics/step.php` now invokes `physics/saturn_stage_transitions.php` after AGC every physics tick (commit `648277f`).
- The new controller directly handles fuel-empty S-IC/S-II separation with atomically updated stage flag, engine shutdown, throttle reset, staging indicator and cancellation of redundant queued AGC separation commands (commit `4c36d63`). Abort and launch-event interlocks are enforced. No artificial stage impulses or ignition are introduced.
- **Objective 1.3 remains open** until DB program/engine inventory and ignition sequence are aligned and an end-to-end launch is tested. This code was not exercised against the live database; transaction behavior depends on transactional table storage and existing schema. **Fixed total: 52; completed: 14.**

## 2026-11-03 — Objective 1.3: centralized Saturn stage ignition and handoff
- Introduced explicit pure launch transition rules in `shared/saturn_stage_rules.php` (`f06fded`): S-IC/S-II/S-IVB first ignition, fuel-empty S-IC/S-II staging, manual staging, abort inhibition, single-use ignition latches and manual S-IVB separation after parking.
- Replaced stage-worker implementation with database-persistent, transaction-based ignition/separation and ignition latches in `physics/saturn_stage_transitions.php` (`0d6cc0a`); later added explicit AGC-off manual ignition and one-shot S-IVB parking-orbit restart (`6509db5`, `b7245b6`), with SQL failures triggering rollback (`1093735`).
- AGC no longer evaluates physical P16–P20 snippets; P23 guidance is retained (`3df7ee5`). `lseq.php` no longer clears staging requests before the worker consumes them (`8da5637`); `physics/step.php` gates automatic ignition on the existing AGC enable flag (`477b595`).
- Added `tests/saturn_stage_rules_test.php` (`e095750`, `a2f4f0b`), 21 decision scenarios. An equivalent local PHP decision-rule copy passed 21 checks and PHP syntax validation; repository tests and the live DB integration have **not** been run together.
- Added live ignition-latch/stage telemetry to `launch_diagnostics.php` (`3ea7d5f`) and idempotent DB migration `database/migrations/2026-11-03-saturn-stage-runtime.sql` (`ce8e99a`).
- **Objective 1.3 stays unchecked:** the actual deployed EngineStatus engine names, database table compatibility, physically observed staging/ignition sequence and S-IVB restart need to be exercised in a reset launch. Source changes alone do not prove a successful flight. **Objective 1.4 remains untouched and unchecked.** Frozen denominator remains **52**.

## 2026-11-03 — project completion criteria clarified; 1.3 complete in source
- The simulator has **not been deployed**. A complete live flight is a final integrated system test, not a per-objective completion requirement. Unexpected behavior during flight may require crew abort/recovery; real software defects discovered then will be fixed as necessary rather than blocking implementation indefinitely.
- Stage ignition, staging, abort interlocks, stage engine-state changes, runtime latches, S-IVB restart, AGC physical-command handoff, SQL migration and operator diagnostics have been implemented in repository code. Latest fix `7436cfa` restores P16–P20 AGC register countdown while continuing to suppress duplicate physical command evaluation.
- Marked **1.3 implemented** under the corrected repository completion standard. The actual deployment database engine labels, migration application, flight behavior and physical performance are **unverified**; these remain explicitly part of deployment/flight-testing in Section 13. This check is not a claim that a flight has passed. Frozen total remains **52**.

## 2026-11-03 — Objective 1.4: ascent guidance implemented
- Inertial, Earth-relative launch targeting and limited body pitch/yaw rate commands added in `shared/ascent_guidance_math.php` (`74c5ed6`). Guidance uses measured radial/tangential speed and altitude, not direct Euler attitude teleportation.
- Central `physics/ascent_guidance.php` added (`59c50af`): profile-dependent steering, reference center-engine shutdown and LES jettison, state-based first S-IVB cutoff and AGC launch program transition on confirmed bound low-Earth orbit. It leaves the physical stack/staging to the central stage controller.
- The one-second physics worker calls the guidance controller (`4d98ca0`). The AGC keeps existing register/DSKY timers but no longer evaluates stale database P23 guidance (`e028ce1`). Manual control is preserved with AGC off; abort inhibits ascent steering.
- Committed the analytical ascent direction/rate/orbital-cutoff test script `tests/ascent_guidance_math_test.php` (`eafbb5e`). Updated launch diagnostics to acknowledge repository-driven P23 (`e972049`). The regression script has not been run in production, and no integrated flight or deployed orbit validation has yet occurred.
- **1.4 marked implemented in the source, not flight-validated.** Future deployment and flight tests remain under existing Section 13. Denominator stays frozen at **52**.

## 2026-11-03 — Objective 2.3: orbit propagation validated at source level
- Extracted the velocity-Verlet drift/kick arithmetic into `shared/verlet_kernel.php` (`3ea09e8`) and reconnected the real CSM, LM and Moon production integrator to the identical kernels (`88f6cc2`). Moved the existing gravity functions into `shared/nbody_gravity.php` (`8fd606c`, `441b16b`).
- Fixed an identified coordinate-frame error: with Earth fixed, the direct lunar gravitational acceleration on a craft must be reduced by the Moon's acceleration of Earth's origin (the indirect tidal term). The Moon's Earth-relative dynamics now use the sum of Earth and Moon GM (`a48e005`, `f475cb2`).
- Committed `tests/orbital_propagation_checks.php` (`eb76b4b`, `c757f78`) for two-orbit circular energy/angular momentum drift, elliptical turning radii, 24-hour Moon motion, lunar tidal perturbations, and actual parking-orbit criteria. A local PHP 8.4 harness reproducing the production numerical formulas passed the corresponding checks. See `docs/ORBITAL_VALIDATION_2026-11-03.md` (`1ef4cc7`) for values and reproducibility instructions. **The exact committed script has not been run on a deployed server.**
- **2.3 checked under the agreed source-implementation standard**, not as proof of a successful end-to-end flight. Section 13 continues to own deployment and actual mission verification. Frozen checklist total remains **52**.
