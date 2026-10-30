# Moon Simulator — Step-by-Step Implementation Plan

Last updated: 2026-10-29

## How to use this file

This is the authoritative work order for the Moon Simulator.

Before changing simulator code, read these files in this order:

1. `PROJECT_STATE.md` — current project memory and decisions.
2. `SYSTEM_MAP.md` — how the original simulator actually works.
3. `WORK_PLAN.md` — this file; the exact order of implementation.

After every meaningful coding session:
- mark completed steps here,
- record what was tested,
- record any new defect discovered,
- update `PROJECT_STATE.md`,
- do not skip ahead merely because a later feature is more interesting.

The central rule is:

> **Do not replace the simulator. Repair and complete the simulator that already exists.**

The visual spirit, dual-station cockpit concept, DSKY/program behavior, real-time pacing, Saturn/CSM/LM separation, ground-control tools, sounds, and mission flow should be preserved unless a specific part is proven broken.

---

# Target operator experience

The finished simulator should provide four principal operator pages:

1. **GROUND / FLIGHT DATA**
   - dense live telemetry
   - orbit data
   - trajectory predictions
   - burn planning
   - mission events
   - system/engine status
   - Ground Control voice/AI button

2. **PILOT**
   - primary CSM flying view
   - attitude / FDAI
   - translational and rotational controls
   - RCS
   - docking/rendezvous information
   - primary flight indications
   - Ground Control voice/AI button

3. **COMMANDER**
   - DSKY / guidance
   - program registers
   - staging
   - systems
   - engine status
   - emergency controls
   - mission procedures
   - Ground Control voice/AI button

4. **LUNAR MODULE**
   - independent LM flight station
   - LM DSKY/guidance
   - DPS and APS
   - LM RCS
   - landing radar
   - descent/landing data
   - ascent/rendezvous/docking
   - Ground Control voice/AI button

These pages may internally reuse the existing original PHP includes. They should not become a new generic square-panel framework.

---

# Phase 0 — Freeze and baseline the original simulator

Status: AUDIT COMPLETE / BASELINE PRESERVED

- [x] Restore original simulator source as the GitHub baseline.
- [x] Import/study the uploaded `moonland` SQL dump.
- [x] Map the active physics worker.
- [x] Map the CSM cockpit and systems station.
- [x] Map the LM/root pages.
- [x] Map the CM guidance / DSKY system.
- [x] Map ground-control tools.
- [x] Identify active vs dormant/experimental physics files.
- [x] Save findings in `SYSTEM_MAP.md`.
- [x] Save persistent project memory in `PROJECT_STATE.md`.

Before major physics changes:
- [ ] Create a known-good baseline SQL/reset snapshot.
- [ ] Add a small diagnostic/status page showing the currently loaded simulator version/commit.
- [ ] Remove or quarantine the interrupted `.upgrade_payload` directory after confirming nothing useful remains in it.

Acceptance:
- A clean checkout plus the SQL dump can reproduce the pre-repair simulator state.

---

# Phase 1 — Fix broken wiring without redesigning behavior

Goal: make the systems that already exist connect to the correct data.

## LM propulsion

- [ ] Fix the LM EngineStatus lookup mismatch.
  - Current SQL uses `engine_stage='Lunar Module'`.
  - Existing physics looks for `Lunar Module Ascent` and `Lunar Module Descent`.
- [ ] Distinguish DPS vs APS by engine name/component while preserving the existing table if possible.
- [ ] Provide a real active path for LM engine ignition.
- [ ] Provide a real descent-stage jettison / APS activation transition.
- [ ] Remove the accidental 60% LM throttle limitation unless it is deliberately required by an operating mode.
- [ ] Verify descent and ascent fuel are deducted from the correct tanks.

## CSM / Saturn naming

- [ ] Normalize `excape_fuel` / `escape_fuel` without losing existing state.
- [ ] Fix LES thrust to use the LES thrust model rather than first-stage thrust.
- [ ] Normalize engine failure state to the actual SQL values (`OK` / `FAILED`) or migrate the schema deliberately.
- [ ] Correct S-IVB engine naming in indicators/shutdown controls.
- [ ] Remove references to nonexistent `engine_fuel` unless that subsystem is intentionally restored.

## Stale root controls

- [ ] Repair or retire root Launch/Abort code that assumes nonexistent system rows.
- [ ] Repair or retire stale root switch actions with obsolete coordinate/state assumptions.
- [ ] Fix XZ map state so it never overwrites LM RCS propellant.
- [ ] Remove references to nonexistent `eagle` state or implement it deliberately.

