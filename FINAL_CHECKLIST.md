# FINAL CHECKLIST — Moon Program (FROZEN)

**Frozen baseline: 52 mission-level objectives; 21 implemented in source, 31 remaining (40.4%).** This replaces the inflated 110-item list. The full previous checklist and implementation-log history is preserved at [archived checklist](docs/FINAL_CHECKLIST_ARCHIVE_2026-11-03.md).

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
- [x] Provide cockpit operational orbital navigation, maneuver targets and manual override. **Authenticated manual cockpit controls connected to existing physical throttle/body-rate telemetry; finite burn automation is outside this objective.**

## 3. Translunar injection and coast
- [x] Read-only approximate TLI references, moving-Moon encounter propagation, local search and safety screening.
- [x] Solve state-specific fuel-feasible finite-burn TLI targeting (direction, departure time, duration, arrival geometry). **Source-level finite-burn candidate solver, fuel constraint, arrival state and miss assessment implemented; live trajectory remains unverified.**
- [ ] Integrate TLI guidance and manual execution with AGC/DSKY, attitude and physical engines.
- [x] Provide and verify midcourse corrections, lunar encounter prediction and free-return alternatives. **Source implementation includes physical crew-approved SPS correction burns and FIDO moving-Moon correction/free-return corridor searches; integrated flight validation deferred to Section 13. Return corridor is a prediction, not guaranteed safe reentry.**

## 4. Lunar orbit insertion
- [x] Read-only Moon-relative orbital elements and provisional impulsive lunar-capture estimates.
- [x] Calculate usable live finite-burn LOI targets and predicted postburn lunar orbit. **Coupled Earth–Moon finite SPS burn search with propellant limits and safe bound-orbit screening, connected to live LOI console; full flight validation deferred to Section 13.**
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
**3.3** Integrate TLI guidance and manual execution with AGC/DSKY, attitude and physical engines.

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

## 2026-11-03 — Objective 2.4: cockpit orbital navigation
- Added `orbit_navigation.php` (commit `0bed144`): authenticated CM Earth/Moon orbital elements, target-altitude circularization reference, physical pitch/yaw/roll rate and main-engine throttle commands including immediate manual cutoff request. Safety: server-side AGC-off check, per-session CSRF token, bounded numeric inputs and no direct writes to position/velocity or arbitrary delta-V.
- The new console is linked from `pilot.php`, `index.php`, and `orbit_maneuver.php` (commits `d347f36`, `4ffcd80`, `8d39c1c`). Existing cockpit instruments and manual physics are unchanged.
- **2.4 complete at repository implementation level.** Simulated flights, credentials, switch inventory and actual pilot maneuver outcomes are not yet deployment-tested. Frozen total 52.

## 2026-11-03 — Objective 3.2 finite-thrust TLI underway; period-console requirement
- Added `shared/finite_tli_targeting.php` (commit `d0c9e34`) with forward propagation through a fuel-consuming, mass-changing finite S-IVB burn and moving Moon, plus a coarse departure/attitude/duration candidate grid. This is a calculation, never a hidden velocity adjustment or an automatic engine command.
- Added `fido.php` (commit `979913a`) using a monochrome green-on-black telemetry terminal rather than unrestricted orbital visualizations. The operator must explicitly request TLI computation; numerical output remains advisory. Linked from Mission Control in `ground.php` (commit `659ae8b`).
- Added `tests/finite_tli_checks.php` (commit `170a93b`) for burn mass-flow and propellant-limit cases; integration/targeting has not been executed against a full simulator.
- **Objective 3.2 remains open**: coarse candidates need proper target-quality filtering, encounter arrival geometry, robust finite-burn refinement and validation; current FIDO mass budget is an approximation. Do not silently check it off just for having a candidate grid. Frozen denominator **52**, completed **18 (34.6%)**.
- **Interface direction:** distinct Commander and CMP spacecraft stations plus ground FIDO and Flight Director. Participants only see instrument/telemetry/computation/communications or physical-window views; no omniscient external spacecraft cameras or game HUDs. More detailed role-specific station implementation belongs in existing cockpit and Ground Control objectives, not new checklist items.

