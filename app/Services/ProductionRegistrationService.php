<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Worker;
use App\Models\WorkerProduction;
use Illuminate\Support\Facades\DB;

class ProductionRegistrationService
{
    public function __construct(private WorkerWageCalculationService $wageService) {}

    public function registerMachinistProduction(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $machinist = Worker::findOrFail($data['machinist_worker_id']);
            $product = Product::findOrFail($data['product_id']);
            $quantity = (int) $data['quantity'];
            $date = $data['date'];
            $status = $data['status'] ?? 'present';

            if ($machinist->production_role !== 'machinist') {
                throw new \InvalidArgumentException('الموظف المختار ليس مكنجي إنتاج.');
            }

            $machinistPay = $this->wageService->calculate($machinist, $product, $quantity);
            if ($machinistPay['total_pay'] <= 0) {
                throw new \InvalidArgumentException('لم يُحدَّد أجر للمكنجي (سعر قطعة، أجر وردية، أو سعر المنتج).');
            }

            Attendance::updateOrCreate(
                ['worker_id' => $machinist->id, 'date' => $date],
                [
                    'status' => $status,
                    'overtime_hours' => $data['overtime_hours'] ?? 0,
                    'notes' => $data['notes'] ?? null,
                ]
            );

            $machinistRecord = WorkerProduction::create([
                'worker_id' => $machinist->id,
                'product_id' => $product->id,
                'production_role' => 'machinist',
                'scissors_worker_id' => $data['scissors_worker_id'] ?? null,
                'date' => $date,
                'quantity' => $quantity,
                'labor_cost_per_piece' => $machinistPay['labor_cost_per_piece'],
                'total_pay' => $machinistPay['total_pay'],
                'inventory_added' => true,
                'notes' => $data['notes'] ?? null,
            ]);

            $scissorsRecord = null;
            if (!empty($data['scissors_worker_id'])) {
                $scissors = Worker::findOrFail($data['scissors_worker_id']);
                $scissorsPay = $this->wageService->calculate($scissors, $product, $quantity);

                if ($scissors->production_role !== 'scissors') {
                    throw new \InvalidArgumentException('الموظف المختار في المقص ليس موظف مقص.');
                }

                if ($scissorsPay['total_pay'] <= 0) {
                    throw new \InvalidArgumentException('لم يُحدَّد أجر للمقص (سعر قطعة، أجر وردية، أو سعر المنتج).');
                }

                $scissorsRecord = WorkerProduction::create([
                    'worker_id' => $scissors->id,
                    'product_id' => $product->id,
                    'production_role' => 'scissors',
                    'machinist_worker_id' => $machinist->id,
                    'paired_production_id' => $machinistRecord->id,
                    'date' => $date,
                    'quantity' => $quantity,
                    'labor_cost_per_piece' => $scissorsPay['labor_cost_per_piece'],
                    'total_pay' => $scissorsPay['total_pay'],
                    'inventory_added' => false,
                    'notes' => "مقص وراء المكنجي: {$machinist->name}",
                ]);

                $machinistRecord->update(['paired_production_id' => $scissorsRecord->id]);
            }

            $this->addToInventory($product, $quantity);

            return [
                'machinist' => $machinistRecord->load(['worker', 'product', 'scissorsWorker']),
                'scissors' => $scissorsRecord?->load(['worker', 'product', 'machinistWorker']),
            ];
        });
    }

    protected function addToInventory(Product $product, int $quantity): void
    {
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $product->id],
            ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
        );

        $inventory->increment('quantity_in', $quantity);
        $inventory->increment('current_stock', $quantity);
        $inventory->update(['last_updated' => now()]);

        $product->loadMissing('materials');
        foreach ($product->materials as $material) {
            $neededQty = $material->pivot->quantity_needed * $quantity;
            $material->decrement('current_stock', $neededQty);
        }
    }

    public function getWeeklyPairingReport(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ?? now()->startOfWeek()->toDateString();
        $end = $endDate ?? now()->endOfWeek()->toDateString();

        $records = WorkerProduction::with(['worker', 'product', 'machinistWorker', 'scissorsWorker'])
            ->where('production_role', 'machinist')
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $pairings = WorkerProduction::with(['worker', 'product', 'machinistWorker'])
            ->where('production_role', 'scissors')
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->groupBy('worker_id');

        $scissorsSummary = $pairings->map(function ($items, $scissorsId) {
            $worker = $items->first()->worker;
            $machinists = $items->groupBy('machinist_worker_id')->map(function ($group) {
                return [
                    'machinist' => $group->first()->machinistWorker?->name ?? '-',
                    'machinist_id' => $group->first()->machinist_worker_id,
                    'total_qty' => $group->sum('quantity'),
                    'total_pay' => $group->sum('total_pay'),
                    'days' => $group->pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->unique()->values(),
                ];
            })->values();

            return [
                'scissors' => $worker?->name ?? '-',
                'scissors_id' => $scissorsId,
                'total_qty' => $items->sum('quantity'),
                'total_pay' => $items->sum('total_pay'),
                'machinists' => $machinists,
            ];
        })->values();

        return [
            'start' => $start,
            'end' => $end,
            'machinist_records' => $records,
            'scissors_summary' => $scissorsSummary,
            'total_produced' => $records->sum('quantity'),
        ];
    }
}
