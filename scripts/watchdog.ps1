$ErrorActionPreference = 'Continue'

$root = Split-Path -Parent $PSScriptRoot
$log = Join-Path $root 'storage\logs\watchdog.log'

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

$checks = @(
    @{ Port = 3306; Name = 'MySQL';        Script = 'service-mysql.ps1' },
    @{ Port = 8000; Name = 'App server';   Script = 'service-app.ps1' },
    @{ Port = 8443; Name = 'HTTPS proxy';  Script = 'service-https.ps1' }
)

foreach ($check in $checks) {
    if (Test-LocalPort $check.Port) {
        continue
    }

    Add-Content -Path $log -Value "$(Get-Date -Format o) $($check.Name) is down; starting it."

    & (Join-Path $PSScriptRoot $check.Script)
    $code = $LASTEXITCODE

    Add-Content -Path $log -Value "$(Get-Date -Format o) $($check.Name) startup exit=$code."
}

$scheduler = Get-CimInstance Win32_Process -Filter "Name='php.exe'" |
    Where-Object { $_.CommandLine -like '*schedule:work*' } |
    Select-Object -First 1

if (-not $scheduler) {
    Add-Content -Path $log -Value "$(Get-Date -Format o) Scheduler is down; starting it."
    Start-ScheduledTask -TaskName 'E-Sabong Scheduler'
}