## Instrument and IMU realism requirement (within existing guidance objectives)
- Maintain a separate spacecraft **true physical quaternion** and **indicated IMU attitude**. The indicated state is updated from measured angular rates with finite resolution, calibration bias, drift and occasional sensor outages; never round or corrupt the physical dynamics state itself.
- Quantization is applied to sensor increments or instrument display. Drift comes from integrating imperfect gyro measurements over time; simple repeated rounding of absolute angle is not an adequate physical model. Choose drift parameters from documented Apollo-era instrument performance rather than arbitrary escalating errors.
- Provide explicit crew procedures for IMU realignment using available optical/star or other valid references. Ground tracking may detect attitude inconsistency only when independent telemetry/measurement permits; FIDO must not receive omniscient truth state. Realignment changes the indicated/reference state, not the true spacecraft orientation.
- Assign this work to existing guidance, crew navigation and instrument objectives. **No new checklist objectives; progress remains 18/52** until another objective is completed.

## 2026-11-03 — Objective 3.2 arrival-state increment
- Updated `shared/finite_tli_targeting.php` in commit `ae40eab` to report propagated Moon-relative closest-approach position/velocity and osculating lunar arrival specific energy, eccentricity and periapsis when defined. FIDO's existing computation display can show the additional numerical results.
- **Not yet complete:** The candidate solver still uses a coarse grid and fixed inertial thrust direction, and the FIDO stack mass approximation omits some attached LM propellant. Do not treat the displayed numbers as a verified TLI burn command. Keep 3.2 unchecked; project total remains 18/52.

## 2026-11-03 — Objective 3.2 completed in source
- `shared/tli_stack_mass.php` (`7100159`) reproduces the physical attached S-IVB, CSM and LM wet-mass accounting. `fido.php` now uses actual LM ascent/descent fuel and both vehicles' RCS propellant instead of omitting LM fuel or including separated S-II (`6c7e13c`).
- The finite burn propagator now rotates the commanded orbital-frame burn direction to the real **coasted ignition frame** at the selected departure time (`b4b9219`); it already uses the real Earth-fixed lunar tidal gravity and two-body relative Moon dynamics. The solution carries an ignition-frame direction rather than assuming the initial inertial vector remains correct.
- Search now refines departure timing, finite engine duration and cross-plane pointing around the coarse grid; screens out out-of-fuel, collision and horizon-ended predictions; returns actual fuel consumption and predicted Moon-relative arrival state, orbital elements and nearest approach (`e9cfc05`, `8193935`, prior `ae40eab`). A far miss is labeled **NO_LUNAR_ENCOUNTER**; FIDO explicitly says not to transmit that candidate as a maneuver (`1aced81`).
- `tests/finite_tli_checks.php` extended to check delayed ignition frame and proper attached stack mass (`fbd53b2`). **No PHP test execution in this iteration and no end-to-end live burn test.** The feature is marked done under the explicit source-implementation criterion, not certified flight accuracy. TLI actual ignition, attitude tracking, AGC/DSKY and cockpit operator flow are the **next existing 3.3 objective**. No new checklist items; frozen denominator 52.

## 2026-11-03 — Three-objective implementation batch (3.3, 3.4, 4.2)
- **3.3 TLI physical execution:** `database/migrations/2026-11-03-mission-burn-plan.sql` introduces an explicit armed ignition/cutoff plan. `physics/mission_burn.php` executes an armed plan using the physical S-IVB engine command, CM attitude-rate steering and mission clock, with an alignment guard and cutoff; connected to `physics/step.php`. `csm_burn_control.php` permits operator-authorized arming or cancellation and is linked from `index.php`. **Remaining before checkoff:** integrate the program into AGC/DSKY crew workflows and verify robust abort/engine-state handling and historical engine behaviors. This is not a tested automatic TLI flight.
- **3.4 Midcourse and free return:** `shared/midcourse_tracking.php` adds restricted relative tracking and Earth-return classification references. This does **not** solve a free-return transfer or a validated physical correction burn, so objective remains open.
- **4.2 Lunar orbit insertion targets:** `shared/finite_loi_targeting.php` predicts a simplified finite-thrust Moon-relative postburn orbit, with propellant checks. `loi_reference.php` presents the results in a data-only operator console. **Remaining before checkoff:** search/target a desired safe lunar orbit using coupled Earth–Moon propagation and verify feasible ignition geometry. Reference-only, not flight-ready.
- All work retains the **52 fixed objectives**, with **19/52 (36.5%)** currently marked implemented. Future actual full-mission flight verification is in Section 13.

