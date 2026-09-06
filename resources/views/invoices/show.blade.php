@extends('layouts.app')
@section('title', 'فاتورة ' . $invoice->invoice_number)
@section('page_title', 'تفاصيل الفاتورة — ' . $invoice->invoice_number)

@push('styles')
<style>
.action-bar { background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 14px 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.action-bar .action-title { color: #fff; font-weight: 700; font-size: 1.1rem; }
.action-bar .btn-action { border-radius: 12px; font-weight: 700; padding: 10px 20px; font-size: 0.9rem; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
.action-bar .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
.btn-print { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; }
.btn-print-pc { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.btn-whatsapp { background: linear-gradient(135deg, #25D366, #128C7E); color: #fff; }
.btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
.btn-back { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
/* Mobile sticky bar */
@media (max-width: 767px) {
  .action-bar { flex-direction: column; align-items: stretch; padding: 12px; }
  .action-bar .btn-action { justify-content: center; padding: 12px; font-size: 1rem; }
}
</style>
@endpush

@section('content')
{{-- ═══ Action Bar ═══ --}}
<div class="action-bar mb-4">
    <div>
        <div class="action-title"><i class="bi bi-receipt me-2"></i>{{ $invoice->invoice_number }}</div>
        @php $statusLabels=['draft'=>'مسودة','sent'=>'مُرسلة','paid'=>'مدفوعة','partial'=>'مدفوعة جزئياً','overdue'=>'متأخرة']; @endphp
        <small style="color:rgba(255,255,255,0.6)">{{ $invoice->customer->name ?? '-' }} — {{ $statusLabels[$invoice->status] ?? $invoice->status }}</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('invoices.print', $invoice) }}?t={{ time() }}" target="_blank" class="btn-action btn-print">
            <i class="bi bi-printer-fill"></i> طباعة / واتساب
        </a>
        <form action="{{ route('system.direct-print') }}" method="POST" class="m-0 p-0">
            @csrf
            <input type="hidden" name="print_url" value="{{ route('invoices.print', $invoice) }}">
            <button type="submit" class="btn-action btn-print-pc">
                <i class="bi bi-pc-display"></i> طباعة على الكمبيوتر
            </button>
        </form>
        @if($invoice->customer && $invoice->customer->phone)
            <a href="https://wa.me/2{{ ltrim($invoice->customer->phone, '0') }}" target="_blank" class="btn-action btn-whatsapp">
                <i class="bi bi-whatsapp"></i> واتساب مباشر
            </a>
        @endif
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn-action btn-edit">
            <i class="bi bi-pencil-fill"></i> تعديل
        </a>
        @hasrole('Admin')
        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="m-0 p-0 d-inline" onsubmit="return confirm('هل أنت متأكد من حذف الفاتورة نهائياً؟');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-action bg-danger text-white border-0 w-100">
                <i class="bi bi-trash-fill"></i> حذف
            </button>
        </form>
        @endhasrole
        <a href="{{ route('invoices.index') }}" class="btn-action btn-back">
            <i class="bi bi-arrow-right"></i> عودة
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Invoice Details -->
        <div class="glass-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>{{ $invoice->invoice_number }}</h5>
                <span class="badge bg-{{ $invoice->status_color }} bg-opacity-25 text-{{ $invoice->status_color }} border border-{{ $invoice->status_color }} rounded-pill px-3 py-2 fs-6">
                    {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                </span>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <small class="text-muted d-block mb-1">العميل</small>
                    <div class="d-flex align-items-center">
                        <h6 class="fw-bold text-dark mb-0 me-3">{{ $invoice->customer->name ?? '-' }}</h6>
                        @if(isset($customerBalance) && $invoice->customer)
                            @if($customerBalance > 0)
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill px-2">
                                    عليه مستحقات: {{ number_format($customerBalance, 2) }}
                                </span>
                            @elseif($customerBalance < 0)
                                <span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-2">
                                    له رصيد: {{ number_format(abs($customerBalance), 2) }}
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary rounded-pill px-2">
                                    لا ديون مستحقة
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block mb-1">تاريخ الفاتورة</small>
                    <h6 class="text-dark">{{ $invoice->invoice_date->format('Y-m-d') }}</h6>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block mb-1">تاريخ الاستحقاق</small>
                    <h6 class="text-dark">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '-' }}</h6>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-clean mb-0">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>خصم</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="fw-bold text-dark">{{ $item->product->name ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-danger">{{ number_format($item->discount, 2) }}</td>
                            <td class="fw-bold text-success">{{ number_format($item->total, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Items -->
            <div class="d-md-none p-2 bg-light rounded">
                <h6 class="fw-bold text-muted mb-2 ps-1">أصناف الفاتورة:</h6>
                @foreach($invoice->items as $item)
                <div class="bg-white p-3 rounded-3 shadow-sm border mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="fw-bold text-dark">{{ $item->product->name ?? '-' }}</span>
                        <span class="fw-bold text-success">{{ number_format($item->total, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span>الكمية: {{ $item->quantity }}</span>
                        <span>السعر: {{ number_format($item->unit_price, 2) }}</span>
                        @if($item->discount > 0)
                            <span class="text-danger">خصم: {{ number_format($item->discount, 2) }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Totals -->
            <div class="mt-4 pt-3 border-top border-secondary" style="border-color:rgba(255,255,255,0.08)!important;">
                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">المجموع الفرعي</span><span>{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">الخصم ({{ $invoice->discount_type == 'percent' ? $invoice->discount_value.'%' : 'ثابت' }})</span><span class="text-danger">-{{ number_format($invoice->discount_value, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">الضريبة ({{ $invoice->tax_rate }}%)</span><span>{{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span></div>
                        <div class="d-flex justify-content-between fw-bold fs-5 pt-2 border-top border-secondary" style="border-color:rgba(255,255,255,0.08)!important;"><span>الإجمالي</span><span class="text-success">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments History -->
        <div class="glass-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-wallet me-2 text-primary"></i>سجل المدفوعات</h5>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-clean mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>الطريقة</th>
                            <th>المرجع</th>
                            <th>ملاحظات</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->payments as $pay)
                        @php $methodLabels=['cash'=>'كاش','bank_transfer'=>'تحويل بنكي','check'=>'شيك','deposit'=>'من العربون']; @endphp
                        <tr>
                            <td><span class="badge bg-light text-dark">{{ $pay->payment_date->format('Y-m-d') }}</span></td>
                            <td class="text-success fw-bold">{{ number_format($pay->amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</td>
                            <td>{{ $methodLabels[$pay->payment_method] ?? $pay->payment_method }}</td>
                            <td class="text-muted">{{ $pay->reference_number ?? '-' }}</td>
                            <td class="text-muted">{{ $pay->notes ?? '-' }}</td>
                            <td class="text-end">
                                <form action="{{ route('payments.destroy', $pay) }}" method="POST" onsubmit="return confirm('حذف الدفعة؟');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle border-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد مدفوعات مسجلة</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Payments -->
            <div class="d-md-none p-2 bg-light rounded">
                @forelse($invoice->payments as $pay)
                @php $methodLabels=['cash'=>'كاش','bank_transfer'=>'تحويل بنكي','check'=>'شيك','deposit'=>'من العربون']; @endphp
                <div class="bg-white p-3 rounded-3 shadow-sm border mb-2 position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="badge bg-light text-dark"><i class="bi bi-calendar me-1"></i> {{ $pay->payment_date->format('Y-m-d') }}</span>
                        <span class="fw-bold text-success">{{ number_format($pay->amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span>
                    </div>
                    <div class="mb-1 small">
                        <span class="text-muted">طريقة الدفع:</span> 
                        <span class="fw-bold">{{ $methodLabels[$pay->payment_method] ?? $pay->payment_method }}</span>
                    </div>
                    @if($pay->reference_number)
                    <div class="mb-1 small">
                        <span class="text-muted">المرجع:</span> 
                        <span class="fw-bold">{{ $pay->reference_number }}</span>
                    </div>
                    @endif
                    @if($pay->notes)
                    <div class="mb-2 small text-muted">
                        <i class="bi bi-info-circle me-1"></i> {{ $pay->notes }}
                    </div>
                    @endif
                    
                    <form action="{{ route('payments.destroy', $pay) }}" method="POST" class="mt-2 text-start border-top pt-2" onsubmit="return confirm('حذف الدفعة؟');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill"><i class="bi bi-trash me-1"></i> حذف الدفعة</button>
                    </form>
                </div>
                @empty
                <div class="text-center py-4 text-muted fw-bold">لا توجد مدفوعات مسجلة</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Payment Summary -->
        <div class="glass-card mb-4">
            <h5 class="fw-bold mb-3">ملخص الدفع</h5>
            @if(isset($previousBalance) && $previousBalance != 0)
            <div class="d-flex justify-content-between mb-2 p-2 rounded" style="background: rgba(255,193,7,0.08); border: 1px solid rgba(255,193,7,0.2);">
                <span class="text-muted"><i class="bi bi-clock-history me-1 text-warning"></i>الرصيد السابق</span>
                <span class="fw-bold {{ $previousBalance > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $previousBalance > 0 ? number_format($previousBalance, 2) . ' (عليه)' : number_format(abs($previousBalance), 2) . ' (له)' }}
                    {{ $invoice->currency ?? 'ج.م' }}
                </span>
            </div>
            @endif
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">الإجمالي</span><span class="fw-bold">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">المدفوع</span><span class="text-success fw-bold">{{ number_format($invoice->paid_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span></div>
            <div class="d-flex justify-content-between pt-2 border-top border-secondary fs-5" style="border-color:rgba(255,255,255,0.08)!important;"><span class="fw-bold">المتبقي</span><span class="text-danger fw-bold">{{ number_format($invoice->remaining_amount, 2) }} {{ $invoice->currency ?? 'ج.م' }}</span></div>
            @if(isset($customerBalance) && $invoice->customer)
            <div class="d-flex justify-content-between mt-3 pt-2 border-top border-secondary" style="border-color:rgba(255,255,255,0.08)!important;">
                <span class="text-muted small">إجمالي حساب العميل الآن</span>
                <span class="fw-bold {{ $customerBalance > 0 ? 'text-danger' : 'text-success' }} small">
                    {{ $customerBalance > 0 ? number_format($customerBalance, 2) . ' عليه' : ($customerBalance < 0 ? number_format(abs($customerBalance), 2) . ' له' : 'خالص') }}
                </span>
            </div>
            @endif
        </div>

        <!-- Add Payment -->
        <div class="glass-card">
            <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>تسجيل دفعة (سند قبض)</h5>
            <form action="{{ route('payments.store', $invoice) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12"><label class="form-label text-muted">المبلغ <span class="text-danger">*</span></label><input type="number" step="0.01" name="amount" class="form-control form-control-glass" required></div>
                    <div class="col-12"><label class="form-label text-muted">التاريخ <span class="text-danger">*</span></label><input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="form-control form-control-glass" required></div>
                    <div class="col-12">
                        <label class="form-label text-muted">طريقة الدفع</label>
                        <select name="payment_method" id="payment_method" class="form-control form-control-glass" onchange="toggleTreasury()">
                            <option value="cash">كاش (نقدي)</option>
                            <option value="bank_transfer">تحويل بنكي / إلكتروني</option>
                            <option value="check">شيك</option>
                            <option value="deposit">خصم من الرصيد الدائن للعميل</option>
                        </select>
                    </div>
                    <div class="col-12" id="treasury_container">
                        <label class="form-label text-muted">إيداع في خزينة <span class="text-danger">*</span></label>
                        <select name="treasury_id" class="form-control form-control-glass">
                            @foreach($treasuries ?? [] as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label text-muted">رقم مرجعي</label><input type="text" name="reference_number" class="form-control form-control-glass"></div>
                    <div class="col-12"><label class="form-label text-muted">ملاحظات</label><input type="text" name="notes" class="form-control form-control-glass"></div>
                    <div class="col-12"><button type="submit" class="btn-glass w-100"><i class="bi bi-check-circle me-1"></i> تسجيل الدفعة</button></div>
                </div>
            </form>
            <script>
                function toggleTreasury() {
                    const method = document.getElementById('payment_method').value;
                    const container = document.getElementById('treasury_container');
                    if(method === 'deposit') {
                        container.style.display = 'none';
                    } else {
                        container.style.display = 'block';
                    }
                }
                document.addEventListener('DOMContentLoaded', toggleTreasury);
            </script>
        </div>
    </div>
</div>
@endsection
