$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$log = Join-Path $root 'storage\logs\https-auto.log'
$out = Join-Path $root 'storage\logs\https-auto.out.log'
$err = Join-Path $root 'storage\logs\https-auto.err.log'

# Set-Location is deliberately not used here: the dedicated service account
# cannot make this folder its current directory (access denied), which made the
# task exit with code 1 before it logged anything. Every path below is absolute,
# and Start-Process sets -WorkingDirectory.

# Do not start a second proxy if one is already listening.
if (Get-NetTCPConnection -LocalPort 8443 -State Listen -ErrorAction SilentlyContinue) {
    Add-Content -Path $log -Value "$(Get-Date -Format o) HTTPS proxy already listening on 8443; task exiting."
    exit 0
}

Add-Content -Path $log -Value "$(Get-Date -Format o) Starting HTTPS proxy."

# The proxy must outlive the short-lived scheduled-task wrapper.
$process = Start-Process -FilePath 'C:\Program Files\nodejs\node.exe' `
    -ArgumentList @('scripts\https-proxy.js') `
    -WorkingDirectory $root `
    -WindowStyle Hidden `
    -RedirectStandardOutput $out `
    -RedirectStandardError $err `
    -PassThru

Start-Sleep -Seconds 2

if (Get-NetTCPConnection -LocalPort 8443 -State Listen -ErrorAction SilentlyContinue) {
    Add-Content -Path $log -Value "$(Get-Date -Format o) HTTPS proxy is listening on 8443 (PID $($process.Id))."
    exit 0
}

Add-Content -Path $log -Value "$(Get-Date -Format o) HTTPS proxy failed to bind port 8443."
exit 1
