@echo off

title AutoChain - Blockchain locale

cd /d "%~dp0blockchain"



echo ========================================

echo  AutoChain Emma+ - Blockchain locale

echo ========================================

echo.



REM Verifier si le port 8545 est deja utilise

netstat -ano | findstr ":8545" | findstr "LISTENING" >nul 2>&1

if %errorlevel%==0 (

    echo [OK] Ganache deja actif sur 127.0.0.1:8545

    echo      Pas besoin de relancer un second noeud.

    echo.

    goto deploy

)



echo 1. Demarrage Ganache (port 8545, chainId 1337)...

echo    Laissez la fenetre Ganache ouverte.

echo.

echo    Note: l'avertissement uWS est normal avec Node.js recent.

echo    Ganache utilise le fallback Node.js (sans impact en dev local).

echo.



start "Ganache AutoChain" cmd /k "npx ganache --port 8545 --chain.chainId 1337 --wallet.deterministic true --miner.blockTime 1 --host 127.0.0.1"



echo Attente du noeud (8 sec)...

timeout /t 8 /nobreak >nul



:deploy

echo.

echo 2. Deploiement du contrat VehicleRegistry...

call npx truffle migrate --reset --network development

if %errorlevel% neq 0 (

    echo.

    echo [ERREUR] Migration echouee. Verifiez que Ganache tourne sur 8545.

    pause

    exit /b 1

)



echo.

echo 3. Adresse du contrat deploye :

node scripts/update-env.js



echo.

echo Pour arreter Ganache : fermez la fenetre "Ganache AutoChain"

echo   ou: taskkill /F /PID ^(netstat -ano ^| findstr :8545 ^| findstr LISTENING^)

echo.

pause

