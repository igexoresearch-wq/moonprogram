# Moon Simulator — Original Working System Snapshot

This repository is a direct working-source snapshot reconstructed from the user-provided `moon.zip` on 2026-10-28. It intentionally preserves the original simulator architecture rather than replacing it with the later generic rewrite.

Important notes:

- The original ZIP did **not** contain a SQL/database export. The application expects the existing `moonland` MySQL database and its state/configuration tables.
- The original database password was embedded in multiple PHP files. Because this repository is public, that password has been replaced with `CHANGE_ME`. Put the correct credential back only in the deployed private/server copy (or centralize credentials later after we finish documenting the existing behavior).
- Historical `error_log` and `*.log` files were omitted from GitHub. They were reviewed during the import but are runtime artifacts, not source.
- The application uses the original database-as-shared-state design. No simulator redesign was performed during this import.

## Main areas found in the snapshot

- Root cockpit/UI: Lunar Module-oriented controls and instrumentation using the `telemetry` state table.
- `CM/`: Command Module cockpit, instruments, switches, DSKY/guidance, viewport, RCS, staging and systems views.
- `physics/`: Real-time physics loop, Earth/Moon gravity, atmosphere/reentry, thrust/fuel, Moon motion, CM/LM state updates, guidance computer and program logic.
- `ground_control/`: Orbit/guidance/trajectory displays and ground-control calculations.
- `physicsupdate.php`: 60-second physics worker used by the original once-per-minute launch pattern.
- `runmoon.php`: Background launcher for `physicsupdate.php`.

The simulator's behavior is also dependent on database-resident configuration/program data, especially `CMguidance`, `CMprogram_register`, `CMswitches`, `EngineStatus`, `systems`, `command_module`, `telemetry`, `moon`, and related tables.
