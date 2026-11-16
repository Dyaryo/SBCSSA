$ErrorActionPreference = 'Continue'

$root = Split-Path -Parent $PSScriptRoot
# No Set-Location: the service account cannot make this folder its current
# directory. Paths are absolute so the check still works at boot.
$logPath = Join-Path $root 'storage\logs\autostart-verification.txt'

function Test-LocalPort([int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $client.Connect('127.0.0.1', $Port)
        return $true
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

# The tasks wait 5-20 seconds after logon. Give MySQL and both servers
# enough time to bind before recording the result.
$deadline = (Get-Date).AddSeconds(120)
do {
    $mysql = Test-LocalPort 3306
    $app = Test-LocalPort 8000
    $https = Test-LocalPort 8443
    if ($mysql -and $app -and $https) { break }
    Start-Sleep -Seconds 3
} while ((Get-Date) -lt $deadline)

# The LAN address is DHCP, so read it at boot instead of hardcoding it: a
# changed lease must not make a healthy console look like a failed boot.
$lanIp = (Get-NetIPConfiguration -ErrorAction SilentlyContinue |
    Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' } |
    Select-Object -First 1).IPv4Address.IPAddress
if (-not $lanIp) { $lanIp = '192.168.1.15' }

$httpCode = (& curl.exe -s -o NUL -w '%{http_code}' --max-time 10 'http://127.0.0.1:8000/up' 2>$null)
$httpsCode = (& curl.exe -s -o NUL -w '%{http_code}' --max-time 15 --ssl-no-revoke --cacert (Join-Path $root 'storage/certs/ca.crt') "https://${lanIp}:8443/login" 2>$null)

# Stylesheets and scripts are files on disk: if their permissions are wrong the
# page still returns 200 but arrives unstyled, which looks like a broken app.
$cssCode = (& curl.exe -s -o NUL -w '%{http_code}' --max-time 10 'http://127.0.0.1:8000/css/app.css' 2>$null)
$jsCode = (& curl.exe -s -o NUL -w '%{http_code}' --max-time 10 'http://127.0.0.1:8000/js/app.js' 2>$null)

$taskLines = Get-ScheduledTask -TaskName 'E-Sabong*' |
    Sort-Object TaskName |
    ForEach-Object { '  ' + $_.TaskName.PadRight(24) + $_.State }

$scheduler = Get-CimInstance Win32_Process -Filter "Name='php.exe'" |
    Where-Object { $_.CommandLine -like '*schedule:work*' } |
    Select-Object -First 1

# Proof that the console came up before anyone reached the desktop: record who,
# if anyone, held an interactive logon at the exact moment the endpoints
# answered. A report showing "jerem" here was written after a human signed in,
# so it does not prove unattended startup.
# The service account cannot read logon-session accounts, so use the two
# signals it can see: the console user and the desktop shell process.
$consoleUser = (Get-CimInstance Win32_ComputerSystem -ErrorAction SilentlyContinue).UserName
$desktopShell = Get-Process explorer -ErrorAction SilentlyContinue | Select-Object -First 1
$userName = if ($consoleUser) { $consoleUser.Substring($consoleUser.LastIndexOf('\') + 1) } else { $null }
$who = @()
if ($userName) { $who += $userName }
if ($desktopShell) { $who += 'desktop shell running' }

$signInState = if ($who.Count -gt 0) {
    'SIGNED IN: ' + ($who -join ', ') + ' (this run does not prove unattended startup)'
} else {
    'nobody signed in - the console answered with no user at the desktop'
}
$shellState = if ($desktopShell) { "running (PID $($desktopShell.Id))" } else { 'not running' }

$bootTime = (Get-CimInstance Win32_OperatingSystem).LastBootUpTime
$secondsAfterBoot = [int]((Get-Date) - $bootTime).TotalSeconds

$overall = $mysql -and $app -and $https -and ($httpCode -eq '200') -and ($httpsCode -eq '200') -and ($cssCode -eq '200') -and ($jsCode -eq '200') -and $scheduler
$mysqlState = if ($mysql) { 'PASS' } else { 'FAIL' }
$appState = if ($app) { 'PASS' } else { 'FAIL' }
$httpsState = if ($https) { 'PASS' } else { 'FAIL' }
$schedulerState = if ($scheduler) { "PASS (PID $($scheduler.ProcessId))" } else { 'FAIL' }
$overallState = if ($overall) { 'PASS' } else { 'FAIL' }

$lines = @(
    'E-SABONG AUTOSTART VERIFICATION'
    "Checked: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    "Boot time: $($bootTime.ToString('yyyy-MM-dd HH:mm:ss'))  (checked $secondsAfterBoot s after boot)"
    ''
    'Sign-in state when the console answered:'
    "  $signInState"
    "  Desktop shell (explorer.exe): $shellState"
    ''
    'Listeners:'
    "  3306 MySQL          $mysqlState"
    "  8000 App server     $appState"
    "  8443 HTTPS proxy    $httpsState"
    ''
    'Endpoints:'
    "  http://127.0.0.1:8000/up              HTTP $httpCode"
    "  https://${lanIp}:8443/login        HTTP $httpsCode"
    "  http://127.0.0.1:8000/css/app.css      HTTP $cssCode"
    "  http://127.0.0.1:8000/js/app.js        HTTP $jsCode"
    ''
    "Scheduler process: $schedulerState"
    ''
    'Windows tasks (only those this account may read):'
) + $taskLines + @('', "OVERALL: $overallState")

$lines | Set-Content -Path $logPath -Encoding UTF8

# Also leave the result where the operator can read it after signing in.
$programData = 'C:\ProgramData\E-Sabong'
if (Test-Path $programData) {
    $lines | Set-Content -Path (Join-Path $programData 'autostart-verification.txt') -Encoding UTF8
}

$desktop = [Environment]::GetFolderPath('Desktop')
if ($desktop) {
    $lines | Set-Content -Path (Join-Path $desktop 'E-SABONG-REBOOT-VERIFICATION.txt') -Encoding UTF8
}
