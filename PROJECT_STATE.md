# Moon Simulator — Project State / Working Memory

Last updated: 2026-10-29

## Purpose of this file

This file is the persistent working memory for the Moon Simulator project.

Before making simulator changes, read this file first. Update it after every meaningful audit or coding session so the next session can continue without re-inventing the architecture.

## Non-negotiable design direction

- Preserve the spirit and working structure of the original simulator.
- Do **not** replace the simulator with a generic greenfield framework.
- Do **not** redesign working cockpit pages just because a different architecture looks cleaner.
- Fix accuracy, broken wiring, timing, physics, controls, guidance, LM independence, and usability in place.
- Visual changes should respect the existing Apollo-style / instrument-panel feel.
- The simulator remains real-time.
- Reset returns the simulator to the launch site / prelaunch state.
- The normal flow is reset -> prepare/start -> launch sequence -> staging/jettison -> orbital/translunar operations -> LM operations -> return/reentry as applicable.
- Mission selection should configure a mission without making the core simulator mission-specific.

## Requested final operator-facing pages

The target is four principal pages:
1. Ground / Flight Data page — dense flight telemetry and mission-control data.
2. Pilot page — pilot flight controls and primary flying view.
3. Commander page — command/guidance/systems role, retaining the existing dual-screen spirit.
4. Lunar Module page — independent LM cockpit and controls after transfer/separation.

These can be supported by existing sub-pages/includes; the goal is not to delete useful current instruments.

## Current source baseline

The repository is based on the original working Moon Simulator source rather than the earlier replacement framework.

