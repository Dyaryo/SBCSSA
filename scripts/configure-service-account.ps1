# Configures the E-Sabong console to start at boot, before any Windows sign-in,
# under a dedicated standard local account. Run this script elevated.
#
#   powershell -ExecutionPolicy Bypass -File scripts\configure-service-account.ps1
#
# Why this script does what it does:
#   * Register-ScheduledTask (PowerShell) does NOT grant the account the
#     "Log on as a batch job" right the way the Task Scheduler GUI does. Without
#     it a password-logon task registers, reports Ready, and silently never
#     starts - the exact symptom of a reboot that leaves the console down.
#   * A password-logon task stores the password from registration time. Rotating
#     the account password afterwards breaks every existing task with
#     0x8007052E, so the stored password is validated and reused, not reset.
#   * Every task is verified after registration and then test-started, so a
#     failure is reported here instead of at 3am after a reboot.
#Requires -RunAsAdministrator
param(
    [string]$AccountName = 'svc-esabong',
    [switch]$SkipStartTest
)
$ErrorActionPreference = 'Stop'

$account = "$env:COMPUTERNAME\$AccountName"
$root = Split-Path -Parent $PSScriptRoot
$parent = Split-Path $root -Parent
$powershell = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
$backupDir = Join-Path ([Environment]::GetFolderPath('Desktop')) 'E-Sabong-task-backup'
$credentialFile = Join-Path ([Environment]::GetFolderPath('Desktop')) 'E-SABONG-SERVICE-ACCOUNT.txt'
$programData = 'C:\ProgramData\E-Sabong'
$logDir = Join-Path $root 'storage\logs'
$summaryFile = Join-Path $programData 'service-account-setup.txt'

if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir -Force | Out-Null }
try { Start-Transcript -Path (Join-Path $logDir 'service-account-setup.log') -Append -Force | Out-Null } catch { }

# The live task names, and the older names this script replaces.
$taskNames = @(
    'E-Sabong MySQL',
    'E-Sabong Application',
    'E-Sabong Web Proxy',
    'E-Sabong Scheduler',
    'E-Sabong Watchdog',
    'E-Sabong Verify Autostart'
)
$supersededTasks = @(
    'E-Sabong App',
    'E-Sabong App Server',
    'E-Sabong HTTPS',
    'E-Sabong HTTPS Proxy'
)

$failures = New-Object System.Collections.Generic.List[string]

function Write-Step([string]$Message) {
    Write-Host ''
    Write-Host "== $Message"
}

function Grant-Acl([string]$Path, [string]$Rights, [switch]$Recurse) {
    if (-not (Test-Path $Path)) { return }
    $arguments = @($Path, '/grant', "${account}:$Rights", '/C', '/Q')
    if ($Recurse) { $arguments += '/T' }
    & icacls.exe @arguments | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "icacls returned $LASTEXITCODE for $Path"
    }
}

# --- Password: reuse the working one, never rotate it silently ---------------
function Test-AccountPassword([string]$Password) {
    # LOGON32_LOGON_NETWORK validates the password without needing the batch
    # logon right this script is about to grant.
    if (-not ('ESabong.CredCheck' -as [type])) {
        Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;
namespace ESabong {
    public static class CredCheck {
        [DllImport("advapi32.dll", SetLastError = true, CharSet = CharSet.Unicode)]
        private static extern bool LogonUser(string user, string domain, string password, int logonType, int logonProvider, out IntPtr token);
        [DllImport("kernel32.dll", SetLastError = true)]
        private static extern bool CloseHandle(IntPtr handle);
        public static bool Valid(string user, string domain, string password) {
            IntPtr token;
            bool ok = LogonUser(user, domain, password, 3, 0, out token);
            if (token != IntPtr.Zero) { CloseHandle(token); }
            return ok;
        }
    }
}
'@
    }
    return [ESabong.CredCheck]::Valid($AccountName, $env:COMPUTERNAME, $Password)
}

function Read-StoredPassword {
    if (-not (Test-Path $credentialFile)) { return $null }
    $match = Select-String -Path $credentialFile -Pattern '^Password: (.+)$' | Select-Object -First 1
    if ($match) { return $match.Matches[0].Groups[1].Value }
    return $null
}

