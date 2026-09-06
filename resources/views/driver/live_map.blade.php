@extends('layouts.app')

@section('title', 'تتبع السائق | ' . $shipment->driver->name ?? 'سائق')
@section('page_title', 'التتبع المباشر لرحلة #' . $shipment->id)

@section('content')
<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    #map { height: 600px; width: 100%; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1; }
    .info-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .pulse-marker {
        background: #ea580c;
        border-radius: 50%;
        width: 15px;
        height: 15px;
        border: 2px solid white;
        box-shadow: 0 0 0 0 rgba(234, 88, 12, 1);
        animation: pulseMap 1.5s infinite;
    }
    @keyframes pulseMap {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.7); }
        70% { transform: scale(1.5); box-shadow: 0 0 0 10px rgba(234, 88, 12, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0); }
    }
</style>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="info-card">
            <h5 class="fw-bold"><i class="bi bi-info-circle-fill text-primary me-2"></i>تفاصيل الرحلة</h5>
            <hr>
            <p><strong>السائق:</strong> {{ $shipment->driver->name ?? $shipment->driver_name }}</p>
            <p><strong>العميل:</strong> {{ $shipment->order->customer_name }}</p>
            <p><strong>العنوان:</strong> {{ $shipment->order->address }}</p>
            <p><strong>حالة الرحلة:</strong> 
                @if($shipment->status === 'en_route')
                    <span class="badge bg-warning text-dark"><span class="spinner-grow spinner-grow-sm me-1"></span> في الطريق</span>
                @elseif($shipment->status === 'completed')
                    <span class="badge bg-success">تم التوصيل</span>
                @else
                    <span class="badge bg-secondary">قيد الانتظار</span>
                @endif
            </p>
            <p><strong>وقت الخروج:</strong> {{ $shipment->shipped_at ? $shipment->shipped_at->format('Y-m-d H:i') : 'لم تخرج بعد' }}</p>
        </div>
        
        <div class="info-card">
            <h5 class="fw-bold"><i class="bi bi-geo-alt-fill text-danger me-2"></i>معلومات التتبع المباشر</h5>
            <hr>
            <p><strong>آخر سرعة مُسجلة:</strong> <span id="currentSpeed" class="fw-bold fs-5 text-primary">0</span> كم/س</p>
            <p><strong>آخر تحديث للموقع:</strong> <span id="lastUpdate">جاري الجلب...</span></p>
            <p class="text-muted small mb-0"><i class="bi bi-arrow-repeat"></i> الخريطة تتحدث تلقائياً كل 10 ثوانٍ.</p>
        </div>
        
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-right"></i> العودة للطلبيات</a>
    </div>
    
    <div class="col-lg-8">
        <div id="map"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map centered on Egypt (default)
    var map = L.map('map').setView([30.0444, 31.2357], 10);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    var driverMarker = null;
    var polyline = L.polyline([], {color: '#ea580c', weight: 4}).addTo(map);
    
    // Custom pulsing icon for driver
    var pulseIcon = L.divIcon({
        className: 'custom-div-icon',
        html: '<div class="pulse-marker"></div>',
        iconSize: [15, 15],
        iconAnchor: [7.5, 7.5]
    });

    function fetchLocations() {
        fetch('{{ route('shipments.locations', $shipment->id) }}')
            .then(res => res.json())
            .then(data => {
                if(data.length > 0) {
                    var latlngs = data.map(loc => [parseFloat(loc.latitude), parseFloat(loc.longitude)]);
                    
                    // Update polyline path
                    polyline.setLatLngs(latlngs);
                    
                    // Update Marker position
                    var latest = data[data.length - 1];
                    var currentPos = [parseFloat(latest.latitude), parseFloat(latest.longitude)];
                    
                    if(!driverMarker) {
                        driverMarker = L.marker(currentPos, {icon: pulseIcon}).addTo(map);
                        map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
                    } else {
                        driverMarker.setLatLng(currentPos);
                        // Optional: map.panTo(currentPos);
                    }
                    
                    // Update Info Card
                    if (latest.speed !== null) {
                        // speed might be in m/s from HTML5 api, convert to km/h: speed * 3.6
                        let speedKm = Math.round(parseFloat(latest.speed) * 3.6);
                        document.getElementById('currentSpeed').innerText = speedKm;
                    }
                    
                    let d = new Date(latest.recorded_at);
                    document.getElementById('lastUpdate').innerText = d.toLocaleTimeString('ar-EG');
                }
            })
            .catch(err => console.error("Error fetching locations:", err));
    }

    // Initial fetch
    fetchLocations();

    // Set interval for live updates if not completed
    @if($shipment->status !== 'completed')
        setInterval(fetchLocations, 10000);
    @endif
});
</script>
@endsection
