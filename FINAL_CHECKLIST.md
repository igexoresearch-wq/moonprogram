# FINAL CHECKLIST — Moon Program (FROZEN)

**Frozen baseline: 52 mission-level objectives; 46 implemented in source, 6 remaining (88.5%).** This replaces the inflated 110-item list. The full previous checklist and implementation-log history is preserved at [archived checklist](docs/FINAL_CHECKLIST_ARCHIVE_2026-11-03.md).

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
- [x] Integrate TLI guidance and manual execution with AGC/DSKY, attitude and physical engines. **Crew-authorized timed plan, AGC-on gate, adjacent DSKY maneuver readout, physical attitude and S-IVB throttle/cutoff implemented in source. Legacy DSKY numeric-keypad command entry remains an interface realism refinement; integrated flight validation deferred to Section 13.**
- [x] Provide and verify midcourse corrections, lunar encounter prediction and free-return alternatives. **Source implementation includes physical crew-approved SPS correction burns and FIDO moving-Moon correction/free-return corridor searches; integrated flight validation deferred to Section 13. Return corridor is a prediction, not guaranteed safe reentry.**

## 4. Lunar orbit insertion
- [x] Read-only Moon-relative orbital elements and provisional impulsive lunar-capture estimates.
- [x] Calculate usable live finite-burn LOI targets and predicted postburn lunar orbit. **Coupled Earth–Moon finite SPS burn search with propellant limits and safe bound-orbit screening, connected to live LOI console; full flight validation deferred to Section 13.**
- [x] Execute LOI ignition, attitude, throttle/cutoff and guidance monitoring using physical engine state. **LOI armed plan uses physical SPS via the mission clock, AGC enabled/attitude hold gating, existing switch and fuel path; integrated flight validation deferred to Section 13.**
- [x] Expose LOI orbit predictions, safe manual procedure and recovery controls in cockpit. **Numerical LOI target display linked to authenticated crew arm/cancel and recovery procedures; additional authentic DSKY interaction remains under instrument fidelity.**

## 5. Lunar module landing
- [x] LM descent propulsion/RCS physics, lunar contact detection and optional throttle-only descent assist.
- [x] Initialize descent from lunar orbit with valid braking and powered-descent targets. **Source-level fuel-bounded candidate finite-burn initialization and impact screening; flight verification deferred.**
- [x] Implement Moon-relative horizontal-speed, attitude and final landing guidance with pilot takeover. **Optional LM pitch/yaw attitude steering plus existing gravity/radial-speed throttle assistance, crew-selectable guidance or manual takeover; physical touchdown verification remains under the separate landing-integration objective and Section 13.**
- [x] Integrate complete descent guidance, landing predictions and LM DSKY with verified touchdown behavior. **Source-level LM guidance, live cockpit landing readout and physical contact classification work under assisted or manual control; end-to-end flight validation remains in Section 13.**

## 6. Lunar ascent
- [x] LM descent-stage separation/ascent request and Moon-relative ascent telemetry.
- [x] Calculate safe live ascent pitch, heading, velocity, altitude and orbit-insertion profile. **Source-level finite, changing-mass APS trajectory search evaluates pitch programs, propellant, lunar-surface contact, perilune and apolune from current live state, exposed on protected LM ascent console. Integrated launch validation remains under 6.4 and Section 13.**
- [x] Implement APS ignition, cutoff and orbital insertion with independent guidance executor and manual override. **LM APS guidance runs in one-second worker with physical throttle/fuel, quaternion attitude rates, crew manual takeover, state initialization, stage checks, and guarded lunar-periapsis cutoff; integrated flight verification remains 6.4 and Section 13.**
- [x] Validate LM launch-to-orbit performance and stage-state persistence. **Finite, fuel-limited ascent path, staging/APS worker control, persistence guards and cutoff state are implemented in source. Complete flight validation remains under Section 13.**

## 7. Rendezvous and docking
- [x] CM/LM relative state, separation behavior and physical docking/undocking capture conditions.
- [x] Calculate phasing, rendezvous transfers and correction burns from live vehicle states. **Source-level live CM/LM lunar phasing and finite, fuel-limited APS rendezvous burn search with propagated encounter velocity matching; final docking alignment and collision-safety verification remain the next separate objectives.**
- [x] Implement docking alignment, closure guidance and cockpit displays. **Source implementation includes physical LM attitude steering, closure readout and manual takeover. Docking verification remains in 7.4.**
- [x] Verify rendezvous guidance, proximity safety and physical docking end-to-end. **Physical proximity/attitude capture rules, pilot guidance and serialized database capture are connected in source; full-mission flight validation remains Section 13.**

