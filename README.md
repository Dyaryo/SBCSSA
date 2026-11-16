# E-Sabong Virtual Console

A multi-terminal, role-based console for running sabong (cockfight) events —
**Supervisor → Game Master → POS Teller** — on one computer or a small LAN.

> **Play money only.** Every balance is a *virtual credit with no cash value*.
> There is no cash-in, cash-out, e-wallet, payment gateway or real-money payout
> anywhere in the code. This is an operations and record-keeping system for
> events run with credits, and it is not a gambling product.

---

## 1. What you get

| Role | What they do |
|---|---|
| **Supervisor** | Overview and live floor (cards running, open windows, winners road), reports and sealed snapshots, a block-by-block mobile report, accounts and virtual credits, workstation registry, POS shifts and force-close, audit log, reconciliation |
| **Game Master** | Create cards with their event-form settings (planned fights, stopper, draw ratio, TV share, bomba, payout display), schedule fights, switch sides open/locked, open/close/start/grade, regrade a wrong call, next-fight creation, bet history, live-feed dashboard |
| **POS Teller** | Pay in and pay out only: shifts, quick-amount bet pad (up to 100,000) with TAB/F2/F3/F4 keys, automatic receipt printing, payout queue, slip lookup with F9 scan, history |

Shared engine highlights:

- Append-only credit ledger; every balance change is a `wallet_transactions` row.
- Guarded fight state: `scheduled → betting_open → betting_locked → live → settled` (plus void/cancelled).
- Meron, Wala and Draw as real priced sides; draw pays the card ratio (×7 default).
- Per-side **Meron Locked / Wala Locked** when betting runs one-sided.
- Result **regrade** with ledger reversal, blocked once a winning slip has been paid out.
- Winners road (bead plate) on the arena board, GM dashboard, fight history and supervisor floor.
- Arena LED scoreboard at `/board` — public, read-only, no login, no slip data.
- Receipts with barcode **and** a readable MANUAL CODE, plus QR; claim slips for winners.
- Hash-chained report snapshots and a `system:verify` ledger check.

---

## 2. Requirements

- PHP **8.2+** with `pdo_mysql` / `pdo_sqlite`, `mbstring`, `openssl`
- Composer 2.x
- SQLite (zero setup) or MySQL/MariaDB (e.g. XAMPP)
- Any modern browser. No internet, npm build or CDN is needed at runtime.

---

## 3. Localhost setup (Windows / XAMPP)

From the project folder:

```bat
cd esabong-system
scripts\install.bat
scripts\start-server.bat
```

Then open <http://localhost:8000>.

`install.bat` installs Composer dependencies, creates `.env`, generates the app
key, migrates and seeds, wipes the demo card so the console starts empty, and
verifies the ledger.

Manual equivalent:

```bat
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan system:reset --force
php artisan serve --host=0.0.0.0 --port=8000
```

### Using MySQL / XAMPP

Start **Apache** and **MySQL** in the XAMPP control panel, create a database
(for example `esabong`), then set in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esabong
DB_USERNAME=root
DB_PASSWORD=
```

Then run `php artisan migrate --seed`. On a fresh machine, `php artisan migrate`
(not `migrate:fresh`) keeps existing data.

### Accounts

The six accounts are `supervisor`, `gm.north`, `gm.south`, `pos01`, `pos02`
and `pos03`. On a commissioned system their passwords are unique and are kept
in the **offline handover sheet**, never in this repository or the ZIP.

If a password is lost, sign in as Supervisor → **Accounts** → **Reset** on that
row and record the new one in the handover sheet. Fund teller tills from the
same screen with **Grant** before the first bet.

A fresh `php artisan migrate --seed` still creates the development seed
accounts from `DatabaseSeeder`; replace those passwords immediately after a
fresh install.

The install starts with **no events, fights, bets or shifts** — that is
intentional. Create the first card as the Game Master.

---

## 4. Sharing it over the LAN

The server PC runs the console; every other device only needs a browser.

1. Start the server: `scripts\start-server.bat` (runs on all interfaces, port 8000).
2. Find the server's IPv4 address:

   ```bat
   ipconfig
   ```

   Look for `IPv4 Address` on the active adapter, e.g. `192.168.1.9`.
3. On a terminal, open `http://192.168.1.9:8000`.
4. If it does not load, allow PHP through Windows Firewall for **Private**
   networks, or add an inbound rule for TCP port 8000.
