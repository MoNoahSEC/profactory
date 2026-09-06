@extends('layouts.app')
@section('title', 'المالية والخزائن')
@section('page_title', 'المالية والخزائن')

@push('styles')
<style>
    /* Theme: White and Orange */
    :root {
        --primary: #ea580c; /* Orange-600 */
        --primary-light: #ffedd5; /* Orange-100 */
        --primary-dark: #c2410c; /* Orange-700 */
        --bg: #ffffff;
        --text: #1e293b;
    }

    body {
        background-color: #f8fafc;
    }

    .text-orange  { color: var(--primary) !important; }
    .bg-orange { background-color: var(--primary) !important; color: white !important; }
    .bg-orange-soft { background-color: var(--primary-light) !important; color: var(--primary-dark) !important; }
    .btn-orange   { background: var(--primary); color: white; border: none; font-weight: 700; transition: all 0.3s; }
    .btn-orange:hover { background: var(--primary-dark); color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(234,88,12,0.3); }
    .btn-outline-orange { background: white; color: var(--primary); border: 2px solid var(--primary); font-weight: 700; transition: all 0.3s; }
    .btn-outline-orange:hover { background: var(--primary); color: white; }
    .border-orange { border-color: var(--primary) !important; }

    /* Treasuries Grid */
    .treasury-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.5rem;
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .treasury-card:hover {
        border-color: var(--primary);
        box-shadow: 0 10px 25px rgba(234,88,12,0.15);
        transform: translateY(-4px);
    }
    .treasury-card.active {
        border-color: var(--primary);
        background: var(--primary);
        color: white;
        box-shadow: 0 10px 25px rgba(234,88,12,0.3);
    }
    .treasury-card.active .text-muted {
        color: rgba(255,255,255,0.8) !important;
    }
    .treasury-card.active .balance-amt {
        color: white !important;
    }
    .treasury-icon {
        font-size: 2.5rem;
        color: var(--primary);
        opacity: 0.2;
        position: absolute;
        bottom: -10px;
        left: -10px;
    }
    .treasury-card.active .treasury-icon {
        color: white;
        opacity: 0.3;
    }
    
    .balance-amt {
        font-size: 2rem;
        font-weight: 900;
        color: var(--primary);
        direction: ltr;
        text-align: right;
    }

    /* Action Tiles */
    .action-tile {
        border: 2px solid #e2e8f0; border-radius: 16px; padding: 1.2rem 1rem;
        text-align: center; cursor: pointer; transition: all 0.2s; background: white;
    }
    .action-tile:hover { border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 8px 20px rgba(234,88,12,0.15); }
    .action-tile .tile-icon { font-size: 2rem; color: var(--primary); display: block; margin-bottom: .5rem; }
    .action-tile .tile-label { font-weight: 700; font-size: .95rem; color: #1e293b; }

    /* Tabs */
    .finance-tabs .nav-link {
        color: #94a3b8; font-weight: 700; border: none;
        border-bottom: 3px solid transparent; padding: 1rem 1.5rem; transition: all .25s;
    }
    .finance-tabs .nav-link:hover { color: var(--primary); }
    .finance-tabs .nav-link.active { color: var(--primary); border-bottom: 3px solid var(--primary); background: white; }

    /* Tables */
    .table-custom th { background-color: var(--primary-light); color: var(--primary-dark); font-weight: 800; border: none; padding: 1rem; }
    .table-custom td { vertical-align: middle; padding: 1rem; border-bottom: 1px solid #f1f5f9; }
    .table-custom tr:hover td { background-color: #fafaf9; }
    
    .badge-in  { background: var(--primary-light); color: var(--primary-dark); border: 1px solid var(--primary); }
    .badge-out { background: white; color: var(--primary); border: 1px solid var(--primary); }

    .modal-content { border-radius: 20px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .modal-header { border-bottom: 2px solid var(--primary-light); }
    .form-control, .form-select { border-radius: 10px; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; }
    .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(234,88,12,0.25); }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert border-0 rounded-3 shadow-sm mb-4 fw-bold" style="background:var(--primary-light);color:var(--primary-dark);">
    <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert border-0 rounded-3 shadow-sm mb-4 fw-bold alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
</div>
@endif

<!-- ══════════════════════════════════════════ TREASURIES GRID ══════════════════════════════════════════ -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bolder m-0"><i class="bi bi-bank text-orange me-2"></i> الخزائن والحسابات</h4>
    <div>
        <button class="btn btn-outline-orange rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createTreasuryModal">
            <i class="bi bi-plus-circle me-1"></i> خزينة جديدة
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Global Total Card -->
    <div class="col-12">
        <div class="p-4 d-flex justify-content-between align-items-center rounded-4 shadow-lg mb-2" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid #334155; position: relative; overflow: hidden;">
            <div style="position: absolute; right: -20px; top: -40px; font-size: 10rem; opacity: 0.03; color: white;">
                <i class="bi bi-bank2"></i>
            </div>
            <div style="z-index: 1;">
                <h4 class="fw-bolder mb-1 text-orange"><i class="bi bi-wallet-fill me-2"></i> إجمالي السيولة بالشركة</h4>
                <p class="mb-0 text-light opacity-75" style="font-size: 1.1rem;">مجموع الأرصدة الحالية في جميع الحسابات والخزائن والعهد</p>
            </div>
            <div class="text-white" style="font-size: 3.2rem; font-weight: 900; letter-spacing: -1px; direction:ltr; text-shadow: 0 4px 15px rgba(0,0,0,0.5); z-index: 1;">
                {{ number_format($treasuries->sum('current_balance'), 2) }} <span class="text-orange" style="font-size: 1.5rem;">ج.م</span>
            </div>
        </div>
    </div>

    @foreach($treasuries as $t)
    <div class="col-md-4 col-sm-6">
        <div class="treasury-card {{ $selectedTreasuryId == $t->id || (!$selectedTreasuryId && $t->is_default) ? 'active' : '' }}">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1" onclick="window.location='{{ route('expenses.index', ['treasury_id' => $t->id, 'filter' => $filter]) }}'">
                    <i class="bi {{ $t->type == 'bank' ? 'bi-bank2' : ($t->type == 'wallet' ? 'bi-phone' : 'bi-safe2') }} treasury-icon"></i>
                    <h5 class="fw-bolder mb-1">{{ $t->name }}</h5>
                    <small class="text-muted d-block fw-bold mb-2">
                        @if($t->type == 'cash') نقدية @elseif($t->type == 'bank') حساب بنكي @elseif($t->type == 'wallet') محفظة إلكترونية @else عهدة @endif
                        @if($t->is_default) <span class="badge bg-white text-orange ms-1">الرئيسية</span> @endif
                    </small>
                    <div class="balance-amt">{{ number_format($t->current_balance, 2) }} <span style="font-size:1rem;">ج.م</span></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@php 
    $activeTreasury = $selectedTreasuryId ? $treasuries->firstWhere('id', $selectedTreasuryId) : $treasuries->firstWhere('is_default', true);
@endphp

@if($activeTreasury)
<!-- ══════════════════════════════════════════ QUICK ACTIONS ══════════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="action-tile" data-bs-toggle="modal" data-bs-target="#depositModal">
            <i class="bi bi-box-arrow-in-down tile-icon"></i>
            <div class="tile-label">إيداع وارد</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="action-tile" data-bs-toggle="modal" data-bs-target="#withdrawModal">
            <i class="bi bi-box-arrow-up tile-icon"></i>
            <div class="tile-label">سحب / مصروف</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="action-tile" data-bs-toggle="modal" data-bs-target="#transferModal">
            <i class="bi bi-arrow-left-right tile-icon"></i>
            <div class="tile-label">تحويل مالي</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="action-tile" data-bs-toggle="modal" data-bs-target="#printReportModal">
            <i class="bi bi-printer tile-icon"></i>
            <div class="tile-label">طباعة تقرير</div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════ DASHBOARD CONTENT ══════════════════════════════════════════ -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom pt-3 pb-0 px-4 rounded-top-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bolder m-0">تفاصيل الخزينة: <span class="text-orange">{{ $activeTreasury->name }}</span></h5>
            <div class="dropdown">
                <button class="btn btn-outline-orange rounded-pill fw-bold px-3 btn-sm" data-bs-toggle="dropdown">
                    <i class="bi bi-calendar-event me-1"></i>
                    @switch($filter)
                        @case('daily') اليوم @break
                        @case('weekly') هذا الأسبوع @break
                        @case('monthly') هذا الشهر @break
                        @case('yearly') هذا العام @break
                        @default الكل
                    @endswitch
                </button>
                <ul class="dropdown-menu shadow">
                    @foreach(['daily'=>'اليوم','weekly'=>'هذا الأسبوع','monthly'=>'هذا الشهر','yearly'=>'هذا العام','all'=>'الكل'] as $k=>$v)
                    <li><a class="dropdown-item fw-bold {{ $filter===$k?'text-orange':'' }}" href="{{ route('expenses.index',['filter'=>$k, 'treasury_id' => $activeTreasury->id]) }}">{{ $v }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <ul class="nav nav-tabs finance-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#transactions-tab" type="button">السجل المالي للخزينة</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#daily-history-tab" type="button">يومية المحاسب (ملخص)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#advances-tab" type="button">سلف عمال اليوم</button>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-0">
        <div class="tab-content" id="myTabContent">
            
            <!-- 1. All Transactions Tab -->
            <div class="tab-pane fade show active" id="transactions-tab">
                {{-- MOBILE VIEW --}}
                <div class="d-md-none mb-3">
                    @forelse($transactions as $tx)
                    <div class="p-3 mb-2 bg-white rounded-3 border shadow-sm">
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <div>
                                <span class="fw-bold text-dark d-block">#{{ $tx->id }}</span>
                                <small class="text-muted" style="font-size:0.75rem;">{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y') }}</small>
                            </div>
                            <div>
                                @if($tx->amount > 0)
                                    <span class="badge badge-in rounded-pill px-2">وارد</span>
                                @else
                                    <span class="badge badge-out rounded-pill px-2">منصرف</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-2 fw-bold text-dark" style="font-size:0.9rem;">
                            {{ $tx->description }}
                            @if($tx->reference_type)
                                <small class="text-orange d-block mt-1"><i class="bi bi-link-45deg"></i> مرتبط بمستند</small>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:0.85rem;">
                            <div class="text-muted">المبلغ: <span class="fw-bolder {{ $tx->amount > 0 ? 'text-success' : 'text-danger' }}" style="direction:ltr;">{{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}</span></div>
                            <div class="text-muted">الرصيد: <span class="fw-bolder text-dark" style="direction:ltr;">{{ number_format($tx->balance_after, 2) }}</span></div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted fw-bold bg-white rounded-3 border">لا توجد حركات مالية في هذه الخزينة.</div>
                    @endforelse
                </div>

                {{-- DESKTOP VIEW --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>رقم الحركة</th>
                                <th>التاريخ</th>
                                <th>البيان / الوصف</th>
                                <th class="text-center">النوع</th>
                                <th class="text-center">المبلغ</th>
                                <th class="text-center">الرصيد بعد الحركة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                            <tr>
                                <td class="fw-bold text-muted">#{{ $tx->id }}</td>
                                <td class="fw-bold">{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y') }}</td>
                                <td>
                                    <div class="fw-bold">{{ $tx->description }}</div>
                                    @if($tx->reference_type)
                                        <small class="text-orange"><i class="bi bi-link-45deg"></i> مرتبط بمستند</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($tx->amount > 0)
                                        <span class="badge badge-in rounded-pill px-3 py-2"><i class="bi bi-arrow-down-left"></i> وارد</span>
                                    @else
                                        <span class="badge badge-out rounded-pill px-3 py-2"><i class="bi bi-arrow-up-right"></i> منصرف</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bolder {{ $tx->amount > 0 ? 'text-success' : 'text-danger' }}" style="direction:ltr;">
                                    {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                                </td>
                                <td class="text-center fw-bolder" style="direction:ltr;">{{ number_format($tx->balance_after, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">لا توجد حركات مالية في هذه الخزينة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $transactions->links() }}</div>
            </div>

            <!-- 2. Today's Ledger (تسليم عهدة اليوم) Tab -->
            <div class="tab-pane fade" id="daily-history-tab">
                <div class="p-4 bg-light rounded-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bolder m-0"><i class="bi bi-journal-check text-orange me-2"></i> تسليم عهدة اليوم ({{ \Carbon\Carbon::now()->format('Y-m-d') }})</h5>
                        <button onclick="window.print()" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                            <i class="bi bi-printer"></i> طباعة كشف اليومية
                        </button>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm bg-white h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-muted fw-bold mb-2">رصيد أول اليوم</h6>
                                    <h4 class="text-dark fw-bolder mb-0" style="direction:ltr;">{{ number_format($todayLedger['opening_balance'], 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-success fw-bold mb-2">إجمالي الدخول (وارد)</h6>
                                    <h4 class="text-success fw-bolder mb-0" style="direction:ltr;">+ {{ number_format($todayLedger['total_in'], 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100">
                                <div class="card-body text-center">
                                    <h6 class="text-danger fw-bold mb-2">إجمالي الخروج (منصرف)</h6>
                                    <h4 class="text-danger fw-bolder mb-0" style="direction:ltr;">- {{ number_format($todayLedger['total_out'], 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm bg-orange-soft h-100 border-start border-4 border-orange">
                                <div class="card-body text-center">
                                    <h6 class="text-orange fw-bold mb-2">رصيد آخر اليوم (المطلوب)</h6>
                                    <h4 class="text-orange fw-bolder mb-0" style="direction:ltr;">{{ number_format($todayLedger['closing_balance'], 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bolder mb-3 text-muted">تفاصيل حركات اليوم:</h6>
                    
                    {{-- MOBILE VIEW --}}
                    <div class="d-md-none">
                        @forelse($todayLedger['transactions'] as $tx)
                        <div class="p-3 mb-2 bg-white rounded-3 border shadow-sm">
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <small class="text-muted fw-bold">{{ \Carbon\Carbon::parse($tx->created_at)->format('H:i A') }}</small>
                                <div>
                                    @if($tx->amount > 0)
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">وارد</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2">منصرف</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-2 fw-bold text-dark" style="font-size:0.9rem;">{{ $tx->description }}</div>
                            <div class="d-flex justify-content-between" style="font-size:0.85rem;">
                                <div class="text-muted">المبلغ: <span class="fw-bolder {{ $tx->amount > 0 ? 'text-success' : 'text-danger' }}" style="direction:ltr;">{{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}</span></div>
                                <div class="text-muted">الرصيد: <span class="fw-bolder text-dark" style="direction:ltr;">{{ number_format($tx->balance_after, 2) }}</span></div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted fw-bold bg-white rounded-3 border">لم تتم أي حركات على هذه الخزينة اليوم.</div>
                        @endforelse
                    </div>

                    {{-- DESKTOP VIEW --}}
                    <div class="table-responsive border rounded-3 bg-white d-none d-md-block">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>الوقت</th>
                                    <th>البيان</th>
                                    <th class="text-center">النوع</th>
                                    <th class="text-center">المبلغ</th>
                                    <th class="text-center">الرصيد بعدها</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todayLedger['transactions'] as $tx)
                                <tr>
                                    <td class="text-muted fw-bold">{{ \Carbon\Carbon::parse($tx->created_at)->format('H:i A') }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $tx->description }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($tx->amount > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">وارد</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1">منصرف</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bolder {{ $tx->amount > 0 ? 'text-success' : 'text-danger' }}" style="direction:ltr;">
                                        {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                                    </td>
                                    <td class="text-center fw-bolder" style="direction:ltr;">{{ number_format($tx->balance_after, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted fw-bold">لم تتم أي حركات على هذه الخزينة اليوم.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. Worker Advances Management Tab -->
            <div class="tab-pane fade" id="advances-tab">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="fw-bolder m-0"><i class="bi bi-person-bounding-box text-orange me-2"></i> إدارة سلف العمال</h5>
                            <small class="text-muted">إجمالي السلف غير المسددة: <strong class="text-danger">{{ number_format($totalPendingAdvances, 2) }} ج.م</strong></small>
                        </div>
                        <button class="btn btn-orange rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addAdvanceModal">
                            <i class="bi bi-plus-circle me-1"></i> صرف سلفة جديدة
                        </button>
                    </div>

                    <!-- Filter Form -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body bg-light rounded">
                            <form action="{{ route('expenses.index') }}" method="GET" class="row g-2 align-items-center">
                                <input type="hidden" name="treasury_id" value="{{ $activeTreasury->id }}">
                                <input type="hidden" name="filter" value="{{ $filter }}">
                                <div class="col-md-8">
                                    <select name="worker_id" class="form-select border-2">
                                        <option value="">-- عرض جميع العمال --</option>
                                        @foreach($workersWithAdvances as $w)
                                            <option value="{{ $w->id }}" {{ $selectedWorkerId == $w->id ? 'selected' : '' }}>
                                                {{ $w->name }} 
                                                @if($w->pending_advance_balance > 0)
                                                (عليه: {{ number_format($w->pending_advance_balance) }} ج.م)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-dark w-100 fw-bold">فلترة</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Advances Table -->
                    {{-- MOBILE VIEW --}}
                    <div class="d-md-none">
                        @forelse($allAdvances as $adv)
                        <div class="p-3 mb-2 bg-white rounded-3 border shadow-sm">
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <a href="{{ route('workers.statement', $adv->worker_id) }}" class="fw-bold text-dark text-decoration-none">
                                    <i class="bi bi-person text-orange me-1"></i>{{ $adv->worker->name }}
                                </a>
                                <span class="text-muted" style="font-size:0.75rem;">{{ \Carbon\Carbon::parse($adv->date)->format('Y-m-d') }}</span>
                            </div>
                            <div class="fw-bold text-danger mb-1" style="font-size:1.1rem;">{{ number_format($adv->amount, 2) }} ج.م</div>
                            @if($adv->notes)
                                <div class="text-muted" style="font-size:0.85rem;">{{ $adv->notes }}</div>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted fw-bold bg-white rounded-3 border">لا يوجد سلف غير مسددة.</div>
                        @endforelse
                    </div>

                    {{-- DESKTOP VIEW --}}
                    <div class="table-responsive border rounded-3 bg-white d-none d-md-block">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>العامل</th>
                                    <th>المبلغ</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allAdvances as $adv)
                                <tr>
                                    <td class="fw-bold">{{ \Carbon\Carbon::parse($adv->date)->format('Y-m-d') }}</td>
                                    <td class="fw-bold">
                                        <a href="{{ route('workers.statement', $adv->worker_id) }}" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-orange-soft" style="width:32px;height:32px;font-size:.9rem;">
                                                <i class="bi bi-person text-orange"></i>
                                            </span>
                                            {{ $adv->worker->name }}
                                        </a>
                                    </td>
                                    <td class="fw-bold text-danger">{{ number_format($adv->amount, 2) }} ج.م</td>
                                    <td>{{ $adv->notes ?: '—' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-5 text-muted fw-bold">لا يوجد سلف غير مسددة.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endif

<!-- ══════════════════════════════════════════ MODALS ══════════════════════════════════════════ -->

<!-- Modal: Create Treasury -->
<div class="modal fade" id="createTreasuryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bolder">إنشاء خزينة / حساب جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('treasury.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم الخزينة/الحساب</label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: حساب انستا باي مصنع 1، خزينة المحاسب..." required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">النوع</label>
                            <select name="type" class="form-select" required>
                                <option value="cash">نقدية (كاش)</option>
                                <option value="bank">حساب بنكي</option>
                                <option value="wallet">محفظة إلكترونية</option>
                                <option value="custody">عهدة محاسب</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الرصيد الافتتاحي</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="initial_balance" class="form-control" value="0" required>
                                <span class="input-group-text bg-light text-orange fw-bold">ج.م</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange px-4">حفظ وإنشاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($activeTreasury)
<!-- Modal: Deposit -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bolder text-success"><i class="bi bi-box-arrow-in-down me-2"></i> إيداع نقدي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('treasury.deposit') }}" method="POST">
                @csrf
                <input type="hidden" name="treasury_id" value="{{ $activeTreasury->id }}">
                <div class="modal-body">
                    <div class="alert alert-info border-0 rounded-3 text-center fw-bold bg-orange-soft mb-4">
                        سيتم إضافة المبلغ إلى: {{ $activeTreasury->name }}
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">المبلغ</label>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-lg text-success fw-bold" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">التاريخ</label>
                            <input type="date" name="transaction_date" class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">البيان / الوصف</label>
                        <input type="text" name="description" class="form-control" required placeholder="مثال: تحصيل من عميل، زيادة رأس مال...">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">تأكيد الإيداع</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Withdraw / Expense -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white" style="border-radius: 18px 18px 0 0;">
                <h5 class="modal-title fw-bolder"><i class="bi bi-exclamation-triangle-fill me-2"></i> صرف / سحب نقدي (مصروفات)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <input type="hidden" name="treasury_id" value="{{ $activeTreasury->id }}">
                <div class="modal-body p-4">
                    <div class="alert mb-4 text-center fw-bold shadow-sm" style="background-color: rgba(220,53,69,0.05); border: 2px dashed #dc3545; color: #dc3545; border-radius: 12px;">
                        <i class="bi bi-shield-exclamation fs-3 d-block mb-2"></i>
                        <span style="font-size: 1.1rem;">أنت تقوم الآن بسحب مبلغ وتسجيله كـ (مصروف) من:</span><br>
                        <span class="fs-3 fw-black text-dark mt-1 d-block">{{ $activeTreasury->name }}</span>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-danger">تصنيف المصروف <span class="text-dark">*</span></label>
                        <input class="form-control form-control-lg border-danger" list="categoryOptions" name="category" placeholder="اختر من القائمة أو اكتب بند جديد..." required>
                        <datalist id="categoryOptions">
                            @foreach($allCategories as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                            <option value="مسحوبات شخصية">
                            <option value="سحب نقدي عام">
                            <option value="كهرباء ومياه">
                            <option value="بنزين ومواصلات">
                            <option value="بوفيه وضيافة">
                            <option value="إيجار">
                            <option value="مصروفات تشغيل">
                        </datalist>
                        <div class="form-text text-muted fw-bold">هذا التصنيف سيظهر في تقارير المصروفات والحسابات الختامية.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">المبلغ المسحوب <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-lg text-danger fw-bolder" required style="font-size: 1.5rem; text-align: left; direction: ltr;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">التاريخ <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold">وصف إضافي (اختياري)</label>
                        <textarea name="description" class="form-control bg-light" rows="2" placeholder="أضف تفاصيل أكثر عن سبب السحب..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0" style="border-radius: 0 0 18px 18px;">
                    <button type="button" class="btn btn-secondary fw-bold px-4 rounded-pill" data-bs-dismiss="modal">إلغاء الأمر</button>
                    <button type="submit" class="btn btn-danger fw-bold px-5 rounded-pill shadow-sm"><i class="bi bi-check2-circle me-1"></i> تأكيد خصم المبلغ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Transfer -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bolder"><i class="bi bi-arrow-left-right me-2 text-orange"></i> تحويل مالي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('treasury.transfer') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">من خزينة (صادر)</label>
                            <select name="from_treasury_id" class="form-select form-select-lg" required>
                                @foreach($treasuries as $t)
                                    <option value="{{ $t->id }}" {{ $activeTreasury->id == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->current_balance }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">إلى خزينة (وارد)</label>
                            <select name="to_treasury_id" class="form-select form-select-lg" required>
                                <option value="">-- اختر الخزينة المستلمة --</option>
                                @foreach($treasuries as $t)
                                    @if($activeTreasury->id != $t->id)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">المبلغ</label>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-lg fw-bold" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">التاريخ</label>
                            <input type="date" name="date" class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات التحويل</label>
                        <input type="text" name="note" class="form-control">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange fw-bold px-4">تنفيذ التحويل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Print Options -->
<div class="modal fade" id="printReportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bolder"><i class="bi bi-printer text-orange me-2"></i> طباعة تقرير الخزينة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="fw-bold mb-4">اختر التقرير المراد طباعته لـ: <span class="text-orange">{{ $activeTreasury->name }}</span></p>
                
                <a href="{{ route('expenses.printReport', ['filter' => 'daily', 'treasury_id' => $activeTreasury->id]) }}" target="_blank" class="btn btn-outline-orange btn-lg w-100 mb-3 fw-bold rounded-pill">
                    <i class="bi bi-file-earmark-text me-2"></i> تقرير اليوم
                </a>
                
                <a href="{{ route('expenses.printReport', ['filter' => 'monthly', 'treasury_id' => $activeTreasury->id]) }}" target="_blank" class="btn btn-outline-orange btn-lg w-100 mb-3 fw-bold rounded-pill">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i> تقرير الشهر الحالي
                </a>
                
                <a href="{{ route('treasury.printLedger', ['treasury_id' => $activeTreasury->id]) }}" target="_blank" class="btn btn-orange btn-lg w-100 fw-bold rounded-pill">
                    <i class="bi bi-journal-text me-2"></i> طباعة كشف حركة كامل
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Modal: Add Advance -->
<div class="modal fade" id="addAdvanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bolder text-orange"><i class="bi bi-person-dash me-2"></i> صرف سلفة لعامل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('advances.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info border-0 rounded-3 text-center fw-bold bg-orange-soft mb-4">
                        سيتم خصم السلفة من: {{ $activeTreasury->name ?? 'الخزينة المحددة' }}
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">اختر العامل</label>
                        <select name="worker_id" class="form-select select2" required>
                            <option value="">-- اختر العامل --</option>
                            @foreach($activeWorkers as $worker)
                                <option value="{{ $worker->id }}">{{ $worker->name }} (الرصيد: {{ number_format($worker->pending_advance_balance) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hidden Treasury ID -->
                    <input type="hidden" name="treasury_id" value="{{ $activeTreasury->id ?? '' }}">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">المبلغ</label>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-lg text-danger fw-bold" required min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">التاريخ</label>
                            <input type="date" name="date" class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-orange fw-bold px-4">صرف السلفة</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
