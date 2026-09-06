@echo off
chcp 65001 >nul
title ProFactory - Database Backup

cd /d "%~dp0"

:: 1. Detect PHP
if exist "%~dp0php_portable\php.exe" (
    set "PHP=%~dp0php_portable\php.exe"
) else (
    set "PHP=php"
)

:: Run Laravel backup command
"%PHP%" artisan db:backup

echo.
echo Backup process completed!
pause
