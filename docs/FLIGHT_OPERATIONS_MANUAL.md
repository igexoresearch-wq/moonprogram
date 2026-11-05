# Moon Program — Flight Operations Manual

## Stations and communication
- **Commander / CM:** `index.php`; Apollo-style instruments, DSKY, stage and burn review.
- **Pilot / LM:** `pilot.php`; lunar descent, ascent and rendezvous links.
- **FIDO:** `fido.php`; trajectory monitoring, planning and ground control.
- **FIDO Radio:** `ground_voice.php`; select GUIDO, RETRO, EECOM, GNC, TELMU, TRAJECTORY or CAPCOM. Hold to talk; release to receive spoken reply. Take notes on paper. No conversation transcripts.
- **Crew Radio:** `crew_radio.php`; astronauts speak only with CAPCOM. Pending calls show a lamp; answer to hear the message.
- **Mission history:** `flight_timeline.php`, `flight_replay.php`, and `flight_report.php` are read-only records.

## Mission sequence
1. **Reset:** Restore initial vehicle and mission-clock state; verify inactive engines, attached stages and flight readiness. Reset alone is not an indication of mission success.
2. **Launch:** Enable the simulation and initiate approved launch guidance. Monitor physical thrust, consumed fuel, velocity and staging.
3. **Parking orbit:** Compare actual apogee/perigee with targets before requesting TLI.
4. **TLI and outbound:** FIDO calculates candidate maneuvers. Crew reviews and manually arms the selected maneuver; FIDO may consult TRAJECTORY, GUIDO and EECOM.
5. **Lunar arrival:** Monitor lunar-relative state, LOI plan and fuel. Failed maneuver planning does not silently repair the trajectory.
6. **LM separation/descent/ascent:** Confirm vehicle attachment state and available ascent propellant. Operate LM controls manually. The simulation's gravitational and contact calculations remain authoritative.
7. **Rendezvous:** Compare CSM and LM relative motion and closing speed. Align, approach and request capture only inside the physical docking envelope.
8. **Earth return:** FIDO may select return/abort calculations. RETRO reviews the computed return corridor; EECOM reviews fuel margin. The crew approves any physical burn.
9. **Entry:** Monitor Earth-relative atmospheric speed, entry angle, thermal state and parachutes.
10. **Recovery:** Only a safe CM Earth contact counts as mission success. An abort is an operational decision, not automatically a failed or successful landing.

## Radio discipline
- Controllers answer within their specialty and cite instrument values. A missing computed solution must be reported as unavailable.
- CAPCOM is the only crew contact; it relays approved ground information. FIDO must deliberately authorize forwarding to the crew.
- Read critical burns back in full: ignition MET, attitude/direction, delta-V, duration, fuel estimate and expected result.
- If the transmission is unclear, call **SAY AGAIN**; write figures in a physical notebook.

## Reproducibility and limitations
- The recorders capture actual transitions and 30-second state samples. Snapshot review does **not** alter simulated state or reproduce all intermediate numerical integration substeps.
- No random/scripted failures are allowed. Real simulation faults remain observable.
- All source-complete checklist items still require separately documented integrated flight and deployment validation; source completion does not assert they passed.
- Operator passwords, database credentials and OpenAI keys belong in private server configuration. Do not commit credentials.
- Use HTTPS and authenticated sessions for microphone and control pages. Running on an unconfigured server is not equivalent to a valid mission.
