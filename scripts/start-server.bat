@echo off
REM ------------------------------------------------------------------
REM  Starts the console on all network interfaces so POS terminals,
REM  the Game Master PC and the Supervisor PC can connect over the LAN.
REM  Change the port here if 8000 is already used.
REM ------------------------------------------------------------------
setlocal

cd /d "%~dp0.."

echo Starting E-Sabong Virtual Console on this machine...
echo.
echo   Local:  http://localhost:8000
echo   LAN:    http://YOUR-SERVER-IP:8000   (see ipconfig)
echo.
echo Keep this window open. Press Ctrl+C to stop the server.
echo.

php artisan serve --host=0.0.0.0 --port=8000
