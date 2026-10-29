# Moon Simulator — System Map

Last audited: 2026-10-29

This document maps the original simulator as it actually works today. It is descriptive, not a redesign proposal.

## 1. Runtime topology

### Physics worker

The live simulation path is:

```
cron / external scheduler
    -> runmoon.php
        -> background HTTP request to physicsupdate.php
            -> 60 iterations
               -> physics/variables.php
               -> physics/constants.php
               -> if systems.sim_on == 1:
                    physics/coordinate.php
                    physics/gravity.php
                    physics/air.php
                    physics/thrust.php
                    physics/fuel.php
                    physics/telemetry.php
                       -> physics/moon.php
                    reload variables/constants
                    physics/CM_AGC.php
                       -> physics/functions.php
                       -> database CMguidance program code
                       -> physics/IMU.php
                  else:
                    physics/reset.php
               -> sleep(1)
```

The active worker does **not** call these files:
- physics/land.php
- physics/launch_stress.php
- physics/recordtelemetry.php
- physics/predict_CM.php
- physics/predict_moon.php
- physics/grav.php
- physics/test.php
- physics.php
- physicsmoon.php

They are legacy, experimental, or manually callable unless intentionally reintroduced.

## 2. Authoritative live state

### Command Module / Saturn / CSM

Table: `command_module`

Stores the current CSM/Saturn state as attribute/value rows:
- position: x, y, z
- velocity: delta_x, delta_y, delta_z
- stage presence: stage1, stage2, stage3
- stage propellant: s1fuel, s2fuel, s3fuel
- service_module
- SPS propellant: fuel
- RCS propellant
- attitude: pitch, roll, yaw
- angular rates
- thrust command
- dock
- escape / LES state
- abort
- parachutes
- staging event flag
- heat shield
- crash
- factor
- engine_on

Important legacy spelling: `excape_fuel` exists in the SQL dump.

### Lunar Module

Table: `telemetry`

Despite its generic name, this is effectively the LM live-state table:
- pitch, roll, yaw
- pitch/roll/yaw rates
- x, y, z
- delta_x, delta_y, delta_z
- descent propellant: fuel
- ascent propellant: fuel_ascent
- RCS
- descent flag
- thrust
- crash
- map display state

There are duplicate map_x/map_y rows in the current SQL dump and no real map_z row.

### Moon

Table: `moon`
- x, y, z
- delta_x, delta_y, delta_z

Earth is not a database body. It is a fixed center defined in constants.

### Mission clock

Table: `mission_clock`
- launch_events
- minutes
- seconds

The active code mainly treats `seconds` as the mission-time counter.

## 3. System/configuration tables

### CSM controls
- `CMswitches`: cockpit switch positions
- `csm_systems`: CSM subsystem state/health values
- `EngineStatus`: individual launch vehicle, SPS and LM engine component states
- `CMguidance`: guidance computer state + program definitions/code
- `CMprogram_register`: five concurrent program slots
- `entry_tracker`: cockpit quick-reference / noun-verb guidance list

### LM / older control model
- `systems`: LM/general system on/off status, including sim_on and landed
- `switches`: root/LM switch bank
- `guidance`: older/root DSKY noun/verb state

### History / analysis
- `CMtelemetry`: CSM telemetry history structure
- `orbital_changes`: ground-control orbital history
- `radio_data`: present in DB but no active source-code use found in audit

### Unrelated DB content
- `rise_of_nations_resources` is unrelated to the Moon simulator.

## 4. Operator pages

### LM systems page — root index.php
Includes:
- alt.php
- fuel.php
- alert.php
- Diskey.php
- readout.php
- switches.php

This is best understood as the LM systems/instrument screen, not a generic home page.

### LM pilot page — root pilot.php
Includes:
- controlls.php
- window.php
- rel_mot.php
- RCS.php
- rcs_g.php
- rel_vel.php
- abort_launch.php

This is the LM flying/rendezvous screen.

### CSM pilot/commander flight station — CM/index.php
Includes:
- DSKY
- CM viewport
- RCS
- docking radar / relative velocity
- FDAI / 8-ball
- roll gauge
- hand controls
- panel A

### CSM systems / guidance station — CM/command_Module.php
Includes:
- altitude/orbit readout
- control panel
- reentry chart
- guidance program register
- orbital telemetry chart
- engine indicators
- emergency engine shutdown
- panel B
- stage display

