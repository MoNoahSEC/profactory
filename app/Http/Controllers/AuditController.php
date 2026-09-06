<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalaryRecord;
use App\Models\WorkerAdvance;
use App\Models\CashTransaction;
use App\Models\Worker;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $startDate = null;
        $endDate = Carbon::today();

        switch ($period) {
            case 'today':
                $startDate = Carbon::today();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek(Carbon::SATURDAY);
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
                $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::today()->endOfDay();
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
        }

        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        // =============================================
        // 📊 إحصائيات الفواتير
        // =============================================
        $invoices = Invoice::with(['customer', 'payments'])
            ->whereBetween('invoice_date', [$startStr, $endStr])
            ->latest('invoice_date')
            ->get();

        $invoiceStats = [
            'total_count'       => $invoices->count(),
            'total_amount'      => $invoices->sum('total_amount'),
            'total_collected'   => $invoices->sum('paid_amount'),
            'total_remaining'   => $invoices->sum('remaining_amount'),
            'paid_count'        => $invoices->where('status', 'paid')->count(),
            'partial_count'     => $invoices->where('status', 'partial')->count(),
            'draft_count'       => $invoices->whereIn('status', ['draft', 'overdue'])->count(),
        ];

        // =============================================
        // 💰 إحصائيات قبض العمال
        // =============================================
        $salaryRecords = SalaryRecord::with('worker')
            ->whereBetween('payment_date', [$startStr, $endStr])
            ->where('payment_status', 'paid')
            ->latest('payment_date')
            ->get();

        $salaryStats = [
            'total_count'       => $salaryRecords->count(),
            'total_paid'        => $salaryRecords->sum('net_salary'),
            'total_advances_deducted' => $salaryRecords->sum('advances'),
            'total_penalties'   => $salaryRecords->sum('deductions'),
            'total_bonuses'     => $salaryRecords->sum('bonuses'),
        ];

        // =============================================
        // 💸 إحصائيات السلف
        // =============================================
        $advances = WorkerAdvance::with('worker')
            ->whereBetween('date', [$startStr, $endStr])
            ->orderByDesc('date')
            ->get();

        $advanceStats = [
            'total_count'       => $advances->count(),
            'total_amount'      => $advances->sum('amount'),
            'deducted_amount'   => $advances->where('is_deducted', true)->sum('amount'),
            'pending_amount'    => $advances->where('is_deducted', false)->sum('amount'),
        ];

        // =============================================
        // 📋 سجل الخزينة (آخر العمليات)
        // =============================================
        $cashTransactions = CashTransaction::whereBetween('transaction_date', [$startStr, $endStr])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $cashStats = [
            'total_in'  => $cashTransactions->where('type', 'in')->sum('amount'),
            'total_out' => $cashTransactions->where('type', 'out')->sum('amount'),
            'net'       => $cashTransactions->where('type', 'in')->sum('amount') - $cashTransactions->where('type', 'out')->sum('amount'),
        ];

        // =============================================
        // 🚨 تنبيهات المراجعة (تناقضات)
        // =============================================
        $alerts = [];

        // تحقق: فواتير بدون أي دفعات مضى عليها وقت
        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->whereRaw('(total_amount - paid_amount) > 0.01')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today())
            ->count();
        if ($overdueInvoices > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'bi-exclamation-triangle-fill', 'msg' => "يوجد {$overdueInvoices} فاتورة متأخرة (تجاوزت تاريخ الاستحقاق ولم تُسدَّد)"];
        }

        // تحقق: عمال عليهم سلف متراكمة كبيرة
        $bigDebtWorkers = Worker::where('is_active', true)
            ->where('pending_advance_balance', '>', 1000)
            ->count();
        if ($bigDebtWorkers > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'bi-cash-coin', 'msg' => "يوجد {$bigDebtWorkers} موظف عليه سلف تتجاوز 1000 ج.م"];
        }

        // تحقق: فواتير تم حذف دفعاتها (paid_amount لا يساوي مجموع المدفوعات المسجلة)
        $invoicesWithMismatch = Invoice::withSum('payments', 'amount')
            ->get()
            ->filter(function ($invoice) {
                $paymentsSum = $invoice->payments_sum_amount ?? 0;
                return abs($invoice->paid_amount - $paymentsSum) > 0.01;
            })
            ->count();

        if ($invoicesWithMismatch > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'bi-bug-fill', 'msg' => "يوجد {$invoicesWithMismatch} فاتورة بها فارق في الحسابات (المبلغ المُحصّل المسجل لا يساوي مجموع الدفعات الفعلية)"];
        }

        $remainingMismatch = Invoice::query()
            ->whereRaw('ABS(remaining_amount - (total_amount - paid_amount)) > 0.01')
            ->count();
        if ($remainingMismatch > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'bi-calculator-fill', 'msg' => "يوجد {$remainingMismatch} فاتورة المتبقي المسجل فيها لا يطابق (الإجمالي − المدفوع). شغّل: php artisan invoices:reconcile-amounts"];
        }

        // أعلى 5 عملاء مديونية
        $topDebtors = Customer::where('is_active', true)
            ->orderByDesc('balance')
            ->where('balance', '>', 0)
            ->limit(5)
            ->get();

        return view('audit.index', compact(
            'invoices', 'invoiceStats',
            'salaryRecords', 'salaryStats',
            'advances', 'advanceStats',
            'cashTransactions', 'cashStats',
            'alerts', 'topDebtors',
            'startStr', 'endStr', 'period'
        ));
    }
}
