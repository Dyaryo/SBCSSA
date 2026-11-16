$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$log = Join-Path $root 'storage\logs\serve-auto.log'
$out = Join-Path $root 'storage\logs\serve-auto.out.log'
$err = Join-Path $root 'storage\logs\serve-auto.err.log'

# Set-Location is deliberately not used here: the dedicated service account can
# read, write and launch in this folder, but Windows refuses to make it the
# current directory, which made the task exit with code 1 before it logged
# anything. Every path below is absolute, and Start-Process sets -WorkingDirectory.

# Do not start a second server if one is already listening.
if (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue) {
    Add-Content -Path $log -Value "$(Get-Date -Format o) App already listening on 8000; task exiting."
    exit 0
}

Add-Content -Path $log -Value "$(Get-Date -Format o) Starting app server."

# Do not use & here: that makes the server a child of the scheduled-task
# PowerShell console and it receives Ctrl+C when that task ends.
$process = Start-Process -FilePath 'C:\xampp\php\php.exe' `
    -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', '--port=8000') `
    -WorkingDirectory $root `
    -WindowStyle Hidden `
    -RedirectStandardOutput $out `
    -RedirectStandardError $err `
    -PassThru

Start-Sleep -Seconds 3

if (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue) {
    Add-Content -Path $log -Value "$(Get-Date -Format o) App server is listening on 8000 (PID $($process.Id))."
    exit 0
}

Add-Content -Path $log -Value "$(Get-Date -Format o) App server failed to bind port 8000."
exit 1
