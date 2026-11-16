# SUPERSEDED - do not use for the live console.
#
# This script registered at-logon tasks for the signed-in operator. Those tasks
# only start after someone signs in, so a reboot left the login screen serving
# nothing. The console now starts with Windows, before sign-in, from six boot
# tasks running as the dedicated service account.
#
# Re-running the old registration would replace the boot tasks with at-logon
# ones and bring the outage back, so this script refuses to act.
$ErrorActionPreference = 'Stop'

Write-Host 'register-autostart.ps1 is superseded and did nothing.'
Write-Host ''
Write-Host 'The console starts at boot, before Windows sign-in, under the dedicated'
Write-Host 'service account. Registering at-logon tasks here would undo that.'
Write-Host ''
Write-Host 'Use instead (elevated):'
Write-Host '  powershell -ExecutionPolicy Bypass -File scripts\configure-service-account.ps1'
Write-Host ''
Write-Host 'Check the result in C:\ProgramData\E-Sabong\service-account-setup.txt'
exit 1
