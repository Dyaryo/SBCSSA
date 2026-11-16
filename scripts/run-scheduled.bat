@echo off
REM ------------------------------------------------------------------
REM  Runs Laravel's scheduler once. Windows Task Scheduler calls this
REM  file every 30 minutes; the schedule itself lives in routes/console.php.
REM ------------------------------------------------------------------
setlocal

cd /d "%~dp0.."

if not exist storage\logs mkdir storage\logs

php artisan schedule:run >> storage\logs\scheduler.log 2>&1

exit /b %errorlevel%
