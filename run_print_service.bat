@echo off
title JUSTSALE Print Service
echo --------------------------------------------------
echo      JUSTSALE BACKGROUND AUTOMATIC PRINTING
echo --------------------------------------------------
echo This window will start the printing service.
echo Keep this window open for automatic printing.
echo.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0print_service.ps1"
pause
