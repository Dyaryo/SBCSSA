# Operator quick reference

One page for the counter. Print it and keep it beside each terminal.
Every amount is a **virtual credit** — there is no cash-in or cash-out anywhere
in this system.

---

## POS teller — start of shift

| Step | Where | Action |
|---|---|---|
| 1 | Browser | Open `https://SERVER-IP:8443` (or `http://SERVER-IP:8000` if HTTPS is not set up) |
| 2 | Login | Your username + password. Never share it, never let a supervisor use your login |
| 3 | Betting desk | Check **Credits available** and **Workstation** at the top |
| 4 | Betting desk | Enter an opening note (e.g. "Counter 1, morning") → **Open shift** |
| 5 | — | Betting buttons are disabled until the shift is open. This is normal |

## POS teller — accepting a bet

1. Confirm the **fight number** on screen matches the fight in progress.
2. Type the amount, then press **Bet RED** or **Bet BLUE** — the side is chosen by the button, not by a dropdown.
3. Check the confirmation message: `Bet #123 recorded: RED for 500 credits.`
4. The stake leaves the wallet immediately. If the message says *duplicate submission*, wait 5 seconds and retry once.
5. Hand the printed slip: **My latest bets → slip** opens the receipt → **Print**.

If the teller's balance is too low: stop, and ask the Game Master to allocate credits. Never accept a bet "on the side".

## POS teller — end of shift

1. Wait until every bet on the shift is settled (no `active` bets).
2. Betting desk → **Close shift** with a closing note. The shift report is sealed automatically.
3. Open the **closeout slip**, print it, sign it, hand it to the Game Master.
4. A shift with unsettled bets cannot be closed — ask the Game Master to settle or void first.

## POS teller — paying out a winning ticket

1. Take the ticket and scan its QR code — or type the code printed under it — into **Pay out — slip lookup**. The same slips are already listed on the desk under **Pay out — winning slips to hand over**.
2. Green **“Pay out N credits”** means a winning slip. The credits already moved into this terminal's wallet when the Game Master graded the fight — hand the player the amount shown and keep the ticket.
3. Press **Record payout** so the slip is marked paid and drops off the queue. A second scan then shows **“Already paid out”** — never pay it again.
4. **“No payout”** = the losing side. **“Not settled yet”** = never pay on it; the fight has no result.
5. A slip issued by another terminal says so — send the player to that counter.
6. A code that does not match the record has been altered. Do not pay; call the Game Master.

---

## Who does what

| Task | Owner |
|---|---|
| Create a POS teller login, teller code and opening credits | **Supervisor → Accounts** |
| Top up a till — the dashboard flags tills below `POS_LOW_TILL` | **Supervisor dashboard → Top up** (pre-filled shortfall), or **Accounts → Grant** for another amount |
| Schedule fights, open/lock betting, grade meron · wala · draw · cancelled, create the next fight | **Game Master** |
| Pay in on bets, pay out winning slips | **POS teller** |
| Reset a POS password, suspend a terminal, force-close a stuck shift | **Supervisor → Accounts / POS shifts** |
| See what a till still owes before closing it | **Supervisor → POS shifts → shift #** (unsettled bets stay visible after a forced close) |

The Game Master's teller page is read-only: no accounts, no credits, no password resets, no suspensions, no shift closures. Ask the Supervisor — every allocation and intervention is recorded in the ledger and audit log with the Supervisor as the actor.

---

## Game Master — running a fight

| Step | Action | Notes |
|---|---|---|
| 1 | **Fights → Console** | Confirm both fighters and the odds |
| 2 | **Open betting** | Only from `scheduled`. Teller screens now show the fight |
| 3 | Watch the monitor | Live stakes per side update on refresh |
| 4 | **Lock betting** | Before the fight starts. No new bets after this |
| 5 | **Mark live** | Records the start |
| 6 | **Declare result** | **Meron** (red) or **Wala** (blue) pays that side; **Draw** or **Cancelled** refunds every stake. Each button shows the amount it will move before you press it |
| 7 | **Cancel** | Needs a written reason. Refunds every stake on that fight |
| 8 | **Next fight** | After grading, the panel on the right creates fight #N+1. You don't pick fighters — the same corners carry over in every fight of an event, and odds are pre-filled |
| 9 | **Stop ratio** | Optional cap: a payout can never exceed *stake × ratio*, even when the posted odds are higher. Leave it blank for pure fixed odds |
| 10 | **Event type** | Every event has a title. **Regular** is a fight day. **Derby** also needs at least two entry names, which are listed on the event |
| 11 | **Order of fights** | A new fight cannot be created until the previous one has a declared winning side. Fight numbers must climb; grade #2 before #3 exists |
| 12 | **Start empty** | `php artisan system:reset --force` clears every event, fight, bet, shift and ledger row but keeps the logins. Add `--till=150000` to leave a starting balance in each till |

Never settle a fight twice — settled and void fights are terminal. Schedule a new fight instead.

## Game Master — tellers and terminals

- **Tellers** — create accounts, allocate credits, reset passwords, suspend. A suspended teller cannot sign in.
- **POS monitor** — bind a browser to a workstation. After binding, approve it as Supervisor.
- **Force-close shift** — requires a reason, appears in the audit log, seals a snapshot.
- **Bet history** — void an individual bet only with a reason.

---

## Supervisor — daily close

1. **Overview** — confirm no unexpected open shifts or balances.
2. **Reconciliation** — pick today's date → **Rebuild**. Expect `Issues found: 0`.
3. Any mismatch: stop, read `docs/OPERATIONS.md §3` before touching anything.
4. Confirm **0 open shifts** and **0 unsettled fights**.
5. **Reports** — generate the event or monthly report, then **Seal snapshot**.
6. `php artisan system:nightly` → ledger, report chain and today's reconciliation in one run. Expect `Nightly check: OK`.
7. Check `backups\` holds today's dump. The nightly job takes it at 23:45 automatically — see `docs/OPERATIONS.md §6`.
8. Sign the printed closeout slips and file them with the day's reconciliation CSV.

---

## Who can do what

| | Supervisor | Game Master | Teller |
|---|---|---|---|
| See every event | yes | own events only | no |
| Create events / fights / odds | no | yes | no |
| Open, lock, settle, void a fight | no | yes | no |
| Place a bet | no | no | own login only |
| Allocate virtual credits | yes | own tellers | no |
| Edit financial records | **no — read-only** | no | no |
| Reconciliation + reports | yes | own events | no |
| Print receipts / closeout slip | no | closeout slip | own bets, own shifts |

Supervisors monitor; they cannot alter a transaction. That separation is the point —
if a Supervisor ever needs a correction, it goes in as an `adjustment` with a reason,
never as a silent edit.

---

## When something goes wrong

| Screen says | Meaning | Do this |
|---|---|---|
| "Open a shift before accepting bets" | No open shift | Open shift (teller) |
| "Betting is closed for this fight" | Locked or settled | Check the fight number; do not retry |
| "Insufficient virtual credits" | Balance too low | Game Master allocates credits |
| "This looks like a duplicate submission" | Double click | Wait 5 s, retry once |
| "Unsettled bets remain on this shift" | Shift has active bets | Game Master settles or voids |
| Blank page / 500 | Server or DB problem | Tell the Supervisor; check `storage/logs/laravel.log` |
| Slip prints tiny / cut off on the left | Printer paper set to A4, not 80mm | In the print dialog choose the receipt printer and its 80mm roll; the slip already declares an 80mm page |

**Never** reboot the server, clear the browser, or log in as another user to "get
around" an error mid-event — that is how records get corrupted and how an
investigation starts.
