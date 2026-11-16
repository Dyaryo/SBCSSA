@echo off
REM ------------------------------------------------------------------
REM  Registers (or repairs) the Windows task that runs Laravel's
REM  scheduler every 30 minutes.
REM
REM  routes/console.php then decides what actually runs:
REM    23:30  system:nightly   ledger + report chain + reconciliation
REM    23:45  system:backup    database dump into .\backups (30 day keep)
REM
REM  Requires an Administrator prompt the first time.
REM ------------------------------------------------------------------
setlocal

cd /d "%~dp0.."

for %%I in ("%~dp0.") do set SCRIPT_DIR=%%~fI
for %%I in ("%~dp0..") do set APP_DIR=%%~fI

where php >nul 2>&1
if errorlevel 1 (
    echo ERROR: php.exe is not on PATH. Install PHP or add it to PATH first.
    pause
    exit /b 1
)

set TASK_NAME=E-Sabong Nightly Checks

echo Registering "%TASK_NAME%"...
echo   runs: "%SCRIPT_DIR%\run-scheduled.bat"
echo   from: %APP_DIR%

schtasks /Create /F /SC MINUTE /MO 30 /TN "%TASK_NAME%" /TR "\"%SCRIPT_DIR%\run-scheduled.bat\"" /ST 00:00

if errorlevel 1 (
    echo.
    echo ERROR: could not create the task. Close this window, right-click
    echo schedule-checks.bat and choose "Run as administrator", then retry.
    pause
    exit /b 1
)

schtasks /Run /TN "%TASK_NAME%" >nul 2>&1

echo.
echo Done. Current state:
schtasks /Query /TN "%TASK_NAME%" /FO LIST | findstr /i "TaskName Status Next Last"

echo.
echo To test the check right now, run:  php artisan system:nightly
echo To remove the task later:          schtasks /Delete /TN "%TASK_NAME%" /F

pause