Acceptance:
- Existing buttons/switches no longer write to the wrong row/table.
- LM DPS and APS can both be intentionally turned on/off.
- No map/control function modifies propellant by mistake.

---

# Phase 2 — Remove UI pages from the physics control loop

Goal: loading or closing a cockpit page must never change physical simulation state.

Known example:
- `CM/sound.php` currently clears the staging flag after playing staging audio.
- This can make stage sequencing depend on whether that page is open.

Work:
- [ ] Replace UI-cleared event flags with durable physics events.
- [ ] Make sound pages observers only.
- [ ] Make indicators observers only.
- [ ] Make charts observers only.
- [ ] Audit every cockpit include for database writes that should belong in physics/control endpoints.
- [ ] Keep genuine operator switch/button writes, but route them to explicit command/state fields.

Acceptance:
- A mission produces the same trajectory whether zero, one, or all cockpit pages are open.
- Refreshing a display cannot stage, dock, change fuel, or otherwise advance physics.

---

# Phase 3 — Real-world timing and worker locking

The simulator remains real-time. There is no accelerated game clock.

Goal:
- real wall-clock elapsed time determines how much physics time is owed,
- the integrator decides how to subdivide that elapsed time.

Work:
- [ ] Add one authoritative high-resolution `last_physics_at` value.
- [ ] Use database time or an equivalent shared authoritative clock.
- [ ] Add a single-worker lock so overlapping cron launches cannot double-simulate time.
- [ ] Preserve the existing once-per-minute cron + approximately-minute-long worker concept.
- [ ] Measure elapsed time instead of assuming `sleep(1)` means exactly 1.000 seconds.
- [ ] Break elapsed time into small fixed physics substeps.
- [ ] Define a safe catch-up policy after downtime.
- [ ] Keep the mission clock as mission display/procedure time, not as the authoritative numerical integrator clock.

Initial substep target:
- start testing around 0.05–0.10 second physics steps,
- adjust from measured accuracy/performance rather than guessing.

Acceptance:
- overlapping workers do not double-count time.
- a 2.3-second scheduling delay advances approximately 2.3 seconds, not 1 or 3.
- simulation state does not depend on PHP execution speed.

---

# Phase 4 — Establish one authoritative physical-data source

Goal: cockpit, ground control, guidance and physics must not calculate from contradictory Saturn/Apollo constants.

Current problem:
- `physics/constants.php`
- `CM/constants.php`
- `ground_control/constants.php`

contain materially different values.

Work:
- [ ] Create one authoritative physical-data include/table.
- [ ] Validate values against NASA/source documentation.
- [ ] Keep display-unit conversions separate from physical values.
- [ ] Audit:
  - Earth mass/radius
  - Moon mass/radius
  - Saturn stage dry masses
  - Saturn propellant masses
  - F-1 thrust/flow
  - J-2 thrust/flow
  - S-IVB/J-2 values
  - CSM dry mass
  - SM propellant
  - SPS thrust/flow
  - LM ascent/descent dry masses
  - LM ascent/descent propellant
  - DPS/APS thrust and throttle behavior
  - RCS thrust/propellant
  - LES values
- [ ] Fix the current Moon mass factor-of-ten error.

Acceptance:
- physics, cockpit, guidance and ground-control calculations all reference the same physical numbers.

---

# Phase 5 — Translational physics and orbital conservation

This is the phase aimed directly at the old orbit-precession / gradual-energy-loss problem.

## Why the existing orbit drifts

Current code effectively does:
- acceleration from current state,
- velocity += acceleration,
- position += previous velocity + acceleration * tuned factor.

It also assumes exactly one second passed.

That combination can systematically add/remove orbital energy and angular momentum.

## Replacement direction

Preserve:
- current XYZ frame,
- current DB-facing position/velocity fields,
- real-time worker,
- Earth/Moon model.

Replace internally:
- [ ] use a stable symplectic integrator such as velocity-Verlet / leapfrog,
- [ ] compute all gravity from one coherent state snapshot per substep,
- [ ] remove `factorCM` as an orbital-energy correction,
- [ ] use PHP double precision for local relative calculations,
- [ ] avoid unnecessary rounding/formatting inside the physics loop,
- [ ] write full useful precision back to DB.

## Conservation diagnostics

Add a diagnostic module that records, at minimum:

