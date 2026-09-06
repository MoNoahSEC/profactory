<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\Supplier;
use App\Services\CashLedgerService;
use Illuminate\Http\Request;

class RawMaterialPurchaseController extends Controller
{
    public function index()
    {
        $purchases = RawMaterialPurchase::with(['supplier', 'rawMaterial'])->latest()->paginate(50);
        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $materials = RawMaterial::all();
        return view('purchases.create', compact('suppliers', 'materials'));
    }

    public function edit(RawMaterialPurchase $purchase)
    {
        $suppliers = Supplier::all();
        $materials = RawMaterial::all();
        return view('purchases.edit', compact('purchase', 'suppliers', 'materials'));
    }

    public function store(Request $request, CashLedgerService $ledger)
    {
        $request->validate([
            'supplier_id' => 'required',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'quantity' => 'required|numeric|min:0.1',
            'unit_price' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $totalPrice = $request->quantity * $request->unit_price;
        $paidAmount = $request->paid_amount;
        $excess = 0;

        if ($paidAmount > $totalPrice) {
            $excess = $paidAmount - $totalPrice;
            $paidAmount = $totalPrice; // Cap the current invoice temporarily
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $totalPrice, &$paidAmount, &$excess, $ledger) {
            
            // Auto-create supplier if a new name was provided (not an ID)
            $supplierId = $request->supplier_id;
            if (!is_numeric($supplierId)) {
                $newSupplier = Supplier::firstOrCreate(['name' => trim($supplierId)]);
                $supplierId = $newSupplier->id;
            } else {
                // Ensure it exists just in case
                $exists = Supplier::find($supplierId);
                if (!$exists) {
                    $newSupplier = Supplier::create(['name' => trim($supplierId)]);
                    $supplierId = $newSupplier->id;
                }
            }

            $purchase = RawMaterialPurchase::create([
                'supplier_id' => $supplierId,
                'raw_material_id' => $request->raw_material_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_price' => $totalPrice,
                'paid_amount' => $paidAmount,
                'purchase_date' => $request->purchase_date,
                'notes' => $request->notes,
            ]);

            // Distribute excess to old unpaid debts
            if ($excess > 0) {
                $supplier = Supplier::find($supplierId);
                $unpaidPurchases = $supplier->purchases()
                    ->whereRaw('total_price > paid_amount')
                    ->where('id', '!=', $purchase->id)
                    ->orderBy('purchase_date', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach($unpaidPurchases as $oldPurchase) {
                    if ($excess <= 0) break;
                    $rem = $oldPurchase->total_price - $oldPurchase->paid_amount;
                    $payNow = min($excess, $rem);
                    $oldPurchase->increment('paid_amount', $payNow);
                    $excess -= $payNow;
                }

                // If there's STILL excess (they paid more than all debts), put it on current invoice as credit
                if ($excess > 0) {
                    $purchase->increment('paid_amount', $excess);
                }
            }

            // Add to stock
            $material = RawMaterial::lockForUpdate()->find($request->raw_material_id);
            $material->increment('current_stock', $request->quantity);

            // Record payment in cash ledger (Record the full original amount at once)
            if ($request->paid_amount > 0) {
                $ledger->record(
                    'expense',
                    $request->paid_amount,
                    "سداد فاتورة شراء خامات من המورد ({$purchase->supplier->name})" . ($request->paid_amount > $totalPrice ? " وتخفيض ديون سابقة" : ""),
                    $purchase,
                    $request->purchase_date
                );
            }
        });

        return redirect()->route('purchases.index')->with('success', 'تم تسجيل الفاتورة بنجاح. ' . ($request->paid_amount > $totalPrice ? 'وتم ترحيل المبلغ الزائد لحساب المورد.' : ''));
    }

    public function update(Request $request, RawMaterialPurchase $purchase, CashLedgerService $ledger)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.1',
            'unit_price' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $purchase, $ledger) {
            $purchase = RawMaterialPurchase::lockForUpdate()->find($purchase->id);
            $material = RawMaterial::lockForUpdate()->find($purchase->raw_material_id);

            // Handle stock change
            $qtyDifference = $request->quantity - $purchase->quantity;
            if ($qtyDifference != 0) {
                $material->increment('current_stock', $qtyDifference);
            }

            // Handle cash change
            $paidDifference = $request->paid_amount - $purchase->paid_amount;
            $tx = \App\Models\CashTransaction::where('reference_type', get_class($purchase))
                ->where('reference_id', $purchase->id)
                ->first();
            if ($tx) {
                $tx->update([
                    'amount' => -abs($request->paid_amount),
                    'transaction_date' => $request->purchase_date,
                ]);
            }

            $purchase->update([
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_price' => $request->quantity * $request->unit_price,
                'paid_amount' => $request->paid_amount,
                'purchase_date' => $request->purchase_date,
                'notes' => $request->notes,
            ]);
        });
        $ledger->recalculateLedger();
        return redirect()->route('purchases.index')->with('success', 'تم تعديل الفاتورة وتحديث المخزون والخزينة بنجاح');
    }

    public function destroy(RawMaterialPurchase $purchase, CashLedgerService $ledger)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($purchase) {
            // Remove from stock
            $material = RawMaterial::lockForUpdate()->find($purchase->raw_material_id);
            if ($material) {
                $material->decrement('current_stock', $purchase->quantity);
            }

            // Refund cash if paid
            if ($purchase->paid_amount > 0) {
                \App\Models\CashTransaction::where('reference_type', get_class($purchase))
                    ->where('reference_id', $purchase->id)
                    ->delete();
            }

            $purchase->delete();
        });
        
        $ledger->recalculateLedger();
        
        return redirect()->back()->with('success', 'تم حذف الفاتورة وخصم الكمية من المخزون واسترداد المبلغ كأنه لم يكن');
    }

    public function print(RawMaterialPurchase $purchase)
    {
        $purchase->load(['supplier', 'rawMaterial']);
        return view('purchases.print', compact('purchase'));
    }
}
