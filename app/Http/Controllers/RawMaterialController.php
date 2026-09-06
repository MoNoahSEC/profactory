<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ClearsLayoutCache;

use App\Models\RawMaterial;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    use ClearsLayoutCache;
    public function index()
    {
        $materials = RawMaterial::with('category')->get();
        $materialCategories = \App\Models\MaterialCategory::withCount('materials')->get();
        return view('raw-materials.index', compact('materials', 'materialCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_category_id' => 'nullable|exists:material_categories,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        RawMaterial::create($request->all());
        $this->clearLayoutCache();
        return redirect()->route('raw-materials.index')->with('success', 'تم إضافة الخامة بنجاح');
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $request->validate([
            'material_category_id' => 'nullable|exists:material_categories,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $rawMaterial->update($request->all());
        $this->clearLayoutCache();
        return redirect()->route('raw-materials.index')->with('success', 'تم تعديل الخامة بنجاح');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        // حماية من حذف خامة مستخدمة في وصفة تصنيع منتج
        if (\App\Models\ProductMaterial::where('raw_material_id', $rawMaterial->id)->exists()) {
            $this->clearLayoutCache();
        return redirect()->route('raw-materials.index')->with('error', 'لا يمكن حذف خامة مستخدمة في وصفة تصنيع منتج. قم بإزالتها من المنتج أولاً.');
        }
        $rawMaterial->delete();
        $this->clearLayoutCache();
        return redirect()->route('raw-materials.index')->with('success', 'تم حذف الخامة بنجاح');
    }

    public function restock(Request $request, RawMaterial $rawMaterial)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.1'
        ]);

        $rawMaterial->increment('current_stock', $request->quantity);
        $this->clearLayoutCache();
        return redirect()->route('raw-materials.index')->with('success', "تم إضافة {$request->quantity} {$rawMaterial->unit} إلى المخزون");
    }
}
