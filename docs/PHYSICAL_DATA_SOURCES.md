# Moon Simulator — Physical Data Sources

Last updated: 2026-10-30

This file records where the simulator's physical constants come from and which values still require reconciliation. It exists so later work does not silently replace a sourced value with a remembered or guessed number.

## Reference policy

For historical Apollo/Saturn values, prefer:
1. Apollo mission reports / mission operation reports
2. Saturn V flight manuals / post-launch reports
3. Apollo Operations Handbooks
4. Apollo Flight Plans
5. NASA News Reference / press kits
6. NASA technical reports / experience reports

If two official sources differ, preserve the disagreement here and choose one deliberately for the simulator model.

---

## Celestial constants

The simulator currently uses a spherical Earth and spherical Moon:
- Earth mean radius: 6,371,000 m
- Earth mass: 5.9723e24 kg
- Moon mean radius: 1,737,400 m
- Moon mass: 7.342e22 kg
- gravitational constant: 6.67430e-11 SI

These are model constants, not an attempt to reproduce the high-order Earth/Moon gravity fields used in the Apollo navigation reconstruction.

The Apollo 11 Mission Report notes that postflight trajectory reconstruction used a higher-order geodetic/gravity Earth model and lunar harmonic terms. The simulator intentionally starts with spherical bodies so conservation errors can be separated from perturbation-model effects.

---

## Saturn V AS-506 / Apollo 11

### S-IC

NASA Apollo 11 Press Kit / mission operation material:
- inert/dry: about 288,750 lb
- LOX: 3,307,855 lb
- RP-1: 1,426,069 lb
- liftoff thrust: 7,653,854 lbf
- actual center engine cutoff: GET 00:02:15.2
- actual outboard cutoff: GET 00:02:41.6
- separation: GET 00:02:42.3

Current simulator baseline uses these mass/thrust values.

### S-II

NASA Apollo 11 material:
- inert/dry: about 79,918–79,920 lb
- LOX: 821,022 lb
- LH2: 158,221 lb
- total thrust documented around 1.12–1.16 million lbf
- actual center-engine cutoff: GET 00:07:40.6
- actual outboard cutoff: GET 00:09:08.2
- S-II/S-IVB separation: GET 00:09:09.0

Current simulator baseline uses 1,150,000 lbf as a nominal five-engine total.

### S-IVB

NASA Apollo 11 material:
- inert/dry: about 25,000 lb
- LOX: 192,023 lb
- LH2: 43,500 lb
- J-2 thrust is documented over a range; current baseline uses 203,779 lbf
- first ignition: GET 00:09:12.2
- first cutoff: GET 00:11:39.3
- first burn duration: about 147.1 sec
- second ignition / TLI: GET 02:44:16.2
- second cutoff: GET 02:50:03.0
- second burn duration: about 346.8 sec
- Earth parking orbit insertion: about GET 00:11:49.3
- Apollo 11 parking orbit: approximately 100.4 by 98.9 n mi

Important modeling consequence:
The S-IVB must **not** burn all of its propellant during launch insertion. The first burn must stop with enough propellant for the later TLI restart.

---

## CSM

### Command Module

Apollo 11 mission operation weight summary:
- CM launch/inert weight represented by the current model: 12,250 lb

### Service Module

Apollo 11 mission operation weight summary:
- inert: 10,555 lb
- total expendables: 40,605 lb
- total listed SM weight: 51,160 lb

Apollo 11 Mission Report actual SPS loading:
- SPS propellant total: 40,803 lb

Apollo 11 Flight Plan SM-RCS table:
- nominal SM-RCS loaded: 1,342.4 lb
- deliverable: 1,220.0 lb

These official figures do not form one perfectly consistent additive mass total because they come from different accounting products/times/definitions.

**Open mass-model issue:**
The simulator currently adds:
- SM inert 10,555 lb
- SPS load 40,803 lb
- SM RCS load 1,342.4 lb

That is useful for tracking separate consumables, but it is not identical to the mission-operation-report SM total. Do not “fix” this by deleting a consumable. Reconcile the mass accounting against a single mission epoch before final trajectory validation.

### SPS

Current baseline:
- nominal thrust: 20,500 lbf
- Isp model: about 314 sec
- Apollo 11 actual loaded SPS propellant: 40,803 lb

