<?php

namespace App\Http\Controllers;

use App\Models\MaterialCategory;
use Illuminate\Http\Request;

class MaterialCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'default_unit' => 'nullable|string|max:50',
        ]);

        MaterialCategory::create($request->all());
        return redirect()->route('raw-materials.index')->with('success', 'تم إضافة تصنيف المادة الخام بنجاح');
    }

    public function update(Request $request, MaterialCategory $materialCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'default_unit' => 'nullable|string|max:50',
        ]);

        $materialCategory->update($request->all());
        return redirect()->route('raw-materials.index')->with('success', 'تم تعديل تصنيف المادة الخام بنجاح');
    }

    public function destroy(MaterialCategory $materialCategory)
    {
        if ($materialCategory->materials()->count() > 0) {
            return redirect()->route('raw-materials.index')->with('error', 'لا يمكن حذف التصنيف لوجود مواد خام مرتبطة به');
        }
        $materialCategory->delete();
        return redirect()->route('raw-materials.index')->with('success', 'تم حذف تصنيف المادة الخام بنجاح');
    }
}
