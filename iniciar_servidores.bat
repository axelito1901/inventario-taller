@echo off
title Servidores Inventario Taller

echo Iniciando servidores WebSocket y Emisor...

REM Abrir servidor WebSocket en una nueva ventana
start "WebSocket" cmd /k "cd /d %~dp0 && node servidor-socket.js"

REM Esperar 1 segundo
timeout /t 1 >nul

REM Abrir servidor Emisor en otra nueva ventana
start "Emisor" cmd /k "cd /d %~dp0 && node emisor.js"

echo Todo listo. Cierra esta ventana si querés.
pause
