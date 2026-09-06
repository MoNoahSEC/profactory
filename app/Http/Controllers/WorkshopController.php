<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use App\Models\WorkshopTransaction;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function index()
    {
        $workshops = Workshop::withSum('invoices', 'net_amount')
            ->withSum('invoices', 'paid_amount')
            ->paginate(50);
            
        // Pre-calculate outstanding_balance to avoid running queries in the view
        foreach ($workshops as $workshop) {
            $totalInvoiced = $workshop->invoices_sum_net_amount ?? 0;
            $totalPaid = $workshop->invoices_sum_paid_amount ?? 0;
            $unpaidInvoices = $totalInvoiced - $totalPaid;
            $workshop->precalculated_balance = round($unpaidInvoices - (float)$workshop->deposit_balance, 2);
        }
            
        return view('workshops.index', compact('workshops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'initial_balance' => 'nullable|numeric',
        ]);

        Workshop::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'deposit_balance' => $request->initial_balance ?? 0,
        ]);

        return redirect()->route('workshops.index')->with('success', 'تم إضافة الورشة بنجاح.');
    }

    public function update(Request $request, Workshop $workshop)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $workshop->update($request->only(['name', 'phone', 'address']));

        return redirect()->route('workshops.index')->with('success', 'تم تحديث الورشة بنجاح.');
    }

    public function show(Workshop $workshop)
    {
        $workshop->load(['invoices', 'transactions']);
        $sorted = $workshop->getStatement();

        // Calculate totals for summary
        $totalInvoicesNet = $workshop->invoices->sum('net_amount');
        $totalInvoicesPaid = $workshop->invoices->sum('paid_amount');
        $invoiceDebt = $totalInvoicesNet - $totalInvoicesPaid;
        
        $depositBalance = $workshop->deposit_balance;
        $netBalance = $invoiceDebt - $depositBalance; // Positive = owes us, Negative = we owe them

        return view('workshops.show', compact('workshop', 'sorted', 'netBalance', 'depositBalance', 'invoiceDebt'));
    }

    public function print(Workshop $workshop)
    {
        $workshop->load(['invoices', 'transactions']);
        $sorted = $workshop->getStatement();

        $totalInvoicesNet = $workshop->invoices->sum('net_amount');
        $totalInvoicesPaid = $workshop->invoices->sum('paid_amount');
        $invoiceDebt = $totalInvoicesNet - $totalInvoicesPaid;
        
        $depositBalance = $workshop->deposit_balance;
        $netBalance = $invoiceDebt - $depositBalance; 

        return view('workshops.print', compact('workshop', 'sorted', 'netBalance', 'depositBalance', 'invoiceDebt'));
    }

    public function printDirect(Workshop $workshop)
    {
        $url = route('workshops.print', $workshop->id);
        
        // Using MS Edge Headless to print directly to the default printer on the Windows server
        $cmd = "start msedge --headless --disable-gpu --print-to-default \"{$url}\"";
        
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen($cmd, "r"));
                return back()->with('success', 'تم إرسال أمر الطباعة إلى طابعة الكمبيوتر بنجاح.');
            }
            return back()->with('error', 'الطباعة المباشرة مدعومة فقط على خوادم الويندوز.');
        } catch (\Exception $e) {
            return back()->with('error', 'فشل في إرسال أمر الطباعة: ' . $e->getMessage());
        }
    }

    public function storeTransaction(Request $request, Workshop $workshop)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'type' => 'required|in:payment,receipt,debt_adjustment,old_debt_us,old_debt_them',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = (float) $request->amount;

        $transaction = WorkshopTransaction::create([
            'workshop_id' => $workshop->id,
            'type' => $request->type,
            'amount' => $amount,
            'date' => $request->date,
            'description' => $request->description,
            'created_by' => auth()->id(),
        ]);

        // Update deposit balance
        if ($request->type === 'payment') {
            // We paid them => reduces our debt to them / increases their debt to us => lowers deposit_balance
            $workshop->decrement('deposit_balance', $amount);
        } elseif ($request->type === 'receipt') {
            // They paid us => increases deposit_balance
            $workshop->increment('deposit_balance', $amount);
        } elseif (in_array($request->type, ['debt_adjustment', 'old_debt_us'])) {
            // Adjust old debt (they owe us more) => decreases deposit_balance
            $workshop->decrement('deposit_balance', $amount);
        } elseif ($request->type === 'old_debt_them') {
            // Adjust old debt (we owe them more) => increases deposit_balance
            $workshop->increment('deposit_balance', $amount);
        }

        return redirect()->back()->with('success', 'تم تسجيل الدفعة بنجاح.');
    }

    public function reset(Workshop $workshop)
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Revert Inventory for all WorkshopInvoices
            $invoices = $workshop->invoices()->with('items')->get();
            foreach ($invoices as $invoice) {
                foreach ($invoice->items as $item) {
                    if ($item->item_type === 'raw_material' && $item->transaction_type === 'sell') {
                        $rawMaterial = \App\Models\RawMaterial::find($item->item_id);
                        if ($rawMaterial) {
                            $rawMaterial->increment('current_stock', $item->quantity);
                        }
                    } elseif ($item->item_type === 'product' && $item->transaction_type === 'buy') {
                        $inventory = \App\Models\Inventory::where('product_id', $item->item_id)->first();
                        if ($inventory) {
                            $inventory->decrement('current_stock', $item->quantity);
                            $inventory->decrement('quantity_in', $item->quantity);
                        }
                    }
                }
                // Delete Items
                $invoice->items()->delete();
                // Delete Invoice
                $invoice->delete();
            }

            // 2. Delete all transactions
            $workshop->transactions()->delete();

            // 3. Reset balance
            $workshop->update(['deposit_balance' => 0]);

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('workshops.show', $workshop)->with('success', 'تم تصفير حساب الورشة واسترجاع المخازن بنجاح.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء تصفير الورشة: ' . $e->getMessage());
        }
    }
    public function updateTransaction(Request $request, \App\Models\WorkshopTransaction $transaction)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'type' => 'required|in:payment,receipt,debt_adjustment,old_debt_us,old_debt_them',
            'description' => 'nullable|string|max:255',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $workshop = $transaction->workshop;
            
            // Revert old effect
            if ($transaction->type === 'payment') {
                $workshop->increment('deposit_balance', $transaction->amount);
            } elseif ($transaction->type === 'receipt') {
                $workshop->decrement('deposit_balance', $transaction->amount);
            } elseif (in_array($transaction->type, ['debt_adjustment', 'old_debt_us'])) {
                $workshop->increment('deposit_balance', $transaction->amount);
            } elseif ($transaction->type === 'old_debt_them') {
                $workshop->decrement('deposit_balance', $transaction->amount);
            }

            // Update
            $transaction->update([
                'amount' => $request->amount,
                'date' => $request->date,
                'type' => $request->type,
                'description' => $request->description,
            ]);

            // Apply new effect
            if ($transaction->type === 'payment') {
                $workshop->decrement('deposit_balance', $request->amount);
            } elseif ($transaction->type === 'receipt') {
                $workshop->increment('deposit_balance', $request->amount);
            } elseif (in_array($transaction->type, ['debt_adjustment', 'old_debt_us'])) {
                $workshop->decrement('deposit_balance', $request->amount);
            } elseif ($transaction->type === 'old_debt_them') {
                $workshop->increment('deposit_balance', $request->amount);
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'تم تعديل العملية بنجاح.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء التعديل: ' . $e->getMessage());
        }
    }

    public function destroyTransaction(\App\Models\WorkshopTransaction $transaction)
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $workshop = $transaction->workshop;
            
            // Revert effect
            if ($transaction->type === 'payment') {
                $workshop->increment('deposit_balance', $transaction->amount);
            } elseif ($transaction->type === 'receipt') {
                $workshop->decrement('deposit_balance', $transaction->amount);
            } elseif (in_array($transaction->type, ['debt_adjustment', 'old_debt_us'])) {
                $workshop->increment('deposit_balance', $transaction->amount);
            } elseif ($transaction->type === 'old_debt_them') {
                $workshop->decrement('deposit_balance', $transaction->amount);
            }

            $transaction->delete();

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'تم حذف العملية واسترجاع الرصيد بنجاح.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }
}
