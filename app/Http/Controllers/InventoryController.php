<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ClearsLayoutCache;
    public function index()
    {
        $inventory = Inventory::with('product.category')->get();
        $totalValue = $inventory->sum(function ($inv) {
            return $inv->current_stock * ($inv->product->selling_price ?? 0);
        });
        return view('inventory.index', compact('inventory', 'totalValue'));
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
        ]);

        $inventory = Inventory::firstOrCreate(
            ['product_id' => $request->product_id],
            ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
        );

        if ($request->type === 'in') {
            $inventory->increment('quantity_in', $request->quantity);
            $inventory->increment('current_stock', $request->quantity);
        } else {
            if ($inventory->current_stock < $request->quantity) {
                $this->clearLayoutCache();
        return redirect()->back()->with('error', 'الكمية المطلوبة أكبر من المخزون المتاح');
            }
            $inventory->increment('quantity_out', $request->quantity);
            $inventory->decrement('current_stock', $request->quantity);
        }

        $inventory->update(['last_updated' => now()]);
        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تعديل المخزون بنجاح');
    }

    public function updateMinimumStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'minimum_stock' => 'required|integer|min:0',
        ]);

        $inventory = Inventory::firstOrCreate(
            ['product_id' => $request->product_id],
            ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0]
        );

        $inventory->update(['minimum_stock' => $request->minimum_stock]);

        $this->clearLayoutCache();
        return redirect()->back()->with('success', 'تم تحديث الحد الأدنى للمخزون بنجاح');
    }

    public function quickUpdate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'selling_price' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|integer|min:0',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                // Update Product Price if provided
                if ($request->has('selling_price')) {
                    $product = Product::lockForUpdate()->find($request->product_id);
                    $product->update(['selling_price' => $request->selling_price]);
                }

                // Update Inventory if provided
                if ($request->has('current_stock')) {
                    $inventory = Inventory::lockForUpdate()->firstOrCreate(
                        ['product_id' => $request->product_id],
                        ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
                    );

                    $oldStock = $inventory->current_stock;
                    $newStock = (int) $request->current_stock;
                    $diff = $newStock - $oldStock;

                    if ($diff > 0) {
                        // Stock increased -> means we got new items IN
                        $inventory->increment('quantity_in', $diff);
                        $inventory->increment('current_stock', $diff);
                    } elseif ($diff < 0) {
                        // Stock decreased -> means items went OUT
                        $absDiff = abs($diff);
                        $inventory->increment('quantity_out', $absDiff);
                        $inventory->decrement('current_stock', $absDiff);
                    }

                    $inventory->update(['last_updated' => now()]);
                }
            });

            // Fetch fresh inventory data to return to the frontend
            $inv = Inventory::where('product_id', $request->product_id)->first();

            return response()->json([
                'success' => true, 
                'message' => 'تم التحديث بنجاح!',
                'data' => [
                    'quantity_in' => $inv->quantity_in,
                    'quantity_out' => $inv->quantity_out,
                    'last_updated' => $inv->last_updated ? $inv->last_updated->diffForHumans() : 'الآن'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage()], 500);
        }
    }
}