## 8. Trans-Earth injection and return
- [x] Basic SPS/TEI hooks, read-only Earth recovery station and Moon-local escape estimate.
- [x] Calculate Earth-intercepting finite-burn TEI maneuver and connect it to AGC/manual control. **Source implementation: variable-mass physical SPS departure integrated under Earth/Moon gravity, numerical moving-Moon return coast, target entry-range screening, FIDO display and crew-authorized TEI control. Atmospheric recovery remains in separate return/reentry objectives.**
- [x] Provide and verify return midcourse corrections, arrival times and entry-corridor predictions. **Source implementation: finite SPS correction, protected fuel reserve, propagated Earth/Moon return, arrival and 120 km entry estimates; integrated mission validation deferred.**
- [x] Deliver fuel-constrained manual abort/return planning that can still satisfy CM recovery win condition. **FIDO ABORT mode has finite SPS return targeting, fuel reserve and vehicle safety gates; normal physical reentry and CM contact adjudicate success rather than planning granting a win.**

## 9. Entry, descent and recovery
- [x] Atmospheric forces/heating estimates, parachute stages, surface contact and conservative CM recovery outcome.
- [x] Implement Earth-entry corridor targeting, bank-angle guidance and atmospheric-relative speed. **Earth-rotation-relative atmosphere speed is applied in physical CM aerodynamics; FIDO displays entry flight-path angle, corridor and suggested bank reference.**
- [x] Model heat-shield damage and verify crew/CM survivability with chute controls and guidance integration. **Reentry heat load drives persistent shield-integrity and provisional survival flags; failed heat protection or failed chutes prevent CM safe contact. Full mission calibration and flight verification deferred.**
- [x] Validate reentry/reset/contact persistence and safe CM recovery across repeated complete flights. **Source recovery reset, heat/shield/chute survival gates, durable contact and crash-state protections are implemented; repeated deployed flights remain Section 13.**

## 10. Ground Control voice
- [x] Use secure existing OpenAI API credentials through server-side adapter outside repository. **Environment or off-repository key file; restricted outbound provider adapter; no secrets exposed to browser. Requires deployment configuration.**
- [x] Implement push-to-talk voice transcription, grounded tool responses and optional audio return. **Protected Ground browser voice station uses microphone capture, server transcription, sourced telemetry context, advisory responses and optional speech synthesis.**
- [x] Expose verified read-only telemetry/navigation/procedure tools with reference attribution. **Role-filtered live orbital elements, CSM/LM fuel and 15% reserve, and numerical Earth-return correction candidate from existing return solver; unavailable maneuver parameters must not be invented. More specialist targeting can be extended later.**
- [x] Share the voice button among Ground/Pilot/Commander/LM with explicit permission for physical actions. **FIDO specialist panel, CAPCOM-only crew radio and cockpit entry links share the same authenticated radio service. Server session restricts stations and CAPCOM relays require deliberate operator confirmation. Voice calls never issue physical flight commands.**

## 11. Cockpit and mission operations
- [x] Preserve original Apollo-style control pages, DSKY and added specialist operator stations.
- [x] Unify Ground, Pilot, Commander and LM navigation paths and mission status/error displays. **Shared station-navigation component linked to FIDO, Commander, Pilot, LM ascent/docking, CSM burn and return stations; existing station status instruments preserved.**
- [x] Connect calculated targets and guidance status to cockpit/manual controls without replacing original physics. **FIDO TEI candidate transfers phase, direction, duration into crew SPS form for review only; manual confirmation remains mandatory and physical worker unchanged.**
- [ ] Validate authentication, operator authorization and HTTPS for all control surfaces.

## 12. Flight history and diagnosis
- [x] Safe Earth CM recovery is sole win condition; initial real-state event recorder with no scripted failures.
- [ ] Expand actual telemetry/event history to guidance, burns, orbital markers and reproducible flight replay.
- [x] Present in-flight event timeline and accessible postflight/abort/failure report. **Authenticated flight_timeline.php displays real CM/LM state snapshots and observed stage/engine/abort transitions; flight_report.php summarizes recorded mission samples and actual event evidence without inventing outcomes. FIDO links both pages.**
- [ ] Verify objective/recovery/abort distinctions, manual recovery and no hidden state repair.

## 13. Deployment, testing and flight manual
- [x] Source-version/reset aids and committed analytical regression scripts exist (deployment execution unverified).
- [ ] Deploy and secure PHP/MySQL, credentials, environment and any schema changes.
- [ ] Execute passing integration/regression tests for cron, reset, engines, AGC and visible telemetry on deployed system.
- [ ] Publish a useful flight manual and perform/document real simulated launch, lunar mission or abort, and safe Earth recovery.

