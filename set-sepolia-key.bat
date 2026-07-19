@echo off
title AutoChain - Cle MetaMask Sepolia
cd /d "%~dp0"

echo ============================================================
echo  IMPORTANT : utilisez la cle de VOTRE compte MetaMask
echo  (pas la cle Ganache locale — elle n'a pas d'ETH Sepolia)
echo ============================================================
echo.
echo  MetaMask : Details du compte ^> Exporter la cle privee
echo  Reseau MetaMask : SEPOLIA
echo  ETH test : https://sepoliafaucet.com
echo.
set /p PK="Collez PRIVATE_KEY MetaMask (0x...) : "

if "%PK%"=="" (
    echo [ERREUR] Cle vide.
    pause
    exit /b 1
)

echo %PK%| findstr /R "^0x" >nul 2>&1
if %errorlevel% neq 0 set PK=0x%PK%

powershell -NoProfile -Command ^
  "$c = Get-Content 'blockchain\.env' -Raw; $c = $c -replace 'PRIVATE_KEY=.*', 'PRIVATE_KEY=%PK%'; Set-Content 'blockchain\.env' $c.TrimEnd()"

echo.
echo [OK] PRIVATE_KEY enregistree dans blockchain\.env
echo.
echo Lancement du deploiement Sepolia...
call setup-sepolia.bat
