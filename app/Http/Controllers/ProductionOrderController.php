<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\Product;
use App\Models\Inventory;
use App\Services\CostCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionOrderController extends Controller
{
    public function index()
    {
        $orders = ProductionOrder::with(['product', 'cost'])->latest()->get();
        $products = Product::where('is_active', true)->get();
        return view('production.index', compact('orders', 'products'));
    }

    public function store(Request $request, CostCalculationService $costService)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_ordered' => 'required|integer|min:1',
            'production_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $product = Product::with('materials')->findOrFail($request->product_id);

        // إنشاء رقم أمر الإنتاج تلقائياً
        $lastOrder = ProductionOrder::latest()->first();
        $nextNum = $lastOrder ? intval(substr($lastOrder->order_number, 4)) + 1 : 1;
        $orderNumber = 'PRD-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $order = ProductionOrder::create([
            'order_number' => $orderNumber,
            'product_id' => $request->product_id,
            'quantity_ordered' => $request->quantity_ordered,
            'production_date' => $request->production_date,
            'status' => 'pending',
            'notes' => $request->notes,
            'created_by' => Auth::id(),
        ]);

        // خصم الخامات من مخزون المواد الخام
        foreach ($product->materials as $material) {
            $neededQty = $material->pivot->quantity_needed * $request->quantity_ordered;
            $material->decrement('current_stock', $neededQty);
        }

        // حساب تكلفة الأمر
        $costData = $costService->calculateOrderCost($order);
        $order->cost()->create([
            'material_cost' => $costData['material_cost'],
            'labor_cost' => $costData['labor_cost'],
            'overhead_cost' => $costData['overhead_cost'],
            'total_cost' => $costData['total_cost'],
            'cost_per_unit' => $costData['cost_per_unit'],
            'profit_per_unit' => $costData['expected_profit'] / $request->quantity_ordered,
            'profit_margin' => $costData['expected_profit'] > 0
                ? ($costData['expected_profit'] / $costData['expected_revenue']) * 100
                : 0,
        ]);

        return redirect()->route('production-orders.index')->with('success', "تم إنشاء أمر الإنتاج {$orderNumber} بنجاح وتم خصم الخامات");
    }

    public function complete(ProductionOrder $order)
    {
        $order->update([
            'status' => 'completed',
            'quantity_produced' => $order->quantity_ordered,
        ]);

        // إضافة المنتجات للمخزون
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $order->product_id],
            ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
        );

        $inventory->increment('quantity_in', $order->quantity_ordered);
        $inventory->increment('current_stock', $order->quantity_ordered);
        $inventory->update(['last_updated' => now()]);

        return redirect()->route('production-orders.index')->with('success', "تم إكمال أمر الإنتاج {$order->order_number} وإضافة {$order->quantity_ordered} وحدة للمخزون");
    }

    public function destroy(ProductionOrder $productionOrder)
    {
        if ($productionOrder->status === 'completed') {
            return redirect()->route('production-orders.index')->with('error', 'لا يمكن حذف أمر إنتاج مكتمل');
        }
        $productionOrder->delete();
        return redirect()->route('production-orders.index')->with('success', 'تم حذف أمر الإنتاج');
    }

    public function print(ProductionOrder $productionOrder)
    {
        $productionOrder->load(['product', 'cost', 'creator']);
        return view('production.print', compact('productionOrder'));
    }
}
