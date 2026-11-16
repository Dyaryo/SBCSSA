@echo off
REM ------------------------------------------------------------------
REM  Starts the application on loopback and terminates HTTPS for the
REM  LAN on port 8443. Terminals use https://SERVER-IP:8443
REM  Requires: scripts\make-certs.bat has been run.
REM ------------------------------------------------------------------
setlocal

cd /d "%~dp0.."

if not exist "storage\certs\server.crt" (
    echo ERROR: no certificate found.
    echo Run scripts\make-certs.bat first.
    pause
    exit /b 1
)

where node >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js was not found on PATH. It is needed for the HTTPS proxy.
    pause
    exit /b 1
)

echo Starting the application on 127.0.0.1:8000 (not exposed to the LAN)...
start "E-Sabong app" /min php artisan serve --host=127.0.0.1 --port=8000

timeout /t 3 /nobreak >nul

echo Starting the HTTPS terminator on 0.0.0.0:8443...
echo.
echo   Terminals:  https://YOUR-SERVER-IP:8443
echo   Plain HTTP on 8000 is loopback-only while this runs.
echo.
echo Keep this window open. Press Ctrl+C to stop the HTTPS proxy.
echo (The app process keeps running in its own window.)
echo.

node scripts\https-proxy.js
