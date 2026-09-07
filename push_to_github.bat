@echo off
chcp 65001 >nul
title ProFactory - Push to GitHub
cd /d "%~dp0"

echo ========================================
echo   جاري رفع المشروع إلى GitHub...
echo ========================================
echo.

git push -u origin main

echo.
pause