For Earth-only two-body validation:
- [ ] position and velocity magnitude
- [ ] radius
- [ ] specific orbital energy
- [ ] specific angular momentum vector and magnitude
- [ ] eccentricity vector/magnitude
- [ ] semi-major axis
- [ ] periapsis/apoapsis
- [ ] drift from initial energy
- [ ] drift from initial angular momentum

Important:
- in an Earth+Moon perturbed trajectory, spacecraft angular momentum about Earth alone is **not** expected to remain perfectly constant,
- therefore the strict conservation test must first be run with a controlled Earth-only orbit.

Validation scenarios:
- [ ] circular LEO for multiple orbits
- [ ] elliptical Earth orbit
- [ ] translunar coast with Moon enabled
- [ ] lunar orbit
- [ ] free-return-like trajectory

Acceptance target:
- no visible artificial apsidal precession in an isolated spherical Earth two-body test,
- energy/angular-momentum drift is small, bounded, and non-monotonic rather than steadily leaking,
- tighter substeps demonstrably converge toward a stable solution.

---

# Phase 6 — Robust 3D attitude and rotation

Goal: remove the Euler-angle singularity behavior while keeping familiar cockpit pitch/yaw/roll readouts.

Preserve externally:
- pitch
- yaw
- roll
- pitch/yaw/roll rates
- FDAI/8-ball presentation
- manual control concept

Internally:
- [ ] add a robust orientation representation (quaternion or equivalent rotation matrix state),
- [ ] convert cockpit Euler angles to/from internal orientation,
- [ ] remove the +/-90-degree pitch reflection hack,
- [ ] transform engine/RCS thrust from body coordinates to world coordinates using full 3D orientation,
- [ ] integrate angular velocity consistently with dt,
- [ ] introduce moments of inertia / torque where needed,
- [ ] make roll physically meaningful for body-frame controls and lift direction.

Acceptance:
- continuous 360-degree pitch/yaw/roll maneuvers do not flip or discontinuously jump,
- identical control inputs produce predictable rotations regardless of starting attitude,
- the FDAI remains readable using Euler-style display values.

---

# Phase 7 — Saturn V launch and staging

Goal: preserve the original automated launch character, but make the stack physically and procedurally correct.

## Pad / liftoff

Current hack:
- gravity is suppressed at the surface,
- launch sequence gives the rocket a position/velocity kick.

Replace with:
- [ ] explicit pad/support constraint before liftoff,
- [ ] engines build thrust while vehicle remains supported,
- [ ] release when launch conditions/interlocks are satisfied,
- [ ] natural upward acceleration after support release,
- [ ] no artificial initial velocity injection.

## Engine/stage behavior

- [ ] verify five F-1 engine logic.
- [ ] verify five S-II J-2 engines.
- [ ] verify S-IVB single J-2.
- [ ] stage mass drops only on separation.
- [ ] engine shutdown and propellant depletion are separate concepts.
- [ ] realistic ignition/shutdown/separation timing.
- [ ] staging events are physics events, not page flags.
- [ ] LES/abort logic works independently.
- [ ] engine-out behavior affects thrust correctly.
- [ ] failure detection uses valid EngineStatus state.

## Guidance

- [ ] keep automated launch sequencing.
- [ ] replace missing hard-coded Apollo11LaunchTelemetry dependency with mission-profile launch data.
- [ ] verify pitch program against selected mission/profile.
- [ ] validate orbital insertion target.

Acceptance:
- reset -> countdown -> ignition -> liftoff -> S-IC sep -> S-II -> S-II sep -> S-IVB -> parking orbit can run with cockpit pages closed.
- stage mass/fuel/thrust history is internally consistent.

---

# Phase 8 — CSM propulsion, RCS and guidance

## SPS

- [ ] SPS switch/interlock actually gates SPS firing.
- [ ] SPS thrust and mass flow use authoritative constants.
- [ ] burn duration produces expected delta-v within tolerance.
- [ ] docked LM mass is included when appropriate.

## CSM RCS

Replace the current scalar `thrusters` addition to SPS percentage.

- [ ] model translational RCS directions separately.
- [ ] model rotational RCS torque separately.
- [ ] consume RCS propellant.
- [ ] body-axis controls work at arbitrary attitude.
- [ ] RCS does not implicitly fire the SPS.

## Guidance computer

Preserve:
- DSKY
- noun/verb entry
- program register concept
- visible program state

Repair:
- [ ] multiple-execute dispatch behavior
- [ ] undefined variables such as `$differencer`
- [ ] missing launch-profile data
- [ ] switch/program mapping inconsistencies
- [ ] TLI calculations
- [ ] TEI calculations
- [ ] rendezvous calculations
- [ ] reentry targeting

