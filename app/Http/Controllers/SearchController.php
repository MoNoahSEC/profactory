<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Worker;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = collect();

        Product::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(fn ($p) => $results->push([
                'type' => 'منتج',
                'icon' => 'bi-box-seam',
                'title' => $p->name,
                'subtitle' => $p->code,
                'url' => route('products.index'),
            ]));

        Customer::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(fn ($c) => $results->push([
                'type' => 'عميل',
                'icon' => 'bi-people',
                'title' => $c->name,
                'subtitle' => $c->phone ?? '',
                'url' => route('customers.show', $c),
            ]));

        Order::where('order_number', 'like', "%{$query}%")
            ->orWhere('customer_name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(fn ($o) => $results->push([
                'type' => 'طلبية',
                'icon' => 'bi-gear',
                'title' => $o->order_number,
                'subtitle' => $o->customer_name ?? $o->customer?->name ?? '',
                'url' => route('orders.index'),
            ]));

        Invoice::where('invoice_number', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(fn ($i) => $results->push([
                'type' => 'فاتورة',
                'icon' => 'bi-receipt',
                'title' => $i->invoice_number,
                'subtitle' => number_format($i->total_amount, 0) . ' ج.م',
                'url' => route('invoices.show', $i),
            ]));

        RawMaterial::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(fn ($m) => $results->push([
                'type' => 'خامة',
                'icon' => 'bi-tools',
                'title' => $m->name,
                'subtitle' => 'مخزون: ' . $m->current_stock,
                'url' => route('raw-materials.index'),
            ]));

        Worker::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->each(fn ($w) => $results->push([
                'type' => 'موظف',
                'icon' => 'bi-person-badge',
                'title' => $w->name,
                'subtitle' => $w->worker_type === 'daily' ? 'يومية' : 'إنتاج',
                'url' => route('workers.index'),
            ]));

        return response()->json(['results' => $results->take(15)->values()]);
    }
}
