<?php

namespace App\Http\Controllers;

use App\Models\OrderShipment;
use App\Models\DriverLocation;
use App\Services\CashLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function index()
    {
        $driverId = Auth::id();

        $activeTrip = OrderShipment::with(['order.customer', 'items.orderItem.product'])
            ->where('driver_id', $driverId)
            ->whereIn('status', ['pending', 'en_route'])
            ->orderBy('created_at', 'asc')
            ->first();

        $completedTrips = OrderShipment::with('order')
            ->where('driver_id', $driverId)
            ->where('status', 'completed')
            ->whereDate('shipped_at', Carbon::today())
            ->latest('shipped_at')
            ->get();

        return view('driver.dashboard', compact('activeTrip', 'completedTrips'));
    }

    public function startTrip(OrderShipment $shipment)
    {
        if ($shipment->driver_id !== Auth::id()) {
            abort(403);
        }

        $shipment->update([
            'status' => 'en_route',
            'shipped_at' => now(),
        ]);

        return redirect()->route('driver.dashboard')->with('success', 'تم بدء الرحلة! التتبع المباشر يعمل الآن.');
    }

    public function endTrip(Request $request, OrderShipment $shipment, CashLedgerService $ledger)
    {
        if ($shipment->driver_id !== Auth::id()) {
            abort(403);
        }

        $isCashCollected = $request->boolean('is_cash_collected');

        DB::transaction(function () use ($shipment, $isCashCollected, $ledger) {
            $shipment->update([
                'status' => 'completed',
                'is_cash_collected' => $isCashCollected,
            ]);

            $order = $shipment->order;

            if ($isCashCollected && $shipment->show_invoice) {
                $remaining = max(0, (float) $order->total_amount - (float) $order->paid_deposit);

                if ($remaining > 0) {
                    $ledger->record(
                        'payment',
                        $remaining,
                        "تحصيل نقدي للسائق — شحنة #{$shipment->id} للطلبية {$order->order_number}",
                        $shipment,
                        now()->toDateString()
                    );

                    $order->increment('paid_deposit', $remaining);

                    if ($order->converted_to_invoice) {
                        $invoice = \App\Models\Invoice::where('notes', 'like', "%{$order->order_number}%")->first();
                        if ($invoice) {
                            $invoice->increment('paid_amount', $remaining);
                            $invoice->refresh();
                            app(\App\Services\InvoiceAccountingService::class)->syncFromPaidAmount($invoice);
                        }
                    }
                }
            }

            $this->syncOrderDeliveryState($order);
        });

        return redirect()->route('driver.dashboard')->with('success', 'تم إنهاء الرحلة وتسليم الطلبية بنجاح.');
    }

    protected function syncOrderDeliveryState($order): void
    {
        $totalOrdered = $order->items()->sum('quantity');
        $totalLoaded = $order->items()->sum('loaded_quantity');
        $allShipmentsDone = $order->shipments()
            ->where('status', '!=', 'completed')
            ->doesntExist();

        if ($allShipmentsDone && $totalLoaded >= $totalOrdered && $order->loading_status === 'loaded') {
            $order->update(['delivery_date' => now()->toDateString()]);
        }
    }

    public function logLocation(Request $request, OrderShipment $shipment)
    {
        if ($shipment->driver_id !== Auth::id() || $shipment->status !== 'en_route') {
            return response()->json(['error' => 'Unauthorized or trip not active'], 403);
        }

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric',
        ]);

        DriverLocation::create([
            'driver_id' => Auth::id(),
            'order_shipment_id' => $shipment->id,
            'latitude' => $request->lat,
            'longitude' => $request->lng,
            'speed' => $request->speed,
            'recorded_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function liveMap(OrderShipment $shipment)
    {
        if (!Auth::user()->hasRole('Admin') && !Auth::user()->hasRole('Loader')) {
            abort(403);
        }

        $shipment->load(['order', 'driver']);

        return view('driver.live_map', compact('shipment'));
    }

    public function getLocationsApi(OrderShipment $shipment)
    {
        $locations = DriverLocation::where('order_shipment_id', $shipment->id)
            ->orderBy('recorded_at', 'asc')
            ->get();

        return response()->json($locations);
    }
}
