# Moon Program

A reusable PHP/MySQL real-time space simulation framework.

## What this version contains

- 5×4 gray raised-panel Command Center dashboard
- Reusable object/component definition library
- Reusable engine definition library
- Fuel tank and propellant definitions
- Engine mounting on object definitions
- Missions and live object instancing
- Parent/child attachment and separation
- Live engine enable/throttle controls
- Signed BIGINT centimeter XYZ storage plus sub-centimeter remainder
- Generic N-body gravity
- Generic propulsion forces
- Real-time timestamp-driven physics
- Leapfrog-style position/velocity integration
- Telemetry history
- Configurable ground-command dashboard instrument slots
- Reusable cockpit definitions with independent panel layouts
- Default cockpit assignment on spacecraft/component definitions
- Live spacecraft cockpit view bound to one live object
- Cockpit instruments for attitude, velocity, position, fuel, engine control, vehicle status, and COMMS
- Ground Control COMMS instrument shell for later OpenAI integration

There is no vehicle-specific physics. Saturn, Gemini, Apollo, Mercury, Agena, fictional spacecraft, debris, planets, and stages all use the same live-object system.

## Install

1. Upload the repository contents to your web folder, for example:
   `/moon/`

2. Edit `db.php` and enter the MySQL host, database name, username, and password.

3. For a fresh database import:
   - `schema.sql`
   - then `seed_universe.sql`

   If you already installed the earlier pre-cockpit schema, **do not re-import schema.sql**.
   Instead run `migrations/2026-10-28_cockpits.sql` once.

4. Open:
   - `/moon/index.php`

5. Set a server cron to run `cron.php` once per minute.

Example CLI cron command:

```
* * * * * /usr/bin/php /full/server/path/to/moon/cron.php >/dev/null 2>&1
```

The cron process stays alive for about 58.5 seconds and updates physics approximately once per second. Physics does **not** assume one second passed. Each update reads the database timestamp and advances only the elapsed real time. A MySQL named lock prevents overlapping workers from double-processing the same time.

## Real-time behavior

The universe uses real wall-clock time.

If a physics update is delayed by 2.3 seconds, the next update processes 2.3 seconds in smaller physics steps. If the worker is behind for longer, each call processes a bounded catch-up slice and subsequent calls continue catching up.

Mission elapsed time can be derived from `missions.started_at`; there is no separate game clock.

## Definitions versus live objects

`object_definitions` describes reusable designs.

Examples:

- Apollo Command Module
- Saturn V S-II
- Gemini spacecraft
- Agena target vehicle

`live_objects` contains actual physical instances in the running universe.

A definition can therefore be instantiated many times into different missions without duplicating its design.

## Dry mass rule

`object_definitions.dry_mass_kg` is the complete dry mass of that object/component.

Engine definition dry mass is metadata and is **not automatically added** to live-object mass. This prevents published stage dry masses, which often already include installed engines, from being counted twice.

Current dynamic mass is:

```
object dry mass + remaining propellant
```

Attached components are summed into the root stack mass.

## Attachments

A free object has:

```
is_attached = 0
parent_object_id = NULL
```

An attached object has:

```
is_attached = 1
parent_object_id = <live parent id>
```

During phase-one point-mass physics, attached objects inherit the root object's position, velocity, and orientation. Relative attachment offset fields are already stored for later rigid-body rendering and collision work.

When detached, the object retains its current state and becomes independently simulated.

## Propulsion

Cockpit or control pages should change engine state, not directly edit velocity.

The physics chain is:

```
control/instrument
    -> engine state
    -> thrust + propellant use
    -> force/acceleration
    -> central physics integration
```

The same rule will be used later for RCS, guidance, docking, and other systems.

## Cockpits and instruments

Instruments are reusable PHP includes stored in `panels/` and registered in `instrument_definitions`.
Each instrument declares whether it belongs to ground control, a cockpit, or both.

`cockpit_definitions` describes reusable cockpits such as an Apollo Command Module cockpit,
Gemini cockpit, Lunar Module cockpit, or a test flight deck.

`cockpit_panels` maps each cockpit's panel slots to reusable instruments.

Use `admin/cockpits.php` to create a cockpit, optionally copy an existing layout,
and choose the instrument in each square.

An `object_definition` can select a `default_cockpit_id`. When the definition is instantiated,
that cockpit is copied onto the resulting `live_object`. A specific live object can also override
its cockpit from `admin/live_objects.php`.

Open a live spacecraft with:

```
/cockpit.php?object_id=123
```

The cockpit page establishes object 123 as the controlled-object context. Every instrument in that
cockpit therefore reads or commands that object rather than hard-coding Apollo, Gemini, or another vehicle.

The ground Command Center remains separate. Its layout is still stored in `command_center_panels`
and edited with `admin/panels.php`.

The Ground Control COMMS panel is intentionally an API shell right now. It already receives the
active live-object context when used in a cockpit, but no OpenAI API key or external call is committed
to this public repository.

## SQL examples

See `sql_examples/` for reusable patterns for adding component and engine definitions.

## Important security note

This repository is public. Do not commit real database credentials or API keys.
