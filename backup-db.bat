@echo off
cd /d "%~dp0"
set STAMP=%date:~6,4%-%date:~3,2%-%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set STAMP=%STAMP: =0%
set OUTDIR=%~dp0backups
if not exist "%OUTDIR%" mkdir "%OUTDIR%"
set OUTFILE=%OUTDIR%\autochain_emma_%STAMP%.sql

echo Sauvegarde PostgreSQL vers:
echo   %OUTFILE%
echo.

REM Adapte user/db si besoin (voir backend-laravel\toto\.env)
set PGUSER=postgres
set PGDATABASE=autochain_emma
set PGHOST=127.0.0.1
set PGPORT=5432

where pg_dump >nul 2>&1
if errorlevel 1 (
  echo pg_dump introuvable. Ajoutez PostgreSQL\bin au PATH Laragon.
  echo Exemple: C:\laragon\bin\postgresql\postgresql-*\bin
  pause
  exit /b 1
)

pg_dump -h %PGHOST% -p %PGPORT% -U %PGUSER% -d %PGDATABASE% -F p -f "%OUTFILE%"
if errorlevel 1 (
  echo Echec backup. Verifiez mot de passe / service PostgreSQL.
  pause
  exit /b 1
)

echo OK.
pause
