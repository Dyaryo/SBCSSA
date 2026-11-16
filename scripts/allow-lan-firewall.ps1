<#
.SYNOPSIS
Allows LAN terminals to reach the E-Sabong console without any internet link.

.DESCRIPTION
Windows blocks unsolicited inbound traffic by default, and the allow rules it
prompts you for are often scoped to the Private profile only. When the venue
loses its uplink the network is frequently re-classified as Public or
"Unidentified", and the teller PCs then cannot reach the console even though
the service is running.

This script creates one explicit inbound rule for TCP 8443 that applies to all
profiles, so connectivity depends only on the local network, never on whether
Windows thinks there is internet. It never opens MySQL (3306).

Run it from an elevated PowerShell prompt. It is safe to re-run.
#>

$ErrorActionPreference = 'Stop'

$ruleName = 'E-Sabong console (HTTPS 8443)'

$existing = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
if ($existing) {
    Write-Host "Replacing existing rule '$ruleName'."
    $existing | Remove-NetFirewallRule
}

New-NetFirewallRule `
    -DisplayName $ruleName `
    -Description 'E-Sabong teller console over the local network. Applies to every network profile so an internet outage cannot cut terminals off.' `
    -Direction Inbound `
    -Action Allow `
    -Protocol TCP `
    -LocalPort 8443 `
    -Profile Any | Out-Null

Write-Host "Created inbound allow rule for TCP 8443 on all profiles."

Write-Host ''
Write-Host 'Firewall profiles:'
Get-NetFirewallProfile |
    Select-Object Name, Enabled, DefaultInboundAction |
    Format-Table -AutoSize

Write-Host 'Rule in effect:'
Get-NetFirewallRule -DisplayName $ruleName |
    Select-Object DisplayName, Direction, Action, Enabled, Profile |
    Format-Table -AutoSize

Write-Host 'Listening sockets:'
Get-NetTCPConnection -State Listen |
    Where-Object { $_.LocalPort -in 8000, 8443, 3306 } |
    Select-Object LocalAddress, LocalPort |
    Sort-Object LocalPort |
    Format-Table -AutoSize
