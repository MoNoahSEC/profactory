@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

title  ProFactory Server - مصنع أقفاص العصافير
color 0A
mode con: cols=72 lines=25

cd /d "%~dp0"

:: 1. Detect PHP
if exist "%~dp0php_portable\php.exe" (
    set "PHP=%~dp0php_portable\php.exe"
) else (
    set "PHP=php"
)

:: 2. Ensure Storage & Logs Directories & Permissions
if not exist "%~dp0storage" mkdir "%~dp0storage"
if not exist "%~dp0storage\logs" mkdir "%~dp0storage\logs"
if not exist "%~dp0storage\framework" mkdir "%~dp0storage\framework"
if not exist "%~dp0storage\framework\cache" mkdir "%~dp0storage\framework\cache"
if not exist "%~dp0storage\framework\cache\data" mkdir "%~dp0storage\framework\cache\data"
if not exist "%~dp0storage\framework\sessions" mkdir "%~dp0storage\framework\sessions"
if not exist "%~dp0storage\framework\views" mkdir "%~dp0storage\framework\views"
if not exist "%~dp0storage\backups" mkdir "%~dp0storage\backups"
if not exist "%~dp0bootstrap\cache" mkdir "%~dp0bootstrap\cache"

if not exist "%~dp0storage\logs\laravel.log" type nul > "%~dp0storage\logs\laravel.log" 2>nul

set PORT=8000
set NGROK_DOMAIN=regulator-wages-crunchy.ngrok-free.dev
set DASHBOARD=%~dp0scripts\server_dashboard.html

echo.
echo  ========================================================================
echo    ProFactory Server - مصنع أقفاص العصافير (سيرفر التشغيل السحابي)
echo  ========================================================================
echo.

:: 3. Terminate previous instances cleanly
taskkill /F /IM php.exe 2>nul
taskkill /F /IM cloudflared.exe 2>nul
taskkill /F /IM ngrok.exe 2>nul
del /F /Q "%~dp0cloudflare.log" 2>nul
ping 127.0.0.1 -n 2 >nul

echo  [1/5]  تم إنهاء الجلسات السابقة وتنظيف الذاكرة.

:: 4. Database Backup
echo  [2/5]  جاري إنشاء نسخة احتياطية لقاعدة البيانات...
"%PHP%" artisan db:backup 2>nul
echo.

:: 5. Clear Caches
echo  [3/5]  تنظيف ملفات الكاش والتخزين المؤقت...
"%PHP%" artisan config:clear >nul 2>&1
"%PHP%" artisan cache:clear >nul 2>&1
"%PHP%" artisan view:clear >nul 2>&1
"%PHP%" artisan route:clear >nul 2>&1
echo  تم تنظيف الكاش بنجاح.
echo.

:: 6. Start Laravel Web Server
echo  [4/5]  تشغيل سيرفر النظام (Laravel على منفذ %PORT%)...
start "Laravel-Server" /MIN "%PHP%" artisan serve --host=0.0.0.0 --port=%PORT%
ping 127.0.0.1 -n 3 >nul

:: 7. Start Tunnel (Ngrok with fallback to Cloudflare)
echo  [5/5]  تشغيل نفق الاتصال المشفر المجاني (HTTPS)...

:: Try Ngrok first
where ngrok >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    start "Ngrok-Tunnel" /MIN ngrok http --domain=%NGROK_DOMAIN% %PORT%
    ping 127.0.0.1 -n 4 >nul
)

:: Check if Ngrok succeeded via local API
set "PUBLIC_URL="
for /f "delims=" %%u in ('"%PHP%" "%~dp0scripts\get_tunnel_url.php"') do (
    set "PUBLIC_URL=%%u"
)

:: If Ngrok is not connected (e.g. Needs authtoken), automatically start Cloudflare Tunnel
if "!PUBLIC_URL!"=="http://localhost:%PORT%" (
    if exist "%~dp0cloudflared.exe" (
        echo  [INFO] نيجروك يحتاج إلى توكن، جاري تفعيل نفق Cloudflare HTTPS الفوري المجاني...
        start "Cloudflare-Tunnel" /MIN "%~dp0cloudflared.exe" tunnel --url http://127.0.0.1:%PORT% --logfile "%~dp0cloudflare.log"
        ping 127.0.0.1 -n 6 >nul
        for /f "delims=" %%u in ('"%PHP%" "%~dp0scripts\get_tunnel_url.php"') do (
            set "PUBLIC_URL=%%u"
        )
    )
)

if "!PUBLIC_URL!"=="" (
    set "PUBLIC_URL=http://localhost:%PORT%"
)

"%PHP%" "%~dp0scripts\generate_dashboard.php" "!PUBLIC_URL!" >nul 2>&1

echo.
echo  ========================================================================
echo    السيرفر يعمل الآن بنجاح وبأمان كامل (HTTPS مجاني 100%%)!
echo    رابط الإنترنت المباشر:  !PUBLIC_URL!
echo    رابط شات الإدارة (واتساب): !PUBLIC_URL!/chat
echo    رابط الشبكة المحلية:   http://localhost:%PORT%
echo  ========================================================================
echo.
echo  جاري فتح لوحة التحكم وباركود الدخول في المتصفح...
echo.

:: Open Dashboard in default browser
start "" "%DASHBOARD%"

echo  تم بنجاح! يمكنك تصغير هذه النافذة دون إغلاقها.
echo.
pause