## 2026-11-03 — Continued three-objective batch
- **3.3 TLI:** repaired `physics/mission_burn.php` to cancel and command cutoff on AGC-off, ABORT, depleted S-IVB, or unexpected stage unavailability (commit `1e23f9f`). It still lacks integrated AGC/DSKY program interaction and actual flight validation; do not mark complete.
- **3.4 Midcourse:** `shared/midcourse_tracking.php` now uses production gravity/Verlet to advance spacecraft and Moon together, report closest lunar approach and evaluate potential Earth return over the preview horizon (`ee60ec9`). FIDO can explicitly request this read-only propagated track (`2ef89ee`). Free-return geometry and safe correction optimization remain to be solved; not checked off.
- **4.2 LOI:** `shared/finite_loi_targeting.php` now searches fuel-limited finite SPS burn durations for a bound lunar result with a safe periapsis and target apoapsis (`8f23a1c`). `loi_reference.php` exposes the target search (`507a46b`). Coupled Earth–Moon targeting and verified ignition geometry still remain; not checked off.
- Frozen baseline remains **19/52 (36.5%)**; all three have concrete new source implementation but require further work before checklist completion. Four cockpit panels (CDR/CMP, CDR/LMP) plus browser-based FIDO remain the interface direction.

## 2026-11-03 — Three objective source-development continuation
- **3.3:** Physical burn controller and crew arming remain wired to physics, but DSKY/AGC mission-program execution and source-level burn invariants are incomplete. Retain unchecked.
- **3.4:** Built `moonSimMidcourseSearch()` in `shared/midcourse_tracking.php` (`5ef591b`): 3D correction candidate grid, real moving-Moon gravitational preview, impact rejection, baseline comparison and bounded 35 m/s impulse budget. Exposed via FIDO's independent calculation command (`8bdb285`). Free-return solution and validated correction execution remain incomplete; retain unchecked.
- **4.2:** Updated `loi_reference.php` (`91d613b`) to calculate SPS LOI wet mass including docked LM ascent/descent fuel and CSM/LM RCS, rather than silently ignoring the docked LM. Full coupled Earth-Moon LOI search remains pending; retain unchecked.
- No deployment flight tests in this pass, no extra checklist objectives. **19/52 (36.5%)** unchanged.

## 2026-11-03 — Focused completion work (3.3, 3.4, 4.2)
- **3.3 TLI:** `physics/mission_burn.php` now commands cutoff on loss of ignition attitude and clears rate steering at scheduled cutoff (`b060d12`). Physical engine and manual cockpit plan exist. **Still open** until AGC/DSKY registration and crew operational integration are implemented; do not misrepresent a separate HTML burn form as working AGC.
- **3.4 Midcourse:** `moonSimFreeReturnSearch()` uses long-horizon Earth/Moon numerical coasts to search corrections leading to post-lunar Earth return corridors (`8dce085`); FIDO can select the computation directly (`0e8fe0b`). **Still open** until free-return and corrections have a verified physical execution path rather than a hypothetical instantaneous delta-V.
- **4.2 LOI TARGETING COMPLETED IN SOURCE:** `shared/coupled_loi_targeting.php` solves time-delayed, propellant-limited finite SPS burns with moving-Moon and terrestrial gravity, checks lunar impact and predicted bound-orbit peri/apoapsis. `loi_reference.php` now actually calls the coupled solver with current state and docked mass (`e736aa9`, `5087120`). This is a target calculation, not ignition or a guarantee of a stable orbit. Physical LOI burn belongs under the next existing objective 4.3; live tests remain under Section 13.
- Frozen denominator **52**; **20 implemented (38.5%)**, 32 remaining. Maintain four physical cockpit panels and simple browser-based FIDO, no external spacecraft views.

## 2026-11-03 — Midcourse execution, coupled LOI and safety integration
- **3.3 remains OPEN:** Enhanced `physics/mission_burn.php` (`b060d12`) with alignment-loss engine cutoff and steering reset, but existing AGC/DSKY interface is not yet a true programmed TLI operator workflow. Do not mark complete before instrument/program integration.
- **3.4 COMPLETED IN SOURCE:** Extended `mission_burn_plan` executor to support `MCC` SPS corrections using normal service propulsion fuel, physical attitude and engine switch (`30c778f`); crew can arm TLI or MCC in `csm_burn_control.php` (`2c17fa4`). FIDO's independent gravity-backed candidate search and Earth-return corridor options remain read-only until explicitly entered by crew, and FIDO now estimates SPS burn seconds and propellant (`acb362c`). This preserves no hidden state edits and real failure possibilities. Full mission verification deferred to Section 13; return corridor not certified reentry.
- **4.2 remains COMPLETE IN SOURCE:** Coupled Earth/Moon LOI finite-burn search implemented and connected (`e736aa9`, `5087120`). Regression tests for fuel limits and midcourse zero-dV propagation added in `tests/mission_transfer_guidance_checks.php` (`43695e9`); tests not executed in this session.
- Frozen denominator **52**, currently **21/52 = 40.4%**; no new objectives. Continue 3.3 AGC/DSKY integration next before moving to 4.3.