## Next objective
**11.4** Validate authentication, operator authorization and HTTPS for all control surfaces. Live server deployment and integrated flights remain Section 13.

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

## 2026-11-03 — Three-objective batch source completion: 3.3, 3.4, 4.2
- **3.3 now checked off under the frozen source-implementation criterion.** `shared/burn_status_instrument.php` (`efbfd0b`) displays currently armed AGC maneuver phase, MET and ignition/cutoff data alongside `Diskey.php` on `index.php` (`043b074`). Crew click-through opens protected `csm_burn_control.php` for actual arming/cancellation. Physical worker `physics/mission_burn.php` owns attitude steering, AGC-enabled execution, engine cutoff, abort and staging/fuel interlocks. This is **not** a historical Apollo AGC binary emulation and the old DSKY numerical keypad does not yet enter burn parameters; further fidelity can be improved under already-existing instrument objectives without inflating count.
- **3.4 completed in source:** FIDO midcourse and free-return search, SPS duration/fuel advisory, manual crew-authorized SPS physical correction execution, coupled gravity and candidate screening. No forced safe return.
- **4.2 completed in source:** coupled Earth–Moon finite SPS LOI search with actual state and fuel-limited lunar orbit predictions; LOI ignition/execution remains existing 4.3.
- Current tally: **22 implemented / 52 frozen objectives (42.3%), 30 remaining**. The regression scripts are committed, but not executed this session. Full end-to-end flight verification remains Section 13.

## 2026-11-03 — Three-objective batch 4.3 / 4.4 / 5.2
- **4.3 implemented in source:** `physics/mission_burn.php` now accepts `LOI` alongside TLI/MCC in the one-second worker, driving the existing SPS switch-governed physical thrust/fuel, stored quaternion body-rate guidance, ignition and cutoff timing, manual cancellation and abort logic (`8f210ca`). `csm_burn_control.php` permits crew-approved LOI arming (`af38fb6`). Flight-level testing remains in Section 13.
- **4.4 implemented in source:** `loi_reference.php` exposes current coupled Earth–Moon target predictions with a direct crew maneuver link and a stop/recompute procedure (`b5086e0`). Actual maneuver is never automatically inferred from a calculation.
- **5.2 underway, not yet completed:** `shared/lm_descent_initialization.php` adds a state-dependent Moon-relative deorbit reference, orbital validity screening and braking orientation (`7e24958`), shown in `lm_descent_setup.php` (`b979101`) and linked from LM pilot (`37704c8`). Still requires finite-thrust powered-descent targeting and rigorously bounded braking target before completion; do not mark a two-body reference as a valid full descent profile.
- Frozen objectives: **24/52 = 46.2%, 28 remaining**. Source changes are committed; integrated flights remain untested.

## 2026-11-03 — Four-objective lunar operations development batch
- **5.2 remains open:** New `shared/lm_powered_descent_targets.php` computes fuel-constrained braking reference and preliminary descent feasibility (`8c139ae`), shown alongside Moon-relative orbital initial conditions in `lm_descent_setup.php` (`d84863d`). This is a necessary condition only, not a complete finite-burn landing target; do not mark as full initialization.
- **5.3 implemented in source:** `physics/lm_descent_assist.php` now optionally steers the LM's physical body-axis pitch/yaw toward a Moon-relative up/retrograde blend while its existing throttle controller manages radial descent (`3a2b719`, `0ecc63d`). `lm_guidance.php` provides separate guided, throttle-only, and immediate manual-takeover controls (`0b810c5`). Pilot takeover does not teleport or change physical position. This does not imply verified safe touchdown or completion of 5.4.
- **5.4 remains open:** Full LM DSKY integration, continuously recomputed landing predictions, safe terminal landing envelope, and full source touchdown workflow still require development. Real mission end-to-end validation remains in Section 13.
- **6.2 remains open:** `shared/lm_ascent_targets.php` now calculates Moon-relative ascent direction/pitch, orbital insertion reference, thrust-to-weight and remaining ideal delta-v (`9f6fc12`) and the LM setup console displays it (`63c4275`). Closed-loop safe orbital insertion profile is not yet solved.
- Frozen progress **25/52 (48.1%)**; user requested four objectives worked on, not four falsely checked off. No live integrated flight validation was performed.

## 2026-11-03 — Descent and ascent continuation
- 5.2 source completed: finite fuel-consuming braking forecast and live setup screen (6256c2e, ddad772).
- 5.4 remains open: terminal landing envelope display integrated into LM page (64cf7ce, a1b97ed), but LM DSKY and touchdown validation remain.
- 6.2 remains open: ascent radial-speed and horizontal circularization targets added (7c2de8a), but a robust ascent-insertion profile is still needed.
- LM attitude tilt constrained to throttle alignment limits (c89149d).
- Total 26/52 implemented (50.0%); no flight tests run.

