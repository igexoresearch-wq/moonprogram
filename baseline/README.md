# Moon Simulator baseline snapshot

This directory records the known repair baseline so a future coding session can distinguish the original simulator state from later upgrades.

## Source SQL dump

Uploaded source dump:

- database: `moonland`
- SHA-256: `73642d9066f7920747f4daf21e31e84997351db9dffd57366bcde7c773a57bf7`

The complete uploaded dump is not duplicated here because the repository is public and the working simulator only needs a reproducible reset-state snapshot plus the source dump supplied by the project owner.

## Launch/reset snapshot

`reset_snapshot.sql` restores the live CSM/Saturn, LM, mission clock, general systems, engine state, and CM guidance runtime to the same launch-ready baseline used by `physics/reset.php`.

The snapshot intentionally does **not** reset the Moon's position. That matches the current reset behavior. Mission profiles will later define and reset a mission-specific Moon starting state.

## Important

The authoritative simulator reset path remains `physics/reset.php`.

If values in `shared/physical_constants.php` change after historical validation, update both the reset routine and this snapshot together.
