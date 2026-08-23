<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background: #f2f2f2;
            font-weight: bold;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        .summary-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        .summary-item .label {
            font-size: 11px;
            color: #666;
        }
        .summary-item .value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 20px;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>فنانة فون</h1>
        <p>إدارة معرض الهواتف</p>
        <p>هاتف: 0599-123456</p>
        <h2>{{ $title }}</h2>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <div class="filters">
        <span><strong>الفترة:</strong> {{ $filters['start_date'] ?? 'بداية' }} - {{ $filters['end_date'] ?? 'حتى اليوم' }}</span>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="label">إجمالي المبيعات</div>
            <div class="value">{{ number_format($data['summary']['total_sales'] ?? 0, 2) }} شيكل</div>
        </div>
        <div class="summary-item">
            <div class="label">صافي المبيعات</div>
            <div class="value">{{ number_format($data['summary']['net_sales'] ?? 0, 2) }} شيكل</div>
        </div>
        <div class="summary-item">
            <div class="label">إجمالي الأرباح</div>
            <div class="value">{{ number_format($data['summary']['total_profit'] ?? 0, 2) }} شيكل</div>
        </div>
    </div>

    @if(!empty($data['top_products']) && count($data['top_products']) > 0)
    <h3 style="margin-top: 30px;">المنتجات الأكثر مبيعاً</h3>
    <table>
        <thead>
            <tr>
                <th>المنتج</th>
                <th>الكمية</th>
                <th>الإيراد</th>
                <th>الربح</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['top_products'] as $product)
            <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->total_quantity }}</td>
                <td>{{ number_format($product->total_revenue, 2) }}</td>
                <td>{{ number_format($product->total_profit, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام فنانة فون</p>
        <p>جميع الحقوق محفوظة © {{ date('Y') }}</p>
    </div>
</body>
</html>