5. Sign in per role. One account per browser window: use a normal window plus
   private/incognito windows (or different browsers). The login page explains
   this too.

### Opening the wall display

Put the TV/projector browser on `http://SERVER-IP:8000/board`. It is public and
read-only, needs no account, and refreshes itself. Use full-screen/kiosk mode.

### Optional HTTPS

`docs/NETWORK-SETUP.md` and the `scripts\make-certs.bat`, `scripts\start-https.bat`
and `scripts\trust-cert.bat` helpers set up a local certificate and terminate
TLS on port 8443. Re-run the certificate script whenever the server IP changes.

---

## 5. Daily flow in five minutes

1. Supervisor: sign in, confirm tills, check **Live floor**.
2. Game Master: **Events** → create a card with its stopper, draw ratio, TV share
   and bomba; add fights; **Open fight**.
3. Teller: open a shift, type the amount (TAB focuses it), press F2 Meron,
   F3 Wala or F4 Draw. The receipt prints automatically.
4. Game Master: watch bets arrive on the console, lock a side if needed,
   **Close fight → Mark live → declare the result**.
5. Teller: winning slips appear under **Pay out**; scan the barcode or press F9
   and type the MANUAL CODE; record the payout.
6. Supervisor: check reports, seal a snapshot, run `php artisan system:verify`.

Keyboard on the POS: **TAB** amount, **F2** Meron, **F3** Wala, **F4** Draw,
**F9** jump to the payout scan box.

### Terminal attendance (automatic shifts)

1. Supervisor → **Accounts** → create the teller and pick a **Terminal
   (auto attendance)**. One terminal belongs to one teller; assigning it moves
   the terminal off its previous teller.
2. That teller signing in on the terminal opens their shift automatically and
   shows *“Clocked in on POS-001 — shift #N is open.”*
3. Signing out closes the shift and seals a `teller_shift` snapshot — but only
   when no bet is still running. If slips are unsettled the shift stays open,
   and the Game Master or Supervisor closes it from **POS shifts**.

Tellers without a terminal still open and close shifts by hand. The login page
fills the hidden terminal fingerprint from the browser (`public/js/app.js`);
set `POS_STRICT_DEVICE_BINDING=true` to refuse a login from a different
terminal than the one assigned.

### Mobile report app (Android / iPhone)

Sign in as Supervisor and open **Mobile report** in the sidebar (or
`/supervisor/mobile` on the phone). It is an installable web app: on Android
use the **Install app** button, on iPhone use **Share → Add to Home Screen**.
It opens full-screen like a native app and works over localhost or the LAN
HTTPS address; installing from a plain `http://` LAN address only works in
browsers that allow it, so use the included HTTPS setup for phones.

Every **ten graded fights** form a block. The app shows the block’s overall
summary — fights, stake, payouts, bets — plus **Draw** (count, draw stake paid)
and **Cancelled** (count, credits refunded), the Meron/Wala calls, the fights in
the block, and how many fights remain before the next report. It refreshes on
its own while open; sign-in is still required because the report contains floor
totals.

This is a Progressive Web App, not a Play Store or App Store build: there is no
APK/IPA to sideload, no store account and no review. The manifest and service
worker (`public/manifest.webmanifest`, `public/sw.js`) provide the icon and an
offline app shell.

Printing: receipts open the browser print dialog automatically after a bet. For
unattended counters, launch the kiosk browser with `--kiosk-printing` so it
prints without asking.

---

## 6. Handing it to someone else

Give them the project folder (or the zip) **without** your live `.env`,
`storage\logs`, `storage\certs`, `backups` or database dumps. Then:

1. They install PHP 8.2+ and Composer.
2. `cd esabong-system && scripts\install.bat` — this builds a fresh database and
   prints the seed logins.
