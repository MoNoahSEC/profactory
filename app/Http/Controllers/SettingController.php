<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        $treasuries = \App\Models\Treasury::all();
        return view('settings.index', compact('settings', 'treasuries'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);
        
        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }

    public function zeroTreasury()
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            // Delete all cash transactions
            \App\Models\CashTransaction::truncate();
            
            // Delete all expenses
            \App\Models\Expense::truncate();
            
            // Reset all treasuries balance to 0
            \App\Models\Treasury::query()->update([
                'current_balance' => 0,
                'initial_balance' => 0
            ]);
            
            // Clear old cash_balance setting
            Setting::set('cash_balance', 0);
        });

        return redirect()->back()->with('success', 'تم تصفير جميع الخزائن والمصروفات بنجاح');
    }

    public function zeroAdvances()
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            // Delete all worker advances
            \App\Models\WorkerAdvance::truncate();
            
            // Reset pending_advance_balance for all workers
            \App\Models\Worker::query()->update(['pending_advance_balance' => 0]);
        });

        return redirect()->back()->with('success', 'تم تصفير جميع سلف العمال بنجاح');
    }

    public function wipeDatabase(Request $request)
    {
        try {
            // Force close connection to release locks
            \Illuminate\Support\Facades\DB::disconnect();

            // Delete WAL and SHM to prevent 'disk I/O error' corruption
            @unlink(database_path('database.sqlite-wal'));
            @unlink(database_path('database.sqlite-shm'));

            \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
                '--force' => true,
                '--seed' => true
            ]);

            auth()->logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('success', 'تم مسح جميع البيانات بنجاح وتمت إعادة النظام لوضع المصنع.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء مسح البيانات: ' . $e->getMessage());
        }
    }

    public function backupDatabase()
    {
        try {
            // Force WAL checkpoint to ensure all data is in the main database file
            \Illuminate\Support\Facades\DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
            
            $path = database_path('database.sqlite');
            if (file_exists($path)) {
                return response()->download($path, 'profactory_backup_' . date('Y_m_d_H_i_s') . '.sqlite');
            }
            return redirect()->back()->with('error', 'ملف قاعدة البيانات غير موجود.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء أخذ نسخة احتياطية: ' . $e->getMessage());
        }
    }
}
