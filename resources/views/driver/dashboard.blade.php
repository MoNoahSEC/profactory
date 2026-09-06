@extends('layouts.app')

@section('title', 'لوحة قيادة السائق')
@section('page_title', 'رحلاتي اليوم')

@section('content')
<style>
    body { background-color: #f1f5f9; }
    .app-header, .sidebar { display: none !important; }
    .main-content { margin-left: 0 !important; padding-top: 10px !important; padding-bottom: 80px; }
    
    .trip-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
    }
    .trip-header {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #fff;
        padding: 20px;
    }
    .status-badge {
        background: rgba(255,255,255,0.2);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    .pulse-dot {
        height: 10px; width: 10px; background-color: #ef4444; border-radius: 50%; display: inline-block;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 1); transform: scale(1); animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    
    .btn-giant {
        padding: 15px 20px;
        font-size: 1.2rem;
        border-radius: 12px;
        font-weight: bold;
    }
    .detail-row {
        display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0;
    }
    .detail-row:last-child { border-bottom: none; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 px-2">
    <div>
    <div class="d-flex align-items-center gap-2">
        <div class="logo-circle shadow-sm" style="width: 55px; height: 55px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
        </div>
        <h4 class="fw-bold mb-0 text-dark">لوحة السائق</h4>
    </div>
        <small class="text-muted">أهلاً بك، {{ Auth::user()->name }}</small>
    </div>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="btn btn-sm btn-outline-danger rounded-circle p-2"><i class="bi bi-power fs-5"></i></button>
    </form>
</div>

@if($activeTrip)
    <div class="trip-card mb-4" id="activeTripCard" data-shipment-id="{{ $activeTrip->id }}" data-status="{{ $activeTrip->status }}">
        <div class="trip-header">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-warning text-dark fs-6">شحنة #{{ $activeTrip->id }}</span>
                @if($activeTrip->status === 'en_route')
                    <span class="status-badge text-white"><span class="pulse-dot me-1"></span> جاري التوصيل والتتبع</span>
                @else
                    <span class="status-badge">في الانتظار للتحرك</span>
                @endif
            </div>
            <h3 class="fw-bold mb-1">{{ $activeTrip->order->customer_name }}</h3>
            <p class="mb-0 text-white-50"><i class="bi bi-geo-alt-fill"></i> {{ $activeTrip->order->address }}</p>
        </div>
        
        <div class="p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box warning me-3" style="width: 45px; height: 45px; font-size: 1.2rem;"><i class="bi bi-box-seam-fill"></i></div>
                <h6 class="fw-bold text-dark mb-0 fs-5">تفاصيل الحمولة</h6>
            </div>
            
            <div class="bg-light rounded-4 border border-secondary p-3 mb-4">
                @foreach($activeTrip->items as $item)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary last-border-none">
                        <span class="text-dark fw-bold"><i class="bi bi-check2-circle text-success me-2"></i>{{ $item->orderItem->product->name ?? $item->product->name ?? '-' }}</span>
                        <span class="badge bg-white text-dark border border-secondary px-3 py-2 fs-6 rounded-pill shadow-sm">{{ $item->quantity }} قطعة</span>
                    </div>
                @endforeach
            </div>
            
            @if($activeTrip->show_invoice)
            <div class="bg-primary bg-opacity-10 rounded-4 border border-primary p-4 d-flex justify-content-between align-items-center shadow-sm">
                <div>
                    <small class="text-primary fw-bold d-block mb-1"><i class="bi bi-cash-stack"></i> المبلغ المطلوب تحصيله</small>
                    <span class="fw-bold text-dark fs-3">{{ number_format($activeTrip->order->total_amount - $activeTrip->order->paid_deposit, 0) }} <small class="fs-6">ج.م</small></span>
                </div>
                <div class="icon-box primary" style="background: white !important; border-radius: 50%; width: 55px; height: 55px; font-size: 1.5rem;">
                    <i class="bi bi-wallet2 text-primary"></i>
                </div>
            </div>
            @endif
        </div>

        <div class="p-3 bg-light border-top">
            @if($activeTrip->status === 'pending')
                <form action="{{ route('driver.startTrip', $activeTrip) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-giant w-100 shadow"><i class="bi bi-play-circle-fill me-2"></i> بدء الرحلة والتحرك</button>
                </form>
            @elseif($activeTrip->status === 'en_route')
                <div class="d-flex gap-2 mb-3">
                    <a href="https://maps.google.com/?q={{ urlencode($activeTrip->order->address) }}" target="_blank" class="btn btn-outline-primary w-50 fw-bold py-3 rounded-3"><i class="bi bi-map-fill d-block fs-4 mb-1"></i> خريطة</a>
                    <a href="tel:{{ $activeTrip->order->customer->phone ?? '' }}" class="btn btn-outline-success w-50 fw-bold py-3 rounded-3"><i class="bi bi-telephone-fill d-block fs-4 mb-1"></i> اتصال</a>
                </div>
                
                <form action="{{ route('driver.endTrip', $activeTrip) }}" method="POST" id="endTripForm">
                    @csrf
                    @if($activeTrip->show_invoice)
                    <div class="form-check form-switch mb-3 bg-white p-3 rounded border" style="font-size: 1.1rem;">
                        <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" name="is_cash_collected" id="cashCollectedSwitch" style="width: 50px; height: 25px;">
                        <label class="form-check-label fw-bold text-dark" for="cashCollectedSwitch">تم استلام المبلغ نقداً من العميل</label>
                    </div>
                    @endif
                    <button type="button" onclick="confirmEndTrip()" class="btn btn-dark btn-giant w-100 shadow"><i class="bi bi-check-circle-fill me-2"></i> إنهاء الرحلة وتسليم الحمولة</button>
                </form>
            @endif
        </div>
    </div>
@else
    <div class="text-center py-5">
        <i class="bi bi-cup-hot text-muted" style="font-size: 4rem;"></i>
        <h4 class="fw-bold mt-3 text-muted">لا توجد رحلات حالية</h4>
        <p class="text-muted">أنتظر تكليف موظف التحميل لك برحلة جديدة.</p>
        <button onclick="window.location.reload()" class="btn btn-outline-secondary mt-3 rounded-pill px-4"><i class="bi bi-arrow-clockwise"></i> تحديث</button>
    </div>
@endif

@if($completedTrips->count() > 0)
    <h5 class="fw-bold mb-3 px-2 mt-4 text-muted">رحلات اليوم المكتملة</h5>
    @foreach($completedTrips as $trip)
        <div class="card border-0 shadow-sm rounded-4 mb-2">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold text-dark">{{ $trip->order->customer_name }}</span>
                    <span class="badge bg-success"><i class="bi bi-check-all"></i> سلمت</span>
                </div>
                <small class="text-muted">{{ $trip->order->address }}</small>
            </div>
        </div>
    @endforeach
@endif

<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('scripts')
<script>
    function confirmEndTrip() {
        if(confirm('هل أنت متأكد من إنهاء الرحلة؟ سيتم إيقاف التتبع.')) {
            document.getElementById('endTripForm').submit();
        }
    }

    // Live Tracking & WakeLock Logic
    const activeCard = document.getElementById('activeTripCard');
    let wakeLock = null;
    let locationInterval;

    if (activeCard && activeCard.getAttribute('data-status') === 'en_route') {
        const shipmentId = activeCard.getAttribute('data-shipment-id');
        
        // Request WakeLock to keep screen on
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                    console.log('Wake Lock is active');
                    
                    wakeLock.addEventListener('release', () => {
                        console.log('Wake Lock was released');
                    });
                }
            } catch (err) {
                console.error(`${err.name}, ${err.message}`);
            }
        }
        
        requestWakeLock();
        
        // Re-request wake lock when document becomes visible again
        document.addEventListener('visibilitychange', async () => {
            if (wakeLock !== null && document.visibilityState === 'visible') {
                requestWakeLock();
            }
        });

        // Start GPS tracking
        if ("geolocation" in navigator) {
            // Watch position is better for high accuracy and movement
            navigator.geolocation.watchPosition(
                (position) => {
                    sendLocationToServer(shipmentId, position.coords.latitude, position.coords.longitude, position.coords.speed);
                },
                (error) => {
                    console.error("Error getting location: ", error);
                },
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 10000 }
            );
            
            // Also set a backup interval in case watchPosition fires too often or too rarely
            locationInterval = setInterval(() => {
                navigator.geolocation.getCurrentPosition((position) => {
                    sendLocationToServer(shipmentId, position.coords.latitude, position.coords.longitude, position.coords.speed);
                });
            }, 15000); // Send every 15 seconds
            
        } else {
            alert("المتصفح الخاص بك لا يدعم تحديد الموقع (GPS). الرجاء تحديث التطبيق أو استخدام متصفح حديث.");
        }
    }

    function sendLocationToServer(shipmentId, lat, lng, speed) {
        fetch(`/driver/shipments/${shipmentId}/location`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                lat: lat,
                lng: lng,
                speed: speed
            })
        }).catch(err => console.log('Location sync error', err));
    }
</script>
@endpush
