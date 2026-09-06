<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Product;
use App\Models\Worker;
use App\Models\WorkerProduction;
use App\Models\Inventory;
use App\Services\WorkerWageCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkerProductionController extends Controller
{
    use ClearsLayoutCache;
    public function __construct(private WorkerWageCalculationService $wageService) {}
    public function index()
    {
        $workers = Worker::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        $productions = WorkerProduction::with(['worker', 'product'])
            ->whereHas('worker', function($q) {
                $q->where('is_active', true);
            })
            ->orderBy('date', 'desc')->get();

        return view('worker-productions.index', compact('workers', 'products', 'productions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:workers,id',
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'quantity' => 'required|integer|min:1',
        ]);

        $worker = Worker::findOrFail($request->worker_id);
        $product = Product::with('materials')->findOrFail($request->product_id);

        if ($worker->production_role === 'scissors') {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'سجّل إنتاج المقص عبر تحضير المكنجي.');
        }

        $pay = $this->wageService->calculate($worker, $product, (int) $request->quantity);

        if ($pay['total_pay'] <= 0) {
            $this->clearLayoutCache();
        return redirect()->back()->with('error', 'لم يُحدَّد أجر لهذا الموظف (سعر قطعة، أجر وردية، أو سعر المنتج).');
        }

        DB::transaction(function () use ($request, $worker, $product, $pay) {
            WorkerProduction::create([
                'worker_id' => $request->worker_id,
                'product_id' => $request->product_id,
                'production_role' => $worker->production_role ?? 'machinist',
                'date' => $request->date,
                'quantity' => $request->quantity,
                'labor_cost_per_piece' => $pay['labor_cost_per_piece'],
                'total_pay' => $pay['total_pay'],
                'inventory_added' => true,
                'notes' => $request->notes,
            ]);

            $inventory = Inventory::firstOrCreate(
                ['product_id' => $product->id],
                ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
            );
            $inventory->increment('quantity_in', $request->quantity);
            $inventory->increment('current_stock', $request->quantity);
            $inventory->update(['last_updated' => now()]);

            foreach ($product->materials as $material) {
                $neededQty = $material->pivot->quantity_needed * $request->quantity;
                $material->decrement('current_stock', $neededQty);
            }
        });

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تسجيل الإنتاج وتحديث المخزون بنجاح');
    }

    public function destroy(WorkerProduction $worker_production)
    {
        if ($worker_production->inventory_added) {
            DB::transaction(function () use ($worker_production) {
                $product = Product::with('materials')->find($worker_production->product_id);

                $inventory = Inventory::where('product_id', $worker_production->product_id)->first();
                if ($inventory) {
                    $inventory->decrement('quantity_in', $worker_production->quantity);
                    $inventory->decrement('current_stock', $worker_production->quantity);
                }

                if ($product) {
                    foreach ($product->materials as $material) {
                        $neededQty = $material->pivot->quantity_needed * $worker_production->quantity;
                        $material->increment('current_stock', $neededQty);
                    }
                }

                $worker_production->delete();
            });
        } else {
            $worker_production->delete();
        }

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم حذف السجل بنجاح');
    }
}
