<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير خزينة {{ $periodLabel }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:'Cairo',sans-serif; background:white; color:#1e293b; direction:rtl; font-size:13px; }
        .page { width:210mm; min-height:297mm; margin:0 auto; padding:12mm 10mm; }

        .report-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #ea580c; padding-bottom:12px; margin-bottom:16px; }
        .factory-info h1 { font-size:22px; font-weight:900; color:#ea580c; }
        .factory-info p { font-size:12px; color:#64748b; margin-top:3px; }
        .report-title { text-align:left; }
        .report-title h2 { font-size:15px; font-weight:700; }
        .date-badge { display:inline-block; background:#ea580c; color:white; font-weight:900; padding:4px 14px; border-radius:20px; font-size:13px; margin-top:5px; }

        /* Summary boxes */
        .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:18px; }
        .sbox { border:1.5px solid #e2e8f0; border-radius:12px; padding:12px; text-align:center; }
        .sbox label { display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; }
        .sbox .amt { font-size:20px; font-weight:900; direction:ltr; }
        .sbox.custody  { border-color:#ea580c; background:#fff7ed; }  .sbox.custody .amt  { color:#ea580c; }
        .sbox.incoming { border-color:#10b981; background:#f0fdf4; }  .sbox.incoming .amt { color:#059669; }
        .sbox.outgoing { border-color:#f43f5e; background:#fff1f2; }  .sbox.outgoing .amt { color:#e11d48; }
        .sbox.balance  { border-color:#6366f1; background:#eef2ff; }  .sbox.balance .amt  { color:#4338ca; }

        .section-title { font-size:13px; font-weight:800; color:#ea580c; border-right:4px solid #ea580c; padding-right:10px; margin:16px 0 10px; }

        table { width:100%; border-collapse:collapse; font-size:12px; }
        th { background:#ea580c; color:white; padding:7px 8px; font-weight:700; text-align:right; }
        td { padding:6px 8px; border-bottom:1px solid #f1f5f9; }
        tr:nth-child(even) td { background:#fafafa; }
        tfoot td { font-weight:900; background:#f8fafc; border-top:2px solid #ea580c; font-size:13px; }

        .badge-cat { display:inline-block; background:rgba(234,88,12,0.1); color:#ea580c; border:1px solid rgba(234,88,12,0.3); padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700; }

        .recon { margin-top:20px; border:2px solid #ea580c; border-radius:14px; overflow:hidden; }
        .recon-head { background:#ea580c; color:white; padding:10px 16px; font-weight:800; font-size:14px; }
        .recon-body { padding:14px 16px; }
        .recon-row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid #f1f5f9; }
        .recon-row:last-child { border:none; }
        .recon-row.total { background:#fff7ed; border-radius:8px; padding:10px; margin-top:6px; }
        .recon-row .lbl { font-weight:600; color:#475569; font-size:13px; }
        .recon-row .val { font-weight:900; direction:ltr; font-size:16px; color:#1e293b; }
        .recon-row.total .lbl { font-size:15px; font-weight:800; color:#9a3412; }
        .recon-row.total .val { font-size:20px; color:#ea580c; }

        .sigs { display:flex; gap:20px; margin-top:28px; }
        .sig-box { flex:1; border-top:2px solid #94a3b8; padding-top:10px; text-align:center; font-size:12px; font-weight:700; color:#64748b; }

        .print-footer { margin-top:18px; text-align:center; font-size:11px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:8px; }

        @media print {
            body { background:white!important; }
            .page { padding:6mm 8mm; }
            .no-print { display:none!important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center;padding:10px;background:#fff7ed;border-bottom:2px solid #ea580c;">
    <button onclick="window.print()" style="background:#ea580c;color:white;border:none;padding:8px 28px;border-radius:30px;font-family:Cairo;font-weight:700;font-size:15px;cursor:pointer;">🖨️ طباعة / PDF</button>
    <button onclick="window.close()" style="background:white;color:#ea580c;border:2px solid #ea580c;padding:8px 28px;border-radius:30px;font-family:Cairo;font-weight:700;font-size:15px;cursor:pointer;margin-right:10px;">✕ إغلاق</button>
</div>

<div class="page">
@php
    $settings = \App\Models\Setting::first();
    $custodyAmt = $todayCustody?->opening_custody ?? 0;
    $balance    = $summary['balance'] ?? 0;
@endphp

<!-- Header -->
<div class="report-header">
    <div class="factory-info">
        <h1>{{ $settings?->factory_name ?? 'مصنع المنتجات' }}</h1>
        @if($settings?->address)<p>📍 {{ $settings->address }}</p>@endif
        @if($settings?->phone)<p>📞 {{ $settings->phone }}</p>@endif
    </div>
    <div class="report-title">
        <h2>تقرير خزينة يومي — {{ $periodLabel }}</h2>
        <div class="date-badge">📅 تاريخ الطباعة: {{ now()->format('d/m/Y H:i') }}</div>
        @if($todayCustody)
        <div style="margin-top:5px;font-size:12px;color:#64748b;">
            👤 مندوب اليوم: <strong>{{ $todayCustody->created_by ?? 'غير محدد' }}</strong>
        </div>
        @endif
    </div>
</div>

<!-- Summary Grid -->
<div class="summary-grid">
    <div class="sbox custody">
        <label>💼 عهدة المندوب</label>
        <div class="amt">{{ number_format($custodyAmt,2) }}</div>
        <div style="font-size:11px;color:#9a3412;">بداية اليوم</div>
    </div>
    <div class="sbox incoming">
        <label>⬇️ وارد اليوم</label>
        <div class="amt">+{{ number_format($todayIn,2) }}</div>
    </div>
    <div class="sbox outgoing">
        <label>⬆️ منصرف اليوم</label>
        <div class="amt">-{{ number_format($todayOut,2) }}</div>
    </div>
    <div class="sbox balance">
        <label>✅ الرصيد الختامي</label>
        <div class="amt">{{ number_format($balance,2) }}</div>
    </div>
</div>

<!-- Expenses Table -->
@if($expenses->count() > 0)
<div class="section-title">📋 تفاصيل المصروفات التشغيلية</div>
<table>
    <thead>
        <tr>
            <th style="width:90px">التاريخ</th>
            <th>التصنيف</th>
            <th>الوصف / البيان</th>
            <th style="width:110px;text-align:center;">المبلغ (ج.م)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expenses as $e)
        <tr>
            <td>{{ $e->expense_date->format('d/m/Y') }}</td>
            <td><span class="badge-cat">{{ $e->category }}</span></td>
            <td>{{ $e->description ?: '—' }}</td>
            <td style="direction:ltr;text-align:center;font-weight:700;color:#ea580c;">-{{ number_format($e->amount,2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;color:#e11d48;">إجمالي المصروفات</td>
            <td style="direction:ltr;text-align:center;color:#e11d48;">-{{ number_format($total,2) }}</td>
        </tr>
    </tfoot>
</table>

<!-- Categories summary -->
@if($categories->count() > 1)
<div class="section-title">📊 توزيع المصروفات حسب التصنيف</div>
<table>
    <thead><tr><th>التصنيف</th><th style="text-align:center;">الإجمالي (ج.م)</th></tr></thead>
    <tbody>
        @foreach($categories as $cat=>$items)
        <tr>
            <td><span class="badge-cat">{{ $cat }}</span></td>
            <td style="direction:ltr;text-align:center;font-weight:700;color:#ea580c;">{{ number_format($items->sum('amount'),2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@else
<div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;font-weight:700;">لا توجد مصروفات في هذه الفترة.</div>
@endif

<!-- Reconciliation Box -->
<div class="recon">
    <div class="recon-head">📊 تسوية خزينة نهاية اليوم</div>
    <div class="recon-body">
        @if($custodyAmt > 0)
        <div class="recon-row">
            <span class="lbl">💼 العهدة الافتتاحية (مندوب المبيعات)</span>
            <span class="val">{{ number_format($custodyAmt,2) }} ج.م</span>
        </div>
        @endif
        <div class="recon-row">
            <span class="lbl">⬇️ إجمالي الوارد اليوم (تحصيلات)</span>
            <span class="val" style="color:#059669;">+ {{ number_format($todayIn,2) }} ج.م</span>
        </div>
        <div class="recon-row">
            <span class="lbl">⬆️ إجمالي المنصرف اليوم (مصروفات)</span>
            <span class="val" style="color:#e11d48;">– {{ number_format($todayOut,2) }} ج.م</span>
        </div>
        <div class="recon-row total">
            <span class="lbl">✅ الرصيد الختامي (المتبقي في الخزينة)</span>
            <span class="val">{{ number_format($balance,2) }} ج.م</span>
        </div>
    </div>
</div>

<!-- Signatures -->
<div class="sigs">
    <div class="sig-box">توقيع المندوب / المحاسب<br><br><br>{{ $todayCustody?->created_by ?? '.........................' }}</div>
    <div class="sig-box">توقيع أمين الخزينة<br><br><br>.........................</div>
    <div class="sig-box">توقيع المدير<br><br><br>.........................</div>
</div>

<div class="print-footer">
    تم الطباعة في: {{ now()->format('d/m/Y H:i') }} | {{ $settings?->factory_name ?? 'مصنع المنتجات' }}
</div>
</div>
</body>
</html>