### Ground
- ground.php is the simple ground display.
- ground_control/ contains the more capable trajectory/orbit/guidance tools.

The requested future four-page structure maps naturally to these existing systems:
1. Ground Flight Data
2. Pilot — CSM flight station
3. Commander — CSM guidance/systems station
4. Lunar Module — the existing root LM systems + pilot functions brought together coherently

## 5. Reset and launch flow

When `systems.sim_on` is off, the physics worker repeatedly calls `physics/reset.php`.

Reset restores:
- Saturn/CSM to the +X Earth-surface launch position
- zero velocity and attitude rates
- all three Saturn stages present
- Saturn stage propellant
- SM, SPS propellant and RCS
- LM near the stack with descent stage, ascent/descent propellant and RCS
- system health
- CM guidance working state
- mission clock to T-900
- EngineStatus to OFF/OK
- CM telemetry history is truncated

The current reset does not reset the Moon state.

The command-module guidance database contains:
- program 24: countdown/start
- program 23: launch sequence
- 16/17/18: stage ignition
- 19/20/21: stage separation
- 22: MECO
- 25: orbit velocity
- 28: auto vector
- 29/30/31: burn/vector functions
- 33: reentry
- 34: TLI
- 37: TEI
- 38: rendezvous
- 40: station keeping

`entry_tracker` exposes launch sequence start as noun/program 24 + execute.

### Current launch-sequence quirks

- `lseq.php` changes T-900 directly to T-15.
- T+0 executes first-stage ignition.
- T+1 manually moves the stack 5 m outward and gives it 10 m/s velocity to get it off the artificial surface hold.
- stage separations are primarily fuel-depletion driven.
- `command_module.staging` is used as an event/interlock flag.
- stage ignition after separation waits for `staging == 0`.
- **CM/sound.php clears staging to 0 when a cockpit page loads.**
  Therefore the physics sequence can depend on a cockpit page being open. This is a critical coupling to remove; audio/UI should observe events, not advance physics state.

## 6. Physics model as implemented

### Coordinate frame

- units are meters and m/s
- Earth center = (10,000,000,000, 10,000,000,000, 10,000,000,000)
- reset launch point = Earth center + Earth radius on +X
- pitch 0 / yaw 0 corresponds to thrust approximately along +X at the launch point
- Earth is fixed and non-rotating
- Moon is dynamically propagated around the fixed Earth

### Gravity

`physics/gravity.php` computes Earth and Moon acceleration separately for CSM and LM and sums them.

Surface gravity is simply disabled when a craft reaches the body radius. There is no active general contact/support model.

### Translation integration

Current update is effectively one-second Euler/semi-Euler math plus a tuned `factor` term:
- velocity += acceleration
- position += old velocity + acceleration * factor

This assumes one second per worker iteration and is not a consistent physical integrator.

### Attitude

CSM:
- pitch/roll/yaw rates are added directly to Euler angles each tick
- pitch crossing +/-90 degrees is reflected and yaw is shifted 180 degrees

LM:
- attitude/rate fields exist and cockpit controls modify some of them
- the active central physics path does not contain a complete equivalent LM attitude integrator

### Main-engine thrust

`physics/thrust.php` chooses propulsion based on active stage:
- S-IC
- S-II
- S-IVB
- SPS
- LM DPS/APS

EngineStatus component rows are used to reduce thrust when combustion chambers are off.

The current main-thrust vector uses pitch and yaw. Roll is calculated but not used in the thrust transform.

## 7. Major confirmed defects

### Physics/data errors

1. **Moon mass is ten times too high in physics/constants.php.**
   Current numeric value is 7.341e23 kg while the comment/intended value is about 7.341e22 kg.

2. **Real elapsed time is not measured.**
   Every loop is assumed to be exactly one second.

3. **Position integration uses factorCM as a tuning fudge rather than a stable integrator.**

4. **CSM Euler attitude has a singularity workaround at +/-90 degrees.**

5. **LM attitude is not fully propagated by the active central physics loop.**

6. **Main-engine thrust orientation is not a full 3D body transform.**

7. **Earth rotation and launch-site latitude are not modeled.**
   This is an acceptable original abstraction only if mission profiles are defined around it; it is not historically exact launch geometry.

### LM blockers

