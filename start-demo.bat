@echo off
title AutoChain Emma+ - Demarrage demo
cd /d "%~dp0"

echo ========================================
echo   AutoChain Emma+ - Mode Demo
echo ========================================
echo.
echo 1) Backend API  -^> http://127.0.0.1:9001
echo 2) Frontend SPA -^> http://localhost:3000
echo.
echo Comptes: admin@autochain.com / password
echo Doc demo: DEMO.md
echo.

start "AutoChain Backend" cmd /k "%~dp0start-backend.bat"
timeout /t 2 /nobreak >nul
start "AutoChain Frontend" cmd /k "%~dp0start-frontend.bat"

echo.
echo Fenetres lancees. Ouvrez http://localhost:3000
echo Pour Sepolia: pas besoin de Ganache. MetaMask sur Sepolia.
echo Pour sync: double-cliquez sync-blockchain.bat (apres login admin).
pause
