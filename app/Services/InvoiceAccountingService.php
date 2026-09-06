<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceAccountingService
{
    /**
     * يُحدّث المتبقي والحالة من (الإجمالي − المدفوع) ويحفظ على الفاتورة.
     *
     * @return array{remaining_amount: float, status: string}
     */
    public function syncFromPaidAmount(Invoice $invoice): array
    {
        $total = round((float) $invoice->total_amount, 2);
        $paid = round((float) $invoice->paid_amount, 2);
        $remaining = max(0, round($total - $paid, 2));

        $status = 'draft';
        if ($paid >= $total && $total > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }

        if ($invoice->exists) {
            if (
                round((float) $invoice->remaining_amount, 2) !== $remaining
                || $invoice->status !== $status
            ) {
                $invoice->update([
                    'remaining_amount' => $remaining,
                    'status' => $status,
                ]);
            }
        } else {
            $invoice->remaining_amount = $remaining;
            $invoice->status = $status;
        }

        return [
            'remaining_amount' => $remaining,
            'status' => $status,
        ];
    }

    public function recalculateAll(): int
    {
        $updated = 0;

        Invoice::query()->chunkById(200, function ($invoices) use (&$updated) {
            foreach ($invoices as $invoice) {
                $before = round((float) $invoice->remaining_amount, 2);
                $result = $this->syncFromPaidAmount($invoice->fresh());
                if ($before !== $result['remaining_amount']) {
                    $updated++;
                }
            }
        });

        return $updated;
    }
}