3. They change the six passwords, grant teller credits, and create their own
   event.
4. They start with `scripts\start-server.bat` and share the LAN URL.

If you are publishing the source, keep the virtual-credit nature and the
no-real-money limitation in your description; those are part of the product,
not just a disclaimer.

Useful commands:

```bat
php artisan test              REM full feature suite
php artisan system:verify     REM ledger + report chain check
php artisan system:backup     REM database dump into backups\
php artisan system:reset --force   REM wipe events/fights/bets/shifts, keep accounts
php artisan system:reset --force --till=5000   REM same, leaving 5,000 cr in every till
```

`system:reset` is destructive: it clears the operational floor (cards, fights,
bets, shifts, ledger movements, audit and login history), zeroes every wallet
unless you pass `--till=`, and keeps user accounts and workstations. Use it to
start a fresh event day. The same reset is available in the console under
**Administration → Floor reset**, with the same choice of starting till; the
Supervisor running it stays signed in so the result can be confirmed.

---

## 7. Configuration

`config/pos.php` (all overridable in `.env`):

| Key | Default | Meaning |
|---|---|---|
| `POS_MIN_BET` | `1` | Smallest bet |
| `POS_MAX_BET` | `100000` | Largest bet |
| `POS_MAX_BETS_PER_FIGHT` | `20` | Per-terminal cap on one fight |
| `POS_MAX_TOP_UP` | `100000` | Largest single credit grant to a teller |
| `POS_BIG_BET` | `10000` | Stake that flashes BIG BET on the arena board (side only, no slip details) |
| `POS_LOW_TILL` | `1000` | Low-till warning threshold |
| `POS_STRICT_DEVICE_BINDING` | `false` | Require the teller to sign in on their assigned, approved terminal |
| `POS_SESSION_TIMEOUT` | `480` | Session lifetime in minutes |

Card settings (planned fights, bet stopper, draw ratio, TV total bets, bomba,
payout display) live on the event form in the Game Master account.

---

## 8. Troubleshooting

| Symptom | Fix |
|---|---|
| `SQLSTATE[HY000] [2002] connection refused` | MySQL is not running — start it in XAMPP, or switch `.env` to SQLite |
| Page loads on the server but not on the LAN | Firewall: allow PHP/port 8000 on private networks; check the IP with `ipconfig` |
| Port 8000 already used | `php artisan serve --host=0.0.0.0 --port=8080` |
| `419 Page Expired` | Refresh the page; the session cookie expired |
| Blank page after editing views | `php artisan view:clear` |
| Print dialog does not appear | Allow pop-ups/printing for the site, or use `--kiosk-printing` |
| Barcode/QR damaged on a slip | Type the MANUAL CODE printed under it into the lookup box (F9) |
| Wrong result declared | Game Master → fight console → **Regrade result** (only before winnings are paid out) |

---

## 9. Project layout

```
app/
  Http/Controllers/{Supervisor,GameMaster,Pos}
  Models/                 users, events, fights, bets, shifts, ledger, reports
  Services/               WalletService, BettingService, ReportService, ShiftSlipService
  Support/                Barcode, QrService, WinnersRoad
public/css, public/js     offline UI assets (no build step)
resources/views/          Blade screens per role, receipts, board
database/migrations/      schema
database/seeders/         accounts and rules only (empty floor)
docs/                     network, operations, security, quick reference, licensed-launch checklist
scripts/                  install, serve, HTTPS, backup, scheduled checks
tests/Feature/            role chain, betting, draw, locks, prints, live feeds
```

---

## 10. Known limitations

- One server, one currency, one arena. No multi-tenant hosting.
- Built for a trusted LAN; not hardened for direct exposure to the internet.
- No WebSocket push; operational screens poll for changes. On the POS, a fight opening or closing swaps the betting cards in place with a short slide (amounts already typed are kept); other changes reload the page.
- No SMS/email, no PDF export (CSV and print views only).
- Report snapshots are immutable records of their moment; live data can change
  after a snapshot is sealed.
- Not security-audited. Read `docs/SECURITY.md` before real operational use.
