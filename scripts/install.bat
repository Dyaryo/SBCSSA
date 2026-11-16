@echo off
REM ------------------------------------------------------------------
REM  E-Sabong Virtual Console - first time setup (Windows / XAMPP)
REM  Run this once from the project folder.
REM ------------------------------------------------------------------
setlocal

cd /d "%~dp0.."

echo.
echo [1/6] Checking PHP...
php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP was not found on PATH. Add C:\xampp\php to PATH.
    pause
    exit /b 1
)

echo [2/6] Installing PHP dependencies (skipped if vendor\ exists)...
if not exist vendor (
    call composer install --no-interaction --prefer-dist
    if errorlevel 1 goto :fail
)

echo [3/6] Preparing .env...
if not exist .env (
    copy /Y .env.example .env >nul
)

echo [4/6] Generating application key...
php artisan key:generate --ansi

echo [5/6] Building the database...
php artisan migrate:fresh --seed --force
if errorlevel 1 goto :fail

REM The seed only creates the logins and the rules. Wipe the demo card so the
REM console opens empty: create your own event, fights and tills from scratch.
php artisan system:reset --force
if errorlevel 1 goto :fail

echo [6/6] Verifying ledger and report chain...
php artisan system:verify

echo.
echo ============================================================
echo  Setup complete.
echo  Start the server with:  scripts\start-server.bat
echo  Then open:              http://localhost:8000
echo.
echo  Supervisor : supervisor / Admin@2026
echo  Game Master: gm.north   / Master@2026
echo  POS Teller : pos01      / Teller@2026
echo.
echo  The console starts empty: no events, fights, bets or shifts.
echo  Fund the tills from Supervisor - Accounts before the first bet.
echo ============================================================
echo.
pause
exit /b 0

:fail
echo.
echo Setup failed. Read the error above.
pause
exit /b 1
