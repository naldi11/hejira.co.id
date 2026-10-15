@echo off
setlocal
set "PHP84_DIR=C:\laragon\bin\php\php-8.4.26-Win32-vs17-x64"
if not exist "%PHP84_DIR%\php.exe" (
    echo PHP 8.4.26 belum terpasang di "%PHP84_DIR%". 1>&2
    exit /b 1
)
set "PATH=%PHP84_DIR%;%PATH%"
"%PHP84_DIR%\php.exe" %*
exit /b %ERRORLEVEL%
