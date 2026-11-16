# ---------------------------------------------------------------------------
#  Gives the console a short LAN name so terminals open
#      https://cssa:8443/login
#  instead of the raw IP address.
#
#  Run elevated (it edits the hosts file and the NetBIOS name list):
#      powershell -ExecutionPolicy Bypass -File scripts\set-lan-name.ps1
#
#  What it does
#    1. Adds "192.168.1.15  cssa" to this PC's hosts file.
#    2. Registers CSSA as an extra NetBIOS name, so other Windows terminals on
#       the LAN can resolve it with no configuration of their own.
#    3. Restarts the HTTPS proxy so it serves the certificate that carries
#       cssa in its subjectAltName.
#
#  Terminals that still cannot resolve cssa can use the same one-line hosts
#  entry (see docs\OPERATIONS.md).
# ---------------------------------------------------------------------------
$ErrorActionPreference = 'Stop'

$name = 'cssa'
$ip = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { $_.IPAddress -notlike '127.*' -and $_.PrefixOrigin -ne 'WellKnown' } |
    Select-Object -First 1).IPAddress

if (-not $ip) { throw 'Could not detect this PC''s LAN IP.' }

Write-Host "Binding $name -> $ip"

# 1. hosts entry -------------------------------------------------------------
$hosts = Join-Path $env:SystemRoot 'System32\drivers\etc\hosts'
$lines = Get-Content $hosts
$already = $lines | Where-Object { $_ -match "^\s*$ip\s+.*\b$name\b" }

if ($already) {
    Write-Host '  hosts: entry already present'
} else {
    Add-Content -Path $hosts -Value "$ip`t$name"
    Write-Host '  hosts: added'
}

# 2. NetBIOS name ------------------------------------------------------------
# Windows terminals resolve a short name over the LAN through NetBIOS when DNS
# has nothing for it; OptionalNames makes this PC answer to an extra name
# without renaming the machine or rebooting.
$key = 'HKLM:\SYSTEM\CurrentControlSet\Services\lanmanserver\parameters'
$current = @((Get-ItemProperty -Path $key -Name OptionalNames -ErrorAction SilentlyContinue).OptionalNames)
$current = $current | Where-Object { $_ }

if ($current -contains $name.ToUpper()) {
    Write-Host '  netbios: name already registered'
} else {
    $updated = @($current) + $name.ToUpper()
    New-ItemProperty -Path $key -Name OptionalNames -PropertyType MultiString -Value $updated -Force | Out-Null
    Restart-Service lanmanserver -ErrorAction SilentlyContinue
    Write-Host ('  netbios: registered ' + $name.ToUpper())
}

# 3. Reload the proxy so the new certificate is served -----------------------
$listener = Get-NetTCPConnection -LocalPort 8443 -State Listen -ErrorAction SilentlyContinue

if ($listener) {
    Write-Host ('  proxy: stopping pid ' + $listener.OwningProcess + ' so it picks up the certificate')
    Stop-Process -Id $listener.OwningProcess -Force
    Start-Sleep -Seconds 2
}

# The watchdog task starts the proxy as the service account.
schtasks /run /tn 'E-Sabong Watchdog' | Out-Null
Start-Sleep -Seconds 6

$now = Get-NetTCPConnection -LocalPort 8443 -State Listen -ErrorAction SilentlyContinue

if ($now) {
    Write-Host ('  proxy: listening again on pid ' + $now.OwningProcess)
} else {
    Write-Host '  proxy: NOT listening — run scripts\start-https.bat'
}

$probe = & curl.exe -s -o NUL -w '%{http_code}' --max-time 10 --resolve "${name}:8443:${ip}" -k "https://${name}:8443/login"

Write-Host ''
Write-Host ("  check: https://${name}:8443/login -> HTTP $probe")
Write-Host "  Terminals open: https://$name`:8443/login"
