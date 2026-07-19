@echo off
title AutoChain - Deploiement Sepolia (ethers)
cd /d "%~dp0blockchain"

echo ========================================
echo  Deploiement VehicleRegistry - SEPOLIA
echo  (via ethers.js - plus fiable)
echo ========================================
echo.

if not exist ".env" (
    echo [ERREUR] blockchain\.env absent.
    pause
    exit /b 1
)

findstr /R /C:"PRIVATE_KEY=your_private_key" /C:"PRIVATE_KEY=$" .env >nul 2>&1
if %errorlevel%==0 (
    echo [ERREUR] PRIVATE_KEY non configure
    pause
    exit /b 1
)

echo [1/4] Sauvegarde config Ganache locale...
node scripts\snapshot-local-env.js
if %errorlevel% neq 0 pause & exit /b 1

echo.
echo [2/4] Compilation du contrat...
call npx truffle compile
if %errorlevel% neq 0 (
    echo [ERREUR] Compilation echouee
    pause
    exit /b 1
)

echo.
echo [3/4] Deploiement Sepolia (1-3 min)...
node scripts\deploy-sepolia-ethers.js
if %errorlevel% neq 0 (
    echo.
    echo [ERREUR] Deploiement echoue.
    echo Astuce: configurez Alchemy dans blockchain\.env :
    echo   SEPOLIA_RPC_URL=https://eth-sepolia.g.alchemy.com/v2/VOTRE_CLE
    echo Puis relancez deploy-sepolia.bat
    pause
    exit /b 1
)

echo.
echo [4/4] Termine.
echo MetaMask : reseau Sepolia
echo Redemarrez start-backend.bat et npm run dev
echo.
pause