function Save-CredentialFile([string]$Password) {
    @(
        'E-SABONG DEDICATED SERVICE ACCOUNT'
        ''
        "Username: $account"
        "Password: $Password"
        ''
        'Keep this file private. The scheduled tasks already store the credential.'
        'Do not change this account password: every startup task would stop'
        'launching with 0x8007052E. Re-run scripts\configure-service-account.ps1'
        'elevated and it will detect the mismatch and repair the tasks.'
    ) | Set-Content -Path $credentialFile -Encoding UTF8
}

function New-RandomPassword {
    return ([guid]::NewGuid().ToString('N') + [guid]::NewGuid().ToString('N')).Substring(0, 24) + '!aA1'
}

# --- "Log on as a batch job", which PowerShell registration leaves out ------
# Granted through the LSA policy API (what the Task Scheduler GUI uses).
# Modifying the local security policy with secedit /configure proved unreliable
# here: it reports success without the right being applied, which is worse than
# failing, because the tasks then look configured and never start.
function Initialize-AccountRightsApi {
    if ('ESabong.AccountRights' -as [type]) { return }
    Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;

namespace ESabong {
    [StructLayout(LayoutKind.Sequential)]
    public struct LsaUnicodeString {
        public ushort Length;
        public ushort MaximumLength;
        public IntPtr Buffer;
    }

    [StructLayout(LayoutKind.Sequential)]
    public struct LsaObjectAttributes {
        public int Length;
        public IntPtr RootDirectory;
        public IntPtr ObjectName;
        public int Attributes;
        public IntPtr SecurityDescriptor;
        public IntPtr SecurityQualityOfService;
    }

    public static class AccountRights {
        private const int PolicyCreateAccount = 0x00000010;
        private const int PolicyLookupNames = 0x00000800;
        private const uint StatusObjectNameNotFound = 0xC0000034;

        [DllImport("advapi32.dll", SetLastError = true)]
        private static extern uint LsaOpenPolicy(IntPtr systemName, ref LsaObjectAttributes objectAttributes, int accessMask, out IntPtr policyHandle);

        [DllImport("advapi32.dll", SetLastError = true)]
        private static extern uint LsaAddAccountRights(IntPtr policyHandle, byte[] accountSid, LsaUnicodeString[] userRights, int countOfRights);

        [DllImport("advapi32.dll", SetLastError = true)]
        private static extern uint LsaEnumerateAccountRights(IntPtr policyHandle, byte[] accountSid, out IntPtr userRights, out int countOfRights);

        [DllImport("advapi32.dll")]
        private static extern uint LsaNtStatusToWinError(uint status);

        [DllImport("advapi32.dll")]
        private static extern uint LsaClose(IntPtr policyHandle);

        [DllImport("advapi32.dll")]
        private static extern uint LsaFreeMemory(IntPtr buffer);

        private static IntPtr OpenPolicy(int accessMask) {
            LsaObjectAttributes attributes = new LsaObjectAttributes();
            attributes.Length = Marshal.SizeOf(typeof(LsaObjectAttributes));
            IntPtr handle;
            uint status = LsaOpenPolicy(IntPtr.Zero, ref attributes, accessMask, out handle);
            if (status != 0) {
                throw new InvalidOperationException("LsaOpenPolicy failed with Windows error " + LsaNtStatusToWinError(status));
            }
            return handle;
        }

        public static int Add(byte[] sid, string right) {
            IntPtr handle = OpenPolicy(PolicyCreateAccount | PolicyLookupNames);
            IntPtr buffer = Marshal.StringToHGlobalUni(right);
            try {
                LsaUnicodeString[] rights = new LsaUnicodeString[1];
                rights[0].Buffer = buffer;
                rights[0].Length = (ushort)(right.Length * 2);
                rights[0].MaximumLength = (ushort)((right.Length + 1) * 2);
                uint status = LsaAddAccountRights(handle, sid, rights, 1);
                return (int)LsaNtStatusToWinError(status);
            } finally {
                Marshal.FreeHGlobal(buffer);
                LsaClose(handle);
            }
        }

        public static string[] List(byte[] sid) {
            IntPtr handle = OpenPolicy(PolicyCreateAccount | PolicyLookupNames);
            IntPtr rightsPointer;
            int count;
            try {
                uint status = LsaEnumerateAccountRights(handle, sid, out rightsPointer, out count);
                if (status == StatusObjectNameNotFound) { return new string[0]; }
                if (status != 0) {
                    throw new InvalidOperationException("LsaEnumerateAccountRights failed with Windows error " + LsaNtStatusToWinError(status));
                }
                string[] result = new string[count];
                try {
                    int size = Marshal.SizeOf(typeof(LsaUnicodeString));
                    for (int i = 0; i < count; i++) {
                        LsaUnicodeString item = (LsaUnicodeString)Marshal.PtrToStructure(
                            new IntPtr(rightsPointer.ToInt64() + (i * size)), typeof(LsaUnicodeString));
                        result[i] = Marshal.PtrToStringUni(item.Buffer, item.Length / 2);
                    }
                } finally {
                    LsaFreeMemory(rightsPointer);
                }
                return result;
            } finally {
                LsaClose(handle);
            }
        }
    }
}
'@
}