---

## Lunar Module LM-5

Apollo 11 press-kit / mission material:
- ascent-stage dry/inert: about 4,804 lb
- descent-stage dry/inert: about 4,483 lb
- LM RCS propellant: about 604 lb
- descent propellant: about 18,100 lb
- ascent propellant: about 5,214 lb

### DPS

NASA LM references:
- nominal full-throttle-position thrust: about 9,870 lbf
- design-command scale often stated as 10,500 lbf
- continuously throttleable region: approximately 1,050 to 6,300 lbf
- above the throttleable region, the engine automatically goes to the fixed full-throttle position
- engine gimbal capability: approximately ±6 degrees
- Apollo 11 powered descent began near minimum throttle, then advanced to full throttle before later throttle-down

Simulator rule implemented:
- command 0: engine off
- positive low commands are clamped to minimum DPS thrust
- command range through 60: throttleable region
- commands above 60: fixed full-throttle position (~9,870 lbf)

This restores the meaning behind the original simulator's 60% landing-throttle concept while retaining access to the real full-throttle braking mode.

### APS

NASA LM references:
- nominal thrust: about 3,500 lbf
- fixed-thrust / non-throttleable
- not gimbaled
- LM RCS provides attitude control during ascent

Simulator rule implemented:
- any positive APS engine command means full nominal APS thrust
- zero command means cutoff

---

## LM RCS

NASA Apollo 11 RCS performance material describes four clusters / 16 engines and extensive pulse operation. Current simulator uses a simplified coordinated-translation model and still needs the rotational torque model completed.

---

## Launch timing reference

Apollo 11 actual launch events used for validation:

- liftoff: 00:00:00.6
- pitch/roll start: about 00:00:12.4
- roll complete: about 00:00:31.1
- S-IC center cutoff: 00:02:15.2
- S-IC outboard cutoff: 00:02:41.6
- S-IC/S-II separation: 00:02:42.3
- S-II ignition: 00:02:43.0
- LES jettison: 00:03:17.9
- S-II center cutoff: 00:07:40.6
- S-II outboard cutoff: 00:09:08.2
- S-II/S-IVB separation: 00:09:09.0
- S-IVB first ignition: 00:09:12.2
- S-IVB first cutoff: 00:11:39.3
- Earth parking-orbit insertion: 00:11:49.3
- S-IVB second ignition: 02:44:16.2
- S-IVB second cutoff: 02:50:03.0

These are validation targets for an Apollo 11 profile. They must not become hard-coded universal physics rules.

---

## NASA source URLs

- Apollo 11 Mission Report:
  https://www.nasa.gov/wp-content/uploads/static/apollo50th/pdf/A11_MissionReport.pdf

- Apollo 11 Mission Operation Report:
  https://www.nasa.gov/wp-content/uploads/static/history/alsj/a11/A11_MissionOpReport.pdf

- Apollo 11 Flight Plan:
  https://www.nasa.gov/wp-content/uploads/static/apollo50th/pdf/a11final-fltpln.pdf

- Apollo 11 post-launch report:
  https://www.nasa.gov/wp-content/uploads/static/history/afj/ap11fj/pdf/a11-postlaunch-rep.pdf

- Apollo LM propulsion reference:
  https://www.nasa.gov/wp-content/uploads/static/history/alsj/LM09_Main_Propulsion_ppMP1-22.pdf

- Apollo lunar descent/ascent trajectories:
  https://www.nasa.gov/wp-content/uploads/static/history/alsj/nasa58040.pdf

- Apollo 11 press kit (NTRS):
  https://ntrs.nasa.gov/api/citations/19690022248/downloads/19690022248.pdf

---

## Remaining data work

Before declaring historical mass/propulsion validation complete:
- reconcile CSM/SM mass accounting at a single mission epoch,
- model Saturn residual propellant/cutoff rather than burning tanks to zero,
- model S-IC and S-II center-engine cutoff,
- validate J-2 mass flow / stage residuals against the actual burn sequence,
- validate SPS mass flow against Apollo 11 burns,
- add DPS gimbal behavior,
- add rotational RCS torque and moment-of-inertia values,
- define mission-profile-specific starting Moon state and launch geometry.
