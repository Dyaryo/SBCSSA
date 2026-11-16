# Network setup — one central server, many terminals

```
                    ┌──────────────────────────────┐
                    │   CENTRAL SERVER PC (SSD)    │
                    │  Laravel + MySQL + Apache    │
                    │  fixed IP 192.168.1.10:8000  │
                    └──────────────┬───────────────┘
                                   │
                    ┌──────────────┴───────────────┐
                    │  Gigabit switch / Wi-Fi AP    │
                    └───┬───────────┬───────────┬──┘
                        │           │           │
                 ┌──────┴──┐  ┌─────┴─────┐  ┌──┴────────────┐
                 │ GM PC   │  │Supervisor │  │ POS 1..N      │
                 │         │  │   PC      │  │ tellers       │
                 └─────────┘  └───────────┘  └───────────────┘
```

Every terminal opens a browser to the **same** URL and therefore reads and writes
the **same** database. There are no local copies of data on the terminals.

---

## 1. Give the server a fixed address

Dynamic IPs break every terminal the moment the server reboots.

**Option A — DHCP reservation (best):** in the router, bind the server's MAC
address to a fixed IP (e.g. `192.168.1.10`).

**Option B — static IP on the server:**

```
Control Panel → Network → Adapter → Properties → IPv4
  IP address : 192.168.1.10
  Subnet mask: 255.255.255.0
  Gateway    : 192.168.1.1
  DNS        : 192.168.1.1, 8.8.8.8
```

Verify with `ipconfig` and note the IPv4 address.

---

## 2. Start the application for the LAN

```bat
cd esabong-system
php artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` (not `127.0.0.1`) is what makes it reachable from other PCs.
`scripts\start-server.bat` already does this.

Test from the server: <http://localhost:8000>
Test from a POS terminal: `http://192.168.1.10:8000`

---

## 3. Firewall

Allow inbound TCP 8000 (and 80/443 if you use Apache/HTTPS) on the **private**
network profile only.

```bat
netsh advfirewall firewall add rule name="ESabong HTTP 8000" dir=in action=allow protocol=TCP localport=8000 profile=private
```

Do **not** port-forward 8000 on the router. This system is for the venue LAN, not
the public internet.

---

## 4. Keep the server running unattended

`php artisan serve` stops when the console window closes, and it is a
single-threaded development server. For real operation use Apache (already in
XAMPP) and point a VirtualHost at `esabong-system\public`:

```apache
<VirtualHost *:80>
    ServerName esabong.local
    DocumentRoot "C:/xampp/htdocs/esabong-system/public"

    <Directory "C:/xampp/htdocs/esabong-system/public">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/esabong-error.log"
    CustomLog "logs/esabong-access.log" combined
</VirtualHost>
```

Enable it in `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, restart Apache, then
terminals use `http://192.168.1.10/`.

To auto-start after a power interruption: XAMPP Control Panel → Config → tick
**Autostart** for Apache and MySQL, or register them as Windows services.

---

## 5. Use HTTPS on the LAN

Betting data, sessions and passwords should not travel in plain text, even on a
private network.

The repository ships its own TLS terminator, so no external tool is needed.

1. On the server run `scripts\make-certs.bat`. It creates a local CA and a
   server certificate covering `localhost`, the machine name and the current LAN
   IP, saved in `storage\certs`.
2. Start it with `scripts\start-https.bat`. The app then listens on
   **127.0.0.1:8000 only** and TLS is served on **8443** — plain HTTP is no
   longer reachable from other PCs.
3. On each terminal, copy `storage\certs\ca.crt` over and run
   `scripts\trust-cert.bat` as Administrator (or import it with `certmgr.msc` →
   Trusted Root Certification Authorities → Local Computer).
4. Terminals use `https://192.168.1.10:8443`.
5. Set `APP_URL=https://192.168.1.10:8443` in `.env`, then
   `php artisan config:clear`.
6. Re-run `scripts\make-certs.bat` if the server's LAN IP changes, and re-import
   the new `ca.crt` on the terminals.

`storage\certs\server.key` is a private key: never copy it to a terminal and
keep it out of backups that leave the server.

> Using Apache instead? Point the VirtualHost at the same pair:
> `SSLCertificateFile` → `server.crt`, `SSLCertificateKeyFile` → `server.key`,
> `SSLCertificateChainFile` → `ca.crt`, and forward with `X-Forwarded-Proto` if
> you proxy to the app.

---

## 6. Terminal preparation (each POS PC)

1. Chrome/Edge, full screen (`F11`), homepage set to the server URL.
2. **Disable sleep and screen saver** — a POS PC that sleeps loses its session.
3. Set the Windows clock to sync automatically; every terminal must agree on time
   or shift reports will not reconcile.
4. Log in as the assigned teller, open a shift, and leave the tab on *Betting desk*.
5. Optional but recommended: create a dedicated Windows user with no admin rights
   for the counter staff, and a Chrome kiosk shortcut.

### Terminal binding (recommended)

With `POS_STRICT_DEVICE_BINDING=true`, a teller can only sign in from the
workstation assigned to them:

1. On the POS PC, sign in as the Game Master.
2. *POS monitor → Bind this terminal* → choose `POS-001`, assign the teller.
3. Supervisor → *Workstations → Approve*.
4. Set `POS_STRICT_DEVICE_BINDING=true` in `.env`, then `php artisan config:clear`.

The fingerprint is stored in the browser's `localStorage`; clearing site data or
using a different browser requires re-binding.

---

## 7. Wi-Fi vs cable

- **Wire the server and the POS terminals.** Wi-Fi drops cause failed bets and
  half-open shifts.
- If Wi-Fi is unavoidable, use 5 GHz with a dedicated AP, disable client
  isolation, and keep the AP on a UPS.
- Put the server on a **UPS**. A power cut mid-transaction is the one failure
  that can corrupt a database.

---

## 8. Troubleshooting

| Symptom | Check |
|---|---|
| Terminal cannot reach server | Same subnet? `ping 192.168.1.10`; firewall rule; server bound to `0.0.0.0` |
| Page loads but login fails | Check `storage/logs/laravel.log`; confirm system clock |
| "This terminal is not registered" | Bind the terminal (Game Master → POS monitor) or set `POS_STRICT_DEVICE_BINDING=false` while testing |
| Very slow screens | MySQL not running? see `docs/OPERATIONS.md`; check SSD health |
| Everyone logged out at once | PHP session table cleared or `APP_KEY` changed — both invalidate sessions |
