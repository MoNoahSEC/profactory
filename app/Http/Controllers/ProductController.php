<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\RawMaterial;
use App\Services\CostCalculationService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $categories = Category::with('products.inventory')->get();
        return view('products.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:products,code|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'dimensions' => 'nullable|string|max:100',
            'cages_per_carton' => 'nullable|integer|min:1',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'selling_price' => 'required|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'scissors_cost' => 'nullable|numeric|min:0',
            'overhead_cost' => 'nullable|numeric|min:0',
            'plastic_weight' => 'nullable|numeric|min:0',
            'plastic_price_per_kg' => 'nullable|numeric|min:0',
            'paint_cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'shift_target_quantity' => 'nullable|integer|min:1',
            'piece_wage' => 'nullable|numeric|min:0',
            'piece_wage_scissors' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create($this->productData($request));
        
        // Initialize Inventory
        \App\Models\Inventory::create([
            'product_id' => $product->id,
            'quantity_in' => 0,
            'quantity_out' => 0,
            'current_stock' => 0,
            'minimum_stock' => 10,
            'last_updated' => now()
        ]);
        
        return redirect()->route('products.index')->with('success', 'تم إضافة المنتج بنجاح');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|unique:products,code,' . $product->id . '|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'dimensions' => 'nullable|string|max:100',
            'cages_per_carton' => 'nullable|integer|min:1',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'selling_price' => 'required|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'scissors_cost' => 'nullable|numeric|min:0',
            'overhead_cost' => 'nullable|numeric|min:0',
            'plastic_weight' => 'nullable|numeric|min:0',
            'plastic_price_per_kg' => 'nullable|numeric|min:0',
            'paint_cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'shift_target_quantity' => 'nullable|integer|min:1',
            'piece_wage' => 'nullable|numeric|min:0',
            'piece_wage_scissors' => 'nullable|numeric|min:0',
        ]);

        $product->update($this->productData($request, $product));
        return redirect()->route('products.index')->with('success', 'تم تعديل المنتج بنجاح');
    }

    private function productData(Request $request, Product $product = null): array
    {
        $data = array_merge($request->except(['image_file']), [
            'labor_cost' => $request->labor_cost ?: 0,
            'scissors_cost' => $request->scissors_cost ?: 0,
            'overhead_cost' => $request->overhead_cost ?: 0,
            'plastic_weight' => $request->plastic_weight ?: 0,
            'plastic_price_per_kg' => $request->plastic_price_per_kg ?: 0,
            'paint_cost' => $request->paint_cost ?: 0,
            'piece_wage' => $request->piece_wage ?: 0,
            'piece_wage_scissors' => $request->piece_wage_scissors ?: 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->hasFile('image_file')) {
            if ($product && $product->image_path && file_exists(public_path($product->image_path))) {
                @unlink(public_path($product->image_path));
            }
            $file = $request->file('image_file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $data['image_path'] = 'uploads/products/' . $filename;
        }

        return $data;
    }

    public function destroy(Product $product)
    {
        if ($product->image_path && file_exists(public_path($product->image_path))) {
            @unlink(public_path($product->image_path));
        }
        $product->inventory()?->delete();
        $product->delete();
        return redirect()->route('products.index')->with('success', 'تم حذف المنتج بنجاح');
    }

    public function showCost(Product $product, CostCalculationService $costService)
    {
        $costDetails = $costService->calculateProductCost($product);
        $materials = RawMaterial::all();
        
        return view('products.cost', compact('product', 'costDetails', 'materials'));
    }

    public function saveMaterials(Request $request, Product $product)
    {
        $request->validate([
            'materials' => 'required|array',
            'materials.*.id' => 'required|exists:raw_materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $syncData = [];
        foreach ($request->materials as $mat) {
            $syncData[$mat['id']] = ['quantity_needed' => $mat['quantity']];
        }

        $product->materials()->sync($syncData);

        return redirect()->route('products.cost', $product)->with('success', 'تم تحديث وصفة التصنيع وحساب التكلفة بنجاح');
    }
    public function printInventory()
    {
        $products = Product::with('category')->orderBy('category_id')->orderBy('name')->get();
        return view('products.print_inventory', compact('products'));
    }
}
