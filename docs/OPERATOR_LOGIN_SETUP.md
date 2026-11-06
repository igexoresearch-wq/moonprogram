# Moon Program: Standard Window Cleaning login and database

Moon is installed at `/moon` inside the Standard Window Cleaning web root.

**No Moon-specific login, password, or database credentials.**

## Authentication

- All Moon pages use `moon/shared/operator_security.php`.
- The guard reads the existing Back Office 5 `SWCSESSION` session and, when present
  with its own required DB include, executes
  `/backoffice5/dbfiles/auth.php` → `require_login()`.
- The canonical SWC login is used for sign-in; `/moon/login.php` forwards to
  `/backoffice5/login.php`. If production's SWC login is at another URL,
  update **only** `moonSimLoginUrl()` in the shared security file.
- The guard supports an SWC session check if the production auth.php's own
  `dbfiles/db.php` dependency is absent (it is not in the Customer Relations
  repository snapshot). This fallback does not create Moon credentials.
- `moon/logout.php` logs out of the shared SWC session, not just Moon.
- Worker token authentication remains independent for web-triggered physics;
  CLI cron remains independent of browser login.

## Database

`moon/physics/db.php` loads `../db.php` from the SWC website root.
The live file defines `$db_host`, `$db_user`, `$db_password`; the connection
selects MySQL schema `moonland` with that account.
The Customer Relations GitHub copy of root db.php loads database credentials
from environment values; production must already have those configured.
This does not add or change passwords or create database tables.

## Deployment

Upload a fresh Moon repository folder into the same SWC web root as
`db.php` and `backoffice5/`. No separate config file is needed.
Open the SWC login, sign in, then visit `/moon/index.php`.

**Important:** Verify the actual production paths and SWC login endpoint.
GitHub contains `Root/backoffice5/dbfiles/auth.php` and `Root/db.php`,
but not `Root/backoffice5/dbfiles/db.php` or the canonical login page.
If production differs, deployment integration may require adjusting one
shared path; do not edit individual cockpit pages.

Source audit/commits are not live-server integration tests.
