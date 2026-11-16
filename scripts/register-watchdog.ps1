# SUPERSEDED - do not use for the live console.
#
# This script registered the watchdog at logon of the interactive user, which
# replaced the boot-time watchdog task with one that only runs after a sign-in.
# The watchdog is now registered by configure-service-account.ps1 to run at
# boot (and every five minutes) as the dedicated service account.
$ErrorActionPreference = 'Stop'

Write-Host 'register-watchdog.ps1 is superseded and did nothing.'
Write-Host ''
Write-Host 'E-Sabong Watchdog is registered to run at boot and every five minutes'
Write-Host 'under the dedicated service account.'
Write-Host ''
Write-Host 'Use instead (elevated):'
Write-Host '  powershell -ExecutionPolicy Bypass -File scripts\configure-service-account.ps1'
Write-Host ''
Write-Host "To run the check once without waiting: Start-ScheduledTask -TaskName 'E-Sabong Watchdog'"
exit 1