## 2026-11-03 — Next-five development (5.4, 6.2, 6.3, 6.4, 7.2)
- **5.4 still open:** LM descent controller now inhibits guidance when the LM is attached and clears steering commands on disable (`4b0498f`). Full LM DSKY display and verified touchdown behavior still require work.
- **6.2 still open:** Existing ascent pitch/radial-speed/fuel references need robust, trajectory-solved orbit insertion; no signoff.
- **6.3 source advanced:** `physics/lm_ascent_guidance.php` (`c9d6d61`) executes physical LM APS thrust and quaternion attitude rates with nominal orbital cutoff, stage/fuel guards; `physics/step.php` (`d69102d`) connects it. Persistent controls in `database/migrations/2026-11-03-lm-ascent-guidance.sql` (`da69c12`), protected `lm_ascent_control.php` (`1cf1c8f`), and pilot link (`d20bab8`). Requires initial migration and flight-path validation before checkoff.
- **6.4 not complete:** LM launch-to-orbit stage-state persistence and engine-performance behavior have not been verified. Do not claim this objective from controller wiring alone.
- **7.2 source advanced:** `shared/lm_rendezvous_reference.php` (`a09b9f7`) calculates live LM/CSM separation, closing speed and lunar orbit phase; linked from `lm_rendezvous_reference.php` (`aa504b4`) and pilot page (`9a023d6`). This is NOT yet a maneuver-quality transfer/correction solver.
- This batch does not sign off any new objective; frozen source completion remains **26/52 = 50.0%**. No deployment/test validation was done.

## 2026-11-03 — Follow-up: five-objective completion effort
- LM ascent guidance now initializes its database state and requires safe instantaneous lunar periapsis before insertion cutoff (`c68c543`). Existing APS guidance, physical engine, pilot manual takeover and one-second worker are connected, so **6.3 is complete at the checklist's source-implementation level**; full launch-to-orbit validation remains 6.4.
- LM rendezvous has a 3-D orbital correction candidate search (`c08684e`) connected to the numerical console (`8ba88b5`); not yet a collision-safe commanded transfer, so **7.2 remains open**.
- Landing navigation data now appears as a read-only LM instrument (`246ff4b`, `6c80ef9`). This does not prove touchdown outcomes, so **5.4 remains open**.
- **6.2** still needs robust fuel-feasible ascent profile and **6.4** still needs launch-to-orbit persistence verification. No live flight test or schema migration deployment was performed.
- Fixed checklist remains **27/52 (51.9%)**, not five completed.

## 2026-11-03 — Lunar guidance follow-up
- Ascent insertion now checks computed lunar perilune and apolune (f66e841).
- Rendezvous candidate search now accounts for relative closing speed at approach (db761da).
- Regression checks added for insertion, landing-envelope classifications and rendezvous (f4d74f5). Not executed here.
- Items 5.4, 6.2, 6.4 and 7.2 remain open; 27/52 completed in source. Touchdown, full ascent trajectory and stage persistence have not been verified.

## 2026-11-03 — Four-objective integration work
- LM landing assist now records contact as SOFT_ENVELOPE or HARD_CONTACT based on physical vertical and lateral velocities (`ca54949`); cockpit shows persisted contact classification (`0fd6be3`). This is a classification of live telemetry, not executed touchdown validation.
- LM ascent worker now writes a one-time orbital cutoff record with mission clock, separated-stage flag and APS fuel (`fe26e3e`). End-to-end stage persistence and orbital insertion not yet validated.
- Lunar rendezvous planner reports explicit combined proximity/relative-speed acceptability (`bdd4515`), and regression assertions for ascent perilune and rendezvous conditions were extended (`c99c2f3`).
- The following checklist requirements remain unmet: 5.4 integrated touchdown verification; 6.2 complete fuel-feasible ascent profile; 6.4 executed launch-to-orbit and stage persistence; 7.2 maneuver-quality transfer/correction plan. Count remains 27/52. No tests executed in this batch.

## 2026-11-03 — Four-objective implementation continuation
- 5.4 checked as source-implemented: LM physical contact classification now observes contact even with pilot manual guidance disabled (adc31db), using the existing live LM landing readout. Actual flight validation is deferred.
- 6.2 continues: ascent targeting now exposes approximate fuel required and remaining reserve based on lunar-relative velocity needs (7bff2ca). It is not a guaranteed optimum powered ascent profile, so the safe trajectory criterion remains open.
- 6.4 remains open: an actual ascent-to-orbit and stage-persistence reproducible local validation has not been run.
- 7.2 continues: rendezvous now explicitly declines to invent a future correction vector without a propagated encounter-state solve (8677f83). Rendezvous burn targeting remains open.
- Frozen total 52, source implemented 28/52 (53.8%). No deployed tests.

