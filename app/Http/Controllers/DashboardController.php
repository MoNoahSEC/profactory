<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Inventory;
use App\Models\Attendance;
use App\Models\RawMaterial;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\CashLedgerService;

class DashboardController extends Controller
{
    public function index(CashLedgerService $ledger)
    {
        if (auth()->user()) {
            if (auth()->user()->hasRole('Loader') && !auth()->user()->hasRole('Admin')) {
                return redirect()->route('loading.index');
            }
            if (auth()->user()->hasRole('Driver') && !auth()->user()->hasRole('Admin')) {
                return redirect()->route('driver.dashboard');
            }
            if (auth()->user()->hasRole('Supervisor') && !auth()->user()->hasRole('Admin')) {
                return redirect()->route('supervisor.attendance.index');
            }
        }

        // Cache الـ queries الثقيلة لمدة دقيقتين
        $stats = Cache::remember('dashboard_stats', 120, function () {
            // 1. مبيعات الشهر
            $currentMonthSales = Invoice::whereMonth('invoice_date', Carbon::now()->month)
                ->whereYear('invoice_date', Carbon::now()->year)
                ->sum('total_amount');

            // 2. المنتجات في المخزن
            $totalProductsInStock = Inventory::sum('current_stock');

            // 3. أوامر الإنتاج الجارية
            $pendingOrdersCount = \App\Models\Order::whereIn('status', ['pending', 'in_progress', 'awaiting_approval'])
                ->where('converted_to_invoice', false)
                ->count();

            // 4. غيابات الموظفين اليوم
            $absentWorkersToday = Attendance::whereDate('date', Carbon::today())
                ->where('status', 'absent')
                ->count();

            // 5. بيانات الرسم البياني — query واحدة بدل 6
            $salesChartData = [];
            $salesChartLabels = [];
            $sixMonthsAgo = Carbon::now()->startOfMonth()->subMonths(5);
            $monthlySales = Invoice::where('invoice_date', '>=', $sixMonthsAgo)
                ->selectRaw("strftime('%Y-%m', invoice_date) as ym, SUM(total_amount) as total")
                ->groupByRaw("strftime('%Y-%m', invoice_date)")
                ->pluck('total', 'ym');
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->startOfMonth()->subMonths($i);
                $key = $month->format('Y-m');
                $salesChartLabels[] = $month->translatedFormat('F');
                $salesChartData[] = $monthlySales[$key] ?? 0;
            }

            // 6. التنبيهات: مواد خام ناقصة
            $lowStockMaterials = RawMaterial::whereColumn('current_stock', '<=', 'minimum_stock')->get();

            // 6b. التنبيهات: منتجات جاهزة ناقصة
            $lowStockProducts = Inventory::whereColumn('current_stock', '<=', 'minimum_stock')->with('product')->get();

            // 7. التنبيهات: فواتير متأخرة
            $overdueInvoices = Invoice::where(function($query) {
                    $query->where('status', 'overdue')
                          ->orWhere(function($q) {
                              $q->whereIn('status', ['draft', 'sent', 'partial'])
                                ->where('due_date', '<', Carbon::today())
                                ->whereRaw('paid_amount < total_amount');
                          });
                })->with('customer')->get();

            // 8. طلبيات بانتظار مراجعة التحميل
            $awaitingApprovalOrders = \App\Models\Order::where('status', 'awaiting_approval')
                ->latest('updated_at')->take(3)->get();

            // 9. طلبيات أُرسلت للتحميل
            $loadingOrders = \App\Models\Order::where('loading_status', 'loading')
                ->latest('updated_at')->take(3)->get();

            $overdueDebts = \App\Models\ExternalDebt::where('status', '!=', 'paid')
                ->where('type', 'owed_by_us')
                ->where('due_date', '<=', Carbon::today()->addDays(7))
                ->get();

            return compact(
                'currentMonthSales', 'totalProductsInStock', 'pendingOrdersCount',
                'absentWorkersToday', 'salesChartLabels', 'salesChartData',
                'lowStockMaterials', 'lowStockProducts', 'overdueInvoices',
                'awaitingApprovalOrders', 'loadingOrders', 'overdueDebts'
            );
        });

        // Cash لا يُخزّن في cache (يتغير مع كل عملية)
        $cashSummary = $ledger->getSummary();

        // استخراج المتغيرات من الـ cache
        extract($stats);

        return view('dashboard', compact(
            'currentMonthSales',
            'totalProductsInStock',
            'pendingOrdersCount',
            'absentWorkersToday',
            'salesChartLabels',
            'salesChartData',
            'lowStockMaterials',
            'lowStockProducts',
            'overdueInvoices',
            'awaitingApprovalOrders',
            'loadingOrders',
            'overdueDebts',
            'cashSummary'
        ));
    }
}
