@echo off
REM ------------------------------------------------------------------
REM  Installs this system's local CA into the Windows trusted roots so
REM  terminals stop showing certificate warnings.
REM  MUST be run as Administrator (right-click - Run as administrator).
REM ------------------------------------------------------------------
setlocal

cd /d "%~dp0.."

set CA=%CD%\storage\certs\ca.crt

if not exist "%CA%" (
    echo ERROR: %CA% not found. Run scripts\make-certs.bat first.
    pause
    exit /b 1
)

echo Installing local CA into "Trusted Root Certification Authorities"...
certutil -addstore -f Root "%CA%"
if errorlevel 1 goto :fail

echo.
echo Trusted. Close and reopen the browser, then open https://SERVER-IP:8443
echo.
echo Run this on every terminal PC, using the same ca.crt file.
pause
exit /b 0

:fail
echo.
echo Could not install the CA. This script must run as Administrator.
pause
exit /b 1
