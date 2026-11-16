<#
.SYNOPSIS
Reboots the server to re-test that the console comes up with nobody signed in.

.DESCRIPTION
Gives a warning window so anyone at the desk can save work. After the reboot,
do NOT sign in for about two minutes: the boot tasks start MySQL, the app server,
the HTTPS proxy and the scheduler as the svc-esabong account.

Evidence is written by the boot-time verification task to:
  C:\ProgramData\E-Sabong\autostart-verification.txt
  <desktop>\E-SABONG-REBOOT-VERIFICATION.txt

That report now records who, if anyone, was signed in when the console answered,
so a run taken after a human signed in cannot be mistaken for a successful
unattended start.

To cancel during the countdown:  shutdown /a
#>

param(
    [int] $Seconds = 180
)

$reason = 'E-Sabong autostart test: console must answer before anyone signs in'

# Invoke through the full path: Git Bash rewrites "/r" style arguments, which
# makes a direct shutdown call print its usage text instead of rebooting.
& "$env:SystemRoot\System32\shutdown.exe" /r /t $Seconds /c $reason

if ($LASTEXITCODE -ne 0) {
    Write-Error "shutdown.exe refused the request (exit $LASTEXITCODE)."
    exit $LASTEXITCODE
}

Write-Host "Reboot scheduled in $Seconds seconds. Cancel with: shutdown /a"
