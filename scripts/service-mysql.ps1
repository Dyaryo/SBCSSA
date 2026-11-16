$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$log = Join-Path $root 'storage\logs\mysql-auto.log'
$out = Join-Path $root 'storage\logs\mysql-auto.out.log'
$err = Join-Path $root 'storage\logs\mysql-auto.err.log'
$mysqld = 'C:\xampp\mysql\bin\mysqld.exe'
$ini = 'C:/xampp/mysql/bin/my.ini'

# XAMPP MySQL is not installed as a Windows service, so start it here.
if (Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue) {
    Add-Content -Path $log -Value "$(Get-Date -Format o) MySQL already listening on 3306; task exiting."
    exit 0
}

if (-not (Test-Path $mysqld)) {
    Add-Content -Path $log -Value "$(Get-Date -Format o) MySQL binary not found at $mysqld."
    exit 1
}

Add-Content -Path $log -Value "$(Get-Date -Format o) Starting XAMPP MySQL."

# Keep mysqld independent of the scheduled-task console.
$process = Start-Process -FilePath $mysqld `
    -ArgumentList @("--defaults-file=$ini", '--standalone') `
    -WorkingDirectory 'C:\xampp\mysql' `
    -WindowStyle Hidden `
    -RedirectStandardOutput $out `
    -RedirectStandardError $err `
    -PassThru

$deadline = (Get-Date).AddSeconds(20)
do {
    if (Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue) {
        Add-Content -Path $log -Value "$(Get-Date -Format o) MySQL is listening on 3306 (PID $($process.Id))."
        exit 0
    }
    Start-Sleep -Seconds 1
} while ((Get-Date) -lt $deadline)

Add-Content -Path $log -Value "$(Get-Date -Format o) MySQL failed to bind port 3306."
exit 1