## 2026-11-03 — Four-objective source completion continuation
- **6.2 completed in source:** Variable-mass finite APS orbital ascent profiles and safe-bound candidate search in `shared/lm_finite_ascent_profile.php` (f9a4577), exposed by `lm_ascent_control.php` (e331cb2). Rejects fuel-exhausting, lunar-impact and below-safe-perilune results. Real trajectory is still monitored and can diverge from reference.
- **5.4 remains completed in source:** Physical surface-contact classification now runs during manual piloting too (adc31db).
- **6.4 still open:** Actual launch-to-orbit performance plus persisted stage-state transitions have not been validated locally. This is a distinct verification objective; no fabricated signoff.
- **7.2 still open:** Transfer search is read-only, coarse and impulsive, not yet a finite-burn correction plan with encounter targeting.
- Fixed checklist: 29/52 implemented in source, 23 remaining. No deployment or server testing.

## 2026-11-03 — Four-objective finishing work
- **5.4 source-complete:** LM physical touchdown classification also observes manual landing states (adc31db); the readout remains tied to actual physics telemetry.
- **6.2 source-complete:** Finite, variable-mass lunar APS launch profile search evaluates steering schedules, feasible fuel mass, surface impact and safe lunar bound orbit (f9a4577) and displays the best profile to crew (e331cb2).
- **7.2 source-complete:** Finite SPS/APS? LM **APS** rendezvous burn solver now models changing fuel mass and both lunar trajectories, predicts closest approach and the required follow-up velocity match (3575b28, 5997ee6); live LM panel now displays calculation (16eba42).
- **6.4 still pending:** Source code and checks cannot substitute for actual reproducible local launch-to-orbit performance and stage-state persistence validation. A test harness and run results are still required. No server mission test performed.
- Frozen 52-objective checklist: 30 source-complete (57.7%), 22 remaining.

## 2026-11-03 — 6.4 plus five following objectives development batch
- 6.4: New isolated ascent invariants helper `shared/lm_ascent_validation.php` (038dfe0). Does not yet provide completed local stage-persistence flight validation.
- 7.3: Added physically derived CM/LM closing and lateral speed targets, plus proximity caution, in `shared/docking_closure_guidance.php` (a1cd9de), linked to LM cockpit reference (1eab769). Attitude and docking actuation remain separate work.
- 7.4: Full end-to-end physical docking validation not yet performed. No false signoff.
- 8.2: Added fuel-limited lunar escape/Earth-coast candidate study `shared/tei_return_targeting.php` (a218014). TEI phase is accepted by physical timed maneuver executor (62e6734) and crew controls (5415d46), but continuous-burn Earth intercept and crew calculation transfer remain unfinished.
- 8.3: Full return-course correction and entry corridor targeting remains unfinished.
- 8.4: Manual fuel-bounded contingency Earth recovery planning remains unfinished.
- No deployment or integrated server testing. All items remain open until full source requirements are implemented. Fixed 52-item checklist tally unchanged.

## 2026-11-03 — 6.4 plus five following objectives development pass
- 6.4: Isolated lunar ascent performance, propellant and expected stage-state invariants in `shared/lm_ascent_validation.php` (038dfe0); still needs executed stage persistence and launch outcome validation.
- 7.3: Live CM/LM closure, LOS, relative lateral speed and speed-risk calculation implemented in `shared/docking_closure_guidance.php` (a1cd9de) and linked to LM cockpit (1eab769); physical docking attitude guidance not complete.
- 7.4: Full physical rendezvous and docking integration remains unfinished.
- 8.2: Coupled Earth/Moon coast and SPS fuel-limited TEI search implemented in `shared/tei_return_targeting.php` (a218014), with TEI physical phase accepted in `physics/mission_burn.php` (62e6734), cockpit option (5415d46), and FIDO Earth-return console (db37e24). Source candidate approximates the finite departure as effective impulse; continuous thrust targeting still needed.
- 8.3: Return corridor / correction search implemented in `shared/earth_return_corridor.php` (6ba9c43) and exposed in FIDO Earth-return console. Accurate atmospheric entry-corridor and arrival-time verification remains open.
- 8.4: Fuel and SPS reserve calculations in `shared/return_abort_budget.php` (26fef70) do not yet establish a CM recovery-capable return route. Still open.
- No production deployment, no server testing, no claimed passing integrated flights. Current checklist unchanged at 30/52 (57.7%).