Longer-term maintainability:
- [ ] move program source out of executable DB strings only if it can be done without changing the cockpit/DSKY operator experience.

Acceptance:
- the same DSKY/operator flow can command useful, deterministic burns and guidance modes.

---

# Phase 9 — Make the Lunar Module fully independent

This is not a new spacecraft framework. Finish the LM already represented in `telemetry`.

## Separation / staging

- [ ] LM stays physically attached while appropriate.
- [ ] docking/attachment state determines mass coupling.
- [ ] LM can undock from CSM.
- [ ] descent stage can be jettisoned.
- [ ] ascent stage becomes the active LM vehicle after staging.

## DPS

- [ ] ignition
- [ ] throttle
- [ ] fuel consumption
- [ ] full body-orientation thrust
- [ ] engine availability/failure
- [ ] guidance/manual throttle modes

## APS

- [ ] ignition
- [ ] correct fixed-engine thrust
- [ ] ascent propellant
- [ ] RCS supplies attitude control
- [ ] lunar ascent to rendezvous-capable orbit

## LM RCS

- [ ] remove direct HTTP velocity edits.
- [ ] controls set thruster commands.
- [ ] physics applies body-axis force/torque.
- [ ] consume RCS propellant.
- [ ] translation and rotation can operate simultaneously.

## LM guidance/DSKY

- [ ] preserve the original LM cockpit/DSKY feel.
- [ ] give the LM a real guidance executor rather than a decorative/input-only guidance table.
- [ ] support descent, landing, ascent and rendezvous functions.
- [ ] keep manual control possible.

Acceptance:
- CSM can release LM,
- LM can maneuver independently,
- LM can descend,
- LM can land,
- LM can launch the ascent stage,
- LM can rendezvous and dock again.

---

# Phase 10 — Collision, landing and moving-body surface contact

This directly addresses the old problem where the spacecraft could pass through the Moon.

The Moon is moving. Collision must therefore be based on spacecraft position **relative to the Moon**.

## Continuous collision detection

Do not merely check the end of a long timestep.

For each physics substep:
- [ ] use relative position: spacecraft - body position
- [ ] use relative velocity: spacecraft velocity - body velocity
- [ ] detect whether the path crosses the body radius during the substep
- [ ] solve/interpolate the impact time within that substep
- [ ] place the craft at the actual surface contact point
- [ ] never allow tunneling through the sphere

## Landing vs crash

At contact:
- [ ] decompose relative velocity into surface-normal and tangential components
- [ ] use LM-specific landing limits
- [ ] classify soft landing / hard landing / crash
- [ ] preserve location on the moving Moon after landing
- [ ] stop penetration
- [ ] surface support cancels inward acceleration while landed
- [ ] allow later ascent from landed state

Do the corresponding Earth/splashdown contact handling for the CSM.

Acceptance:
- a high-speed LM hits the lunar surface and crashes at the surface rather than appearing on the other side,
- a soft LM touchdown stays on the Moon while the Moon continues to move,
- a landed LM can later lift off correctly.

---

# Phase 11 — Docking and undocking physics

Current <10 m automatic capture is not sufficient.

Work:
- [ ] calculate relative position and relative velocity in the target craft frame.
- [ ] require docking distance threshold.
- [ ] require safe closure-speed threshold.
- [ ] require acceptable docking-axis alignment.
- [ ] require acceptable relative attitude.
- [ ] create an attached/docked state only after capture criteria pass.
- [ ] when docked, propagate the combined mass appropriately.
- [ ] undocking applies a small physical separation impulse rather than teleporting position.
- [ ] prevent immediate automatic redocking after release.
- [ ] retain rendezvous radar / relative-motion displays.

Acceptance:
- approach too fast -> no clean dock / possible collision.
- proper slow aligned approach -> capture.
- undock -> two independent vehicles with physically continuous velocity.

---

# Phase 12 — Reentry, heating, lift and parachutes

The existing reentry work is valuable but needs to move from approximate display math into a coherent flight model.

## Atmosphere

- [ ] use one consistent atmosphere model.
- [ ] calculate density from altitude.
- [ ] calculate aerodynamic drag from relative atmospheric velocity.
- [ ] introduce a CSM aerodynamic reference area / drag coefficient or ballistic coefficient.
- [ ] include atmospheric rotation later if required for mission accuracy.

## Apollo-style lifting entry

The CM is not purely ballistic.

