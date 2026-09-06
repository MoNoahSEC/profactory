<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SystemLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']); // Protect it, you can add role:Admin later
    }

    public function index()
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logPath)) {
            // Read last 1000 lines for performance
            $file = file($logPath);
            $logs = array_slice($file, -500);
            $logs = array_reverse($logs);
        }

        return view('system-logs', compact('logs'));
    }

    public function clearLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }
        return redirect()->route('system.logs')->with('success', 'تم مسح سجل الأخطاء بنجاح.');
    }

    public function fixSystem()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            
            return redirect()->route('system.logs')->with('success', 'تم تنظيف النظام وإصلاح الأخطاء بنجاح (تم مسح الكاش).');
        } catch (\Exception $e) {
            return redirect()->route('system.logs')->with('error', 'حدث خطأ أثناء محاولة إصلاح النظام: ' . $e->getMessage());
        }
    }
}
