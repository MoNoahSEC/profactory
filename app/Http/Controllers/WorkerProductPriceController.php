<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Worker;
use App\Models\Product;
use App\Models\WorkerProductPrice;

class WorkerProductPriceController extends Controller
{
    public function index()
    {
        // Get all piece-rate production workers
        $workers = Worker::where('worker_type', 'production')
            ->where('wage_system', 'piece')
            ->where('is_active', true)
            ->get();

        // Get all products
        $products = Product::where('is_active', true)->get();

        // Get all saved prices
        $prices = WorkerProductPrice::all()->keyBy(function($item) {
            return $item->worker_id . '_' . $item->product_id;
        });

        return view('worker_prices.index', compact('workers', 'products', 'prices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'prices' => 'required|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($request->prices as $key => $price) {
            if ($price === null || $price === '') continue;

            list($worker_id, $product_id) = explode('_', $key);

            WorkerProductPrice::updateOrCreate(
                ['worker_id' => $worker_id, 'product_id' => $product_id],
                ['price' => $price]
            );
        }

        return redirect()->route('worker-prices.index')->with('success', 'تم حفظ أسعار المصنعيات بنجاح.');
    }
}