8. **LM engine-stage lookup does not match SQL.**
   SQL stores both LM engines under `engine_stage='Lunar Module'`; physics searches for `Lunar Module Ascent` and `Lunar Module Descent`.

9. **EngineStatus is reset OFF and no active original LM cockpit path was found that turns the LM DPS/APS component rows ON.**

10. **telemetry.descent is reset to 1 and no active original path was found that sets it to 0 for ascent-stage separation.**

11. **Root LM controls cap thrust at 60.**
    Combined with the physics descent-engine constant already being reduced to 27 kN, this can cap effective DPS thrust at roughly 16.2 kN even before other failures.

12. **LM RCS directly edits velocity on the HTTP button request instead of being processed centrally by physics.**

13. **LM DSKY/guidance table is mostly an input/state shell.**
    Root Diskey.php writes `guidance`, but no active equivalent of CM_AGC executes those LM guidance commands.

14. **Landing/contact is not in the active worker.**
    physics/land.php is dormant and contains inconsistent body/altitude variable logic even if enabled.

15. **Root alt.php uses stale telemetry.altitude rather than the active calculated LM body altitude and computes descent rate from distance to global coordinate zero.**
    It also references a nonexistent `eagle` systems row.

### CSM / Saturn / controls

16. **LES fuel attribute spelling differs.**
    SQL has `excape_fuel`; some physics code expects `escape_fuel`.

17. **Abort propulsion uses first-stage thrust instead of the defined LES thrust constant.**

18. **EngineStatus operational_status allows OK/FAILED; some abort/failure code checks or writes MALF.**

19. **physics/launch_stress.php is dormant and references nonexistent engine_fuel.**

20. **S-IVB display/shutdown naming is wrong.**
    SQL third-stage engine is `J-2 Engine`; engine_indicator.php and emergency_shutdown.php look for an SPS engine / numbered engine pattern.

21. **CSM RCS translation is not modeled as RCS.**
    CM/thrusters.php writes a scalar `thrusters` value; service-module thrust code adds it to the SPS thrust percentage and then clears it.

22. **Several cockpit switches are display/state only rather than authoritative physics interlocks.**
    Example: the SPS physics path is not consistently gated by the Service Propulsion Engine switch.

23. **csm_systems has useful subsystem fields but is largely static.**
    The audit found reads for displays but no active dynamic subsystem update path.

### Docking / attachment

24. **Docking is accepted automatically below 10 m separation.**
    Relative speed, approach axis and attitude are not checked.

25. **Docking release is a position jump rather than a physical separation impulse.**

26. **LM mass/attachment accounting is tied too closely to stage3.**
    LM mass can disappear from the CSM effective mass after S-IVB separation even while docked.

27. **While stage3 is active, LM state is forced directly to CSM state.**
    This is a useful simplification for the current architecture but does not represent CSM separation/transposition/docking/extraction accurately.

### Stale root/LM code

28. **abort_launch.php expects systems named launch/abort.**
    The actual SQL has no such systems; id 11 is sim_on and id 12 is landed.

29. **switches.php has old schema/coordinate assumptions.**
    It references nonexistent system IDs, uses `docking` instead of `dock`, and its Land Abort coordinates predate the 1e10-offset frame.

30. **xz.php treats telemetry id 19 as map Z.**
    In current SQL, telemetry id 19 is LM RCS propellant. The XZ map can therefore conflict with RCS fuel.

### Guidance computer

31. **CM guidance architecture is useful but some programs are defective.**
    Do not replace it wholesale.

32. Program 9 references undefined `$differencer` in roll completion logic.

33. Program 14 references a missing table `Apollo11LaunchTelemetry`.
    The SQL dump contains references to it but no CREATE TABLE.

34. Program 27 / cockpit switch numbering is inconsistent around AUTO LEVEL / RETRO VECTOR.

35. Program 34 TLI math contains suspect mass/formula logic and should not be treated as an accurate trajectory solver.

36. Program 37 references `$moondistance` without defining it locally.

37. Direct execute handling in CM_AGC only executes when exactly one CMguidance row has execute=1. Multiple simultaneous execute flags can prevent dispatch.

### Constants / mass model

