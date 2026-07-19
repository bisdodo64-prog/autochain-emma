@echo off
cd /d "%~dp0backend-laravel\toto"
echo Migration base de donnees AutoChain Emma+
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan migrate --force
if %errorlevel% neq 0 (
    echo.
    echo [ERREUR] Migration echouee. Verifiez PostgreSQL et le fichier .env
    pause
    exit /b 1
)
echo.
echo Migration terminee.
pause