Important current areas:
- root LM/pilot-oriented pages: index.php, pilot.php, controls/instruments
- Command Module: CM/
- ground tools: ground.php and ground_control/
- physics loop: runmoon.php -> physicsupdate.php -> physics/*
- guidance/AGC: physics/CM_AGC.php, CMguidance, CMprogram_register, physics/prog/*
- live CM state: command_module
- live LM state: telemetry
- Moon state: moon
- system state: systems, csm_systems, CMswitches, EngineStatus

The uploaded SQL dump is the moonland database and contains the missing simulator state/configuration tables.

## Verified architecture findings

### Real-time worker
- runmoon.php launches physicsupdate.php in the background.
- physicsupdate.php loops 60 times with sleep(1).
- When systems.sim_on is enabled, each loop loads variables/constants and runs coordinate, gravity, atmosphere, thrust, fuel, telemetry, and CM AGC logic.
- When sim_on is disabled, physics/reset.php continually restores the launch/reset state.

### Reset behavior
physics/reset.php resets:
- Command Module / Saturn stack position, velocities, stages, propellant, attitude/rates, thrust, parachutes, LES state, heat shield and crash state.
- LM position, velocity, descent stage, descent/ascent fuel, RCS, attitude/rates, thrust and crash state.
- systems health/status.
- CM guidance working state.
- mission clock to T-900 seconds.
- EngineStatus to OFF/OK.
- CM telemetry history is truncated.

This is consistent with the intended reset-to-launch-site flow.

### Existing CM guidance computer
The simulator already has a data-driven command-module guidance system:
- CMguidance stores program definitions and program code.
- CMprogram_register has five running program slots.
- physics/CM_AGC.php executes the program code from the database and counts register timers.
- Existing programs include launch sequence, stage ignition/separation, orbit velocity, auto attitude/vector, retro/prograde burns, reentry, TLI, TEI, rendezvous radar and station keeping.

This system should be repaired/cleaned and made safer rather than replaced wholesale.

### Existing LM and CM separation model
- command_module is the CM/Saturn/CSM live state.
- telemetry is the LM live state.
- Both have independent position/velocity fields.
- When docked, or while stage 3 is still attached, LM position/velocity is currently forced to CM position/velocity.
- When no longer docked/attached, LM translational state is intended to evolve independently.
- The database already has separate LM descent fuel, ascent fuel, RCS, descent-stage flag, position/velocity and crash state.

## Critical defects found so far

These are verified issues in the current implementation and are high priority.

### 1. Lunar engine lookup mismatch — LM thrust can be zero
EngineStatus rows use engine_stage = "Lunar Module" for both ascent and descent engine components.

physics/thrust.php searches for:
- "Lunar Module Ascent"
- "Lunar Module Descent"

Because those strings do not match the database rows, the calculated live-engine fractions for both LM engines can be zero. This is a likely direct reason the LM does not work independently.

### 2. Moon mass is off by a factor of ten
physics/constants.php currently uses:
734100000000000000000000 kg = 7.341e23 kg.

The intended Moon mass comment says 7.341e22 kg. The numeric constant has one extra zero.

This substantially overstates lunar gravity and will distort translunar, lunar orbit, descent, ascent and rendezvous trajectories.

### 3. Physics assumes exactly one second per loop
physicsupdate.php uses sleep(1), and the integrator implicitly treats every pass as dt = 1 second.

If PHP/database work makes a pass take 1.1, 1.5 or 2 seconds, the simulation falls behind real time. If workers overlap, time can also be double-applied.

Upgrade direction:
- preserve the minute runner concept,
- add a DB/shared last-physics timestamp and a lock,
- calculate actual elapsed real time,
- split elapsed time into small fixed integration substeps.

### 4. Position integration contains a tuned fudge factor
physics/telemetry.php updates position approximately as:
position += old_velocity + acceleration * factorCM

factorCM is stored in command_module and has been used as a tuning factor.

This is not a consistent physical integrator. It is a likely source of orbit drift and trajectory tuning problems.

Upgrade direction:
- use a consistent velocity-Verlet / leapfrog or equivalent integrator,
- keep the same external database/interface semantics where possible.

### 5. Rotation is Euler-angle/rate accumulation with ±90° reflection
CM attitude is currently updated by directly adding pitch_rate/roll_rate/yaw_rate each physics pass.

When pitch crosses ±90°, code reflects pitch, reverses pitch rate and shifts yaw by 180°.

This is the source of the awkward rotational behavior / coordinate singularity problem.

Also, physics/thrust.php calculates roll radians but does not use roll in the CM or LM main-engine thrust direction.

Upgrade direction:
- maintain cockpit-readable pitch/yaw/roll,
- internally use a robust orientation representation / rotation matrix or quaternion,
- derive displayed Euler angles from it,
- apply body-frame thrust/RCS through full 3D orientation.

### 6. LM rotational dynamics are incomplete
The LM has pitch/roll/yaw and rate fields in telemetry and cockpit controls change rate values, but the main physics telemetry path primarily advances LM translation. The CM has explicit attitude integration; LM attitude does not have an equivalent complete physics update in the main integrator.

This must be corrected for an independently flyable LM.

### 7. LM lateral RCS bypasses central physics
Root RCS.php directly adds delta-v to telemetry when a button is pressed.

That makes RCS behavior dependent on HTTP button presses rather than force/impulse application by the physics loop, and the local/body-axis transform is incomplete.

Upgrade direction:
- controls set thruster commands/state,
- physics loop applies the corresponding body-frame impulse/force and fuel use.

### 8. LES / abort naming and thrust inconsistencies
The SQL state uses the misspelled command_module attribute "excape_fuel".

physics/reset.php attempts to update "escape_fuel", which does not match that row.

Variables are generated directly from attribute names, so this spelling mismatch can create undefined/wrong variables.

Also physics/thrust.php uses first-stage thrust in the abort/escape branch even though constants.php defines a separate escape_tower_thrust.

This must be normalized carefully without breaking existing cockpit controls.

### 9. Automatic engine malfunction abort check cannot match current enum
EngineStatus.operational_status is defined as OK/FAILED.

physics/CM_AGC.php checks for operational_status = 'MALF'.

As written, that condition cannot represent the SQL enum's FAILED value and the automatic abort path will not behave as intended.

### 10. CSM + LM mass accounting after S-IVB separation is suspect
totalSaturnV currently includes totalLEM inside the stage-3 term.

When stage3 becomes 0, the LM mass stops contributing to the CM/CSM mass even if the LM is still docked to the CSM after extraction.

Mass needs to depend on actual attachment/docking state, not only the stage3 flag.

### 11. Database contains useful systems that should be retained
The SQL contains:
- detailed F-1/J-2/SPS/LM engine component rows,
- CSM subsystem states,
- CM switch states,
- guidance programs/registers,
- mission clock,
- telemetry/state stores,
- orbital-change history,
- entry tracker/checklist-like guidance.

These are valuable parts of the original design and should be repaired and used, not discarded.

## UI findings

### Root index.php
Acts as an LM systems/instrument page:
- altitude
- pitch/roll and rates
- fuel
- alerts
- DSKY
- readout
- switches

### Root pilot.php
Acts as an LM pilot/flying page:
- controls
- window/viewport
- relative motion
- RCS
- docking radar / relative velocity
- abort/launch control

### CM/index.php
Acts as the primary CM pilot/commander visual cockpit:
- DSKY
- viewport
- reentry overlay
- RCS and docking radar
- 8-ball
- roll gauge
- flight controls
- lower panel

### CM/command_Module.php
Acts as the second CM systems/engineering/guidance display:
- altitude
- control panel
- reentry chart
- program register
- orbital telemetry
- engine indicators
- emergency shutdown
- switches/panel B
- staging

This existing dual-screen concept is useful and should guide the requested Pilot/Commander pages rather than being replaced by generic square panels.

### Ground
ground.php is a simple live ground display.
ground_control/ contains more advanced orbit/guidance/position/transfer calculations.

The requested new Ground / Flight Data page should consolidate and improve these capabilities rather than discard them.

## Mission selection direction

Do not hard-code the simulator to one Apollo mission.

A mission selection layer should primarily supply:
- mission name/number
- launch date/time if relevant
- launch-site state/orientation
- launch guidance/profile data
- planned events / reference targets
- optional historical mission values

The underlying spacecraft physics and controls should remain the same simulator.

Existing CM guidance already references Apollo11LaunchTelemetry for launch pitch/velocity targets; this should become mission-profile data rather than a hard-coded Apollo 11 dependency.

## Flight manual requirement

A PDF flight manual is required once the repaired control flow is stable.

It should be operator-oriented, not a code manual, with quickly findable procedures such as:
- reset / mission selection
- prelaunch
- launch sequence
- staging
- orbit insertion
- TLI
- CSM/LM docking and transfer
- LM separation
- lunar descent
- landing
- ascent
- rendezvous/docking
- TEI
- service-module separation
- reentry
- abort procedures
- DSKY/program quick reference
- troubleshooting / warning indications

## Working order

Do not make large visual rewrites first.

Recommended order:
1. Complete source/database dependency map.
2. Fix schema/code naming mismatches that currently prevent systems from working.
3. Introduce reliable real-time dt + worker locking while preserving current cron model.
4. Replace translational integrator without changing operator controls.
5. Replace internal rotational math while preserving displayed pitch/yaw/roll.
6. Make LM fully independent: attitude, RCS, descent engine, ascent engine, staging, landing/contact, rendezvous/docking.
7. Repair Saturn V launch/staging/engine logic and validate against realistic stage data.
8. Repair/normalize AGC/guidance program behavior.
9. Build mission-selection/profile layer.
10. Consolidate into the four principal operator pages while preserving useful original instruments.
11. Validation passes: launch, orbit, TLI/free return, lunar orbit, LM descent/ascent, rendezvous, TEI/reentry.
12. Produce flight manual PDF.

## Changes made so far

- Original simulator source was made the repository baseline.
- Database credentials in public code were replaced with CHANGE_ME rather than publishing secrets.
- A prior incomplete ".upgrade_payload" staging directory exists in the repository from an interrupted upgrade attempt. It is **not** authoritative simulator code and should be removed once any useful staged content is confirmed unnecessary.
- No major simulator architecture should be considered replaced by that staged payload.

## Rule for future sessions

At the start of each session:
1. Read PROJECT_STATE.md.
2. Inspect the exact files being changed.
3. Make small coherent commits.
4. Update this file with:
   - what was learned,
   - what changed,
   - what was tested,
   - known remaining problems,
   - next exact step.

Do not claim a feature is working unless its code/data path has been checked end-to-end.