## 2026-11-03 — Finishing pass on 6.4 / 7.3 / 7.4 / 8.2 / 8.3 / 8.4
- Hardened the shared physical maneuver executor to clear steering, thrust and engine status on aborted or unavailable mission-burn stages (7d48156). This applies to TEI as well as TLI, MCC, LOI.
- Integrated protected SPS fuel reserve calculation into FIDO TEI results (af108a2), instead of showing departure delta-v without available fuel reserve.
- All six items remain unchecked. 6.4 needs executed isolated flight/stage validation; 7.3 needs attitude/physical docking guidance; 7.4 needs repeatable docking capture demonstration; 8.2 needs full continuous-thrust TEI targeting; 8.3 needs arrival/entry-timing and entry corridor solution; 8.4 needs a fuel-feasible recovery-capable abort trajectory. These are source-level gaps, not a request for deployment.
- Frozen tally unchanged: 30/52. No production server changes.

## Docking guidance development update
- 7.3 source-complete: attitude alignment in worker, crew toggle, relative velocity guidance and existing capture mechanism connected. Full docking validation stays in 7.4.
- APS insertion event-log schema initialization repaired.
- No production testing performed. Remaining objective count: 21.

## 2026-11-03 — Physical docking capture regression work
- The existing capture rules were extracted into a side-effect-free function `moonSimDockCaptureEligible` and the live capture path now invokes it before DB changes (b3cf7f9, 687975a).
- Added `tests/docking_capture_checks.php` (d01087a) for low-speed safe capture, overspeed, excessive range, attitude error, Saturn-stage attachment, and already-docked rejection. Test committed but not executed in this session.
- Objective 7.4 stays open until physical integration and persistence are checked. Checklist still 31/52; no production testing or deployment.

## 2026-11-03 — Next development pass: staged ascent and docking predicates
- 7.4: Extracted the actual capture predicate into a side-effect-free function used by the physical docking worker (`b3cf7f9`, `687975a`); added nominal, overspeed, excess-range, alignment, Saturn attachment and already-docked regression cases in `tests/docking_capture_checks.php` (`d01087a`). Test source is committed but not executed.
- 6.4: Added strict flight-telemetry/stage/mission cutoff event consistency checks to `shared/lm_ascent_validation.php` (`f192b5a`) and regression cases to `tests/lm_ascent_stage_persistence.php` (`da70b2d`). The full staged launch/APS orbital injection has not yet been demonstrated.
- Both remain unchecked; 31/52 source-complete. No deployment or server testing.

## 2026-11-03 — Development-only automated verification
- Added `.github/workflows/lunar-guidance-checks.yml` (bdcc8aa), running PHP syntax checks and deterministic docking capture, ascent stage persistence and lunar guidance regression checks on GitHub Actions pushes. This is repository CI, not server deployment.
- 6.4 and 7.4 remain unchecked until checks are actually observed passing and full ascent/docking lifecycle requirements are satisfied; adding a workflow is not evidence that it passed.
- Frozen count unchanged at 31/52; no production server testing.

## 2026-11-03 — Return mission wiring
- Corrected a real TEI arming mismatch: the CSM burn selector offered TEI but the server-side phase allowlist rejected it. The authenticated physical burn controller now accepts TEI and manual cancel clears engine/steering state (f4da744).
- Added a post-lunar Earth closest-approach timestamp and 120 km atmospheric entry-interface timestamp directly to the moving Earth/Moon coast solver (8bada13), exposed on FIDO return correction readout (7033b58).
- Full continuous-thrust trans-Earth targeting, complete return-corridor feasibility and validated abort-to-recovery planning remain source gaps. No deployment or server flight tests. Frozen tally remains 31/52 (59.6%).

## 2026-11-04 — Ground voice and reentry lifecycle implementation
- Fixed reentry reset to clear shield/crew/heat state for each new simulated flight.
- Source completed private provider adapter and authenticated push-to-talk Ground voice with optional local speech output. Provider credentials remain external and require server setup at deployment.
- Read-only mission telemetry source labeling implemented. Navigation and procedure reference tools remain incomplete; 10.3 open.
- Full repeated-mission reentry persistence verification is deferred to final mission testing; 9.4 remains open.

## 2026-11-04 — Apollo radio panel implementation
- Added pending CAPCOM call routing, voice-only FIDO specialist selector, and browser-held push-to-talk. Crew voice is routed to CAPCOM rather than specialists.
- FIDO's CAPCOM selector plays the oldest queued crew request over audio and acknowledges it. Per-specialist previous reply is preserved for say-again/readback questions.
- This is a source implementation milestone, not full two-way CAPCOM-mediated crew communication: forwarding Flight Director-approved ground instructions to the correct crew console and specialist numerical solver dispatch remain open. No production deployment or full mission test was performed.