38. **There are three separate constants files with conflicting values.**
    - physics/constants.php
    - CM/constants.php
    - ground_control/constants.php

    Examples:
    - active physics S-IC thrust: 34.5 MN
    - CM/ground S-IC thrust: 7.607 MN
    - active physics S-II thrust: 5.141 MN
    - CM/ground S-II thrust: 1.0197 MN
    - active physics S-IVB thrust: 1.0331 MN
    - CM/ground S-IVB thrust: 0.100 MN

    Some CM/ground values appear to mix per-engine and total-stage values under the same variable names.

39. **LM and SM mass constants are internally suspect and likely double-count propellant.**
    Historical values must be audited before trajectory validation.

40. **The launch pad is currently a physics hack.**
    Gravity is suppressed at the surface and lseq.php gives the rocket an artificial initial displacement/velocity. A proper support-force/liftoff condition is needed.

## 8. Guidance architecture worth preserving

The CM DSKY is not just decorative.

`CM/disky.php` allows noun/verb/value entry into CMguidance working rows.

`CM_AGC.php`:
- runs five program registers
- loads program source from CMguidance
- executes the stored program
- decrements register timers
- handles direct execute requests
- updates guidance/navigation state
- invokes IMU handling

The implementation is unconventional because executable PHP is stored in the database, but the operator model — DSKY entry, program registers, status, guidance routines — is part of the simulator's character and should be retained while made deterministic and maintainable.

## 9. Historical fidelity reference set

External authoritative references to use when validating/fixing behavior:

- NASA Saturn V Flight Manual SA-507:
  https://www.nasa.gov/wp-content/uploads/static/history/afj/ap12fj/pdf/a12_sa507-flightmanual.pdf

- NASA Apollo Operations Handbook, CSM systems:
  https://www.nasa.gov/wp-content/uploads/static/history/afj/aoh/aoh-v1-2-02-g%2Bn.pdf

- NASA Apollo Operations Handbook, Lunar Module operational procedures:
  https://ntrs.nasa.gov/archive/nasa/casi.ntrs.nasa.gov/19710071423.pdf

- NASA Apollo LM propulsion overview / News Reference:
  https://www.nasa.gov/wp-content/uploads/static/history/alsj/LM09_Main_Propulsion_ppMP1-22.pdf

- NASA Apollo GNC hardware overview:
  https://ntrs.nasa.gov/archive/nasa/casi.ntrs.nasa.gov/20090016290.pdf

- NASA Apollo diagrams / cockpit panel references:
  https://www.nasa.gov/history/diagrams/apollo.html

These are research references. They do not override the current source when documenting how the existing simulator works; they are for validation and correction.

## 10. Historical-control implications already established

NASA material supports several directions that fit the existing simulator rather than replacing it:

- Saturn V launch sequencing was heavily automatic; crew/ground systems monitored interlocks and emergency detection rather than manually flying each launch-stage event.
- Apollo CSM had both primary computer-based GNC and a backup Stabilization and Control System.
- DSKY use covered display, data entry, program control and major-mode changes.
- LM had independent guidance/control and RCS.
- LM descent propulsion was throttable and normally guidance-controlled; RCS handled attitude.
- LM ascent engine was fixed, with RCS providing attitude control.
- LM flight supported rate-command/manual control modes as well as automatic control.

This means the current simulator's concepts — automated Saturn launch sequence, DSKY/program control, manual rate controls, independent LM propulsion/RCS — are directionally good. The work is to make them physically and logically correct.

## 11. What should be preserved

Preserve:
- real-time mission pacing
- reset -> prelaunch -> launch sequence flow
- command_module / telemetry split for CSM and LM unless a concrete limitation forces change
- current CSM cockpit visual identity
- current dual-station idea
- DSKY and program-register interaction
- individual Saturn engine/component status concept
- ground trajectory tools
- sound/event feedback
- failure capability
- docking/rendezvous gameplay

Do not preserve bugs merely because they are old.

## 12. Implementation boundary after audit

The source is understood well enough to begin repairs without a greenfield rewrite.

The first code phase should be deliberately boring:
1. eliminate schema/name mismatches that make existing systems nonfunctional
2. make physics state transitions independent of page loads
3. add reliable worker locking and measured elapsed time
4. centralize one authoritative set of physical constants
5. replace translation integration while keeping the current DB state/interface
6. introduce robust internal attitude math while retaining pitch/yaw/roll cockpit displays
7. complete LM propulsion, attitude, RCS, staging and surface contact
8. only then change/consolidate the operator pages
