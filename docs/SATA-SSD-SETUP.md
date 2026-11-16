# SATA SSD setup for the central server

This guide covers the storage side of the deployment. It assumes one Windows
server PC running XAMPP, with the SSD holding the OS, the application and the
MySQL database.

**The most important correction first:** a SATA SSD is a *storage* device, not a
network device. The SSD goes **inside the server PC**. The Game Master PC,
Supervisor PC and POS terminals never connect to the SSD — they connect to the
server over Ethernet/Wi-Fi and talk to the application (see
`NETWORK-SETUP.md`).

---

## 1. Choose the right SSD

| Item | Recommendation |
|---|---|
| Form factor | 2.5" SATA III SSD (or M.2 SATA if the board supports it) |
| Capacity | 480 GB – 1 TB. The database is small; capacity is for growth and backups |
| Endurance | Prefer drives rated ≥ 300 TBW; a database does small random writes |
| Interface | SATA III (6 Gb/s). A SATA II port will work but caps around 300 MB/s |
| Brand | Any reputable brand with SMART support |

Avoid: USB flash drives, SD cards, and "SSD enclosures over USB" for the
database. They drop off the bus under load and corrupt MySQL.

---

## 2. Physical installation

1. Power the PC off and unplug it. Hold the power button 5 seconds to drain.
2. Mount the 2.5" drive in a bay or with the case's SSD bracket.
3. Connect the **SATA data cable** to a SATA port on the motherboard
   (prefer SATA0/1, usually the fastest controller).
4. Connect the **SATA power cable** from the PSU.
5. Boot into BIOS/UEFI (usually `Del` or `F2`).

### BIOS/UEFI settings

| Setting | Value | Why |
|---|---|---|
| SATA mode | **AHCI** | Required for NCQ/TRIM. IDE mode cripples the SSD |
| Secure Boot | Can stay on | No unsigned drivers are needed |
| Boot order | SSD first | After Windows is installed |
| Fast Boot | Optional | Fine to leave on |
| CSM/Legacy | Off if installing Windows 11 | UEFI + GPT is preferred |

If Windows is **already installed** on another disk in IDE mode, switching to
AHCI without preparation causes a boot failure. Either install Windows fresh on
the SSD (recommended for a server), or apply the AHCI registry fix before
switching.

---

## 3. Install Windows on the SSD

1. Boot the Windows installer from USB.
2. Choose **Custom: Install Windows only**.
3. Select the SSD. If it is new, it appears as "Unallocated Space" — select it
   and click Next; Windows creates the EFI, MSR and primary partitions.
4. Let Windows finish, install chipset and LAN drivers, then run Windows Update.

### Partition layout (recommended)

| Partition | Size | Purpose |
|---|---|---|
| EFI / MSR | automatic | boot |
| C: (system) | 120–150 GB | Windows + XAMPP + application |
| D: (data) | remaining | **MySQL data directory + backups** |

Keeping data on a separate partition/volume means you can reinstall Windows
without touching the database, and it makes backups simpler.

---

## 4. Verify the SSD is healthy and TRIM is on

Open **Command Prompt as Administrator**:

```bat
fsutil behavior query DisableDeleteNotify
```

- `DisableDeleteNotify = 0` → TRIM is **enabled** (correct)
- `= 1` → TRIM is disabled; run `fsutil behavior set DisableDeleteNotify 0`

Check the drive is recognised as a solid-state drive (should return `1`):

```bat
powershell -Command "Get-PhysicalDisk | Select FriendlyName,MediaType,HealthStatus,Size"
```

Check SMART health:

```bat
powershell -Command "Get-PhysicalDisk | Get-StorageReliabilityCounter | Format-List"
```

Watch **Temperature** (keep under ~70 °C) and **Wear**. A server in a closed
case with poor airflow is the usual cause of early SSD death.

### Windows settings that matter on a server

| Setting | Where | Value |
|---|---|---|
| Power plan | Control Panel → Power Options | **High performance** (prevents aggressive disk sleep) |
| "Turn off hard disk after" | Advanced power settings | **Never** |
| Defragmentation | Optimise Drives | **Never defrag an SSD**; Windows sends TRIM instead |
| Indexing | Drive properties | Can be disabled on D: |
| Page file | System properties | Keep on C: (8–16 GB), or move to D: — not both |
| Sleep / hibernate | Power options | **Disabled** — the server must answer POS terminals 24/7 |

---

## 5. Move MySQL's data directory to the SSD data volume (optional but recommended)

XAMPP stores databases in `C:\xampp\mysql\data` by default. If you created a D:
volume and want the database there:

1. Stop MySQL: XAMPP Control Panel → MySQL → **Stop**.
2. Copy the whole `C:\xampp\mysql\data` folder to `D:\mysql-data`.
3. Edit `C:\xampp\mysql\bin\my.ini` (or `my.cnf`):

```ini
[mysqld]
datadir="D:/mysql-data"
innodb_flush_log_at_trx_commit=1
innodb_buffer_pool_size=1G
```

4. Start MySQL and confirm with:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "SHOW VARIABLES LIKE 'datadir';"
```

5. Only after MySQL starts cleanly, delete the old folder.

`innodb_flush_log_at_trx_commit=1` is slower but is the **only** setting that
guarantees a committed bet survives a power cut. Do not lower it for a betting
ledger.

---

## 6. Where the application lives

```
C:\xampp\htdocs\esabong-system     ← application code (this project)
D:\mysql-data                      ← database files (if moved)
D:\esabong-backups                 ← nightly dumps (see OPERATIONS.md)
```

Putting the app under `htdocs` lets XAMPP's Apache serve it if you prefer Apache
over `php artisan serve`. Either way the POS terminals only need the URL.

---

## 7. Backups (a betting system without backups is not a system)

- Nightly `mysqldump` (or SQLite file copy) → `D:\esabong-backups`
- Weekly copy → external USB drive stored off the machine
- Monthly restore drill on a spare PC — a backup you have never restored is a guess

`scripts\backup-database.bat` does the dump and timestamps it.

---

## 8. What NOT to do

- Do not run the database from a USB drive or a network share.
- Do not enable MySQL `datadir` on a OneDrive/Dropbox-synced folder. Sync clients
  lock and rewrite files under MySQL and corrupt InnoDB tables.
- Do not defragment the SSD.
- Do not clone the SSD to a second machine and run both as servers — two MySQL
  instances writing the same data is data loss.
- Do not put the SSD in an external enclosure "so other PCs can use it".

---

## 9. Quick checklist

- [ ] SSD mounted internally, SATA data + power connected
- [ ] BIOS set to AHCI
- [ ] Windows installed on the SSD (UEFI/GPT)
- [ ] C: for system, D: for data
- [ ] `fsutil behavior query DisableDeleteNotify` returns 0
- [ ] SMART status Healthy, temperature reasonable
- [ ] Power plan High performance, sleep disabled, disk never turns off
- [ ] MySQL `datadir` (if moved) points at the data volume, `innodb_flush_log_at_trx_commit=1`
- [ ] Nightly backup scheduled and a restore has been tested