function Get-ServiceAccountSidBytes {
    $sid = New-Object System.Security.Principal.SecurityIdentifier((Get-LocalUser -Name $AccountName).SID.Value)
    $bytes = New-Object byte[] $sid.BinaryLength
    $sid.GetBinaryForm($bytes, 0)
    return ,$bytes
}

function Test-BatchLogonRight {
    Initialize-AccountRightsApi
    return ([ESabong.AccountRights]::List((Get-ServiceAccountSidBytes)) -contains 'SeBatchLogonRight')
}

function Grant-BatchLogonRight {
    Initialize-AccountRightsApi
    $code = [ESabong.AccountRights]::Add((Get-ServiceAccountSidBytes), 'SeBatchLogonRight')
    if ($code -ne 0) {
        throw "LsaAddAccountRights failed with Windows error $code"
    }
    if (-not (Test-BatchLogonRight)) {
        throw 'SeBatchLogonRight was added but is not present when enumerated.'
    }
    return $true
}

# --- Dedicated local service account ---------------------------------------
Write-Step "Service account $account"

$storedPassword = Read-StoredPassword
$password = $null
$existing = Get-LocalUser -Name $AccountName -ErrorAction SilentlyContinue

if ($existing) {
    if ($storedPassword -and (Test-AccountPassword $storedPassword)) {
        $password = $storedPassword
        Write-Host '  Reusing the stored password; the account and its tasks stay in sync.'
        if (-not $existing.PasswordNeverExpires) {
            Set-LocalUser -Name $AccountName -PasswordNeverExpires $true
        }
    } else {
        $password = New-RandomPassword
        Set-LocalUser -Name $AccountName -Password (ConvertTo-SecureString $password -AsPlainText -Force) -PasswordNeverExpires $true
        Save-CredentialFile $password
        Write-Host '  The stored password no longer works for this account; a new one was set and saved.'
        Write-Host '  Any task registered before this run stored the old password, so all six are re-registered below.'
    }
} else {
    $password = New-RandomPassword
    New-LocalUser -Name $AccountName `
        -Password (ConvertTo-SecureString $password -AsPlainText -Force) `
        -Description 'E-Sabong console services (startup tasks only)' `
        -PasswordNeverExpires `
        -UserMayNotChangePassword | Out-Null
    Save-CredentialFile $password
    Write-Host '  Account created; the password is in the operator credential file.'
}
if (-not (Test-Path $credentialFile)) { Save-CredentialFile $password }

$isUser = Get-LocalGroupMember -Group 'Users' -Member $AccountName -ErrorAction SilentlyContinue
if (-not $isUser) {
    Add-LocalGroupMember -Group 'Users' -Member $AccountName
    Write-Host '  Added to the local Users group.'
}

Write-Step 'Batch logon right (required: tasks are Ready but never start without it)'
try {
    if (Test-BatchLogonRight) {
        Write-Host '  Already granted.'
    } elseif (Grant-BatchLogonRight) {
        Write-Host '  Granted "Log on as a batch job" to the service account.'
    }
} catch {
    # Record and keep going: the remaining steps are still worth completing, and
    # the start test below will show whether the tasks can launch.
    $failures.Add('SeBatchLogonRight')
    Write-Host "  FAILED: $($_.Exception.Message)"
    Write-Host '  Add it by hand: secpol.msc -> Local Policies -> User Rights Assignment'
    Write-Host "  -> Log on as a batch job -> add $account"
}

