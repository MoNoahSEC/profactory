@extends('layouts.app')
@section('title', 'أوامر الإنتاج | مصنع المنتجات')
@section('page_title', 'إدارة أوامر الإنتاج')

@section('content')

{{-- ── Header ── --}}
<div class="page-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h4 class="fw-bolder mb-1"><i class="bi bi-box-seam text-orange me-2"></i>قائمة أوامر الإنتاج</h4>
        <p class="text-muted mb-0 small">إدارة وتتبع أوامر الإنتاج والمخزون الناتج</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-orange rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#orderModal">
            <i class="bi bi-plus-lg me-1"></i> أمر إنتاج جديد
        </button>
    </div>
</div>

{{-- ── Table ── --}}
<div class="content-card">
    <div class="content-card-header d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul text-orange me-2"></i>سجل الإنتاج</h6>
        <input type="text" id="productionSearch" class="form-control form-control-sm w-auto" placeholder="🔍 بحث برقم الأمر أو المنتج..." style="border-radius:10px; border: 1.5px solid #e2e8f0; min-width:250px;">
    </div>
    <div class="table-responsive">
        <table class="table table-clean table-hover align-middle mb-0" id="productionTable">
            <thead>
                <tr>
                    <th>رقم الأمر</th>
                    <th>المنتج</th>
                    <th class="text-center">الكمية المطلوبة</th>
                    <th class="text-center">الكمية المنتجة</th>
                    <th class="text-center">تاريخ الإنتاج</th>
                    <th class="text-center">التكلفة الكلية</th>
                    <th class="text-center">الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                @php
                    $statusColors = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
                    $statusLabels = ['pending' => 'معلق', 'in_progress' => 'جاري', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
                    $color = $statusColors[$order->status] ?? 'secondary';
                    $label = $statusLabels[$order->status] ?? $order->status;
                @endphp
                <tr class="cursor-pointer" onclick="openOrderModal({{ json_encode($order) }}, '{{ $order->product->name ?? '-' }}', '{{ $label }}', '{{ $color }}')" title="انقر لعرض الخيارات والتفاصيل">
                    <td>
                        <span class="badge bg-dark bg-opacity-10 text-dark border rounded-pill px-3 py-1 fw-bold">
                            #{{ $order->order_number }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:36px;height:36px;font-size:1rem;flex-shrink:0;">
                                <i class="bi bi-box text-orange"></i>
                            </span>
                            <span class="fw-bold text-dark">{{ $order->product->name ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="text-center fw-bold">{{ $order->quantity_ordered }}</td>
                    <td class="text-center fw-bold text-primary">{{ $order->quantity_produced }}</td>
                    <td class="text-center text-muted fw-bold">{{ $order->production_date->format('Y-m-d') }}</td>
                    <td class="text-center fw-bold text-danger">
                        {{ $order->cost ? number_format($order->cost->total_cost, 2) : '-' }} <small class="text-muted fw-normal">ج.م</small>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $color }} rounded-pill px-3 py-1">
                            {{ $label }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <i class="bi bi-box-seam text-muted display-4 d-block mb-2 opacity-25"></i>
                        <span class="text-muted fw-bold">لا توجد أوامر إنتاج مسجلة حالياً.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ══  MODALS  ═══════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════ --}}

{{-- ── Action Modal ── --}}
<div class="modal fade" id="actionOrderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-sliders text-orange me-2"></i>خيارات أمر الإنتاج</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h4 class="fw-bolder mb-1" id="actOrderNum"></h4>
                <p class="text-muted mb-3" id="actOrderProduct"></p>
                <div class="mb-4" id="actOrderStatus"></div>
                
                <div class="d-grid gap-3">
                    <form id="completeOrderForm" method="POST" onsubmit="return confirm('تأكيد إكمال أمر الإنتاج؟ سيتم إضافة المنتجات للمخزون تلقائياً.');">
                        @csrf
                        <button type="submit" class="btn btn-success fw-bold rounded-pill w-100 shadow-sm py-2">
                            <i class="bi bi-check-circle me-1"></i> تأكيد إكمال الإنتاج
                        </button>
                    </form>

                    <a href="#" id="printOrderBtn" target="_blank" class="btn btn-outline-dark fw-bold rounded-pill w-100 py-2">
                        <i class="bi bi-printer me-1"></i> طباعة أمر الإنتاج
                    </a>

                    <form id="deleteOrderForm" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف أمر الإنتاج نهائياً؟');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger fw-bold rounded-pill w-100 py-2">
                            <i class="bi bi-trash me-1"></i> حذف أمر الإنتاج
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold w-100" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Add Modal ── --}}
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <form action="{{ route('production-orders.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill text-orange me-2"></i>إنشاء أمر إنتاج جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-start">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">المنتج المراد إنتاجه <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required style="border-radius:10px;">
                            <option value="">-- اختر منتج --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold">الكمية المطلوبة <span class="text-danger">*</span></label>
                            <input type="number" name="quantity_ordered" class="form-control fw-bold fs-5 text-primary" min="1" required style="border-radius:10px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted fw-bold">تاريخ الإنتاج <span class="text-danger">*</span></label>
                            <input type="date" name="production_date" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">ملاحظات (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2" style="border-radius:10px;"></textarea>
                    </div>

                    <div class="alert alert-warning border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3 mt-4 mb-0 fw-bold">
                        <i class="bi bi-info-circle me-1"></i> سيتم خصم الخامات تلقائياً من المخزون بناءً على وصفة تصنيع المنتج المحددة مسبقاً.
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange rounded-pill px-5 fw-bold shadow-sm">حفظ وإنشاء الأمر <i class="bi bi-check-circle ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Search
    document.getElementById('productionSearch')?.addEventListener('keyup', function() {
        const v = this.value.toLowerCase();
        document.querySelectorAll('#productionTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
        });
    });

    // Modal Details
    function openOrderModal(order, productName, label, color) {
        document.getElementById('actOrderNum').textContent = 'أمر إنتاج #' + order.order_number;
        document.getElementById('actOrderProduct').textContent = productName;
        document.getElementById('actOrderStatus').innerHTML = `<span class="badge badge-${color} rounded-pill px-3 py-1">${label}</span>`;
        
        const completeForm = document.getElementById('completeOrderForm');
        if (order.status === 'completed') {
            completeForm.style.display = 'none';
        } else {
            completeForm.style.display = 'block';
            completeForm.action = `/production-orders/${order.id}/complete`;
        }

        document.getElementById('printOrderBtn').href = `/production-orders/${order.id}/print`;
        
        const deleteForm = document.getElementById('deleteOrderForm');
        if (order.status === 'completed') {
            deleteForm.style.display = 'none';
        } else {
            deleteForm.style.display = 'block';
            deleteForm.action = `/production-orders/${order.id}`;
        }
        
        var modal = new bootstrap.Modal(document.getElementById('actionOrderModal'));
        modal.show();
    }
</script>
<style>
    .cursor-pointer { cursor: pointer; transition: background-color 0.2s; }
    .cursor-pointer:hover { background-color: rgba(234, 88, 12, 0.03) !important; }
</style>
@endpush
