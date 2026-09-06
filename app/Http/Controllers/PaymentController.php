<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\CustomerDeposit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CashLedgerService;
use App\Services\InvoiceAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        Invoice $invoice,
        CashLedgerService $ledger,
        InvoiceAccountingService $accounting
    ) {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,check,deposit',
            'treasury_id' => 'required_unless:payment_method,deposit|exists:treasuries,id',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, &$invoice, $ledger, $accounting) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
            ]);

            $invoice->increment('paid_amount', $request->amount);
            $invoice->refresh();
            $accounting->syncFromPaidAmount($invoice);

            if ($request->payment_method !== 'deposit') {
                $ledger->record(
                    'payment',
                    $request->amount,
                    "تحصيل فاتورة {$invoice->invoice_number} (طريقة الدفع: {$request->payment_method})",
                    $invoice,
                    $request->payment_date,
                    $request->treasury_id
                );
            } elseif ($invoice->customer_id) {
                $customer = \App\Models\Customer::lockForUpdate()->find($invoice->customer_id);
                if ($customer) {
                    $customer->decrement('deposit_balance', $request->amount);
                    CustomerDeposit::create([
                        'customer_id' => $customer->id,
                        'amount' => $request->amount,
                        'date' => $request->payment_date,
                        'type' => 'applied',
                        'description' => "تطبيق عربون على الفاتورة {$invoice->invoice_number}" . ($request->notes ? " - {$request->notes}" : ''),
                    ]);
                }
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'تم تسجيل الدفعة بنجاح');
    }

    public function destroy(
        Payment $payment,
        CashLedgerService $ledger,
        InvoiceAccountingService $accounting
    ) {
        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice, $ledger, $accounting) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->payment_method === 'deposit' && $invoice->customer_id) {
                $customer = \App\Models\Customer::lockForUpdate()->find($invoice->customer_id);
                if ($customer) {
                    $customer->increment('deposit_balance', $payment->amount);
                    CustomerDeposit::where('customer_id', $customer->id)
                        ->where('type', 'applied')
                        ->where('amount', $payment->amount)
                        ->whereDate('date', $payment->payment_date)
                        ->where('description', 'like', "%{$invoice->invoice_number}%")
                        ->latest('id')
                        ->limit(1)
                        ->delete();
                }
            } else {
                $treasuryId = CashTransaction::where('reference_type', Invoice::class)
                    ->where('reference_id', $invoice->id)
                    ->where('type', 'payment')
                    ->whereDate('transaction_date', $payment->payment_date)
                    ->whereRaw('ABS(amount) = ?', [(float) $payment->amount])
                    ->latest('id')
                    ->value('treasury_id');

                $ledger->record(
                    'payment_reversal',
                    $payment->amount,
                    "عكس تحصيل فاتورة {$invoice->invoice_number}",
                    $invoice,
                    now()->toDateString(),
                    $treasuryId
                );
            }

            $invoice->decrement('paid_amount', $payment->amount);
            $invoice->refresh();
            $accounting->syncFromPaidAmount($invoice);

            $payment->delete();
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'تم حذف الدفعة وعكس القيد بنجاح');
    }
}
