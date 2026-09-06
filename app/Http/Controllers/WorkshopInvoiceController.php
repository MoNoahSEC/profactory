<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use App\Models\WorkshopInvoice;
use App\Models\WorkshopInvoiceItem;
use App\Models\RawMaterial;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkshopInvoiceController extends Controller
{
    public function create()
    {
        $workshops = Workshop::all();
        $rawMaterials = RawMaterial::all();
        $products = Product::where('is_active', true)->with('inventory')->get();
        return view('workshops.invoices.create', compact('workshops', 'rawMaterials', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'workshop_id' => 'required|exists:workshops,id',
            'invoice_date' => 'required|date',
            'materials' => 'nullable|array',
            'materials.*.id' => 'required|exists:raw_materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'materials.*.price' => 'required|numeric|min:0',
            
            'products' => 'nullable|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
            
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        if (empty($request->materials) && empty($request->products)) {
            return back()->with('error', 'يجب إضافة عناصر للفاتورة (خامات أو منتجات).');
        }

        DB::beginTransaction();
        try {
            // Generate Invoice Number
            $lastInvoice = WorkshopInvoice::orderBy('id', 'desc')->first();
            $nextId = $lastInvoice ? $lastInvoice->id + 1 : 1;
            $invoiceNumber = 'W-INV-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $totalMaterials = 0;
            $totalProducts = 0;

            // 1. Calculate totals
            if (!empty($request->materials)) {
                foreach ($request->materials as $mat) {
                    $totalMaterials += ($mat['quantity'] * $mat['price']);
                }
            }

            if (!empty($request->products)) {
                foreach ($request->products as $prod) {
                    $totalProducts += ($prod['quantity'] * $prod['price']);
                }
            }

            $netAmount = $totalMaterials - $totalProducts;
            $paidAmount = (float)($request->paid_amount ?? 0);
            
            // netAmount > 0 => Workshop owes us. So paidAmount reduces the remaining amount.
            // netAmount < 0 => We owe Workshop. So paidAmount reduces what we owe.
            $remainingAmount = $netAmount > 0 ? ($netAmount - $paidAmount) : ($netAmount + $paidAmount);

            // 2. Create Invoice
            $invoice = WorkshopInvoice::create([
                'workshop_id' => $request->workshop_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $request->invoice_date,
                'total_materials_sold' => $totalMaterials,
                'total_products_bought' => $totalProducts,
                'net_amount' => $netAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // 3. Process Items and update inventory
            if (!empty($request->materials)) {
                foreach ($request->materials as $mat) {
                    $total = $mat['quantity'] * $mat['price'];
                    WorkshopInvoiceItem::create([
                        'workshop_invoice_id' => $invoice->id,
                        'item_type' => 'raw_material',
                        'item_id' => $mat['id'],
                        'transaction_type' => 'sell', // we sell raw materials to them
                        'quantity' => $mat['quantity'],
                        'unit_price' => $mat['price'],
                        'total' => $total
                    ]);

                    // Deduct from Raw Material Stock
                    $rawMaterial = RawMaterial::find($mat['id']);
                    if ($rawMaterial) {
                        $rawMaterial->decrement('current_stock', $mat['quantity']);
                    }
                }
            }

            if (!empty($request->products)) {
                foreach ($request->products as $prod) {
                    $total = $prod['quantity'] * $prod['price'];
                    WorkshopInvoiceItem::create([
                        'workshop_invoice_id' => $invoice->id,
                        'item_type' => 'product',
                        'item_id' => $prod['id'],
                        'transaction_type' => 'buy', // we buy products from them
                        'quantity' => $prod['quantity'],
                        'unit_price' => $prod['price'],
                        'total' => $total
                    ]);

                    // Add to Product Inventory
                    $inventory = Inventory::firstOrCreate(
                        ['product_id' => $prod['id']],
                        ['current_stock' => 0, 'quantity_in' => 0, 'quantity_out' => 0]
                    );
                    $inventory->increment('current_stock', $prod['quantity']);
                    $inventory->increment('quantity_in', $prod['quantity']);
                    $inventory->update(['last_updated' => now()]);
                }
            }

            DB::commit();
            return redirect()->route('workshops.show', $request->workshop_id)->with('success', 'تم حفظ الفاتورة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء حفظ الفاتورة: ' . $e->getMessage());
        }
    }

    public function show(WorkshopInvoice $invoice)
    {
        $invoice->load(['workshop', 'items.rawMaterial', 'items.product', 'creator']);
        return view('workshops.invoices.show', compact('invoice'));
    }

    public function print(WorkshopInvoice $invoice)
    {
        $invoice->load(['workshop', 'items.rawMaterial', 'items.product', 'creator']);
        return view('workshops.invoices.print', compact('invoice'));
    }

    public function edit(WorkshopInvoice $invoice)
    {
        $invoice->load(['items']);
        $workshops = Workshop::all();
        $rawMaterials = RawMaterial::all();
        $products = Product::where('is_active', true)->with('inventory')->get();
        return view('workshops.invoices.edit', compact('invoice', 'workshops', 'rawMaterials', 'products'));
    }

    public function update(Request $request, WorkshopInvoice $invoice)
    {
        $request->validate([
            'workshop_id' => 'required|exists:workshops,id',
            'invoice_date' => 'required|date',
            'materials' => 'nullable|array',
            'materials.*.id' => 'required|exists:raw_materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'materials.*.price' => 'required|numeric|min:0',
            
            'products' => 'nullable|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
            
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        if (empty($request->materials) && empty($request->products)) {
            return back()->with('error', 'يجب إضافة عناصر للفاتورة (خامات أو منتجات).');
        }

        DB::beginTransaction();
        try {
            // 1. Revert Old Items from Inventory
            foreach ($invoice->items as $item) {
                if ($item->item_type === 'raw_material' && $item->transaction_type === 'sell') {
                    $rawMaterial = RawMaterial::find($item->item_id);
                    if ($rawMaterial) {
                        $rawMaterial->increment('current_stock', $item->quantity);
                    }
                } elseif ($item->item_type === 'product' && $item->transaction_type === 'buy') {
                    $inventory = Inventory::where('product_id', $item->item_id)->first();
                    if ($inventory) {
                        $inventory->decrement('current_stock', $item->quantity);
                        $inventory->decrement('quantity_in', $item->quantity);
                    }
                }
            }
            // Delete old items
            $invoice->items()->delete();

            // 2. Calculate New Totals
            $totalMaterials = 0;
            $totalProducts = 0;

            if (!empty($request->materials)) {
                foreach ($request->materials as $mat) {
                    $totalMaterials += ($mat['quantity'] * $mat['price']);
                }
            }

            if (!empty($request->products)) {
                foreach ($request->products as $prod) {
                    $totalProducts += ($prod['quantity'] * $prod['price']);
                }
            }

            $netAmount = $totalMaterials - $totalProducts;
            $paidAmount = (float)($request->paid_amount ?? 0);
            $remainingAmount = $netAmount > 0 ? ($netAmount - $paidAmount) : ($netAmount + $paidAmount);

            // 3. Update Invoice
            $invoice->update([
                'workshop_id' => $request->workshop_id,
                'invoice_date' => $request->invoice_date,
                'total_materials_sold' => $totalMaterials,
                'total_products_bought' => $totalProducts,
                'net_amount' => $netAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'notes' => $request->notes,
            ]);

            // 4. Process New Items and update inventory
            if (!empty($request->materials)) {
                foreach ($request->materials as $mat) {
                    $total = $mat['quantity'] * $mat['price'];
                    WorkshopInvoiceItem::create([
                        'workshop_invoice_id' => $invoice->id,
                        'item_type' => 'raw_material',
                        'item_id' => $mat['id'],
                        'transaction_type' => 'sell',
                        'quantity' => $mat['quantity'],
                        'unit_price' => $mat['price'],
                        'total' => $total
                    ]);

                    $rawMaterial = RawMaterial::find($mat['id']);
                    if ($rawMaterial) {
                        $rawMaterial->decrement('current_stock', $mat['quantity']);
                    }
                }
            }

            if (!empty($request->products)) {
                foreach ($request->products as $prod) {
                    $total = $prod['quantity'] * $prod['price'];
                    WorkshopInvoiceItem::create([
                        'workshop_invoice_id' => $invoice->id,
                        'item_type' => 'product',
                        'item_id' => $prod['id'],
                        'transaction_type' => 'buy',
                        'quantity' => $prod['quantity'],
                        'unit_price' => $prod['price'],
                        'total' => $total
                    ]);

                    $inventory = Inventory::firstOrCreate(
                        ['product_id' => $prod['id']],
                        ['current_stock' => 0, 'quantity_in' => 0, 'quantity_out' => 0]
                    );
                    $inventory->increment('current_stock', $prod['quantity']);
                    $inventory->increment('quantity_in', $prod['quantity']);
                    $inventory->update(['last_updated' => now()]);
                }
            }

            DB::commit();
            return redirect()->route('workshops.show', $request->workshop_id)->with('success', 'تم تعديل الفاتورة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء تعديل الفاتورة: ' . $e->getMessage());
        }
    }
    public function destroy(WorkshopInvoice $invoice)
    {
        DB::beginTransaction();
        try {
            // Revert Inventory
            foreach ($invoice->items as $item) {
                if ($item->item_type === 'raw_material' && $item->transaction_type === 'sell') {
                    $rawMaterial = RawMaterial::find($item->item_id);
                    if ($rawMaterial) {
                        $rawMaterial->increment('current_stock', $item->quantity);
                    }
                } elseif ($item->item_type === 'product' && $item->transaction_type === 'buy') {
                    $inventory = Inventory::where('product_id', $item->item_id)->first();
                    if ($inventory) {
                        $inventory->decrement('current_stock', $item->quantity);
                        $inventory->decrement('quantity_in', $item->quantity);
                    }
                }
            }
            
            $workshopId = $invoice->workshop_id;
            
            // Delete items
            $invoice->items()->delete();
            // Delete invoice
            $invoice->delete();

            DB::commit();
            return redirect()->route('workshops.show', $workshopId)->with('success', 'تم حذف الفاتورة واسترجاع الخامات بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }
}
