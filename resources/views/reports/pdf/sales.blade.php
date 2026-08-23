<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }}</title>

    <style>
        @page {
            margin: 9mm 9mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;

            direction: rtl;
            text-align: right;

            font-family: dejavusans, sans-serif;
            font-size: 8.8pt;
            line-height: 1.55;

            color: #172033;
            background: #ffffff;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: embed;
            text-align: left;
        }

        .money {
            direction: ltr;
            unicode-bidi: embed;
            white-space: nowrap;
            text-align: left;
            font-weight: 700;
        }

        /*
         * رأس التقرير
         */

        .report-cover {
            width: 100%;

            border-collapse: separate;
            border-spacing: 0;

            overflow: hidden;

            background: #0f172a;
            border-radius: 4mm;
        }

        .report-cover td {
            border: 0;
            vertical-align: middle;
        }

        .brand-side {
            width: 67%;
            padding: 7mm;
        }

        .report-side {
            width: 33%;
            padding: 7mm;

            background: #0F0F75;
            color: #ffffff;
        }

        .brand-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-table td {
            border: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 22mm;
            padding-left: 4mm;
        }

        .logo-box {
            width: 18mm;
            height: 18mm;
            padding: 2mm;

            text-align: center;

            background: #ffffff;
            border-radius: 4mm;
        }

        .logo {
            max-width: 14mm;
            max-height: 14mm;
        }

        .logo-fallback {
            display: inline-block;

            width: 14mm;
            height: 14mm;
            line-height: 14mm;

            background: #0F0F75;
            color: #ffffff;

            border-radius: 3mm;

            font-size: 16pt;
            font-weight: 700;
            text-align: center;
        }

        .store-name {
            color: #ffffff;
            font-size: 16pt;
            font-weight: 700;
        }

        .store-description {
            margin-top: 1.3mm;

            color: #94a3b8;
            font-size: 8pt;
        }

        .store-contact {
            margin-top: 2.4mm;

            color: #cbd5e1;
            font-size: 7.4pt;
        }

        .report-kicker {
            color: #c7d2fe;
            font-size: 7.5pt;
        }

        .report-title {
            margin-top: 2mm;

            color: #ffffff;

            font-size: 18pt;
            line-height: 1.3;
            font-weight: 700;
        }

        .report-date {
            margin-top: 5mm;

            color: #e0e7ff;
            font-size: 8pt;
        }

        .accent-line {
            height: 2mm;

            margin: 0 4mm 5mm;

            background: #14b8a6;

            border-radius: 0 0 2mm 2mm;
        }

        /*
         * معلومات التقرير
         */

        .report-info {
            width: 100%;

            margin-bottom: 5mm;

            border-collapse: separate;
            border-spacing: 1.8mm 0;

            table-layout: fixed;
        }

        .report-info td {
            padding: 3mm;

            border: 1px solid #e2e8f0;

            background: #f8fafc;
            border-radius: 2.5mm;

            vertical-align: top;
        }

        .info-label {
            color: #64748b;
            font-size: 7.1pt;
        }

        .info-value {
            margin-top: 1mm;

            color: #0f172a;

            font-size: 8.3pt;
            font-weight: 700;
        }

        /*
         * عناوين الأقسام
         */

        .section-heading {
            width: 100%;

            margin: 6mm 0 2.5mm;

            border-collapse: collapse;

            page-break-after: avoid;
        }

        .section-heading td {
            border: 0;
            vertical-align: middle;
        }

        .section-number {
            width: 10mm;
            height: 10mm;
            line-height: 10mm;

            text-align: center;

            background: #0F0F75;
            color: #ffffff;

            border-radius: 3mm;

            font-size: 9pt;
            font-weight: 700;
        }

        .section-title-cell {
            padding-right: 3mm;
        }

        .section-title {
            color: #0f172a;

            font-size: 12pt;
            font-weight: 700;
        }

        .section-subtitle {
            margin-top: 0.8mm;

            color: #64748b;
            font-size: 7.4pt;
        }

        .section-badge {
            width: 34mm;

            padding: 1.7mm 2.5mm;

            color: #0F0F75;
            background: #eef2ff;

            border: 1px solid #c7d2fe;
            border-radius: 6mm;

            text-align: center;

            font-size: 7.2pt;
            font-weight: 700;
        }

        /*
         * بطاقات المؤشرات
         */

        .summary-table {
            width: 100%;

            border-collapse: separate;
            border-spacing: 1.8mm;

            table-layout: fixed;
        }

        .summary-card {
            padding: 3.5mm;

            border: 1px solid #e2e8f0;
            border-top-width: 1.3mm;

            background: #ffffff;
            border-radius: 3mm;

            vertical-align: top;
        }

        .summary-label {
            color: #64748b;
            font-size: 7.3pt;
        }

        .summary-value {
            margin-top: 2mm;

            color: #0f172a;

            font-size: 12pt;
            font-weight: 700;
        }

        .summary-note {
            margin-top: 1.5mm;

            color: #94a3b8;
            font-size: 6.7pt;
        }

        /*
         * بطاقة الفاتورة
         */

        .invoice-card {
            margin-bottom: 5mm;

            border: 1px solid #dce4ee;

            background: #ffffff;
            border-radius: 3.5mm;

            page-break-inside: avoid;
        }

        .invoice-header {
            width: 100%;

            border-collapse: collapse;

            background: #f8fafc;
        }

        .invoice-header td {
            padding: 3mm;

            border: 0;
            border-bottom: 1px solid #e2e8f0;

            vertical-align: middle;
        }

        .invoice-number {
            color: #0F0F75;

            font-size: 10.5pt;
            font-weight: 700;
        }

        .invoice-customer {
            margin-top: 1mm;

            color: #0f172a;

            font-size: 8.4pt;
            font-weight: 700;
        }

        .invoice-phone {
            margin-top: 0.7mm;

            color: #64748b;
            font-size: 7.2pt;
        }

        .invoice-status {
            display: inline-block;

            padding: 1.3mm 2.6mm;

            border-radius: 5mm;

            background: #ecfdf5;
            color: #047857;

            border: 1px solid #a7f3d0;

            font-size: 7pt;
            font-weight: 700;
        }

        .invoice-meta {
            width: 100%;

            border-collapse: collapse;
            table-layout: fixed;
        }

        .invoice-meta td {
            padding: 2.5mm 3mm;

            border: 0;
            border-bottom: 1px solid #edf2f7;

            background: #ffffff;

            vertical-align: top;
        }

        .meta-label {
            color: #94a3b8;
            font-size: 6.8pt;
        }

        .meta-value {
            margin-top: 0.8mm;

            color: #334155;

            font-size: 7.7pt;
            font-weight: 700;
        }

        /*
         * جدول المنتجات
         */

        .items-table {
            width: 100%;

            border-collapse: collapse;
            table-layout: auto;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table th,
        .items-table td {
            padding: 2.3mm 2.5mm;

            border-bottom: 1px solid #e2e8f0;

            text-align: right;
            vertical-align: middle;

            word-wrap: break-word;
        }

        .items-table th {
            color: #ffffff;
            background: #172033;

            font-size: 7.2pt;
            font-weight: 700;
        }

        .items-table td {
            color: #334155;
            background: #ffffff;

            font-size: 7.5pt;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .product-name {
            color: #0f172a;
            font-weight: 700;
        }

        .product-code {
            margin-top: 0.6mm;

            color: #94a3b8;
            font-size: 6.5pt;
        }

        /*
         * ملخص الفاتورة
         */

        .invoice-footer {
            width: 100%;

            border-collapse: collapse;
            table-layout: fixed;

            background: #f8fafc;
        }

        .invoice-footer td {
            padding: 2.7mm 3mm;

            border: 0;
            border-top: 1px solid #e2e8f0;

            vertical-align: middle;
        }

        .invoice-total-label {
            color: #64748b;
            font-size: 7pt;
        }

        .invoice-total-value {
            margin-top: 0.8mm;

            color: #0f172a;
            font-size: 8.5pt;
            font-weight: 700;
        }

        .invoice-grand-total {
            color: #0F0F75;

            font-size: 11pt;
            font-weight: 700;
        }

        /*
         * الدفعات
         */

        .payments-box {
            margin: 2.5mm 3mm 3mm;
            padding: 2.5mm 3mm;

            background: #f0fdfa;
            border: 1px solid #99f6e4;

            border-radius: 2.5mm;
        }

        .payments-title {
            color: #0f766e;

            font-size: 7.5pt;
            font-weight: 700;
        }

        .payment-line {
            margin-top: 1.5mm;

            color: #334155;
            font-size: 7.1pt;
        }

        /*
         * الجداول التحليلية
         */

        .analysis-grid {
            width: 100%;

            border-collapse: separate;
            border-spacing: 2mm;

            table-layout: fixed;
        }

        .analysis-box {
            padding: 3mm;

            border: 1px solid #e2e8f0;

            background: #ffffff;
            border-radius: 3mm;

            vertical-align: top;
        }

        .analysis-title {
            margin-bottom: 2mm;

            color: #0F0F75;

            font-size: 9pt;
            font-weight: 700;
        }

        .analysis-table {
            width: 100%;
            border-collapse: collapse;
        }

        .analysis-table th,
        .analysis-table td {
            padding: 2mm;

            border-bottom: 1px solid #e2e8f0;

            text-align: right;
            font-size: 7pt;
        }

        .analysis-table th {
            color: #64748b;
            background: #f8fafc;
        }

        .analysis-table tr:last-child td {
            border-bottom: 0;
        }

        /*
         * المرتجعات
         */

        .return-table {
            width: 100%;

            border-collapse: collapse;
        }

        .return-table th,
        .return-table td {
            padding: 2.4mm;

            border: 1px solid #e2e8f0;

            text-align: right;
            font-size: 7.3pt;
        }

        .return-table th {
            color: #ffffff;
            background: #be123c;
        }

        .return-table tr:nth-child(even) td {
            background: #fff1f2;
        }

        /*
         * الحالة الفارغة
         */

        .empty-state {
            padding: 10mm;

            border: 1px dashed #cbd5e1;
            background: #f8fafc;

            border-radius: 4mm;

            color: #64748b;
            text-align: center;
        }

        .closing-note {
            margin-top: 7mm;

            padding: 3mm 4mm;

            border-right: 1.2mm solid #14b8a6;

            background: #f0fdfa;
            color: #475569;

            font-size: 7.2pt;
        }
    </style>
</head>

<body>
@php
    $reportData = $raw_data ?? [];
    $invoices = collect($reportData['invoices'] ?? []);
    $returns = collect($reportData['returns'] ?? []);
    $topProducts = collect($reportData['top_products'] ?? []);
    $salesByCategory = collect($reportData['sales_by_category'] ?? []);
    $paymentsByMethod = collect($reportData['payments_by_method'] ?? []);
    $reportSummary = $reportData['summary'] ?? [];

    $periodLabels = [
        'today' => 'اليوم',
        'yesterday' => 'أمس',
        'last_7_days' => 'آخر 7 أيام',
        'this_week' => 'هذا الأسبوع',
        'this_month' => 'هذا الشهر',
        'last_month' => 'الشهر السابق',
        'this_year' => 'هذه السنة',
        'custom' => 'فترة مخصصة',
    ];

    $periodText = $periodLabels[$filters['period'] ?? '']
        ?? 'جميع البيانات';

    if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
        $periodText .= ' من '
            .($filters['start_date'] ?? 'البداية')
            .' إلى '
            .($filters['end_date'] ?? 'اليوم');
    }

    $storeDetails = array_values(array_filter([
        $store['store_phone'] ?? null,
        $store['store_email'] ?? null,
        $store['store_address'] ?? null,
    ]));

    $money = fn ($value) =>
        number_format((float) $value, 2).' شيكل';

    $customerName = fn ($invoice) =>
        $invoice->customer?->name
        ?: $invoice->customer_name
        ?: 'عميل نقدي';

    $paymentStatus = function ($invoice) {
        if (
            is_object($invoice->payment_status)
            && method_exists(
                $invoice->payment_status,
                'label'
            )
        ) {
            return $invoice->payment_status->label();
        }

        return match ((string) $invoice->payment_status) {
            'paid' => 'مدفوعة بالكامل',
            'partially_paid' => 'مدفوعة جزئياً',
            'unpaid' => 'غير مدفوعة',
            default => (string) $invoice->payment_status,
        };
    };

    $paymentMethod = function ($value) {
        if (
            is_object($value)
            && method_exists($value, 'label')
        ) {
            return $value->label();
        }

        return match ((string) $value) {
            'cash' => 'كاش',
            'bank_transfer' => 'تحويل بنكي',
            'banking_app' => 'تطبيق بنكي',
            default => (string) $value,
        };
    };
@endphp

<table class="report-cover">
    <tr>
        <td class="brand-side">
            <table class="brand-table">
                <tr>
                    <td class="logo-cell">
                        <div class="logo-box">
                            @if($logo_data_uri)
                                <img
                                    class="logo"
                                    src="{{ $logo_data_uri }}"
                                    alt="{{ $store['store_name'] ?? 'فنانة فون' }}"
                                >
                            @else
                                <span class="logo-fallback">
                                    ف
                                </span>
                            @endif
                        </div>
                    </td>

                    <td>
                        <div class="store-name">
                            {{ $store['store_name'] ?? 'فنانة فون' }}
                        </div>

                        <div class="store-description">
                            نظام إدارة المبيعات والمخزون والصيانة
                        </div>

                        @if($storeDetails)
                            <div class="store-contact">
                                {{ implode(' | ', $storeDetails) }}
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>

        <td class="report-side">
            <div class="report-kicker">
                تقرير إداري ومالي
            </div>

            <div class="report-title">
                تقرير المبيعات والأرباح
            </div>

            <div class="report-date">
                تاريخ الإصدار:
                <span class="ltr">
                    {{ $generated_at }}
                </span>
            </div>
        </td>
    </tr>
</table>

<div class="accent-line"></div>

<table class="report-info">
    <tr>
        <td style="width: 36%;">
            <div class="info-label">
                الفترة المشمولة
            </div>

            <div class="info-value">
                {{ $periodText }}
            </div>
        </td>

        <td style="width: 22%;">
            <div class="info-label">
                عدد الفواتير
            </div>

            <div class="info-value">
                {{ $invoices->count() }} فاتورة
            </div>
        </td>

        <td style="width: 22%;">
            <div class="info-label">
                عدد المنتجات المباعة
            </div>

            <div class="info-value">
                {{ $invoices->sum(
                    fn ($invoice) =>
                        $invoice->items->sum('quantity')
                ) }} قطعة
            </div>
        </td>

        <td style="width: 20%;">
            <div class="info-label">
                العملة
            </div>

            <div class="info-value">
                الشيكل
            </div>
        </td>
    </tr>
</table>

<table class="section-heading">
    <tr>
        <td class="section-number">1</td>

        <td class="section-title-cell">
            <div class="section-title">
                الملخص التنفيذي
            </div>

            <div class="section-subtitle">
                أهم النتائج المالية خلال الفترة المحددة
            </div>
        </td>

        <td class="section-badge">
            {{ $invoices->count() }} فاتورة معتمدة
        </td>
    </tr>
</table>

<table class="summary-table">
    <tr>
        <td
            class="summary-card"
            style="width: 25%; border-top-color: #0F0F75;"
        >
            <div class="summary-label">
                إجمالي المبيعات
            </div>

            <div class="summary-value">
                {{ $money(
                    $reportSummary['total_sales'] ?? 0
                ) }}
            </div>

            <div class="summary-note">
                قبل خصم المرتجعات
            </div>
        </td>

        <td
            class="summary-card"
            style="width: 25%; border-top-color: #14b8a6;"
        >
            <div class="summary-label">
                صافي المبيعات
            </div>

            <div class="summary-value">
                {{ $money(
                    $reportSummary['net_sales'] ?? 0
                ) }}
            </div>

            <div class="summary-note">
                بعد المرتجعات
            </div>
        </td>

        <td
            class="summary-card"
            style="width: 25%; border-top-color: #10b981;"
        >
            <div class="summary-label">
                إجمالي الربح
            </div>

            <div class="summary-value">
                {{ $money(
                    $reportSummary['total_profit'] ?? 0
                ) }}
            </div>

            <div class="summary-note">
                قبل المصروفات العامة
            </div>
        </td>

        <td
            class="summary-card"
            style="width: 25%; border-top-color: #f59e0b;"
        >
            <div class="summary-label">
                المبالغ المتبقية
            </div>

            <div class="summary-value">
                {{ $money(
                    $reportSummary['total_remaining'] ?? 0
                ) }}
            </div>

            <div class="summary-note">
                ديون العملاء
            </div>
        </td>
    </tr>
</table>

<table class="summary-table">
    <tr>
        <td
            class="summary-card"
            style="width: 25%; border-top-color: #2563eb;"
        >
            <div class="summary-label">
                المبالغ المحصلة
            </div>

            <div class="summary-value">
                {{ $money(
                    $reportSummary['total_paid'] ?? 0
                ) }}
            </div>

            <div class="summary-note">
                الدفعات المسجلة
            </div>
        </td>

        <td
            class="summary-card"
            style="width: 25%; border-top-color: #7c3aed;"
        >
            <div class="summary-label">
                تكلفة المنتجات
            </div>

            <div class="summary-value">
                {{ $money(
                    $reportSummary['total_cost'] ?? 0
                ) }}
            </div>

            <div class="summary-note">
                تكلفة البضاعة المباعة
            </div>
        </td>

        <td
            class="summary-card"
            style="width: 25%; border-top-color: #e11d48;"
        >
            <div class="summary-label">
                المرتجعات
            </div>

            <div class="summary-value">
                {{ $money(
                    $reportSummary['total_returns'] ?? 0
                ) }}
            </div>

            <div class="summary-note">
                مرتجعات معتمدة
            </div>
        </td>

        <td
            class="summary-card"
            style="width: 25%; border-top-color: #64748b;"
        >
            <div class="summary-label">
                هامش الربح
            </div>

            <div class="summary-value">
                {{ number_format(
                    (float) (
                        $reportSummary['profit_margin'] ?? 0
                    ),
                    2
                ) }}٪
            </div>

            <div class="summary-note">
                نسبة الربح من المبيعات
            </div>
        </td>
    </tr>
</table>

<table class="section-heading">
    <tr>
        <td class="section-number">2</td>

        <td class="section-title-cell">
            <div class="section-title">
                تفاصيل فواتير المبيعات
            </div>

            <div class="section-subtitle">
                العملاء والمنتجات والأسعار والدفعات والأرباح
            </div>
        </td>

        <td class="section-badge">
            {{ $invoices->count() }} سجل
        </td>
    </tr>
</table>

@forelse($invoices as $invoice)
    <div class="invoice-card">
        <table class="invoice-header">
            <tr>
                <td style="width: 45%;">
                    <div class="invoice-number ltr">
                        {{ $invoice->invoice_number }}
                    </div>

                    <div class="invoice-customer">
                        {{ $customerName($invoice) }}
                    </div>

                    @if($invoice->customer_phone)
                        <div class="invoice-phone ltr">
                            {{ $invoice->customer_phone }}
                        </div>
                    @endif
                </td>

                <td style="width: 35%;">
                    <div class="meta-label">
                        تاريخ البيع
                    </div>

                    <div class="meta-value ltr">
                        {{ $invoice->sale_date?->format('Y-m-d') }}
                    </div>
                </td>

                <td style="width: 20%; text-align: left;">
                    <span class="invoice-status">
                        {{ $paymentStatus($invoice) }}
                    </span>
                </td>
            </tr>
        </table>

        <table class="invoice-meta">
            <tr>
                <td style="width: 20%;">
                    <div class="meta-label">
                        الإجمالي قبل الخصم
                    </div>

                    <div class="meta-value money">
                        {{ $money($invoice->subtotal) }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="meta-label">
                        إجمالي الخصومات
                    </div>

                    <div class="meta-value money">
                        {{ $money(
                            (float) $invoice->items_discount
                            + (float) $invoice->invoice_discount
                        ) }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="meta-label">
                        صافي الفاتورة
                    </div>

                    <div class="meta-value money">
                        {{ $money($invoice->total_amount) }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="meta-label">
                        المدفوع
                    </div>

                    <div class="meta-value money">
                        {{ $money($invoice->paid_amount) }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="meta-label">
                        المتبقي
                    </div>

                    <div class="meta-value money">
                        {{ $money($invoice->remaining_amount) }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25%;">
                        المنتج
                    </th>

                    <th style="width: 9%;">
                        الكمية
                    </th>

                    <th style="width: 14%;">
                        سعر الوحدة
                    </th>

                    <th style="width: 12%;">
                        الخصم
                    </th>

                    <th style="width: 14%;">
                        الإجمالي
                    </th>

                    <th style="width: 13%;">
                        التكلفة
                    </th>

                    <th style="width: 13%;">
                        الربح
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <div class="product-name">
                                {{ $item->product_name
                                    ?: $item->product?->name
                                    ?: 'منتج غير معروف' }}
                            </div>

                            <div class="product-code ltr">
                                {{ $item->product_code
                                    ?: $item->product?->code
                                    ?: '—' }}
                            </div>
                        </td>

                        <td>
                            {{ $item->quantity }}
                        </td>

                        <td class="money">
                            {{ $money(
                                $item->unit_selling_price
                            ) }}
                        </td>

                        <td class="money">
                            {{ $money(
                                (float) $item->line_discount
                                + (float) $item->allocated_invoice_discount
                            ) }}
                        </td>

                        <td class="money">
                            {{ $money($item->line_total) }}
                        </td>

                        <td class="money">
                            {{ $money($item->line_cost) }}
                        </td>

                        <td class="money">
                            {{ $money($item->line_profit) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="invoice-footer">
            <tr>
                <td style="width: 20%;">
                    <div class="invoice-total-label">
                        عدد الأصناف
                    </div>

                    <div class="invoice-total-value">
                        {{ $invoice->items->count() }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="invoice-total-label">
                        عدد القطع
                    </div>

                    <div class="invoice-total-value">
                        {{ $invoice->items->sum('quantity') }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="invoice-total-label">
                        تكلفة الفاتورة
                    </div>

                    <div class="invoice-total-value money">
                        {{ $money($invoice->total_cost) }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="invoice-total-label">
                        ربح الفاتورة
                    </div>

                    <div class="invoice-total-value money">
                        {{ $money($invoice->gross_profit) }}
                    </div>
                </td>

                <td style="width: 20%;">
                    <div class="invoice-total-label">
                        صافي الفاتورة
                    </div>

                    <div class="invoice-grand-total money">
                        {{ $money($invoice->total_amount) }}
                    </div>
                </td>
            </tr>
        </table>

        @if($invoice->payments->isNotEmpty())
            <div class="payments-box">
                <div class="payments-title">
                    الدفعات المسجلة
                </div>

                @foreach($invoice->payments as $payment)
                    <div class="payment-line">
                        {{ $paymentMethod(
                            $payment->payment_method
                        ) }}

                        — {{ $money($payment->amount) }}

                        — بتاريخ

                        <span class="ltr">
                            {{ $payment->paid_at?->format(
                                'Y-m-d H:i'
                            ) }}
                        </span>

                        @if($payment->financialAccount)
                            — الحساب:
                            {{ $payment->financialAccount->name }}
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <div class="empty-state">
        لا توجد فواتير مبيعات مطابقة للفترة
        أو الفلاتر المحددة.
    </div>
@endforelse

<table class="section-heading">
    <tr>
        <td class="section-number">3</td>

        <td class="section-title-cell">
            <div class="section-title">
                التحليل التجاري
            </div>

            <div class="section-subtitle">
                أفضل المنتجات والفئات وطرق الدفع
            </div>
        </td>

        <td class="section-badge">
            تحليل الفترة
        </td>
    </tr>
</table>

<table class="analysis-grid">
    <tr>
        <td class="analysis-box" style="width: 50%;">
            <div class="analysis-title">
                المنتجات الأكثر مبيعاً
            </div>

            <table class="analysis-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>الكمية</th>
                        <th>الإيراد</th>
                        <th>الربح</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($topProducts->take(8) as $product)
                        <tr>
                            <td>
                                {{ $product->name }}
                            </td>

                            <td>
                                {{ $product->total_quantity }}
                            </td>

                            <td class="money">
                                {{ $money(
                                    $product->total_revenue
                                ) }}
                            </td>

                            <td class="money">
                                {{ $money(
                                    $product->total_profit
                                ) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                لا توجد بيانات.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>

        <td class="analysis-box" style="width: 25%;">
            <div class="analysis-title">
                المبيعات حسب الفئة
            </div>

            <table class="analysis-table">
                <thead>
                    <tr>
                        <th>الفئة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($salesByCategory->take(8) as $category)
                        <tr>
                            <td>
                                {{ $category->name }}
                            </td>

                            <td class="money">
                                {{ $money($category->total) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                لا توجد بيانات.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>

        <td class="analysis-box" style="width: 25%;">
            <div class="analysis-title">
                المقبوضات حسب الطريقة
            </div>

            <table class="analysis-table">
                <thead>
                    <tr>
                        <th>الطريقة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($paymentsByMethod as $payment)
                        <tr>
                            <td>
                                {{ $paymentMethod(
                                    $payment->payment_method
                                ) }}
                            </td>

                            <td class="money">
                                {{ $money($payment->total) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                لا توجد بيانات.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>

@if($returns->isNotEmpty())
    <table class="section-heading">
        <tr>
            <td class="section-number">4</td>

            <td class="section-title-cell">
                <div class="section-title">
                    مرتجعات المبيعات
                </div>

                <div class="section-subtitle">
                    العمليات المعتمدة ضمن الفترة المحددة
                </div>
            </td>

            <td class="section-badge">
                {{ $returns->count() }} مرتجع
            </td>
        </tr>
    </table>

    <table class="return-table">
        <thead>
            <tr>
                <th>رقم المرتجع</th>
                <th>الفاتورة الأصلية</th>
                <th>العميل</th>
                <th>التاريخ</th>
                <th>المنتجات</th>
                <th>القيمة</th>
                <th>خفض الدين</th>
                <th>المسترد نقداً</th>
                <th>السبب</th>
            </tr>
        </thead>

        <tbody>
            @foreach($returns as $return)
                <tr>
                    <td class="ltr">
                        {{ $return->return_number }}
                    </td>

                    <td class="ltr">
                        {{ $return->invoice?->invoice_number
                            ?? '—' }}
                    </td>

                    <td>
                        {{ $return->customer?->name
                            ?? $return->invoice?->customer_name
                            ?? 'عميل نقدي' }}
                    </td>

                    <td class="ltr">
                        {{ $return->return_date?->format(
                            'Y-m-d'
                        ) }}
                    </td>

                    <td>
                        @foreach($return->items as $item)
                            {{ $item->product?->name
                                ?? 'منتج' }}

                            ({{ $item->quantity }})

                            @if(!$loop->last)
                                ،
                            @endif
                        @endforeach
                    </td>

                    <td class="money">
                        {{ $money($return->total_amount) }}
                    </td>

                    <td class="money">
                        {{ $money(
                            $return->amount_used_for_debt
                        ) }}
                    </td>

                    <td class="money">
                        {{ $money($return->amount_refunded) }}
                    </td>

                    <td>
                        {{ $return->reason ?: '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="closing-note">
    يعرض هذا التقرير جميع فواتير المبيعات المعتمدة
    والمنتجات والدفعات والمرتجعات المسجلة ضمن الفترة
    المحددة. تم استخراج البيانات آلياً من نظام
    {{ $store['store_name'] ?? 'فنانة فون' }}
    وقت إصدار التقرير.
</div>
</body>
</html>