# --- File permissions ------------------------------------------------------
Write-Step 'File permissions'
# Traverse the project parent, read the application, write only runtime data.
Grant-Acl -Path $parent -Rights '(RX)'
Grant-Acl -Path $root -Rights '(OI)(CI)RX' -Recurse

# Every folder between the drive root and the project also needs read access:
# Node resolves the real path of its entry script component by component, and
# fails with "EPERM: operation not permitted, lstat ...\OneDrive" otherwise,
# which killed the HTTPS proxy at boot. Not inherited, so the profile stays private.
$ancestor = $parent
while ($ancestor -and (Split-Path $ancestor -Parent)) {
    Grant-Acl -Path $ancestor -Rights '(RX)'
    $ancestor = Split-Path $ancestor -Parent
}
Grant-Acl -Path (Join-Path $root 'storage') -Rights '(OI)(CI)M' -Recurse
Grant-Acl -Path (Join-Path $root 'bootstrap\cache') -Rights '(OI)(CI)M' -Recurse
Grant-Acl -Path 'C:\xampp\mysql\data' -Rights '(OI)(CI)M' -Recurse
Grant-Acl -Path 'C:\xampp\mysql\bin' -Rights '(OI)(CI)RX' -Recurse

if (-not (Test-Path $programData)) {
    New-Item -ItemType Directory -Path $programData -Force | Out-Null
}
Grant-Acl -Path $programData -Rights '(OI)(CI)M'
Grant-Acl -Path $programData -Rights '(OI)(CI)RX'

# Allow the signed-in operator to read the setup and verification results.
$operator = "$env:USERDOMAIN\$env:USERNAME"
& icacls.exe $programData /grant "${operator}:(OI)(CI)RX" /C /Q | Out-Null
Write-Host "  Service account can read the application and write storage, bootstrap\cache, ProgramData\E-Sabong."

if ($root -match '\\OneDrive\\') {
    Write-Warning 'The project is inside a OneDrive folder. Files On-Demand can make a script unreadable at boot; set this folder to "Always keep on this device".'
}

# --- Preserve the current task definitions for rollback --------------------
Write-Step 'Backing up current task definitions'
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
}
foreach ($name in ($taskNames + $supersededTasks)) {
    try {
        $xml = Export-ScheduledTask -TaskName $name -ErrorAction Stop
        $safe = $name -replace '[^A-Za-z0-9._-]', '-'
        $xml | Set-Content -Path (Join-Path $backupDir "$safe.xml") -Encoding UTF8
        Write-Host "  $name"
    } catch {
        # A missing task simply has no backup.
    }
}

# --- Remove the at-logon tasks this configuration replaces -----------------
Write-Step 'Removing superseded at-logon tasks'
$removed = 0
foreach ($name in $supersededTasks) {
    $task = Get-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
    if (-not $task) { continue }
    try {
        Unregister-ScheduledTask -TaskName $name -Confirm:$false -ErrorAction Stop
        Write-Host "  Removed $name"
        $removed++
    } catch {
        $failures.Add("Could not remove $name")
        Write-Host "  FAILED to remove ${name}: $($_.Exception.Message)"
    }
}
if ($removed -eq 0) { Write-Host '  None present.' }

# --- Startup tasks ---------------------------------------------------------
Write-Step 'Registering boot tasks under the service account'

function Register-BootTask {
    param(
        [string]$Name,
        [string]$Description,
        [Microsoft.Management.Infrastructure.CimInstance]$Action,
        [object[]]$Triggers,
        [Microsoft.Management.Infrastructure.CimInstance]$Settings
    )

    try {
        Register-ScheduledTask `
            -TaskName $Name `
            -Description $Description `
            -Action $Action `
            -Trigger $Triggers `
            -Settings $Settings `
            -User $account `
            -Password $password `
            -RunLevel Limited `
            -Force `
            -ErrorAction Stop | Out-Null

        $task = Get-ScheduledTask -TaskName $Name -ErrorAction Stop
        if ($task.Principal.UserId -notlike "*$AccountName") {
            throw "registered under '$($task.Principal.UserId)' instead of $account"
        }
        if ($task.Principal.LogonType -ne 'Password') {
            throw "logon type is '$($task.Principal.LogonType)', so it would not run without a sign-in"
        }
        Write-Host "  OK   $Name"
        return $true
    } catch {
        $failures.Add("Register $Name")
        Write-Host "  FAIL $Name : $($_.Exception.Message)"
        return $false
    }
}

