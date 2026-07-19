@echo off
title AutoChain - Setup Sepolia (Phase 2)
cd /d "%~dp0"

echo ============================================================
echo  PHASE 2 - SEPOLIA (testnet public)
echo ============================================================
echo.
echo  Ce script automatise :
echo   - sauvegarde config Ganache locale
echo   - deploiement contrat sur Sepolia
echo   - mise a jour .env backend + frontend
echo   - liaison wallet admin en base
echo.
echo  Avant de continuer :
echo   1. MetaMask sur reseau Sepolia
echo   2. ETH Sepolia : https://sepoliafaucet.com
echo   3. Cle MetaMask dans blockchain\.env (ou set-sepolia-key.bat)
echo   4. RPC Sepolia deja configure (rpc.sepolia.org)
echo.
pause

cd blockchain

if not exist "node_modules" (
    echo Installation dependances blockchain...
    call npm install
    if %errorlevel% neq 0 pause & exit /b 1
)

if not exist ".env" (
    copy /Y .env.example .env >nul
)

findstr /C:"YOUR_INFURA_KEY" .env >nul 2>&1
if %errorlevel%==0 (
    echo [INFO] Infura absent - RPC public Sepolia utilise.
    powershell -NoProfile -Command "(Get-Content '.env' -Raw) -replace 'SEPOLIA_RPC_URL=.*', 'SEPOLIA_RPC_URL=https://1rpc.io/sepolia' | Set-Content '.env'"
)

findstr /R /C:"PRIVATE_KEY=your_private_key" .env >nul 2>&1
if %errorlevel%==0 (
    echo.
    echo [STOP] PRIVATE_KEY manquante.
    echo Lancez : set-sepolia-key.bat
    echo   ou editez blockchain\.env : PRIVATE_KEY=0xVOTRE_CLE_METAMASK
    notepad .env
    pause
    exit /b 1
)

cd ..
call deploy-sepolia.bat
