<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\WorkerAdvance;
use App\Models\Treasury;
use App\Services\CashLedgerService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use ClearsLayoutCache;
    public function index(Request $request, CashLedgerService $ledger)
    {
        $filter = $request->input('filter', 'monthly');
        
        // Persistence logic for treasury selection
        if ($request->has('treasury_id')) {
            $selectedTreasuryId = $request->input('treasury_id');
            session(['active_treasury_id' => $selectedTreasuryId]);
        } else {
            $selectedTreasuryId = session('active_treasury_id');
        }

        $now = now();
        
        $query = Expense::query();
        if ($filter === 'daily') {
            $query->whereDate('expense_date', $now->toDateString());
        } elseif ($filter === 'weekly') {
            $startDate = $now->copy()->startOfWeek(Carbon::SATURDAY)->toDateString();
            $endDate   = $now->copy()->endOfWeek(Carbon::FRIDAY)->toDateString();
            $query->whereDate('expense_date', '>=', $startDate)->whereDate('expense_date', '<=', $endDate);
        } elseif ($filter === 'monthly') {
            $query->whereMonth('expense_date', $now->month)->whereYear('expense_date', $now->year);
        } elseif ($filter === 'yearly') {
            $query->whereYear('expense_date', $now->year);
        }

        $expenses      = $query->latest('expense_date')->latest('id')->get();
        $filteredTotal = $expenses->sum('amount');
        $categories    = $expenses->groupBy('category');

        $treasuries = Treasury::where('is_active', true)->get();
        
        $summary = $ledger->getSummary($filter, $selectedTreasuryId);

        $txQuery = CashTransaction::with(['reference', 'treasury']);
        if ($selectedTreasuryId) {
            $txQuery->where('treasury_id', $selectedTreasuryId);
        }
        $transactions = $txQuery->latest('transaction_date')->latest('id')->paginate(50);

        // --- Today's Accountant Ledger (تسليم عهدة اليوم) ---
        $historyTreasuryId = $selectedTreasuryId ?? Treasury::defaultTreasury()?->id;
        $today = now()->toDateString();
        
        $todayTransactions = CashTransaction::with('reference')
            ->where('treasury_id', $historyTreasuryId)
            ->whereDate('transaction_date', $today)
            ->orderBy('id', 'asc') // أقدم حركة أولاً لمعرفة الرصيد الافتتاحي بشكل صحيح
            ->get();
            
        $todayIn = $todayTransactions->where('amount', '>', 0)->sum('amount');
        $todayOut = $todayTransactions->where('amount', '<', 0)->sum(fn($tx) => abs($tx->amount));
        
        // Opening balance for today is the balance_after of the first transaction TODAY minus its amount, 
        // OR if no transactions today, just the current treasury balance.
        $openingBalance = 0;
        if ($todayTransactions->count() > 0) {
            $firstTx = $todayTransactions->first();
            $openingBalance = $firstTx->balance_after - $firstTx->amount;
        } else {
            $openingBalance = Treasury::find($historyTreasuryId)?->balance ?? 0;
        }
        
        $closingBalance = $openingBalance + $todayIn - $todayOut;
        
        $todayLedger = [
            'opening_balance' => $openingBalance,
            'total_in' => $todayIn,
            'total_out' => $todayOut,
            'closing_balance' => $closingBalance,
            'transactions' => $todayTransactions->sortByDesc('id')->values() // نعرضها للأحدث فوق
        ];
        // ----------------------------------------------------

        $customCategories = json_decode(Setting::get('expense_categories', '[]'), true) ?: [];
        $pastCategories = Expense::select('category')->distinct()->pluck('category')->toArray();
        $allCategories  = collect(array_unique(array_merge($customCategories, $pastCategories)))->sort()->values();

        // --- Advances Data for the Advances Tab ---
        // 1. Recompute pending balances safely
        $balances = DB::table('worker_advances')
            ->where('is_deducted', false)
            ->select('worker_id', DB::raw('SUM(amount) as total_advances'))
            ->groupBy('worker_id')
            ->pluck('total_advances', 'worker_id');

        $activeWorkers = \App\Models\Worker::where('is_active', true)->get();
        foreach ($activeWorkers as $w) {
            $realBalance = $balances[$w->id] ?? 0;
            if ($w->pending_advance_balance != $realBalance) {
                $w->update(['pending_advance_balance' => $realBalance]);
            }
        }

        $workersWithAdvances = \App\Models\Worker::where('is_active', true)
            ->withCount(['advances as pending_count' => fn($q) => $q->where('is_deducted', false)])
            ->orderByDesc('pending_advance_balance')
            ->get();

        $selectedWorkerId = $request->get('worker_id');
        $advancesQuery = WorkerAdvance::with('worker')->orderBy('date', 'desc');
        if ($selectedWorkerId) {
            $advancesQuery->where('worker_id', $selectedWorkerId);
        }
        $allAdvances = $advancesQuery->where('is_deducted', false)->get();
        $totalPendingAdvances = WorkerAdvance::where('is_deducted', false)->sum('amount');
        // ------------------------------------------

        return view('expenses.index', compact(
            'expenses', 'filteredTotal', 'categories', 'summary', 'transactions', 'filter',
            'todayLedger', 'allCategories', 'treasuries', 'selectedTreasuryId',
            'workersWithAdvances', 'allAdvances', 'totalPendingAdvances', 'selectedWorkerId', 'activeWorkers'
        ));
    }

    public function store(Request $request, CashLedgerService $ledger)
    {
        $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'category'    => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0.01',
            'expense_date'=> 'required|date',
            'description' => 'nullable|string',
        ]);

        $this->saveCustomCategory($request->category);
        $expense = Expense::create($request->only('category', 'amount', 'expense_date', 'description'));

        $ledger->record(
            'expense',
            $request->amount,
            'مصروف (' . $request->category . '): ' . ($request->description ?: '-'),
            $expense,
            $request->expense_date,
            $request->treasury_id
        );

        return back()->with('success', 'تم تسجيل المصروف وخصمه من الخزينة المحددة');
    }

    public function update(Request $request, Expense $expense, CashLedgerService $ledger)
    {
        $request->validate([
            'category'    => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0',
            'expense_date'=> 'required|date',
            'description' => 'nullable|string',
        ]);

        $this->saveCustomCategory($request->category);
        $expense->update($request->only('category', 'amount', 'expense_date', 'description'));

        $tx = CashTransaction::where('reference_type', get_class($expense))
            ->where('reference_id', $expense->id)->first();

        if ($tx) {
            $tx->update([
                'amount'           => -abs($request->amount),
                'description'      => 'مصروف (' . $request->category . '): ' . ($request->description ?: '-'),
                'transaction_date' => $request->expense_date,
            ]);
            $ledger->recalculateLedger($tx->treasury_id);
        }

        return back()->with('success', 'تم تعديل المصروف وتحديث الرصيد');
    }

    public function destroy(Expense $expense, CashLedgerService $ledger)
    {
        $tx = CashTransaction::where('reference_type', get_class($expense))
            ->where('reference_id', $expense->id)->first();
        
        if ($tx) {
            $treasuryId = $tx->treasury_id;
            $tx->delete();
            $ledger->recalculateLedger($treasuryId);
        }
        
        $expense->delete();

        return back()->with('success', 'تم حذف المصروف وتحديث الخزينة');
    }

    public function printReport(Request $request, CashLedgerService $ledger)
    {
        $filter = $request->input('filter', 'daily');
        $treasuryId = $request->input('treasury_id') ?? Treasury::defaultTreasury()?->id;
        $treasury = Treasury::find($treasuryId);
        
        $now    = now();
        $query  = Expense::query();
        $periodLabel = 'اليوم';

        if ($filter === 'daily') {
            $query->whereDate('expense_date', $now->toDateString());
            $periodLabel = 'يوم ' . $now->format('d/m/Y');
        } elseif ($filter === 'weekly') {
            $startDate   = $now->copy()->startOfWeek(Carbon::SATURDAY)->toDateString();
            $endDate     = $now->copy()->endOfWeek(Carbon::FRIDAY)->toDateString();
            $query->whereBetween('expense_date', [$startDate, $endDate]);
            $periodLabel = 'الأسبوع (' . $startDate . ' → ' . $endDate . ')';
        } elseif ($filter === 'monthly') {
            $query->whereMonth('expense_date', $now->month)->whereYear('expense_date', $now->year);
            $periodLabel = 'شهر ' . $now->translatedFormat('F Y');
        } elseif ($filter === 'yearly') {
            $query->whereYear('expense_date', $now->year);
            $periodLabel = 'سنة ' . $now->year;
        } elseif ($filter === 'all') {
            $periodLabel = 'كل الأوقات';
        }

        $expenses   = $query->orderBy('expense_date', 'asc')->get();
        $total      = $expenses->sum('amount');
        $categories = $expenses->groupBy('category');

        $todayIn  = CashTransaction::where('treasury_id', $treasuryId)->whereDate('transaction_date', today())->where('amount', '>', 0)->sum('amount');
        $todayOut = CashTransaction::where('treasury_id', $treasuryId)->whereDate('transaction_date', today())->where('amount', '<', 0)->sum(DB::raw('ABS(amount)'));
        
        $balance = $treasury ? $treasury->current_balance : 0;

        return view('expenses.print_report', compact(
            'expenses', 'total', 'categories', 'periodLabel', 'filter',
            'todayIn', 'todayOut', 'balance', 'treasury'
        ));
    }

    private function saveCustomCategory(string $category): void
    {
        $existing = json_decode(Setting::get('expense_categories', '[]'), true) ?: [];
        if (!in_array($category, $existing)) {
            $existing[] = $category;
            Setting::set('expense_categories', json_encode($existing, JSON_UNESCAPED_UNICODE));
        }
    }
}
