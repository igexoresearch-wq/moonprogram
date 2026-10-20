# Moon Program

A PHP/MySQL Earth-Moon physics sandbox intended to deploy at `/moon`.

This first build is deliberately modular. The physics clock, N-body gravity, rigid-stack attachments, reset logic, API, and dashboard are separate so propulsion, staging impulses, docking, guidance, and cockpit controls can be added without rewriting the core.

## Files

- `schema.sql` — database schema.
- `seed.sql` — Earth, Moon, Apollo/Saturn V launch stack, propellant loads, initial attachments, and reset snapshot.
- `config.php` — PDO/environment configuration. No passwords are committed.
- `physics.php` — gravity, one-second integration, attachment/root mass handling, collision protection, and orientation rates.
- `reset.php` — restores the seeded launch state.
- `cron.php` — CLI physics runner.
- `api.php` — dashboard state and protected control actions.
- `index.php` — single-page dashboard.

## Database setup

Create/select a MySQL database, then import:

~~~sql
SOURCE schema.sql;
SOURCE seed.sql;
~~~

Configure the web server or PHP-FPM environment:

~~~text
MOON_DB_HOST=localhost
MOON_DB_NAME=spacegame
MOON_DB_USER=your_database_user
MOON_DB_PASS=your_database_password
MOON_CONTROL_KEY=choose-a-private-dashboard-control-key
~~~

The public repository intentionally contains no database password.

## Physics clock

`simulation_state.tick_seconds` defaults to exactly 1.0 simulated second.

The cron runner stores `last_tick_at`. Each physics update measures the real elapsed time since that timestamp, multiplies it by `time_scale`, and divides it into 1-second physics steps plus a final fractional step.

A normal once-per-minute cron launches a runner that stays alive for about 58 seconds and writes a fresh physics state roughly once per real second. A separate MySQL runner lock prevents the next minute from starting a second copy if the first one is still active. If a tick is late, reset, or closely repeated, `last_tick_at` ensures only the actual elapsed interval is integrated.

Suggested cron:

~~~cron
* * * * * /usr/bin/php /home/ACCOUNT/public_html/moon/cron.php >> /home/ACCOUNT/moon-physics.log 2>&1
~~~

For testing, `php cron.php --once` performs one elapsed-time update and exits. The timestamp, not the cron schedule, determines elapsed simulation time.

## Coordinate system

Authoritative positions are stored as signed BIGINT centimeters:

- `x_cm`
- `y_cm`
- `z_cm`

Fractional-centimeter remainder columns preserve precision between updates.

Velocities are stored in centimeters per second as DECIMAL values.

At reset:

- Earth is the fixed reference at `(0, 0, 0)`.
- +X passes from Earth's center through the launch point and the Moon's initial position.
- +Y is the Moon's initial prograde direction.
- +Z completes the right-handed frame.
- The launch stack is placed on Earth's equator at the +X surface point.
- Craft pitch/yaw/roll begin at `0.000`.
- Pitch 0 / yaw 0 points along +X, radially upward at the launch point.

Roll is retained as a real orientation value even though the current gravity-only physics does not use it. It can later rotate cockpit/window imagery.

## Attachments

Each rocket/module/stage is a separate row in `space_objects`.

`object_attachments` contains only current physical connections. While attached, descendants inherit the root object's position, velocity, and orientation, and their mass/propellant contributes to the root stack mass.

For example, the initial launch stack is rooted at S-IC. When staging is added, removing the S-IC -> S-II attachment will make S-II the root of the remaining upper stack and S-IC an independent object. A separation impulse can then be added to either new root.

This same mechanism is intended for CSM/LM docking and LM ascent/descent separation.

## Current physics

The integrator uses kick-drift-kick leapfrog rather than basic Euler integration.

Included now:

- Earth gravity
- Moon gravity
- Moon orbit initialized from physical parameters
- complete attached-stack mass
- independent motion after an attachment is removed
- position and velocity
- pitch/yaw/roll and angular rates
- Earth/Moon surface collision protection
- fixed launch stack while surface-locked
- elapsed-time-safe ticking
- sub-centimeter remainder preservation

Not yet included:

- engine thrust
- fuel consumption during burns
- atmosphere/drag
- Earth rotation
- J2/non-spherical gravity
- Sun gravity
- staging impulse
- docking capture physics
- RCS torque
- collision geometry beyond spherical Earth/Moon surfaces

Those are intentionally separate future modules.

## Seed data sources

Earth/Moon physical constants and lunar orbital scale are based on NASA/JPL data:

- JPL planetary physical parameters: https://ssd.jpl.nasa.gov/planets/phys_par.html
- JPL astrodynamic parameters / DE440 GM values: https://ssd.jpl.nasa.gov/astro_par.html
- JPL Moon physical parameters: https://ssd.jpl.nasa.gov/sats/phys_par/
- Mean lunar distance: https://ssd.jpl.nasa.gov/glossary/LD.html

Apollo 11/Saturn V values are based primarily on NASA's Apollo 11 press kit, flight plan, and mission report:

- https://www.nasa.gov/wp-content/uploads/static/apollo50th/pdf/A11_PressKit.pdf
- https://www.nasa.gov/wp-content/uploads/static/apollo50th/pdf/a11final-fltpln.pdf
- https://www.nasa.gov/wp-content/uploads/static/apollo50th/pdf/A11_MissionReport.pdf

The Saturn V seed uses the Apollo 11 nominal stage dry masses and separate loaded fuel/oxidizer quantities. LM DPS/APS values use Apollo 11 actual loaded propellant figures where available.

## Dashboard controls

The dashboard is readable without a control key. Start, pause, reset, and manual one-second step actions require `MOON_CONTROL_KEY`.

The key is entered in the browser and sent only with control requests. Do not commit it to this repository.
