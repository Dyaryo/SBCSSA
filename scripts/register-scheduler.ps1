# Registers the nightly verify/backup schedule as a Windows task.
# Run once from an elevated or normal PowerShell: powershell -ExecutionPolicy Bypass -File scripts\register-scheduler.ps1
$ErrorActionPreference = 'Stop'

$app = Split-Path -Parent $PSScriptRoot
$php = 'C:\xampp\php\php.exe'

if (-not (Test-Path $php)) {
    throw "PHP not found at $php. Update `$php in this script."
}

$action = New-ScheduledTaskAction -Execute $php -Argument 'artisan schedule:work' -WorkingDirectory $app
$trigger = New-ScheduledTaskTrigger -AtLogOn
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit ([TimeSpan]::Zero)
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Limited

Register-ScheduledTask `
    -TaskName 'E-Sabong Scheduler' `
    -Description 'Runs php artisan schedule:work for the E-Sabong play-money console.' `
    -Action $action -Trigger $trigger -Settings $settings -Principal $principal `
    -Force | Out-Null

Start-ScheduledTask -TaskName 'E-Sabong Scheduler'
Write-Host 'E-Sabong Scheduler registered and started.'
