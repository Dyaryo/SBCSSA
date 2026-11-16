# Operations guide

Day-to-day running, verification, backups and recovery.

---

## 1. Daily routine

### Before the first fight

1. Start Apache and MySQL (XAMPP Control Panel).
2. Confirm the server URL responds from one POS terminal.
3. `php artisan system:verify` — expect `Ledger: OK` and `Report chain: OK`.
4. Supervisor: check *Overview* — no unexpected open shifts or balances.
5. Game Master: open the event, confirm the fight list and odds.
6. Each teller: sign in, **open a shift**, confirm the credit balance.

### During the event

- Game Master: **Open betting** → bets arrive → **Lock betting** before the
  fight starts → **Mark live** → **Settle** with the correct winner.
- Never settle a fight from memory of the odds: the console shows the exact
  fixed odds and the payout total before you confirm.
- Voids require a written reason; they are visible in the audit log.

### After the event

1. Settle or void every fight. No fight should stay `betting_open` overnight.
2. Supervisor: force-close any shift a teller left open (requires a reason) — open **POS shifts → the shift** first.
   It lists the unsettled bets and the credits still owed, and keeps showing them after the close, because a
   forced close seals the totals without settling or refunding a single bet. The **Close** confirmation names the
   fights that still need a result, and the close writes their count and staked total into the audit log.
3. Tellers: close their own shifts; the shift report is sealed automatically.
4. Supervisor: generate and **seal** the event and system-monthly reports.
5. Supervisor → **Reconciliation** for today: expect `Issues found: 0`, no open shifts and no unsettled fights.
6. `php artisan system:verify`.
7. Run a backup: `scripts\backup-database.bat`.

---

## 2. Verification

```bat
php artisan system:verify
```

It performs two independent checks:

1. **Ledger integrity** — for every wallet, sums every credit and debit row and
   compares it with the stored balance. Any drift is listed with the difference.
2. **Report chain** — re-hashes every sealed snapshot and verifies each
   `prev_hash` link.

Exit code is non-zero if either check fails, so you can schedule it and alert on
failure.

---

## 3. Ledger mismatch: what to do

A mismatch means the wallet balance and its transaction history disagree. This
should never happen through the application; it means someone edited the
database, a restore was partial, or a very old bug is present.

1. **Stop writes** — put the site in maintenance: `php artisan down`.
2. Export the affected user's rows:

```sql
SELECT id, type, direction, amount_credits, balance_after, reference_no, created_at
FROM wallet_transactions WHERE user_id = <id> ORDER BY id;
```

3. The correct balance is the net of that history:

```sql
SELECT SUM(CASE WHEN direction = 'credit' THEN amount_credits ELSE -amount_credits END)
FROM wallet_transactions WHERE user_id = <id>;
```

4. **Do not** run `UPDATE virtual_wallets`. Instead record a documented
   `adjustment` through the application (or a one-off tinker call to
   `WalletService::credit/debit`) with a reason naming the incident, so the
   correction itself is in the ledger and the audit trail.
5. Record who authorised it, then `php artisan up`.
6. Review `audit_logs` around the timestamp of the drift.

---

## 4. Backups

### SQLite (default)

The whole database is `database/database.sqlite`. Copy it while the app is idle,
or use `scripts\backup-database.bat`.

### MySQL

```bat
php artisan system:backup
```

The command reads host, database, user and password **from `.env`** — nothing is
typed on the command line, so the password never appears in the process list or
the shell history. It writes `backups\esabong-YYYYMMDD-HHMMSS.sql`, refuses to
keep a file that is not a real dump, and prunes copies older than 30 days
(`--keep=0` disables pruning, `--dir=` changes the folder).

Run it through `scripts\backup-database.bat` if you prefer a double-click; that
simply calls the same command.

### Schedule

- Nightly: already wired in `routes/console.php` — 23:30 `system:nightly`,
  23:45 `system:backup --keep=30`. Register the Windows trigger once with
  `scripts\schedule-checks.bat` (see §6).
- Weekly copy the `backups` folder to an external drive kept off the machine.
- Keep at least 30 daily + 12 monthly copies.

### Restore walkthrough

Do this on a spare PC, never on the live server during an event.

