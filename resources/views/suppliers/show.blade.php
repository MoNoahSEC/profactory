@extends('layouts.app')
@section('title', $supplier->name . ' | كشف حساب المورد')

@push('styles')
<style>
.action-bar { background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 14px 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.action-bar .action-title { color: #fff; font-weight: 700; font-size: 1.1rem; }
.action-bar .btn-action { border-radius: 12px; font-weight: 700; padding: 10px 20px; font-size: 0.9rem; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; cursor: pointer; text-decoration: none; }
.action-bar .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
.btn-print { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; }
.btn-print-pc { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.btn-whatsapp { background: linear-gradient(135deg, #25D366, #128C7E); color: #fff; }
.btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
.btn-back { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
@media (max-width: 767px) { .action-bar { flex-direction: column; align-items: stretch; padding: 12px; } .action-bar .btn-action { justify-content: center; padding: 12px; font-size: 1rem; } }
</style>
@endpush

@section('content')
@php
    $totalPurchases   = $supplier->purchases->sum('total_price');
    $totalPaid        = $supplier->purchases->sum('paid_amount');
    $purchaseDebt     = max(0, $totalPurchases - $totalPaid);
    $depositBalance   = $supplier->deposit_balance;
    $netBalance       = $depositBalance + $purchaseDebt; 
@endphp

<div class="action-bar">
    <div>
        <div class="action-title"><i class="bi bi-truck me-2"></i>{{ $supplier->name }}</div>
        <small style="color:rgba(255,255,255,0.6)">كشف حساب مورد @if($supplier->phone) — {{ $supplier->phone }} @endif</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('suppliers.print', $supplier) }}" target="_blank" class="btn-action btn-print">
            <i class="bi bi-printer-fill"></i> طباعة كشف الحساب
        </a>
        <form action="{{ route('system.direct-print') }}" method="POST" class="m-0 p-0">
            @csrf <input type="hidden" name="print_url" value="{{ route('suppliers.print', $supplier) }}">
            <button type="submit" class="btn-action btn-print-pc"><i class="bi bi-pc-display"></i> طباعة على الكمبيوتر</button>
        </form>
        @if($supplier->phone)
            <a href="https://wa.me/2{{ ltrim($supplier->phone, '0') }}" target="_blank" class="btn-action btn-whatsapp">
                <i class="bi bi-whatsapp"></i> واتساب
            </a>
        @endif
        <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editSupplierModal">
            <i class="bi bi-pencil-fill"></i> تعديل
        </button>
        <a href="{{ route('suppliers.index') }}" class="btn-action btn-back">
            <i class="bi bi-arrow-right"></i> عودة
        </a>
    </div>
</div>

<div class="container-fluid">
    {{-- ── Statistics ── --}}
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-bag-check stat-icon"></i>
                <div class="text-muted fw-bold mb-1 small">باقي حساب المشتريات</div>
                <div class="stat-amount {{ $purchaseDebt > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($purchaseDebt, 2) }}
                </div>
                <div class="mt-1 small fw-bold text-muted">
                    إجمالي: {{ number_format($totalPurchases, 2) }} | مدفوع: {{ number_format($totalPaid, 2) }}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-wallet2 stat-icon"></i>
                <div class="text-muted fw-bold mb-1 small">رصيد الدفعات / التسويات</div>
                <div class="stat-amount {{ $depositBalance >= 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format(abs($depositBalance), 2) }}
                </div>
                <div class="mt-1 small fw-bold">
                    @if($depositBalance > 0) <span class="text-danger">(دين علينا للمورد)</span>
                    @elseif($depositBalance < 0) <span class="text-success">(رصيد دائن لنا)</span>
                    @else <span class="text-muted">(مسفر)</span> @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-primary">
                <i class="bi bi-calculator stat-icon"></i>
                <div class="stat-label">الوضع المالي الكلي (الصافي)</div>
                <div class="stat-amount">
                    {{ number_format(abs($netBalance), 2) }} <small style="font-size:0.8rem;">ج.م</small>
                </div>
                <div class="mt-1 small fw-bold">
                    @if($netBalance >= 0)
                        <span class="badge badge-danger"><i class="bi bi-arrow-down-right"></i> إجمالي المديونية للمورد</span>
                    @else
                        <span class="badge badge-success"><i class="bi bi-arrow-up-right"></i> رصيد دائن لنا عند المورد</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Content Tabs ── --}}
    <ul class="nav nav-pills mb-3 gap-2" role="tablist">
        <li class="nav-item">
            <button class="nav-link active rounded-pill fw-bold px-4" data-bs-toggle="pill" data-bs-target="#account" type="button">كشف الحساب الشامل</button>
        </li>
        <li class="nav-item">
            <button class="nav-link rounded-pill fw-bold px-4" data-bs-toggle="pill" data-bs-target="#purchases" type="button">فواتير المشتريات</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- كشف الحساب الموحد -->
        <div class="tab-pane fade show active" id="account" role="tabpanel">
            <div class="content-card">
                <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-journal-text text-orange me-2"></i>كشف حساب المورد</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-orange rounded-pill btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="bi bi-cash-coin me-1"></i> دفعة نقدية
                        </button>
                        <button class="btn btn-outline-secondary rounded-pill btn-sm fw-bold shadow-sm bg-white" data-bs-toggle="modal" data-bs-target="#adjustBalanceModal">
                            <i class="bi bi-sliders me-1"></i> تسوية يدوية
                        </button>
                    </div>
                </div>
                {{-- MOBILE VIEW --}}
                @php $sorted = $supplier->getStatement(); @endphp
                <div class="d-md-none p-2">
                    @forelse($sorted as $entry)
                        <div class="glass-card mb-2 p-3 bg-white border rounded-3 shadow-sm cursor-pointer" onclick="openSupplierActionModal('{{ $entry['source'] }}', '{{ $entry['id'] }}', '{{ $entry['label'] }}', '{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}', '{{ $entry['source'] === 'deposit' ? json_encode($entry) : '{}' }}')">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="fw-bold text-muted small"><i class="bi bi-calendar me-1"></i>{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}</span>
                                <span>{!! $entry['badge'] !!}</span>
                            </div>
                            <div class="fw-bold text-dark mb-2 text-wrap" style="font-size:0.95rem;">
                                {{ $entry['label'] }}
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                @if($entry['credit'] > 0)
                                    <div class="text-success fw-bold"><small class="text-muted d-block" style="font-size:0.7rem;">لنا (دفعات)</small>{{ number_format($entry['credit'], 2) }}</div>
                                @else
                                    <div></div>
                                @endif
                                
                                @if($entry['debit'] > 0)
                                    <div class="text-danger fw-bold text-end"><small class="text-muted d-block" style="font-size:0.7rem;">لهم (مشتريات/دين)</small>{{ number_format($entry['debit'], 2) }}</div>
                                @else
                                    <div></div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted fw-bold border rounded-3 bg-light">
                            <i class="bi bi-journal-x display-4 d-block opacity-25 mb-2"></i>
                            لا توجد حركات مسجلة لهذا المورد
                        </div>
                    @endforelse
                    <div class="mt-3 p-3 text-center border-2 border-top">
                        <div class="fw-bold text-muted mb-2">الصافي النهائي:</div>
                        <span class="badge {{ $netBalance>=0?'bg-danger':'bg-success' }} px-3 py-2 fs-5 border border-2 shadow-sm w-100">
                            {{ $netBalance>=0?'للمورد':'لنا' }}: {{ number_format(abs($netBalance),2) }} ج.م
                        </span>
                    </div>
                </div>

                {{-- DESKTOP VIEW --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-clean table-hover align-middle mb-0 text-center">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>البيان</th>
                                <th>النوع</th>
                                <th class="text-success">لنا (دفعات)</th>
                                <th class="text-danger">لهم (مشتريات/دين)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sorted as $entry)
                                <tr class="cursor-pointer" onclick="openSupplierActionModal('{{ $entry['source'] }}', '{{ $entry['id'] }}', '{{ $entry['label'] }}', '{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}', '{{ $entry['source'] === 'deposit' ? json_encode($entry) : '{}' }}')" title="انقر لعرض الخيارات">
                                    <td class="fw-bold text-muted">{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}</td>
                                    <td class="text-wrap" style="min-width: 150px;">{{ $entry['label'] }}</td>
                                    <td>{!! $entry['badge'] !!}</td>
                                    <td class="text-success fw-bold">{{ $entry['credit']>0 ? number_format($entry['credit'],2) : '-' }}</td>
                                    <td class="text-danger fw-bold">{{ $entry['debit']>0 ? number_format($entry['debit'],2) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-journal-x display-4 d-block opacity-25 mb-2"></i>
                                        لا توجد حركات مسجلة لهذا المورد
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light">
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">الصافي النهائي:</td>
                                <td colspan="2" class="text-center">
                                    <span class="badge {{ $netBalance>=0?'bg-danger':'bg-success' }} px-3 py-2 fs-6 border border-2">
                                        {{ $netBalance>=0?'للمورد':'لنا' }}: {{ number_format(abs($netBalance),2) }} ج.م
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- سجل المشتريات -->
        <div class="tab-pane fade" id="purchases" role="tabpanel">
            <div class="content-card">
                <div class="content-card-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-bag-check text-orange me-2"></i>سجل المشتريات</h6>
                </div>
                {{-- MOBILE VIEW --}}
                <div class="d-md-none p-2">
                    @forelse($supplier->purchases as $pur)
                        <div class="glass-card mb-2 p-3 bg-white border rounded-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <span class="fw-bold text-muted small"><i class="bi bi-calendar me-1"></i>{{ $pur->purchase_date->format('Y-m-d') }}</span>
                                <span class="badge bg-light text-dark border">{{ $pur->quantity }} {{ $pur->rawMaterial->unit ?? '' }}</span>
                            </div>
                            <div class="fw-bold text-dark mb-2" style="font-size:1.05rem;">
                                {{ $pur->rawMaterial->name ?? 'غير معروف' }}
                            </div>
                            <div class="row g-2 mb-2 text-center small">
                                <div class="col-4">
                                    <div class="rounded p-1" style="background: #f1f5f9; color: #0f172a;">إجمالي<br><span class="fw-bold">{{ number_format($pur->total_price, 2) }}</span></div>
                                </div>
                                <div class="col-4">
                                    <div class="rounded p-1" style="background: #dcfce7; color: #14532d;">مدفوع<br><span class="fw-bold">{{ number_format($pur->paid_amount, 2) }}</span></div>
                                </div>
                                <div class="col-4">
                                    <div class="rounded p-1" style="background: #fee2e2; color: #991b1b;">متبقي<br><span class="fw-bold">{{ number_format($pur->total_price - $pur->paid_amount, 2) }}</span></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted fw-bold border rounded-3 bg-light">لا توجد مشتريات مسجلة</div>
                    @endforelse
                </div>

                {{-- DESKTOP VIEW --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-clean table-hover align-middle mb-0 text-center">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الخامة</th>
                                <th>الكمية</th>
                                <th>الإجمالي</th>
                                <th class="text-success">مدفوع</th>
                                <th class="text-danger">المتبقي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplier->purchases as $pur)
                            <tr>
                                <td class="fw-bold text-muted">{{ $pur->purchase_date->format('Y-m-d') }}</td>
                                <td class="fw-bold">{{ $pur->rawMaterial->name ?? 'غير معروف' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $pur->quantity }} {{ $pur->rawMaterial->unit ?? '' }}</span></td>
                                <td class="fw-bold">{{ number_format($pur->total_price, 2) }}</td>
                                <td class="text-success">{{ number_format($pur->paid_amount, 2) }}</td>
                                <td class="text-danger fw-bold">{{ number_format($pur->total_price - $pur->paid_amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted">لا توجد فواتير مسجلة</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ══  MODALS  ═══════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════ --}}

<!-- Action Modal for Rows -->
<div class="modal fade" id="supplierActionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-gear text-orange me-2"></i>خيارات الحركة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="fw-bold mb-1" id="actSupRowTitle"></p>
                <small class="text-muted d-block mb-4" id="actSupRowDate"></small>
                
                <div class="d-grid gap-3" id="actSupDepositBtns" style="display:none;">
                    <button type="button" id="btnEditSupAdj" class="btn btn-outline-warning fw-bold rounded-pill w-100 py-2"><i class="bi bi-pencil me-1"></i> تعديل الحركة</button>
                    <form id="formDeleteSupAdj" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الحركة؟ سيتم تحديث الرصيد تلقائياً.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger fw-bold rounded-pill w-100 py-2"><i class="bi bi-trash me-1"></i> حذف الحركة</button>
                    </form>
                </div>
                
                <div id="actSupNoAction" style="display:none;" class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> هذه الحركة مرتبطة بفاتورة مشتريات. يمكنك تعديلها أو حذفها من شاشة المشتريات الرئيسية.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: تسجيل دفعة مقدمة -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-cash-coin text-success me-2"></i>دفع مبلغ للمورد (من الخزينة)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('suppliers.deposit', $supplier) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">المبلغ (ج.م) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">ملاحظات / بيان</label>
                        <input type="text" name="description" class="form-control" placeholder="دفعة تحت الحساب..." style="border-radius:10px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">حفظ وتسجيل النقدية</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: تعديل تسوية يدوية / Edit Adjustment Modal (Combined) -->
<div class="modal fade" id="adjustBalanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark" id="adjModalTitle"><i class="bi bi-sliders text-warning me-2"></i>تسوية حساب مورد (بدون خزينة)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjForm" action="{{ route('suppliers.adjustBalance', $supplier) }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="adjMethod" value="POST">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold">نوع التعديل <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type" id="typeCredit" value="credit" required>
                                <label class="btn btn-outline-danger w-100 py-3 text-center rounded-3" for="typeCredit">
                                    <i class="bi bi-plus-circle d-block fs-4 mb-1"></i>
                                    <span class="fw-bold">دين للمورد</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type" id="typeDebt" value="debt">
                                <label class="btn btn-outline-success w-100 py-3 text-center rounded-3" for="typeDebt">
                                    <i class="bi bi-dash-circle d-block fs-4 mb-1"></i>
                                    <span class="fw-bold">خصم من المورد</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">المبلغ (ج.م) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="adjAmount" class="form-control" required style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="adjDate" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">البيان <span class="text-danger">*</span></label>
                        <input type="text" name="description" id="adjDesc" class="form-control" required style="border-radius:10px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" id="adjSubmitBtn">حفظ التسوية</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('suppliers._modals')
@endsection

@push('scripts')
<script>
    let supActionModal;
    let adjustModal;
    
    document.addEventListener("DOMContentLoaded", () => {
        supActionModal = new bootstrap.Modal(document.getElementById('supplierActionModal'));
        adjustModal = new bootstrap.Modal(document.getElementById('adjustBalanceModal'));
    });

    function openSupplierActionModal(source, id, title, date, dataStr) {
        document.getElementById('actSupRowTitle').textContent = title;
        document.getElementById('actSupRowDate').textContent = date;
        
        const depDiv = document.getElementById('actSupDepositBtns');
        const noActDiv = document.getElementById('actSupNoAction');
        
        if(source === 'deposit') {
            depDiv.style.display = 'grid';
            noActDiv.style.display = 'none';
            
            document.getElementById('formDeleteSupAdj').action = `/suppliers/adjustments/${id}`;
            
            const dataObj = JSON.parse(dataStr);
            document.getElementById('btnEditSupAdj').onclick = function() {
                supActionModal.hide();
                setTimeout(() => {
                    document.getElementById('adjModalTitle').innerHTML = '<i class="bi bi-pencil text-warning me-2"></i>تعديل تسوية الحساب';
                    document.getElementById('adjForm').action = `/suppliers/adjustments/${id}`;
                    document.getElementById('adjMethod').value = 'PUT';
                    
                    if (dataObj.type === 'deposit') {
                        document.getElementById('typeCredit').checked = false;
                        document.getElementById('typeDebt').checked = false;
                        // For pure deposits (money paid via treasury), we cannot edit type/amount without breaking ledger.
                        // Wait, can we edit deposit? The old view let us edit it by calling editAdjustment. 
                        // It's safer to only allow editing description.
                        // So we map it to adjusting deposit logic if needed.
                        alert('تعديل الدفعات النقدية غير متاح من هنا للحفاظ على سلامة الخزينة. يرجى حذف الحركة وإعادتها إن لزم الأمر.');
                        return;
                    } else if (dataObj.type === 'debt_adjustment') {
                        // In adjustment, amount was saved. The `editAdjustment` in old code did this.
                        if (dataObj.amount > 0) { // meaning we owe supplier
                            document.getElementById('typeCredit').checked = true;
                        } else {
                            document.getElementById('typeDebt').checked = true;
                        }
                    }
                    
                    document.getElementById('adjAmount').value = Math.abs(dataObj.amount);
                    document.getElementById('adjDate').value = dataObj.date.substring(0, 10);
                    document.getElementById('adjDesc').value = dataObj.description || '';
                    document.getElementById('adjSubmitBtn').textContent = 'حفظ التعديلات';
                    
                    adjustModal.show();
                }, 400);
            };
        } else {
            depDiv.style.display = 'none';
            noActDiv.style.display = 'block';
        }
        
        supActionModal.show();
    }

    document.getElementById('adjustBalanceModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('adjModalTitle').innerHTML = '<i class="bi bi-sliders text-warning me-2"></i>تسوية حساب مورد (بدون خزينة)';
        document.getElementById('adjForm').reset();
        document.getElementById('adjForm').action = "{{ route('suppliers.adjustBalance', $supplier) }}";
        document.getElementById('adjMethod').value = 'POST';
        document.getElementById('adjDate').value = "{{ date('Y-m-d') }}";
        document.getElementById('adjSubmitBtn').textContent = 'حفظ التسوية';
    });
</script>
<style>
    .cursor-pointer { cursor: pointer; transition: background-color 0.2s; }
    .cursor-pointer:hover { background-color: rgba(234, 88, 12, 0.03) !important; }
</style>
@endpush
