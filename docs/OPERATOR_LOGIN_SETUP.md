# One login for the whole Moon Program

All simulator web stations use `shared/operator_security.php`, which starts the
same `MOON_SIM_SESSION` PHP session. The single login form is `login.php`.
The simulation's cron/physics worker continues to use its separate worker token.

## One-time GoDaddy/cPanel setup

1. In cPanel **File Manager**, identify the website **document root**
   (usually `/home/ACCOUNT/public_html`).
2. Go **one folder above** that document root
   (usually `/home/ACCOUNT/`, **not** `public_html`).
3. Create the file `moon_operator_config.php` there with the following
   contents, replacing the placeholder. Do NOT add this file to GitHub:

```php
<?php
return [
    'username' => 'operator',
    'password' => 'CHOOSE_A_LONG_UNIQUE_PASSWORD_HERE',
];
```

4. Save it. Restrict file permissions to the hosting account if your
   host supports this (e.g. 0600), and confirm it is outside the public
   document root. Never send this file or its password in chat.
5. Upload the latest GitHub Moon Program code. The private configuration
   file stays in place, even if the entire `moon` website folder is replaced.
6. Visit `/moon/login.php`. Log in as `operator` with that password.

The private file takes priority over `MOON_OPERATOR_PASSWORD` and
`MOON_OPERATOR_USER` environment variables, which remain usable as a fallback.
If the file is in the wrong directory and no environment password is configured,
login safely reports `Operator password is not configured on server`.
For custom document roots, place the file immediately in the *parent* of the
actual `DOCUMENT_ROOT`, not necessarily the hosting home directory.

## Behavior

- No individual cockpit, calculation, radio, or mission station should
  perform an independent Basic Authentication password challenge.
- A successful login creates the session. All simulator pages share that
  browser session (each person signs in once on their own browser).
- Logout removes the session.
- Unauthenticated control POST requests receive 401 rather than following
  a login redirect, so they cannot accidentally execute when signed out.
- The database is configured independently using `MOON_DB_*` environment
  variables in `physics/db.php`. This file only solves operator access.
- If browser-native password prompts still appear after this deployment,
  investigate Apache/cPanel Directory Privacy or other `.htaccess` Basic
  Auth rules. Those are separate from the Moon Program PHP login.
