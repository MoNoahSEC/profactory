<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\RawMaterial;
use App\Models\SalaryRecord;
use App\Services\CostCalculationService;
use App\Services\ProductionRegistrationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, CostCalculationService $costService)
    {
        $filter = $request->input('filter', 'all');
        $now = Carbon::now();

        $startDate = null;
        $endDate = null;

        if ($filter === 'daily') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($filter === 'weekly') {
            $startDate = $now->copy()->startOfWeek(Carbon::SATURDAY);
            $endDate = $now->copy()->endOfWeek(Carbon::FRIDAY);
        } elseif ($filter === 'monthly') {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        } elseif ($filter === 'yearly') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        }

        // Helper function to apply date filter
        $applyDate = function($query, $dateColumn = 'created_at') use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                return $query->whereBetween($dateColumn, [$startDate, $endDate]);
            }
            return $query;
        };

        $totalSales = $applyDate(Invoice::query(), 'invoice_date')->sum('total_amount');
        $paidSales = $applyDate(Invoice::query(), 'invoice_date')->sum('paid_amount');
        $remainingSales = $applyDate(Invoice::query(), 'invoice_date')->sum('remaining_amount');

        $totalProductionOrders = $applyDate(Order::query())->count();
        $completedProductionOrders = $applyDate(Order::where('status', 'completed'))->count();

        $totalProductionCost = $applyDate(Order::where('status', 'completed'))
            ->with(['items.product.materials'])
            ->get()
            ->sum(function ($order) use ($costService) {
                return $order->items->sum(function ($item) use ($costService) {
                    $cost = $costService->calculateProductCost($item->product);
                    return $cost['total_cost'] * $item->quantity;
                });
            });

        $productsValue = Inventory::with('product')->get()->sum(function ($inv) {
            return $inv->current_stock * ($inv->product->selling_price ?? 0);
        });

        $rawMaterialsValue = RawMaterial::all()->sum(function ($material) {
            return $material->current_stock * $material->unit_cost;
        });

        $totalRevenue = $totalSales;
        $totalExpenses = $applyDate(Expense::query(), 'expense_date')->sum('amount');
        $totalSalaries = $applyDate(SalaryRecord::query(), 'payment_date')->sum('net_salary');
        $netProfit = $totalRevenue - ($totalExpenses + $totalSalaries + $totalProductionCost);

        $salesByMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonths($i);
            $salesByMonth[] = [
                'label' => $month->translatedFormat('M Y'),
                'total' => Invoice::whereMonth('invoice_date', $month->month)
                    ->whereYear('invoice_date', $month->year)
                    ->sum('total_amount'),
            ];
        }

        return view('reports.index', compact(
            'totalSales', 'paidSales', 'remainingSales',
            'totalProductionOrders', 'completedProductionOrders', 'totalProductionCost',
            'productsValue', 'rawMaterialsValue',
            'totalRevenue', 'totalExpenses', 'totalSalaries', 'netProfit',
            'salesByMonth', 'filter'
        ));
    }

    public function weeklyProduction(Request $request, ProductionRegistrationService $service)
    {
        $start = $request->get('start', Carbon::now()->startOfWeek()->toDateString());
        $end = $request->get('end', Carbon::now()->endOfWeek()->toDateString());
        $report = $service->getWeeklyPairingReport($start, $end);

        return view('reports.weekly-production', compact('report', 'start', 'end'));
    }

    public function customerInvoices(Request $request)
    {
        $filter = $request->input('filter', 'monthly');
        $now = Carbon::now();

        $startDate = null;
        $endDate = null;

        if ($filter === 'daily') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
        } elseif ($filter === 'weekly') {
            $startDate = $now->copy()->startOfWeek(Carbon::SATURDAY);
            $endDate = $now->copy()->endOfWeek(Carbon::FRIDAY);
        } elseif ($filter === 'monthly') {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        } elseif ($filter === 'yearly') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        }

        $query = Invoice::with('customer')->orderBy('invoice_date', 'desc');

        if ($startDate && $endDate) {
            $query->whereBetween('invoice_date', [$startDate, $endDate]);
        }

        $invoices = $query->get();

        return view('reports.customer_invoices', compact('invoices', 'filter', 'startDate', 'endDate'));
    }
}
