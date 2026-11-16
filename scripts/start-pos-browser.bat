@echo off
REM ------------------------------------------------------------------
REM  Opens the POS terminal with silent printing enabled, so completed
REM  bet and payout slips go straight to the default printer without a
REM  print dialog. Optional first argument: the POS URL.
REM ------------------------------------------------------------------
setlocal

set "URL=%~1"
if "%URL%"=="" set "URL=https://192.168.1.15:8443/pos"

set "EDGE=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe"
set "CHROME=%ProgramFiles%\Google\Chrome\Application\chrome.exe"

if exist "%EDGE%" (
    start "" "%EDGE%" --kiosk-printing --app=%URL%
    exit /b 0
)

if exist "%CHROME%" (
    start "" "%CHROME%" --kiosk-printing --app=%URL%
    exit /b 0
)

echo Edge or Chrome was not found. Open %URL% and enable silent printing manually.
pause
exit /b 1
