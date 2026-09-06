<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Worker;
use App\Models\WorkerProduction;

class WorkerWageCalculationService
{
    /**
     * حساب أجر الإنتاج للموظف حسب أولوية: سعر القطعة → أجر الوردية → سعر المنتج.
     *
     * @return array{labor_cost_per_piece: float, total_pay: float, wage_type: string}
     */
    public function calculate(Worker $worker, Product $product, int $quantity): array
    {
        $quantity = max(0, $quantity);

        if ($quantity === 0) {
            return [
                'labor_cost_per_piece' => 0,
                'total_pay' => 0,
                'wage_type' => 'none',
            ];
        }

        $isScissors = $worker->production_role === 'scissors';

        // =============================
        // نظام القطعة: السعر من جدول أسعار العمال
        // =============================
        if ($worker->worker_type === 'production' && ($worker->wage_system ?? 'shift') === 'piece') {
            $rate = 0;
            
            // Check specific worker product price (only for persisted workers)
            if ($worker->id) {
                $customPrice = \App\Models\WorkerProductPrice::where('worker_id', $worker->id)
                    ->where('product_id', $product->id)
                    ->first();
                    
                if ($customPrice && $customPrice->price > 0) {
                    $rate = (float) $customPrice->price;
                }
            }

            if ($rate <= 0) {
                // Fallback to default product price if not set
                if ($isScissors) {
                    $rate = (float) ($product->piece_wage_scissors ?? 0);
                    if ($rate <= 0) $rate = (float) ($product->scissors_cost ?? 0);
                } else {
                    $rate = (float) ($product->piece_wage ?? 0);
                    if ($rate <= 0) $rate = (float) ($product->labor_cost ?? 0);
                }
            }

            return [
                'labor_cost_per_piece' => $rate,
                'total_pay' => round($rate * $quantity, 2),
                'wage_type' => 'piece_product',
            ];
        }

        // =============================
        // نظام الورديات
        // =============================
        if (($worker->shift_wage ?? 0) > 0) {
            $target = (int) ($product->shift_target_quantity ?? 0);

            if ($target > 0) {
                $totalPay = round(($quantity / $target) * (float) $worker->shift_wage, 2);
                $effectiveRate = round((float) $worker->shift_wage / $target, 4);
            } else {
                $totalPay = round((float) $worker->shift_wage, 2);
                $effectiveRate = $quantity > 0 ? round($totalPay / $quantity, 4) : 0;
            }

            return [
                'labor_cost_per_piece' => $effectiveRate,
                'total_pay' => $totalPay,
                'wage_type' => 'shift',
            ];
        }

        // =============================
        // احتياطي: سعر القطعة على الموظف (قديم)
        // =============================
        if (($worker->piece_price ?? 0) > 0) {
            $rate = (float) $worker->piece_price;
            return [
                'labor_cost_per_piece' => $rate,
                'total_pay' => round($rate * $quantity, 2),
                'wage_type' => 'piece_legacy',
            ];
        }

        // احتياطي أخير: سعر الموظفينة من المنتج نفسه
        $productRate = (float) ($isScissors ? ($product->scissors_cost ?? 0) : ($product->labor_cost ?? 0));
        return [
            'labor_cost_per_piece' => $productRate,
            'total_pay' => round($productRate * $quantity, 2),
            'wage_type' => 'product',
        ];
    }

    public function hasConfiguredWage(Worker $worker, Product $product): bool
    {
        if (($worker->piece_price ?? 0) > 0 || ($worker->shift_wage ?? 0) > 0) {
            return true;
        }

        $isScissors = $worker->production_role === 'scissors';
        
        $pieceWage = (float) ($isScissors ? ($product->piece_wage_scissors ?? 0) : ($product->piece_wage ?? 0));
        if ($pieceWage > 0) {
            return true;
        }

        $productRate = (float) ($isScissors ? ($product->scissors_cost ?? 0) : ($product->labor_cost ?? 0));

        return $productRate > 0;
    }

    /**
     * يحسب أجر سجل الإنتاج من إعدادات الموظف الحالية ويُحدّث السجل إذا كان المبلغ المحفوظ قديماً/خاطئاً.
     */
    public function resolveProductionPay(Worker $worker, WorkerProduction $production, bool $sync = true): float
    {
        $product = $production->product;
        if (!$product) {
            return (float) $production->total_pay;
        }

        $pay = $this->calculate($worker, $product, (int) $production->quantity);

        if ($sync) {
            $storedPay = round((float) $production->total_pay, 2);
            $storedRate = round((float) $production->labor_cost_per_piece, 4);

            if ($storedPay !== $pay['total_pay'] || $storedRate !== (float) $pay['labor_cost_per_piece']) {
                $production->update([
                    'total_pay' => $pay['total_pay'],
                    'labor_cost_per_piece' => $pay['labor_cost_per_piece'],
                ]);
            }
        }

        return $pay['total_pay'];
    }

    /**
     * إعادة حساب أجور سجلات الإنتاج لفترة معينة.
     */
    public function recalculateForPeriod(string $startDate, string $endDate): int
    {
        $updated = 0;

        WorkerProduction::with(['worker', 'product'])
            ->whereBetween('date', [$startDate, $endDate])
            ->chunkById(200, function ($records) use (&$updated) {
                foreach ($records as $production) {
                    if (!$production->worker || !$production->product) {
                        continue;
                    }

                    $before = round((float) $production->total_pay, 2);
                    $after = $this->resolveProductionPay($production->worker, $production);

                    if ($before !== $after) {
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}

