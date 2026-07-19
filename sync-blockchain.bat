@echo off
setlocal
cd /d "%~dp0"

echo Sync blockchain via API (admin)...
echo.

REM Token: connectez-vous d abord dans l app, ou collez un token Sanctum
set /p TOKEN="Collez votre token API (Bearer) puis Entree: "
if "%TOKEN%"=="" (
  echo Aucun token. Connectez-vous sur http://localhost:3000 puis reessayez.
  pause
  exit /b 1
)

curl -s -X POST "http://127.0.0.1:9001/api/blockchain/sync" ^
  -H "Authorization: Bearer %TOKEN%" ^
  -H "Accept: application/json" ^
  -H "Content-Type: application/json"

echo.
echo.
echo Si erreur 401: reconnectez-vous et recuperez le token (localStorage auth_token).
pause