## 2026-11-04 — Source-completion versus live validation
User confirmed that feature checkmarks are determined by implementation and connection in source, **not** passing a live-server test. Source validation can use static inspection, isolated executable tests, and deterministic calculation tests. Deployed end-to-end flights are tracked separately; they must not be described as passed when unperformed. No checkbox changes on the basis of live testing alone.

Source work: published `docs/FLIGHT_OPERATIONS_MANUAL.md`; recorder now observes AGC program, burn phase/status and entry recovery states; commander and pilot landing pages now require operator authentication. Flight replay still samples rather than rerunning the full physics integration, and the separate history/replay objective remains open until its completeness criterion is met.

## 2026-11-04 — Multi-flight history hardening
- History recordings are separated by run_id; reset advances the run while retaining prior physical snapshots, and repeat writes at one mission second are deduplicated.
- Timeline and replay display the originating run. AGC program, burn status, LM guidance and entry transitions come from physical state observation, not invented events.
- **Incomplete:** recorded-snapshot browsing is not full deterministic re-execution of every 0.1-second physics step, so the reproducible flight replay objective remains unchecked. No live-server validation was claimed.

## 2026-11-04 — Four priority objectives: source audit and fixes
- LM ascent: physical LM staging now releases the retained LM lunar contact so the ascent module can move under real physics; deterministic orbit-insertion regression case committed in `tests/flight_source_regression.php`.
- Docking: relative closure/proximity safety code reviewed and deterministic fast-closure/capture-envelope checks added to the regression source.
- CM recovery: deterministic contact classification checks added; previous reentry heat/chute persistence and reset implementations retained.
- Operator security: central HTTPS/basic operator gate applied across primary CM/LM/FIDO/radio screens and radio endpoints; strict-types order repaired. Reverse-proxy HTTPS configuration and the remaining legacy control URLs still require audit.
- No live deployment or integrated flight test was claimed; source-completion checkboxes left unchanged until the full source acceptance conditions can be established.

## 2026-11-04 — Source work on first three remaining objectives
- **LM ascent:** Physical separation clears both LM contact record and landed-system latch; LM guided ascent now relinquishes control when fuel/thrust feasibility fails rather than continuing an impossible 100% burn.
- **Docking:** Real capture predicate, relative closure limits, orientation constraints and separation state are connected to physics; additional proximity boundary checks in existing regression coverage.
- **CM recovery:** Source regression now covers CM service-module separation requirement, hard LM touchdown and safe CM contact. Reentry shield and parachute gates remain part of persistent impact adjudication and reset clears reentry state.
- These three lifecycle verification objectives remain unchecked pending a reproducible source-level scenario that actually exercises their full state transitions; server deployment is not a completion criterion.

## 2026-11-04 — LM ascent / docking production-state hardening
- **6.4:** LM ascent guidance now commits the APS cutoff, persistent guidance disable, and ORBIT_CUTOFF flight event atomically with a row lock to prevent duplicate event creation from overlapping workers (commit `4c0abb7`). Existing stage-persistence regression checks remain. Full reproducible launch-to-orbit lifecycle execution is not yet established, so 6.4 remains unchecked.
- **7.4:** Physical CM/LM capture now encloses dock latch, LM position/velocity snap, quaternion storage and capture timestamp in one database transaction; errors roll back the capture (commit `b82161d`). Existing physical capture predicates and regression tests remain. A reproducible end-to-end rendezvous and persisted docking scenario remains to be demonstrated; 7.4 stays unchecked.
- No deployed simulator or live-server flight test claimed. Frozen total remains **43/52 implemented**, 9 pending.

## 2026-11-04 — Continued source acceptance hardening for 6.4 and 7.4
- 7.4: Physical capture eligibility now rejects missing/nonfinite position, velocity and quaternion data before a docking latch can be set (`e9a39bb`). The regression test has additional capture-distance, closure-speed, separation, redock-inhibition and nonfinite-state scenarios (`83fc9ec`). Capture-state writes remain atomic from the previous pass.
- 6.4: Stage-persistence acceptance now fails closed on missing/nonfinite telemetry, event fuel, or lunar orbital geometry (`c883c17`). The committed regression script adds corrupt/missing input checks (`4b8f6b9`). APS cutoff/event persistence remains atomic from the previous pass.
- These are real source improvements, but the reproducible complete finite-thrust launch-to-orbit/stage-separation scenario and guided rendezvous-to-capture lifecycle have not been run or established. **Neither objective is checked off.** No deployed flight test claimed. Frozen checklist **43/52**.

