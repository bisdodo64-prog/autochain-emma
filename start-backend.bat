@echo off
cd /d "%~dp0backend-laravel\toto\public"
echo Demarrage API Laravel sur http://127.0.0.1:9001
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe -S 127.0.0.1:9001 router.php
