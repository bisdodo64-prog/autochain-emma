@echo off

title AutoChain - Retour Ganache local

cd /d "%~dp0blockchain"



if not exist "scripts\apply-local-env.js" (

    echo [ERREUR] Script introuvable.

    pause

    exit /b 1

)



node scripts\apply-local-env.js

if %errorlevel% neq 0 pause & exit /b 1



echo.

echo Relancez start-blockchain.bat puis start-backend.bat

pause