1. Stop the app (`php artisan down` on the live box) and stop MariaDB.
2. Create an empty database and the app user (see §5), or restore over the
existing one:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS esabong; CREATE DATABASE esabong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root esabong < backups\esabong-20261111-234500.sql
```

3. Re-apply the app user's grants if the database itself was recreated (§5).
4. `php artisan config:clear` then `php artisan system:verify` — it must report
   `Ledger: OK` and `Report chain: OK`. A restored database that fails the
   chain check is a corrupt or partial dump: restore the previous copy.
5. Sign in as each role, open one fight console, place one small bet, void it,
   and confirm the reconciliation screen shows `Issues found: 0`.

### Restore drill (do this once a month)

1. On a spare PC, install XAMPP + the same PHP version.
2. Copy the project, restore the dump (or the `.sqlite` file).
3. `php artisan system:verify` — must report OK.
4. Sign in, open a fight console, confirm the history matches the production
   screenshots you took.

---

## 5. Switching from SQLite to MySQL

> **This machine is already on MySQL/MariaDB 10.4** — database `esabong`,
> application user `esabong_app` (password lives in `.env` only). Start the
> server with XAMPP Control Panel → MySQL → Start, or:
> `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini`

1. Start MySQL in XAMPP.
2. Create the database and app user (see `docs/SECURITY.md`):

```sql
CREATE DATABASE esabong CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'esabong_app'@'127.0.0.1' IDENTIFIED BY '<strong password>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON esabong.* TO 'esabong_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```
3. Edit `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esabong
DB_USERNAME=esabong_app
DB_PASSWORD=your-password
```

4. Clear cached config and migrate:

```bat
php artisan config:clear
php artisan migrate --force
php artisan db:seed        REM only for a fresh demo install
php artisan system:verify
```

Migrating with existing SQLite data? Dump the data first and import it, or
re-create the event from the UI — the seeder is demo data and must not be run
over production records.

---

## 6. Scheduled tasks

The schedule lives in `routes/console.php`:

| Time | Command | What it does |
|---|---|---|
| 23:30 | `system:nightly` | Ledger integrity, report hash chain, and today's reconciliation. Every problem is written to `storage/logs/laravel.log`; exit code is non-zero on failure |
| 23:45 | `system:backup --keep=30` | Database dump into `backups`, old copies pruned |

Register the trigger once (Administrator prompt the first time):

```bat
scripts\schedule-checks.bat
```

That creates the task **E-Sabong Nightly Checks**, which calls
`scripts\run-scheduled.bat` every 30 minutes; Laravel decides what actually
runs. Check it with `schtasks /Query /TN "E-Sabong Nightly Checks"`, review
`storage/logs/nightly-check.log` and `storage/logs/scheduler.log`.

To run the sweep immediately (recommended before handing over a shift):

```bat
php artisan system:nightly
php artisan system:nightly --date=2026-11-10   REM audit a past day
```

Remove the task later with
`schtasks /Delete /TN "E-Sabong Nightly Checks" /F`.

### Starting before Windows sign-in

The console must be reachable after a reboot without anyone signing in at the
server. Six boot tasks run as the dedicated standard account `svc-esabong`, with
`LogonType=Password` and an "At startup" trigger, so the Windows login screen is
irrelevant to whether the POS terminals can reach the console:

| Task | Port / process |
|---|---|
| `E-Sabong MySQL` | 3306 |
| `E-Sabong Application` | 8000 (`php artisan serve`) |
| `E-Sabong Web Proxy` | 8443 (HTTPS terminator) |
| `E-Sabong Scheduler` | `php artisan schedule:work` |
| `E-Sabong Watchdog` | restarts any of the above, every 5 minutes |
| `E-Sabong Verify Autostart` | writes the boot health check |

Set it up (or repair it) once, elevated:

```bat
powershell -ExecutionPolicy Bypass -File scripts\configure-service-account.ps1
```

It creates the account, grants *Log on as a batch job* (PowerShell registration
leaves that out, and without it a task shows `Ready` and never starts), grants
the account read access to the project and write access to `storage`,
`bootstrap\cache` and `C:\ProgramData\E-Sabong`, backs up the old task
definitions to the Desktop, removes superseded at-logon tasks, registers the six
tasks, and then **starts each one to prove it launches**. It ends with `PASS` or
with the failing task and its result code; the same report is written to
`C:\ProgramData\E-Sabong\service-account-setup.txt`.

Notes:

- The account password is stored once in `E-SABONG-SERVICE-ACCOUNT.txt` on the
  Desktop and is **reused** on later runs. Changing it by hand breaks every task
  with `0x8007052E`; re-run the script and it detects the mismatch and repairs
  both.
- `scripts\register-autostart.ps1` and `scripts\register-watchdog.ps1` are
  superseded: they registered at-logon tasks that left the console down after a
  reboot. They now refuse to run.
- The project lives in a OneDrive folder. Keep that folder set to *Always keep on
  this device*, or a dehydrated file can make a startup task fail at boot.
- After moving the project folder, run `php artisan config:clear` and
  `php artisan config:cache`. A cached `bootstrap/cache/config.php` still pointing
  at the old path makes every page return 500 even though the services start.

---

## 7. Logs and what to watch

| File / table | Watch for |
|---|---|
| `storage/logs/laravel.log` | exceptions, failed jobs, DB errors |
| `login_events` | bursts of `failed_login`, `blocked_login` |
| `audit_logs` | `wallet.seeded`, `fight.voided`, `bet.voided`, `account.*` |
| `report_snapshots` | unexpected gaps or `system:verify` chain failures |
| Apache `error.log` | 500s after a deployment |

A healthy week has: no `failed_login` spikes, no voids without reasons, every
shift closed, and `system:verify` OK on every run.

---

## 8. Common problems

| Symptom | Cause | Fix |
|---|---|---|
| "Insufficient virtual credits" | Teller balance too low | Allocate credits (Supervisor → Accounts → Grant) |
| "Open a shift before accepting bets" | Shift closed/never opened | Teller → Open shift |
| "Betting is closed for this fight" | Fight locked/settled | Game Master reopens only from `scheduled`/`betting_locked` — for a settled fight, schedule a new one |
| "This looks like a duplicate submission" | Double click | Wait 5 seconds and retry once |
| Reports empty | Wrong scope/reference | Pick the fight/event/shift in the form |
| Everyone logged out | `APP_KEY` changed or sessions table cleared | expected; sign in again |
| 500 after editing `.env` | cached config | `php artisan config:clear` |

---

## 9. Updating the application

1. Back up the database.
2. Copy the new code over the old (keep `.env`, `storage/`, `database/`).
3. `composer install --no-dev`
4. `php artisan migrate --force`
5. `php artisan config:cache` `php artisan route:cache`
6. `php artisan system:verify`
7. Smoke test: log in as each role, open one fight console, place one small bet,
   void it.

Never run `migrate:fresh` on a database holding real records — it destroys them.

---

## 10. Running without internet

The console does not need an internet connection. Everything it uses is on the
server itself: MySQL for data, sessions, cache and the queue; local files for
receipts and certificates; and no third-party scripts, fonts or CDNs. Each page
loads only its own `/css` and `/js` files, so an outage cannot leave the teller
screen half-styled.

What *is* required is a local network between the server and the terminals
(switch or router, with or without an uplink).

Points to keep in mind for an offline venue:

- **Firewall.** `scripts/allow-lan-firewall.ps1` (run elevated) creates the
  inbound rule for TCP 8443 on **all** profiles. This matters because when the
  uplink drops, Windows often re-classifies the network as Public or
  "Unidentified" and rules scoped to Private stop applying — terminals then
  cannot reach a console that is running perfectly well. Re-run the script
  after resetting the firewall or adding a network adapter.
- **Address.** Terminals open **`https://cssa:8443/login`**. `cssa` is a short
  name for this PC: `scripts/set-lan-name.ps1` (run elevated) adds
  `192.168.1.15  cssa` to the server's hosts file, registers `CSSA` as an extra
  NetBIOS name so other Windows terminals on the LAN find it without setup, and
  restarts the HTTPS proxy to serve the certificate that carries `cssa` in its
  subjectAltName (the CA is unchanged, so terminals that trust it stay trusted).
  If a terminal still cannot resolve the name, add the same one-line hosts entry
  there — `C:\Windows\System32\drivers\etc\hosts` needs administrator rights.
  The numeric address `https://192.168.1.15:8443` keeps working and is also in
  the certificate.
