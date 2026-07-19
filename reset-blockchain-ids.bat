@echo off
cd /d "%~dp0backend-laravel\toto"
echo Remise a zero des blockchain_id vehicules (pour re-certifier sur Sepolia)...
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan vehicles:reset-blockchain --force
if errorlevel 1 (
  echo Echec. Verifiez PHP / DB.
  pause
  exit /b 1
)
echo.
echo Fait. Dans l app: page Vehicules -^> Certifier / Re-certifier.
pause
