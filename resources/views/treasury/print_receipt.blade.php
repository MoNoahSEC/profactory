<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال {{ \App\Models\CashTransaction::isInType($transaction->type) ? 'استلام نقدية' : 'صرف نقدية' }}</title>
    <style>
        body {
            font-family: 'Cairo', Tahoma, sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
            color: #000;
        }
        .receipt {
            max-width: 400px; /* Thermal printer width approximation or small A4 segment */
            margin: 0 auto;
            border: 2px dashed #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            font-size: 14px;
        }
        .details {
            margin-bottom: 20px;
        }
        .details table {
            width: 100%;
        }
        .details th {
            text-align: right;
            padding: 5px 0;
            width: 30%;
        }
        .details td {
            text-align: right;
            padding: 5px 0;
        }
        .amount-box {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            border-top: 1px dotted #000;
            padding-top: 10px;
            margin-top: 20px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        .sig-box {
            text-align: center;
            width: 45%;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        @media print {
            body { padding: 0; }
            .receipt { border: none; max-width: 100%; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt">
        <div class="header">
            <h2>مصنع {{ env('APP_NAME', 'برو فاكتوري') }}</h2>
            <p>إدارة الحسابات والخزينة</p>
            <h3>{{ \App\Models\CashTransaction::isInType($transaction->type) ? 'إيصال استلام نقدية (قبض)' : 'إيصال صرف نقدية (دفع)' }}</h3>
        </div>

        <div class="amount-box">
            {{ number_format($transaction->amount, 2) }} ج.م
        </div>

        <div class="details">
            <table>
                <tr>
                    <th>رقم الإيصال:</th>
                    <td>#{{ str_pad($transaction->id, 6, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <th>التاريخ:</th>
                    <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y/m/d H:i') }}</td>
                </tr>
                <tr>
                    <th>البيان:</th>
                    <td>{{ $transaction->description }}</td>
                </tr>
                <tr>
                    <th>النوع:</th>
                    <td>{{ \App\Models\CashTransaction::isInType($transaction->type) ? 'إيداع / إيراد' : 'سحب / مصروف' }} ({{ \App\Models\CashTransaction::typeLabels()[$transaction->type] ?? $transaction->type }})</td>
                </tr>
                @if($transaction->reference_type)
                <tr>
                    <th>المرجع:</th>
                    <td>{{ class_basename($transaction->reference_type) }} #{{ $transaction->reference_id }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="signatures">
            <div class="sig-box">توقيع المستلم</div>
            <div class="sig-box">توقيع المسلّم</div>
        </div>

        <div class="footer">
            تمت الطباعة بواسطة النظام الآلي - {{ now()->format('Y/m/d H:i') }}
        </div>
    </div>
</body>
</html>