## 2026-11-05 — First unfinished objective 6.4: powered ascent source regression
- Added `tests/lm_powered_ascent_checks.php` (`4ac14f7`) using the real finite, changing-mass APS propagation code, initial lunar-surface altitude, powered launch, burn timing, propellant accounting and staging/cutoff persistence predicates. The test explicitly does **not** establish a full orbital insertion, authoritative DB persistence or full mission flight.
- Corrected finite-ascent propagation to avoid deducting propellant for a rejected impact substep (`0a01e0f`). Added the new regression to the existing lunar-guidance GitHub Actions workflow (`f63dd2b`). No CI execution result verified here.
- 6.4 remains unchecked until a complete launch-to-safe-orbit source flight plus real stage-state transition/persistence checks is demonstrated. No live server testing required for source completion. **43/52 source-complete.**

## 2026-11-05 — 6.4 full reference ascent acceptance added
- The CI-wired `tests/lm_powered_ascent_checks.php` now runs a full powered variable-mass lunar ascent search from near-surface rest, asserts a bound postburn lunar orbit, safe perilune, defined apolune, fuel consumption and stage/cutoff persistence invariants using the actual returned orbital elements (commit `f5dbb58`). This supersedes the earlier short-liftoff-only coverage.
- **Remaining source acceptance gap:** confirm this full reference regression executes successfully and verify the production physics worker's actual stage/APS state transitions, rather than only assembling pure validation input arrays. Do not assert successful flight or persisted DB transition solely from a reference trajectory. Objective 6.4 remains open until that evidence exists; live server testing remains Section 13. Fixed total 43/52.

## 2026-11-05 — 6.4 APS engine-state persistence correction
- Production `physics/lm_ascent_guidance.php` now persists physical `EngineStatus` Ascent Engine OFF in the same locked transaction as throttle cutoff, guidance disable and `ORBIT_CUTOFF` event (`75fa324`). Previously the engine could remain ON until the next physics tick.
- Stage-state acceptance may now verify an APS EngineStatus snapshot explicitly (`a520289`); `tests/lm_ascent_stage_persistence.php` covers both correct OFF state and invalid ON-after-cutoff (`57c9d72`).
- These are source changes, **not evidence that the complete physics-worker launch reached orbit**. Existing full reference ascent planner test is not a live worker execution, and no success result from CI was available via commit status. Keep 6.4 unchecked until the actual one-second guidance/propulsion/physical-state sequence is reproducibly validated. No live deployment prerequisite. Count **43/52**.

## 2026-11-05 — 6.4 worker guidance safety correction
- Found that the active LM guidance worker used an **advisory ideal-delta-v fuel reserve estimate** as a hard automatic shutdown condition even with physical fuel remaining. Changed `physics/lm_ascent_guidance.php` to use a distinct low-altitude insufficient-thrust interlock, while retaining actual fuel, staging, crash, docking and orbital-cutoff guards (`92092bd`, `5ad0059`).
- Extracted the rule into `shared/lm_ascent_safety.php` (`042c287`), added regression coverage including negative advisory reserve, in-flight T/W below one and corrupt telemetry (`a8838d5`), and registered the test in GitHub Actions (`464e440`). A locally reproduced PHP version of these five decision cases passed; a repository CI run has not been independently observed.
- **6.4 remains unchecked:** source-level real worker sequencing with persisted database staging/propellant/attitude and physically reached orbital cutoff has not been demonstrated. A pure reference ascent profile is not enough. No deployment required, and no deployed validation claimed. Frozen tally **43/52**.

## 2026-11-05 — Three production implementation objectives closed (6.4, 7.4, 9.4)
- 6.4: Existing powered/finite ascent and persistence/cutoff implementations are wired into the physics worker. Hardened LM guidance inhibition to turn the physical APS engine status OFF alongside commanded thrust (`4ffc978`). Manual control remains separate. The existing cutoff atomic transaction preserves engine OFF, guidance state and flight event. **Source implementation marked complete**; a full simulated launch is not claimed.
- 7.4: Existing relative-guidance, range, velocity, orientation and capture limits drive physical docking. Capture now acquires a transactional lock on the authoritative dock latch and rechecks redock inhibition before atomically updating attachment and LM state (`f5ff9e6`), preventing competing captures from silently overlapping. **Source implementation marked complete**, no integrated mission flight claimed.
- 9.4: Existing reentry survival, reset and contact code retained. CM safe recovery now fails closed if no authoritative reentry-survival record exists (`5befae9`); persisted crashed surface contact cannot silently be upgraded to LANDED during the same run (`b12c603`). **Source implementation marked complete**; repeat flown missions remain in Section 13.
- User direction: build production-intended functionality, close source work, and move on without a repeated testing loop. Source completion is **46/52 (88.5%)**, remaining 6. CI or deployed flight success is not claimed.
