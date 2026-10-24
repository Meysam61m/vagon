@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul 2>&1
cd /d "%~dp0"

echo ============================================================
echo   Wagon Loading System - Update and Cache Clear Script
echo ============================================================
echo.

if not exist "artisan" (
    echo [ERROR] File "artisan" not found in: %cd%
    echo Please place update.bat inside the root folder of the project.
    echo.
    pause
    exit /b 1
)

set "PHP_BIN="
where php >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    set "PHP_BIN=php"
)

if not defined PHP_BIN if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
if not defined PHP_BIN if exist "D:\xampp\php\php.exe" set "PHP_BIN=D:\xampp\php\php.exe"
if not defined PHP_BIN if exist "E:\xampp\php\php.exe" set "PHP_BIN=E:\xampp\php\php.exe"

if not defined PHP_BIN (
    for /d %%D in ("C:\laragon\bin\php\php*") do (
        if exist "%%~fD\php.exe" set "PHP_BIN=%%~fD\php.exe"
    )
)
if not defined PHP_BIN (
    for /d %%D in ("C:\wamp64\bin\php\php*") do (
        if exist "%%~fD\php.exe" set "PHP_BIN=%%~fD\php.exe"
    )
)
if not defined PHP_BIN (
    for /d %%D in ("C:\wamp\bin\php\php*") do (
        if exist "%%~fD\php.exe" set "PHP_BIN=%%~fD\php.exe"
    )
)

if not defined PHP_BIN (
    echo [ERROR] PHP executable was not found in PATH, XAMPP, Laragon, or WAMP.
    echo Clearing cached files directly...
    goto CLEAR_FILES
)

echo [INFO] Using PHP: %PHP_BIN%
echo.

:CLEAR_FILES
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions" >nul 2>&1
if not exist "storage\framework\views" mkdir "storage\framework\views" >nul 2>&1
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data" >nul 2>&1
if not exist "bootstrap\cache" mkdir "bootstrap\cache" >nul 2>&1

echo [1/4] Clearing compiled Blade views and old sessions...
del /f /q "storage\framework\views\*.php" >nul 2>&1
del /f /q "storage\framework\views\.wagon_views_*" >nul 2>&1
del /f /q "storage\framework\.wagon_*" >nul 2>&1
del /f /q "storage\framework\sessions\*" >nul 2>&1
del /f /q "bootstrap\cache\routes-*.php" >nul 2>&1
del /f /q "bootstrap\cache\config.php" >nul 2>&1

if not defined PHP_BIN goto FINISH

echo [2/4] Updating .env session settings (30 min idle timeout + expire on browser close)...
"%PHP_BIN%" -r "if (is_file('.env')) { $c = file_get_contents('.env'); $c = preg_replace('/^SESSION_LIFETIME=.*$/m', 'SESSION_LIFETIME=30', $c, -1, $n1); if (!$n1) { $c .= PHP_EOL.'SESSION_LIFETIME=30'.PHP_EOL; } $c = preg_replace('/^SESSION_EXPIRE_ON_CLOSE=.*$/m', 'SESSION_EXPIRE_ON_CLOSE=true', $c, -1, $n2); if (!$n2) { $c .= 'SESSION_EXPIRE_ON_CLOSE=true'.PHP_EOL; } $c = preg_replace('/^SESSION_COOKIE=.*$/m', 'SESSION_COOKIE=wagon_system_session', $c, -1, $n3); if (!$n3) { $c .= 'SESSION_COOKIE=wagon_system_session'.PHP_EOL; } file_put_contents('.env', $c); }"

echo [3/4] Running database migrations...
"%PHP_BIN%" artisan migrate --force

echo [4/4] Clearing Laravel caches (optimize:clear)...
"%PHP_BIN%" artisan optimize:clear

:FINISH
echo.
echo ============================================================
echo   Update completed successfully!
echo   Please press Ctrl+F5 in your browser (or reopen browser).
echo ============================================================
echo.
pause