- [ ] model lift-to-drag behavior at an appropriate simplified fidelity.
- [ ] use roll/bank to rotate the lift vector.
- [ ] make bank angle affect downrange/crossrange/entry path.
- [ ] make guidance able to target an entry corridor.

## Heating

- [ ] replace page-only approximations with one central heating model.
- [ ] accumulate heat load / heat shield condition coherently.
- [ ] warning/display reads the same central values.

## Parachutes

- [ ] separate drogue and main phases if simulator fidelity supports it.
- [ ] deployment conditions depend on altitude/velocity/state.
- [ ] deployment produces aerodynamic force, not an instant arbitrary speed change.
- [ ] unsafe deployment can fail or damage the system.
- [ ] final descent transitions to Earth contact/splashdown.

Acceptance:
- a plausible return trajectory enters, decelerates, heats, deploys chutes and reaches the surface without tunneling or abrupt nonphysical velocity resets.
- a bad entry can skip, burn up, crash or miss the safe corridor.

---

# Phase 13 — Mission selection without constraining the simulator

Goal: missions configure the simulator; they do not become special-case physics code.

A mission profile may contain:
- mission name/number
- launch date/time
- launch-site configuration
- starting Moon state / ephemeris approximation
- launch guidance pitch program
- parking-orbit target
- planned TLI/LOI/TEI references
- landing site
- mission event checklist
- historical target values

Core physics remains common.

Initial profiles can include:
- [ ] generic training mission
- [ ] Apollo-style lunar mission
- [ ] historical Apollo profile(s) after validation

Reset behavior:
- [ ] choose mission
- [ ] reset applies that mission's initial state
- [ ] Moon start state is reset consistently with the mission profile
- [ ] mission is then run in real time

Acceptance:
- adding a mission profile does not require editing gravity/thrust/integrator code.

---

# Phase 14 — Ground Control voice/AI button on all four pages

Requirement:
- one common button appears on Ground, Pilot, Commander and LM pages.
- click/start -> speak.
- click/stop -> recording ends.
- user waits for Ground Control response.

## Security

The simulator will be deployed in a folder under an existing site.

- [ ] never place API keys in this public GitHub repository.
- [ ] load OpenAI credentials from an existing private server-side secrets/config location outside the public simulator folder, or from environment variables.
- [ ] create one small simulator-side config adapter that knows how to read the private secret.
- [ ] fail cleanly if the secret is unavailable.

Do not copy Standard Window Cleaning secrets into the Moon repository.

## Shared cockpit component

Create one shared include/component, conceptually:

```
includes/ground_control_comms.php
```

It should:
- display one COMMS / TALK button,
- record microphone audio in the browser,
- show recording state,
- submit audio to a server-side endpoint,
- show "GROUND WORKING" while calculations are performed,
- display the returned transcript/advice,
- optionally play returned speech later.

All four pages use the same component.

## Ground Control AI architecture

The AI should **not** be the numerical physics engine.

The backend should provide deterministic tools/calculators the AI can call, for example:

- `get_vehicle_state()`
- `get_mission_state()`
- `get_orbit_elements()`
- `get_relative_state(target)`
- `calculate_impulsive_burn(target_orbit)`
- `predict_burn(engine, throttle, duration)`
- `calculate_rendezvous_solution()`
- `calculate_landing_state()`
- `calculate_entry_state()`
- `get_system_status()`
- `get_engine_status()`
- `get_procedure(name)`

The AI's role:
- understand the spoken request,
- decide which deterministic calculator/data source is needed,
- call it,
- explain the result like Ground Control/CAPCOM.

Example:

```
User:
"Ground, give me a burn to raise apogee to 300 kilometers."

AI:
    -> reads current CSM state
    -> calls orbit/burn calculator
    -> receives deterministic solution
    -> reports TIG, attitude/vector, delta-v, burn duration,
       expected post-burn orbit, and cautions
```

The language model must not simply invent burn numbers.

## Knowledge repository

Use two kinds of Ground Control knowledge:

### Structured/live data
Authoritative, calculated from the simulator:
- telemetry
- mission state
- engines/fuel
- orbital elements
- relative motion
- warnings
- guidance state

### Procedures/reference
A small curated simulator knowledge area can contain:
- operator procedures
- control descriptions
- mission checklists
- abort procedures
- DSKY/program reference
- Saturn/CSM/LM reference values

This material eventually becomes the basis of the flight manual too.

## Write permissions

Initial rule:
- Ground Control AI is read-only to physical state.
- It can recommend actions and calculate burns.
- Operator executes the maneuver.

