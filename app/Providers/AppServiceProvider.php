<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\RawMaterial;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── Auto-detect non-localhost (ngrok, global tunnel, etc.) ──────────
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $isLocal = str_starts_with($host, 'localhost')
                || str_starts_with($host, '127.0.0.1')
                || str_starts_with($host, '192.168.')
                || str_starts_with($host, '10.');

        if (!$isLocal) {
            // Force HTTPS URLs for all generated links and assets
            URL::forceScheme('https');

            // Dynamically update the APP_URL so route() and asset() use the correct domain
            $currentUrl = 'https://' . $host;
            config(['app.url' => $currentUrl]);

            // Fix session cookies to work over HTTPS on any domain (ngrok, etc.)
            config([
                'session.secure'    => true,   // Only send cookie over HTTPS
                'session.same_site' => 'none',  // Allow cross-site (required for ngrok)
                'session.domain'    => null,    // Don't lock to a specific domain
            ]);
        }

        // (PRAGMA statements moved to config/database.php to avoid SQLite locks on every request)

        View::composer('layouts.app', function ($view) {
            // Cache all alert/notification counts for 3 minutes to avoid 7 DB queries per page load
            $cached = \Illuminate\Support\Facades\Cache::remember('app_layout_alerts', 180, function () {
                $lowStockMaterials   = RawMaterial::whereColumn('current_stock', '<=', 'minimum_stock')->count();
                $lowStockProducts    = Inventory::whereColumn('current_stock', '<=', 'minimum_stock')->count();
                $overdueInvoices     = Invoice::where('status', 'overdue')
                    ->orWhere(function ($q) {
                        $q->whereIn('status', ['draft', 'sent', 'partial'])
                          ->where('due_date', '<', Carbon::today())
                          ->whereRaw('paid_amount < total_amount');
                    })->count();
                $pendingOrders       = Order::whereIn('status', ['pending', 'in_progress', 'awaiting_approval'])
                    ->where('converted_to_invoice', false)->count();
                $absentToday         = Attendance::whereDate('date', Carbon::today())
                    ->where('status', 'absent')->count();
                $overdueInstallments = \App\Models\DebtInstallment::where('status', '!=', 'paid')
                    ->where('due_date', '<', Carbon::today())->count();

                return compact('lowStockMaterials', 'lowStockProducts', 'overdueInvoices', 'pendingOrders', 'absentToday', 'overdueInstallments');
            });

            // Alerts are user-specific — always fresh (but lightweight)
            $systemAlerts = \App\Models\SystemAlert::where('is_read', false)->latest()->take(10)->get();

            $alertCount = $cached['lowStockMaterials'] + $cached['lowStockProducts']
                        + $cached['overdueInvoices'] + $cached['pendingOrders']
                        + $cached['absentToday'] + $systemAlerts->count()
                        + $cached['overdueInstallments'];

            $view->with(array_merge($cached, compact('systemAlerts', 'alertCount')));
        });
    }
}