function New-BootTrigger([string]$Delay) {
    $trigger = New-ScheduledTaskTrigger -AtStartup
    $trigger.Delay = $Delay
    return $trigger
}

$startup = New-BootTrigger 'PT5S'
$startupLater = New-BootTrigger 'PT20S'
$startupVerify = New-BootTrigger 'PT45S'

$serviceSettings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5) `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -MultipleInstances IgnoreNew

$schedulerSettings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -MultipleInstances IgnoreNew

$watchdogSettings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5) `
    -MultipleInstances IgnoreNew

$verifySettings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5) `
    -MultipleInstances IgnoreNew

function New-ScriptAction([string]$ScriptName) {
    return New-ScheduledTaskAction `
        -Execute $powershell `
        -Argument ('-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "{0}"' -f (Join-Path $root "scripts\$ScriptName")) `
        -WorkingDirectory $root
}

$schedulerAction = New-ScheduledTaskAction `
    -Execute 'C:\xampp\php\php.exe' `
    -Argument 'artisan schedule:work' `
    -WorkingDirectory $root

$watchdogRepeat = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date).AddMinutes(2) `
    -RepetitionInterval (New-TimeSpan -Minutes 5) `
    -RepetitionDuration (New-TimeSpan -Days 3650)

Register-BootTask -Name 'E-Sabong MySQL' `
    -Description 'Starts XAMPP MySQL at boot, before Windows sign-in.' `
    -Action (New-ScriptAction 'service-mysql.ps1') -Triggers $startup -Settings $serviceSettings | Out-Null

Register-BootTask -Name 'E-Sabong Application' `
    -Description 'Starts the Laravel app server at boot, before Windows sign-in.' `
    -Action (New-ScriptAction 'service-app.ps1') -Triggers $startupLater -Settings $serviceSettings | Out-Null

Register-BootTask -Name 'E-Sabong Web Proxy' `
    -Description 'Starts the HTTPS proxy at boot, before Windows sign-in.' `
    -Action (New-ScriptAction 'service-https.ps1') -Triggers $startupLater -Settings $serviceSettings | Out-Null

Register-BootTask -Name 'E-Sabong Scheduler' `
    -Description 'Runs Laravel schedule:work at boot, before Windows sign-in.' `
    -Action $schedulerAction -Triggers $startupLater -Settings $schedulerSettings | Out-Null

Register-BootTask -Name 'E-Sabong Watchdog' `
    -Description 'Checks and restarts the E-Sabong services every five minutes under the service account.' `
    -Action (New-ScriptAction 'watchdog.ps1') -Triggers @($startupLater, $watchdogRepeat) -Settings $watchdogSettings | Out-Null

Register-BootTask -Name 'E-Sabong Verify Autostart' `
    -Description 'Records a boot-time health check in C:\ProgramData\E-Sabong.' `
    -Action (New-ScriptAction 'verify-autostart.ps1') -Triggers $startupVerify -Settings $verifySettings | Out-Null

# --- Prove the tasks actually launch ---------------------------------------
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

$startTest = @()
if (-not $SkipStartTest) {
    Write-Step 'Starting the tasks now to prove the service account can launch them'
    $probes = @(
        @{ Task = 'E-Sabong MySQL';       Port = 3306; Wait = 40 },
        @{ Task = 'E-Sabong Application'; Port = 8000; Wait = 30 },
        @{ Task = 'E-Sabong Web Proxy';   Port = 8443; Wait = 20 }
    )

    foreach ($probe in $probes) {
        try {
            Start-ScheduledTask -TaskName $probe.Task -ErrorAction Stop
        } catch {
            $failures.Add("Start $($probe.Task)")
            $startTest += ('{0,-22} START FAILED  {1}' -f $probe.Task, $_.Exception.Message)
            Write-Host "  FAIL $($probe.Task) could not be started: $($_.Exception.Message)"
            continue
        }

        $deadline = (Get-Date).AddSeconds($probe.Wait)
        $up = $false
        do {
            if (Test-LocalPort $probe.Port) { $up = $true; break }
            Start-Sleep -Seconds 2
        } while ((Get-Date) -lt $deadline)

        $info = Get-ScheduledTaskInfo -TaskName $probe.Task -ErrorAction SilentlyContinue
        $result = if ($info) { '0x{0:X}' -f $info.LastTaskResult } else { 'unknown' }

        if ($up) {
            $startTest += ('{0,-22} PASS  port {1} listening, task result {2}' -f $probe.Task, $probe.Port, $result)
            Write-Host "  PASS $($probe.Task) is listening on $($probe.Port)."
        } else {
            $hint = switch ($result) {
                '0x80070569' { 'missing batch logon right' }
                '0x8007052E' { 'stored password does not match the account' }
                default { 'see storage\logs\*-auto.log and the task history' }
            }
            $failures.Add("Start $($probe.Task)")
            $startTest += ('{0,-22} FAIL  port {1} closed, task result {2} ({3})' -f $probe.Task, $probe.Port, $result, $hint)
            Write-Host "  FAIL $($probe.Task) did not open $($probe.Port). Task result $result ($hint)."
        }
    }

    # The scheduler binds no port; look for its process instead.
    try {
        Start-ScheduledTask -TaskName 'E-Sabong Scheduler' -ErrorAction Stop
        Start-Sleep -Seconds 5
        $schedulerProcess = Get-CimInstance Win32_Process -Filter "Name='php.exe'" -ErrorAction SilentlyContinue |
            Where-Object { $_.CommandLine -like '*schedule:work*' } |
            Select-Object -First 1
        if ($schedulerProcess) {
            $startTest += ('{0,-22} PASS  running (PID {1})' -f 'E-Sabong Scheduler', $schedulerProcess.ProcessId)
            Write-Host "  PASS E-Sabong Scheduler is running (PID $($schedulerProcess.ProcessId))."
        } else {
            $failures.Add('Start E-Sabong Scheduler')
            $startTest += ('{0,-22} FAIL  no schedule:work process' -f 'E-Sabong Scheduler')
            Write-Host '  FAIL E-Sabong Scheduler is not running.'
        }
    } catch {
        $failures.Add('Start E-Sabong Scheduler')
        $startTest += ('{0,-22} START FAILED  {1}' -f 'E-Sabong Scheduler', $_.Exception.Message)
        Write-Host "  FAIL E-Sabong Scheduler could not be started: $($_.Exception.Message)"
    }
}

# --- Summary ---------------------------------------------------------------
$summary = @(
    'E-SABONG STARTUP CONFIGURATION'
    "Checked: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    ''
    "Service account:  $account"
    "Tasks run as:     $account (password logon, runs without anyone signing in)"
    "Credential file:  $credentialFile"
    "Task backups:     $backupDir"
    ''
    'Tasks:'
) + @($taskNames | ForEach-Object {
    $task = Get-ScheduledTask -TaskName $_ -ErrorAction SilentlyContinue
    if (-not $task) { '  {0,-24} MISSING' -f $_ }
    else { '  {0,-24} {1} | {2} | {3}' -f $_, $task.State, $task.Principal.UserId, $task.Principal.LogonType }
}) + @('', 'Start test:') + @(if ($startTest.Count) { $startTest } else { @('  skipped') }) +
    @('', 'Result: ' + $(if ($failures.Count) { 'FAIL - ' + ($failures -join '; ') } else { 'PASS' }))

$summary | Set-Content -Path $summaryFile -Encoding UTF8
$summary | Write-Host

try { Stop-Transcript | Out-Null } catch { }

if ($failures.Count) {
    Write-Host ''
    Write-Host "Setup finished with $($failures.Count) problem(s). The console may not survive a reboot until these are fixed."
    exit 1
}

Write-Host ''
Write-Host 'All startup tasks are registered and launch under the service account.'
exit 0
