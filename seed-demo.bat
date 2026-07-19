@echo off
cd /d "%~dp0backend-laravel\toto"
echo Rechargement des donnees de demo (utilisateurs, vehicules, FCFA)...
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan db:seed --force --class=TestDataSeeder
if errorlevel 1 (
  echo Echec du seed. Verifiez PostgreSQL et .env
  pause
  exit /b 1
)
echo.
echo OK. Comptes:
echo   admin@autochain.com / password
echo   manager@autochain.com / password
echo   driver1@autochain.com / password
echo   garage@autochain.com / password
echo   auditor@autochain.com / password
echo.
echo Les IDs blockchain ont ete remis a null: a re-certifier depuis l app.
pause
