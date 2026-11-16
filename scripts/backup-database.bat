@echo off
REM ------------------------------------------------------------------
REM  Backs up the database into .\backups with a timestamp.
REM  Works for BOTH SQLite and XAMPP MySQL — credentials are read from
REM  .env by the application, never typed on the command line.
REM
REM  Usage:  backup-database.bat              (interactive, pauses)
REM          backup-database.bat --no-pause   (for Task Scheduler)
REM ------------------------------------------------------------------
setlocal

cd /d "%~dp0.."

if not exist backups mkdir backups

php artisan system:backup --keep=30
set RESULT=%errorlevel%

echo.
if not "%RESULT%"=="0" (
    echo BACKUP FAILED — see the message above and storage\logs\laravel.log
) else (
    echo Backups are in %CD%\backups
    echo Copy that folder to a second disk or USB drive weekly.
)

if /i "%~1"=="--no-pause" exit /b %RESULT%

pause
exit /b %RESULT%
