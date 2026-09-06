<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Inventory;
use App\Models\OrderShipmentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoadingController extends Controller
{
    public function index()
    {
        $orders = Order::where('loading_status', 'loading')
            ->where(function ($q) {
                $q->whereNull('loader_id')
                    ->orWhere('loader_id', Auth::id());
            })
            ->with(['customer', 'items'])
            ->latest()
            ->get();

        return view('loading.index', compact('orders'));
    }

    public function createAdhoc()
    {
        $products = \App\Models\Product::with('inventory')->get();
        return view('loading.adhoc', compact('products'));
    }

    public function storeAdhoc(Request $request)
    {
        $request->validate([
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.loaded_quantity' => 'required_with:items|integer|min:0',
            'new_products' => 'nullable|array',
            'new_products.*.name' => 'required_with:new_products|string|max:255',
            'new_products.*.quantity' => 'required_with:new_products|integer|min:0',
            'notes' => 'nullable|string'
        ]);

        $hasItems = false;
        if ($request->has('items')) {
            foreach ($request->items as $itemData) {
                if ((int)$itemData['loaded_quantity'] > 0) {
                    $hasItems = true;
                    break;
                }
            }
        }
        if ($request->has('new_products')) {
            foreach ($request->new_products as $newProd) {
                if (!empty($newProd['name']) && (int)$newProd['quantity'] > 0) {
                    $hasItems = true;
                    break;
                }
            }
        }

        if (!$hasItems) {
            return redirect()->back()->with('error', 'يجب إدخال كمية لمنتج واحد على الأقل.');
        }

        try {
            DB::transaction(function () use ($request) {
                // Generate a unique order number with lock to prevent race condition
                $lastOrder = Order::lockForUpdate()->latest('id')->first();
                $lastNumber = $lastOrder ? intval(substr($lastOrder->order_number, 4)) : 0;
                $newNumber = 'ORD-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

                // Create the Order
                $order = Order::create([
                    'order_number' => $newNumber,
                    'customer_id' => null,
                    'customer_name' => 'طلبية تحميل حر (بدون عميل محدد)',
                    'order_date' => now(),
                    'total_amount' => 0,
                    'status' => 'awaiting_approval',
                    'loading_status' => 'loaded',
                    'loader_id' => Auth::id(),
                    'notes' => $request->notes,
                    'converted_to_invoice' => false
                ]);

                $orderShipment = \App\Models\OrderShipment::create([
                    'order_id' => $order->id,
                    'loader_id' => Auth::id(),
                    'status' => 'pending',
                ]);

                if ($request->has('items')) {
                    foreach ($request->items as $itemData) {
                        $qty = (int)$itemData['loaded_quantity'];
                        if ($qty > 0) {
                            $product = \App\Models\Product::find($itemData['product_id']);
                            
                            $orderItem = clone $order->items()->create([
                                'product_id' => $product->id,
                                'quantity' => $qty,
                                'loaded_quantity' => $qty,
                                'unit_price' => $product->selling_price,
                                'total_price' => $product->selling_price * $qty
                            ]);

                            OrderShipmentItem::create([
                                'order_shipment_id' => $orderShipment->id,
                                'order_item_id' => $orderItem->id,
                                'quantity' => $qty,
                            ]);

                            $inventory = Inventory::firstOrCreate(
                                ['product_id' => $product->id],
                                ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
                            );

                            if ($qty > $inventory->current_stock) {
                                throw new \RuntimeException("الكمية {$qty} للمنتج '{$product->name}' غير متوفرة في المخزن (المتاح: {$inventory->current_stock}). لا يمكن تحميل كمية أكبر من المخزون.");
                            }

                            $inventory->decrement('current_stock', $qty);
                            $inventory->increment('quantity_out', $qty);
                            $inventory->update(['last_updated' => now()]);
                        }
                    }
                }

                if ($request->has('new_products')) {
                    foreach ($request->new_products as $newProd) {
                        $qty = (int)$newProd['quantity'];
                        $name = $newProd['name'] ?? '';
                        if ($qty > 0 && !empty($name)) {
                            // Fetch or create a default category
                            $category = \App\Models\Category::firstOrCreate(
                                ['name' => 'عام (غير مصنف)'],
                                ['description' => 'تصنيف افتراضي للمنتجات المضافة عشوائياً']
                            );

                            // Create the new product
                            $product = \App\Models\Product::create([
                                'name' => $name,
                                'code' => 'ADHOC-' . strtoupper(substr(md5(uniqid()), 0, 6)),
                                'category_id' => $category->id,
                                'selling_price' => 0,
                                'labor_cost' => 0,
                                'overhead_cost' => 0,
                                'scissors_cost' => 0,
                                'plastic_weight' => 0,
                                'plastic_price_per_kg' => 0,
                                'paint_cost' => 0,
                                'is_active' => true
                            ]);

                            // Add to inventory
                            Inventory::create([
                                'product_id' => $product->id,
                                'quantity_in' => $qty,
                                'quantity_out' => $qty,
                                'current_stock' => 0,
                                'minimum_stock' => 10,
                                'last_updated' => now()
                            ]);

                            // Add to order
                            $orderItem = clone $order->items()->create([
                                'product_id' => $product->id,
                                'quantity' => $qty,
                                'loaded_quantity' => $qty,
                                'unit_price' => 0,
                                'total_price' => 0
                            ]);

                            OrderShipmentItem::create([
                                'order_shipment_id' => $orderShipment->id,
                                'order_item_id' => $orderItem->id,
                                'quantity' => $qty,
                            ]);
                        }
                    }
                }

                \App\Models\SystemAlert::create([
                    'title' => 'تحميل طلبية حرة جديدة!',
                    'message' => 'موظف التحميل قام بإنشاء وتجهيز طلبية حرة رقم #' . $order->order_number . '. بانتظار توجيهها لسائق وتحويلها لفاتورة!',
                    'type' => 'success'
                ]);
            });

            return redirect()->route('loading.index')->with('success', 'تم إنشاء الطلبية الحرة وتحميلها بنجاح!');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        if ($order->loading_status !== 'loading' || ($order->loader_id && $order->loader_id !== Auth::id())) {
            return redirect()->route('loading.index')->with('error', 'هذه الطلبية غير متاحة لك.');
        }

        $order->load(['items.product.inventory', 'customer', 'items.shipmentItems']);

        $drivers = \App\Models\User::role('Driver')->get();
        $products = \App\Models\Product::with('inventory')->orderBy('name')->get();

        return view('loading.show', compact('order', 'drivers', 'products'));
    }

    public function confirm(Request $request, Order $order)
    {
        if ($order->loading_status !== 'loading') {
            return redirect()->route('loading.index')->with('error', 'هذه الطلبية غير متاحة للتحميل.');
        }

        $request->validate([
            'items'                    => 'required|array',
            'items.*.order_item_id'    => 'nullable',
            'items.*.product_id'       => 'nullable|exists:products,id',
            'items.*.loaded_quantity'  => 'required|integer|min:0',
            'items.*.unit_price'       => 'nullable|numeric|min:0',
            'driver_id'  => 'nullable|exists:users,id',
            'driver_name'   => 'nullable|string|max:255',
            'truck_details' => 'nullable|string|max:255',
        ]);

        $hasNewLoad = false;
        foreach ($request->items as $itemData) {
            $loadedQty = (int) ($itemData['loaded_quantity'] ?? 0);
            if ($loadedQty > 0) { $hasNewLoad = true; break; }
        }

        if (Auth::user()->hasRole('Admin') && $hasNewLoad && !$request->driver_id && !$request->filled('driver_name')) {
            return redirect()->back()->with('error', 'يرجى تحديد السائق (مسجل أو خارجي) عند تسجيل حمولة جديدة.');
        }

        $allLoaded = true;
        $orderShipment = null;

        try {
            DB::transaction(function () use ($request, $order, &$allLoaded, &$orderShipment) {
            foreach ($request->items as $itemData) {
                $loadedQty = (int) ($itemData['loaded_quantity'] ?? 0);

                // Handle extra item (no order_item_id)
                if (empty($itemData['order_item_id'])) {
                    if (empty($itemData['product_id']) || $loadedQty <= 0) continue;
                    $product = \App\Models\Product::find($itemData['product_id']);
                    if (!$product) continue;

                    $unitPrice = (float) ($itemData['unit_price'] ?? $product->selling_price ?? 0);

                    // Create a new OrderItem for this extra product
                    $orderItem = $order->items()->create([
                        'product_id' => $product->id,
                        'quantity'   => $loadedQty,
                        'loaded_quantity' => $loadedQty,
                        'unit_price' => $unitPrice,
                        'total_price' => $loadedQty * $unitPrice,
                    ]);
                    $order->total_amount += ($loadedQty * $unitPrice);
                    $order->save();
                } else {
                    $orderItem = $order->items()->with('product')->find($itemData['order_item_id']);
                    if (!$orderItem) continue;

                    $alreadyShipped = (int) OrderShipmentItem::where('order_item_id', $orderItem->id)->sum('quantity');

                    if ($loadedQty > $orderItem->quantity) {
                        $qtyDiff = $loadedQty - $orderItem->quantity;
                        $orderItem->quantity = $loadedQty;
                        $orderItem->total_price = $loadedQty * $orderItem->unit_price;
                        $orderItem->save();
                        $order->total_amount += ($qtyDiff * $orderItem->unit_price);
                        $order->save();
                    }

                    $diff = $loadedQty - $alreadyShipped;
                    if ($diff <= 0) {
                        $orderItem->update(['loaded_quantity' => $loadedQty]);
                        if ($loadedQty < $orderItem->quantity) $allLoaded = false;
                        continue;
                    }
                }

                // At this point $orderItem exists (original or newly created)
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $orderItem->product_id],
                    ['quantity_in' => 0, 'quantity_out' => 0, 'current_stock' => 0, 'minimum_stock' => 10]
                );

                // For new items the diff is simply $loadedQty
                $diff = isset($alreadyShipped) ? ($loadedQty - $alreadyShipped) : $loadedQty;

                if ($diff > $inventory->current_stock) {
                    throw new \RuntimeException('الكمية المطلوبة للتحميل أكبر من المتوفرة في المخزن للمنتج: ' . ($orderItem->product->name ?? ''));
                }

                if ($diff > 0) {
                    if (!$orderShipment) {
                        $orderShipment = \App\Models\OrderShipment::create([
                            'order_id'     => $order->id,
                            'loader_id'    => Auth::id(),
                            'driver_id'    => $request->driver_id ?: null,
                            'driver_name'  => $request->driver_name,
                            'truck_details' => $request->truck_details,
                            'show_invoice' => $request->boolean('show_invoice'),
                            'shipped_at'   => null,
                            'status'       => 'pending',
                        ]);
                    }

                    OrderShipmentItem::create([
                        'order_shipment_id' => $orderShipment->id,
                        'order_item_id'     => $orderItem->id,
                        'quantity'          => $diff,
                    ]);

                    $inventory->decrement('current_stock', $diff);
                    $inventory->increment('quantity_out', $diff);
                    $inventory->update(['last_updated' => now()]);
                }

                $orderItem->update(['loaded_quantity' => $loadedQty]);

                if ($loadedQty < $orderItem->quantity) {
                    $allLoaded = false;
                }
                unset($alreadyShipped);
            }

            if (!$order->loader_id) {
                $order->loader_id = Auth::id();
            }

            // Always update to loaded/awaiting_approval upon confirm, regardless of partial loads.
            $order->update([
                'loading_status' => 'loaded',
                'status' => 'awaiting_approval',
            ]);
            
            // Create a SystemAlert for the Admin
            \App\Models\SystemAlert::create([
                'title' => 'تم إنهاء تحميل سيارة!',
                'message' => 'موظف التحميل قام بإنهاء تحميل سيارة للطلبية رقم #' . $order->order_number . '. بانتظار الاعتماد وتوجيهها لسائق!',
                'type' => 'success'
            ]);

            });

            return redirect()->route('loading.index')->with('success', 'تم تسجيل الكميات بنجاح. ' . ($allLoaded ? 'وإرسال إشعار للمسؤول.' : ''));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function saveProgress(Request $request)
    {
        $request->validate([
            'order_item_id' => 'required|exists:order_items,id',
            'loaded_quantity' => 'required|integer|min:0',
        ]);

        $orderItem = \App\Models\OrderItem::with('order')->find($request->order_item_id);

        if (!$orderItem || $orderItem->order->loading_status !== 'loading') {
            return response()->json(['success' => false, 'message' => 'الطلبية غير متاحة.'], 422);
        }

        $loadedQty = (int) $request->loaded_quantity;
        if ($loadedQty > $orderItem->quantity) {
            return response()->json(['success' => false, 'message' => 'الكمية أكبر من المطلوب.'], 422);
        }

        // مسودة فقط — الخصم الفعلي يتم عند confirm بناءً على مجموع الشحنات
        $orderItem->update(['loaded_quantity' => $loadedQty]);

        if (!$orderItem->order->loader_id) {
            $orderItem->order->update(['loader_id' => Auth::id()]);
        }

        return response()->json(['success' => true]);
    }

    public function cancel(Order $order)
    {
        if ($order->loading_status !== 'loading') {
            return redirect()->route('loading.index')->with('error', 'لا يمكن إلغاء هذه الطلبية.');
        }

        // Reset loaded_quantity on all items
        $order->items()->update(['loaded_quantity' => 0]);

        // Reset order status back to pending so Admin can re-send to loading
        $order->update([
            'loading_status' => 'pending',
            'status'         => 'pending',
            'loader_id'      => null,
        ]);

        // Notify admin
        \App\Models\SystemAlert::create([
            'title'   => 'تم إلغاء تحميل طلبية!',
            'message' => 'تم إلغاء عملية تحميل الطلبية رقم #' . $order->order_number . '. يمكنك إعادة إرسالها للتحميل.',
            'type'    => 'warning',
        ]);

        return redirect()->route('loading.index')->with('success', 'تم إلغاء التحميل وإرجاع الطلبية للإدارة.');
    }
}
