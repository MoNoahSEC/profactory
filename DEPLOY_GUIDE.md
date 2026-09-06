# تعليمات تشغيل السيرفر عالمياً (Global)
# ==========================================

## 🔧 الخطوة 1: تنزيل ngrok

اذهب إلى: https://ngrok.com/download
- اختر Windows (64-bit)
- فك الضغط عن الملف وضعه في: `E:\ngrok.exe`

## 🔑 الخطوة 2: تسجيل حساب مجاني

1. اذهب إلى: https://dashboard.ngrok.com/signup
2. سجّل بالإيميل (مجاني 100%)
3. من الصفحة: https://dashboard.ngrok.com/get-started/your-authtoken
   انسخ الـ authtoken

## ⚙️ الخطوة 3: ربط الـ authtoken (مرة واحدة فقط)

افتح PowerShell وشغّل:
```
E:\ngrok.exe config add-authtoken YOUR_TOKEN_HERE
```

## 🚀 الخطوة 4: تشغيل السيرفر

**الطريقة السهلة (بضغطة زر):**
افتح PowerShell واكتب:
```
E:\profactory\start-server.ps1
```

**أو يدوياً:**
افتح PowerShell Terminal 1:
```
cd E:\profactory
php artisan serve --host=0.0.0.0 --port=8000
```

افتح PowerShell Terminal 2:
```
E:\ngrok.exe http 8000
```

## 🌍 الوصول للسيرفر

بعد تشغيل ngrok، هيظهر لك رابط زي:
```
https://abc123.ngrok-free.app
```
ده هو الرابط العالمي - أي حد في العالم يقدر يفتحه!

## ⚠️ ملاحظات مهمة

1. **السيرفر لازم يكون شغال** طول ما الناس بتستخدم البرنامج
2. **إذا أغلقت الـ Terminal** - السيرفر هيوقف
3. **الرابط بيتغير** مع كل مرة تشغّل ngrok (في الخطة المجانية)
4. **لرابط ثابت دائم**: اشترك في ngrok Pro ($10/شهر) أو استخدم Cloudflare Tunnel (مجاني)

## 💎 الحل الدائم (Cloudflare Tunnel - مجاني 100%)

1. انشئ حساب في: https://cloudflare.com
2. نزّل cloudflared: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation
3. شغّل:
   ```
   cloudflared tunnel --url http://localhost:8000
   ```
4. هيديك رابط ثابت يشتغل دايماً!