- **Fixed IP.** `192.168.1.15` is handed out by DHCP, and the name points at it.
  Give the server a DHCP reservation on the router (or a static IP); if the lease
  changes, re-run `scripts/set-lan-name.ps1` so the hosts entry and certificate
  follow the new address.
- **Port 8000 is localhost only** (`artisan serve --host=127.0.0.1`). It is not a
  LAN address; tellers always use 8443, which also carries the secure cookie.
- **MySQL listens on `0.0.0.0:3306`** (XAMPP default) and is reachable from the
  venue network. On a machine that will sit on a shared Wi-Fi, set
  `bind-address=127.0.0.1` in `C:\xampp\mysql\bin\my.ini` and restart MySQL;
  nothing in the app needs remote database access.

---

## 11. Demo accounts (play money)

Created by `scripts/make-demo-accounts.php`, which is safe to re-run: it resets
the password and role and leaves the wallets balanced. The passwords use only
lowercase letters and digits, so they need no Shift key and can be typed on any
venue keyboard.

There is exactly **one** Supervisor: `supervisor` (System Supervisor). The old
`demo.supervisor` login was deleted, and the script no longer creates it — the
venue should not have a second admin account to explain away. Change the
Supervisor password from Supervisor → Accounts → Reset.

| Username | Password | Role | Lands on |
|---|---|---|---|
| `supervisor` | the owner's own password — not recorded in this repository | Supervisor | `/supervisor` |
| `demo.gm` | `master2026` | Game Master | `/gm` |
| `demo.teller` | `teller2026` | POS teller (terminal `POS-DEMO`) | `/pos` |
| `demo.teller2` | `teller2026` | POS teller (terminal `POS-DEMO-2`) | `/pos` |

A teller belongs to exactly one workstation, so a second till needs its own
teller: `demo.teller2` (created by `scripts/make-second-demo-terminal.php`) sits
on `POS-DEMO-2`. Each teller terminal must be bound once from the browser that
will use it (POS monitor → Bind this terminal); supervisor and Game Master logins
are not device bound. Five failed attempts from one address lock sign-in for five
minutes.