Possible later explicit feature:
- "UPLINK" command package that the operator must approve before guidance state changes.

Acceptance:
- asking identical questions from Pilot or Commander gives answers from the same live mission state.
- burn numbers come from deterministic calculators.
- no API key appears in page source, logs or GitHub.
- the AI cannot directly teleport the vehicle or directly overwrite velocity/fuel.

---

# Phase 15 — Four-page consolidation

Only after core simulation behavior is reliable.

## Ground

Consolidate useful pieces from:
- ground.php
- ground_control/*

Include:
- current mission state
- CSM/LM state
- orbit elements
- trajectory
- Earth/Moon position
- burn planner
- engine/system health
- mission events
- COMMS

## Pilot

Evolve primarily from `CM/index.php`.

Do not discard its current visual character.

## Commander

Evolve primarily from `CM/command_Module.php`.

Preserve:
- DSKY
- registers
- systems
- engine status
- emergency functions
- mission guidance

## Lunar Module

Combine/evolve the useful LM functions currently spread between:
- root `index.php`
- root `pilot.php`
- existing LM instruments

It must become a coherent independent vehicle page, not a generic dashboard.

Acceptance:
- all four pages operate against the same live simulator.
- no page has hidden physics responsibilities.

---

# Phase 16 — Full mission validation

Run repeatable test scripts.

## Reset / launch
- [ ] reset remains stable on pad.
- [ ] countdown works.
- [ ] Saturn launches naturally.
- [ ] all stage transitions work.
- [ ] parking orbit is stable.

## Earth orbit
- [ ] multiple-orbit conservation test.
- [ ] SPS maneuvers.
- [ ] CSM RCS.
- [ ] attitude maneuvers.

## Translunar
- [ ] TLI.
- [ ] coast.
- [ ] midcourse correction.
- [ ] free-return-style check.
- [ ] lunar approach.

## Lunar operations
- [ ] lunar orbit.
- [ ] LM extraction/undock.
- [ ] LM descent.
- [ ] soft landing.
- [ ] surface persistence.
- [ ] ascent.
- [ ] rendezvous.
- [ ] docking.

## Return
- [ ] TEI.
- [ ] coast.
- [ ] entry targeting.
- [ ] SM separation.
- [ ] reentry.
- [ ] heating.
- [ ] parachutes.
- [ ] splashdown/contact.

## Failure cases
- [ ] engine-out launch.
- [ ] abort/LES.
- [ ] bad lunar landing.
- [ ] excessive docking closure rate.
- [ ] poor reentry angle.
- [ ] parachute failure/unsafe deployment where modeled.

No feature is considered complete solely because the page renders.

---

# Phase 17 — Flight manual PDF

Create only after controls/procedures stop moving substantially.

The manual should be written for operating the simulator.

Required quick-find sections:
- Simulator setup
- Mission selection
- Reset
- Prelaunch
- Saturn V launch
- Stage sequence
- Parking orbit
- DSKY quick reference
- Attitude/RCS controls
- SPS burns
- TLI
- CSM/LM docking/extraction
- Transfer to LM
- LM separation
- Lunar descent
- Landing
- Surface operations as modeled
- Ascent
- Rendezvous
- Docking
- TEI
- Reentry preparation
- SM separation
- Entry
- Parachutes
- Splashdown
- Abort procedures
- Warning/failure indications
- Ground Control COMMS examples
- Troubleshooting
- Glossary

The manual should include a short "I want to..." index, for example:
- I want to start a mission
- I want to launch
- I want to circularize
- I want to go to the Moon
- I want to land the LM
- I want to rendezvous
- I want to come home

---

# Permanent engineering rules

1. Physics is never advanced by loading a display page.
2. UI controls request actions; central simulation code applies physical effects.
3. One authoritative source for physical constants.
4. Real elapsed time is measured.
5. Numerical integration uses controlled substeps.
6. Position/velocity are not rounded unnecessarily inside physics.
7. Every major physics change gets a conservation/regression test.
8. The Moon is a moving body; lunar collision/landing uses relative state.
9. Docking uses relative position, velocity and attitude.
10. Ground Control AI explains/calculates; deterministic code supplies numerical truth.
11. API secrets stay outside the public repository.
12. Preserve the original simulator's operating character unless a specific behavior is broken.
13. Do not introduce vehicle-specific hacks into gravity/integration merely to force a desired trajectory.
14. Do not call something "working" until its full input -> physics -> state -> display path has been tested.
