@echo off
REM ------------------------------------------------------------------
REM  Creates a local certificate authority and a server certificate
REM  for this machine (localhost, its hostname and its LAN IP).
REM  Run again whenever the server's IP address changes.
REM ------------------------------------------------------------------
setlocal enabledelayedexpansion

cd /d "%~dp0.."
set CERTDIR=%CD%\storage\certs
if not exist "%CERTDIR%" mkdir "%CERTDIR%"

where openssl >nul 2>&1
if errorlevel 1 (
    echo ERROR: openssl not found on PATH.
    echo XAMPP ships one at C:\xampp\apache\bin\openssl.exe
    pause
    exit /b 1
)

set LANIP=%~1
if "%LANIP%"=="" (
    for /f %%i in ('powershell -NoProfile -Command "(Get-NetIPAddress -AddressFamily IPv4 ^| Where-Object {$_.IPAddress -notlike '127.*' -and $_.PrefixOrigin -ne 'WellKnown'} ^| Select-Object -First 1).IPAddress"') do set LANIP=%%i
)
set HOSTNAME=%COMPUTERNAME%

if "%LANIP%"=="" (
    echo WARNING: could not detect a LAN IP. The certificate will only cover localhost.
    set LANIP=127.0.0.1
)

echo Creating certificates for:
echo   localhost, %FRIENDLY%, 127.0.0.1, %HOSTNAME%, %LANIP%
echo.

pushd "%CERTDIR%"

REM FRIENDLY is the short venue name terminals type: https://cssa:8443
set FRIENDLY=%~2
if "%FRIENDLY%"=="" set FRIENDLY=cssa

> san.ext echo subjectAltName=DNS:localhost,DNS:%FRIENDLY%,DNS:%HOSTNAME%,IP:127.0.0.1,IP:%LANIP%
>> san.ext echo extendedKeyUsage=serverAuth
>> san.ext echo keyUsage=critical,digitalSignature,keyEncipherment
>> san.ext echo basicConstraints=CA:FALSE

if not exist ca.crt (
    echo [1/3] Creating local CA...
    openssl req -x509 -newkey rsa:2048 -nodes -keyout ca.key -out ca.crt -days 3650 -subj "/CN=E-Sabong Local CA/O=E-Sabong Virtual Console" -addext "basicConstraints=critical,CA:TRUE" -addext "keyUsage=critical,keyCertSign,cRLSign"
    if errorlevel 1 goto :fail
) else (
    echo [1/3] Keeping the existing local CA so terminals stay trusted.
)

echo [2/3] Creating server key and request...
openssl req -newkey rsa:2048 -nodes -keyout server.key -out server.csr -subj "/CN=esabong-lan/O=E-Sabong Virtual Console"
if errorlevel 1 goto :fail

echo [3/3] Signing the server certificate...
openssl x509 -req -in server.csr -CA ca.crt -CAkey ca.key -CAcreateserial -out server.crt -days 825 -extfile san.ext
if errorlevel 1 goto :fail

del /Q server.csr san.ext 2>nul
popd

echo.
echo Certificates created in storage\certs
echo   ca.crt      - install this on every terminal (scripts\trust-cert.bat)
echo   server.crt  - served by the proxy
echo   server.key  - private key, never copy it off this PC
echo.
echo Next: scripts\start-https.bat
pause
exit /b 0

:fail
popd
echo.
echo Certificate creation failed. Read the error above.
pause
exit /b 1
