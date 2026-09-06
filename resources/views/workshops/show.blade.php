@extends('layouts.app')
@section('title', 'كشف حساب ورشة | ' . $workshop->name)

@push('styles')
<style>
.action-bar { background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 14px 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.action-bar .action-title { color: #fff; font-weight: 700; font-size: 1.1rem; }
.action-bar .btn-action { border-radius: 12px; font-weight: 700; padding: 10px 20px; font-size: 0.9rem; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; cursor: pointer; text-decoration: none; }
.action-bar .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
.btn-new-inv { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; }
.btn-print { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; }
.btn-print-pc { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.btn-whatsapp { background: linear-gradient(135deg, #25D366, #128C7E); color: #fff; }
.btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
.btn-back { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
@media (max-width: 767px) { .action-bar { flex-direction: column; align-items: stretch; padding: 12px; } .action-bar .btn-action { justify-content: center; padding: 12px; font-size: 1rem; } }
</style>
@endpush

@section('content')
<div class="container-fluid">
<div class="action-bar">
    <div>
        <div class="action-title"><i class="bi bi-journal-text me-2"></i>{{ $workshop->name }}</div>
        <small style="color:rgba(255,255,255,0.6)">كشف حساب ورشة @if($workshop->phone) — {{ $workshop->phone }} @endif</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('workshops.invoices.create', ['workshop_id' => $workshop->id]) }}" class="btn-action btn-new-inv">
            <i class="bi bi-receipt"></i> فاتورة جديدة
        </a>
        <a href="{{ route('workshops.print', $workshop) }}" target="_blank" class="btn-action btn-print">
            <i class="bi bi-printer-fill"></i> طباعة كشف الحساب
        </a>
        <form action="{{ route('system.direct-print') }}" method="POST" class="m-0 p-0">
            @csrf <input type="hidden" name="print_url" value="{{ route('workshops.print', $workshop) }}">
            <button type="submit" class="btn-action btn-print-pc"><i class="bi bi-pc-display"></i> طباعة على الكمبيوتر</button>
        </form>
        @if($workshop->phone)
            <a href="https://wa.me/2{{ ltrim($workshop->phone, '0') }}" target="_blank" class="btn-action btn-whatsapp">
                <i class="bi bi-whatsapp"></i> واتساب
            </a>
        @endif
        <button class="btn-action btn-edit" onclick="editWorkshop({{ json_encode($workshop) }})">
            <i class="bi bi-pencil-fill"></i> تعديل
        </button>
        <a href="{{ route('workshops.index') }}" class="btn-action btn-back">
            <i class="bi bi-arrow-right"></i> عودة
        </a>
    </div>
</div>

<div class="container-fluid">
    {{-- ── Statistics ── --}}
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-receipt stat-icon"></i>
                <div class="text-muted fw-bold mb-1 small">الفواتير الآجلة</div>
                <div class="stat-amount {{ $invoiceDebt > 0 ? 'text-danger' : ($invoiceDebt < 0 ? 'text-success' : 'text-dark') }}">
                    {{ number_format(abs($invoiceDebt), 2) }}
                </div>
                <div class="mt-1 small fw-bold">
                    @if($invoiceDebt > 0) <span class="text-danger">(عليهم لنا)</span>
                    @elseif($invoiceDebt < 0) <span class="text-success">(لهم عندنا)</span>
                    @else <span class="text-muted">(مسفر)</span> @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-cash-stack stat-icon"></i>
                <div class="text-muted fw-bold mb-1 small">حركات الخزينة والديون السابقة</div>
                <div class="stat-amount {{ $depositBalance > 0 ? 'text-success' : ($depositBalance < 0 ? 'text-danger' : 'text-dark') }}">
                    {{ number_format(abs($depositBalance), 2) }}
                </div>
                <div class="mt-1 small fw-bold">
                    @if($depositBalance > 0) <span class="text-success">(رصيد دائن / دفعوا لنا)</span>
                    @elseif($depositBalance < 0) <span class="text-danger">(رصيد مدين / دفعنا لهم)</span>
                    @else <span class="text-muted">(مسفر)</span> @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-orange border-0">
                <i class="bi bi-wallet2 stat-icon text-white opacity-25"></i>
                <div class="text-white-50 fw-bold mb-1 small">الرصيد النهائي (الصافي)</div>
                <div class="stat-amount text-white">
                    {{ number_format(abs($netBalance), 2) }} ج.م
                </div>
                <div class="mt-1 small fw-bold">
                    @if($netBalance > 0)
                        <span class="badge bg-white text-danger"><i class="bi bi-arrow-down-right"></i> مديون لنا</span>
                    @elseif($netBalance < 0)
                        <span class="badge bg-white text-success"><i class="bi bi-arrow-up-right"></i> له عندنا</span>
                    @else
                        <span class="badge bg-white text-dark"><i class="bi bi-check2-all"></i> مسفر تماماً</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="content-card">
        <div class="content-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-orange me-2"></i>سجل الحركات المالي</h6>
            <button class="btn btn-orange rounded-pill px-4 btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#transactionModal">
                <i class="bi bi-cash-coin me-1"></i> دفعة نقدية / تسوية
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-clean table-hover align-middle mb-0 text-center">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>البيان</th>
                        <th>النوع</th>
                        <th class="text-danger">لنا (مدين)</th>
                        <th class="text-success">لهم (دائن)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sorted as $entry)
                        @php 
                            $rawId = '';
                            if($entry['source'] === 'invoice') $rawId = str_replace('inv_', '', $entry['id']);
                            if($entry['source'] === 'transaction') $rawId = $entry['raw_transaction']->id;
                        @endphp
                        <tr class="cursor-pointer" onclick="openActionModal('{{ $entry['source'] }}', '{{ $rawId }}', '{{ $entry['label'] }}', '{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}', '{{ $entry['source'] === 'transaction' ? json_encode($entry['raw_transaction']) : '{}' }}')" title="انقر لعرض الخيارات">
                            <td class="fw-bold">{{ \Carbon\Carbon::parse($entry['date'])->format('Y/m/d') }}</td>
                            <td class="text-wrap" style="min-width: 150px;">{{ $entry['label'] }}</td>
                            <td>{!! $entry['badge'] !!}</td>
                            <td class="text-danger fw-bold">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                            <td class="text-success fw-bold">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted py-5 text-center">
                                <i class="bi bi-journal-x display-4 d-block opacity-25 mb-2"></i>
                                لا توجد حركات مالية أو فواتير لهذه الورشة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ══  MODALS  ═══════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════ --}}

<!-- Action Modal for Rows -->
<div class="modal fade" id="rowActionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-gear text-orange me-2"></i>خيارات الحركة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="fw-bold mb-1" id="actRowTitle"></p>
                <small class="text-muted d-block mb-4" id="actRowDate"></small>
                
                <div class="d-grid gap-3" id="actInvoiceBtns" style="display:none;">
                    <a href="#" id="btnViewInv" class="btn btn-outline-primary fw-bold rounded-pill w-100 py-2"><i class="bi bi-eye me-1"></i> عرض الفاتورة</a>
                    <a href="#" id="btnPrintInv" target="_blank" class="btn btn-outline-secondary fw-bold rounded-pill w-100 py-2"><i class="bi bi-printer me-1"></i> طباعة الفاتورة</a>
                    <a href="#" id="btnEditInv" class="btn btn-outline-warning fw-bold rounded-pill w-100 py-2"><i class="bi bi-pencil me-1"></i> تعديل الفاتورة</a>
                    
                    <form id="formDeleteInv" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الفاتورة؟ سيتم استرجاع كل الخامات والمنتجات المتعلقة بها وتعديل رصيد الورشة.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger fw-bold rounded-pill w-100 py-2"><i class="bi bi-trash me-1"></i> حذف الفاتورة</button>
                    </form>
                </div>

                <div class="d-grid gap-3" id="actTransBtns" style="display:none;">
                    <button type="button" id="btnEditTrans" class="btn btn-outline-warning fw-bold rounded-pill w-100 py-2"><i class="bi bi-pencil me-1"></i> تعديل الدفعة</button>
                    <form id="formDeleteTrans" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الدفعة؟ سيتم عكس تأثيرها على الحساب.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger fw-bold rounded-pill w-100 py-2"><i class="bi bi-trash me-1"></i> حذف الدفعة</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Create/Edit Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="transactionForm" action="{{ route('workshops.transactions.store', $workshop) }}" method="POST" class="modal-content" style="border-radius:20px; border:none;">
            @csrf
            <input type="hidden" name="_method" id="transMethod" value="POST">
            <div class="modal-header" style="background:linear-gradient(135deg,#fff7ed,#fff); border-bottom:2px solid var(--primary-light); border-radius:20px 20px 0 0;">
                <h5 class="modal-title fw-bold text-dark" id="transModalTitle"><i class="bi bi-cash-coin text-orange me-2"></i>تسجيل دفعة أو تسوية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">نوع العملية</label>
                    <select name="type" id="transType" class="form-control" required style="border-radius:10px;">
                        <option value="payment">صرف نقدية (نحن دفعنا للورشة)</option>
                        <option value="receipt">قبض نقدية (الورشة دفعت لنا)</option>
                        <option value="debt_adjustment">تسوية دين (نطالبهم بدين)</option>
                        <option value="old_debt_us">حساب قديم (لنا على الورشة)</option>
                        <option value="old_debt_them">حساب قديم (علينا للورشة)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">المبلغ</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0.01" name="amount" id="transAmount" class="form-control" required style="border-radius:0 10px 10px 0;">
                        <span class="input-group-text bg-light" style="border-radius:10px 0 0 10px;">ج.م</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">التاريخ</label>
                    <input type="date" name="date" id="transDate" class="form-control" value="{{ date('Y-m-d') }}" required style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">البيان / ملاحظات</label>
                    <input type="text" name="description" id="transDesc" class="form-control" placeholder="مثال: دفعة تحت الحساب..." style="border-radius:10px;">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-orange rounded-pill px-4 fw-bold shadow-sm" id="transSubmitBtn">حفظ العملية</button>
            </div>
        </form>
    </div>
</div>

@include('workshops._modal')
@endsection

@push('scripts')
<script>
    let rowModal;
    let transModal;
    
    document.addEventListener("DOMContentLoaded", () => {
        rowModal = new bootstrap.Modal(document.getElementById('rowActionModal'));
        transModal = new bootstrap.Modal(document.getElementById('transactionModal'));
    });

    function openActionModal(source, id, title, date, transDataStr) {
        document.getElementById('actRowTitle').textContent = title;
        document.getElementById('actRowDate').textContent = date;
        
        const invDiv = document.getElementById('actInvoiceBtns');
        const transDiv = document.getElementById('actTransBtns');
        
        if(source === 'invoice') {
            invDiv.style.display = 'grid';
            transDiv.style.display = 'none';
            
            document.getElementById('btnViewInv').href = `/workshops/invoices/${id}`;
            document.getElementById('btnPrintInv').href = `/workshops/invoices/${id}/print`;
            document.getElementById('btnEditInv').href = `/workshops/invoices/${id}/edit`;
            document.getElementById('formDeleteInv').action = `/workshops/invoices/${id}`;
        } else if (source === 'transaction') {
            invDiv.style.display = 'none';
            transDiv.style.display = 'grid';
            
            document.getElementById('formDeleteTrans').action = `/workshops/transactions/${id}`;
            
            const transObj = JSON.parse(transDataStr);
            document.getElementById('btnEditTrans').onclick = function() {
                rowModal.hide();
                setTimeout(() => {
                    document.getElementById('transModalTitle').innerHTML = '<i class="bi bi-pencil text-orange me-2"></i>تعديل العملية';
                    document.getElementById('transactionForm').action = `/workshops/transactions/${id}`;
                    document.getElementById('transMethod').value = 'PUT';
                    
                    document.getElementById('transType').value = transObj.type;
                    document.getElementById('transAmount').value = transObj.amount;
                    document.getElementById('transDate').value = transObj.date.substring(0, 10);
                    document.getElementById('transDesc').value = transObj.description || '';
                    document.getElementById('transSubmitBtn').textContent = 'حفظ التعديلات';
                    
                    transModal.show();
                }, 400);
            };
        }
        
        rowModal.show();
    }

    document.getElementById('transactionModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('transModalTitle').innerHTML = '<i class="bi bi-cash-coin text-orange me-2"></i>تسجيل دفعة أو تسوية';
        document.getElementById('transactionForm').reset();
        document.getElementById('transactionForm').action = "{{ route('workshops.transactions.store', $workshop) }}";
        document.getElementById('transMethod').value = 'POST';
        document.getElementById('transDate').value = "{{ date('Y-m-d') }}";
        document.getElementById('transSubmitBtn').textContent = 'حفظ العملية';
    });
</script>
<style>
    .cursor-pointer { cursor: pointer; transition: background-color 0.2s; }
    .cursor-pointer:hover { background-color: rgba(234, 88, 12, 0.03) !important; }
</style>
@endpush
