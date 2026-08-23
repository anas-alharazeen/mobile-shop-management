<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }}</title>

    <style>
        @page {
            margin: 10mm 10mm 18mm;
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
            font-size: 9.3pt;
            line-height: 1.55;

            color: #172033;
            background: #ffffff;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: embed;
            text-align: left;
        }

        /*
         * رأس التقرير
         */

        .hero {
            width: 100%;

            border-collapse: separate;
            border-spacing: 0;

            overflow: hidden;

            background: #0f172a;
            border-radius: 4mm;
        }

        .hero td {
            border: 0;
            vertical-align: middle;
        }

        .hero-main {
            width: 72%;
            padding: 7mm 7mm 6mm;
        }

        .hero-meta {
            width: 28%;

            padding: 6mm;

            background: #111e35;
            text-align: left;
        }

        .brand-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-table td {
            border: 0;
            vertical-align: middle;
        }

        .brand-logo-cell {
            width: 20mm;
            padding-left: 4mm;
        }

        .brand-logo-wrap {
            width: 17mm;
            height: 17mm;

            padding: 2mm;

            text-align: center;

            background: #ffffff;
            border-radius: 4mm;
        }

        .brand-logo {
            max-width: 13mm;
            max-height: 13mm;
        }

        .brand-fallback {
            display: inline-block;

            width: 13mm;
            height: 13mm;
            line-height: 13mm;

            border-radius: 3mm;

            background: {{ $theme['accent'] }};
            color: #ffffff;

            text-align: center;

            font-size: 15pt;
            font-weight: 700;
        }

        .store-name {
            margin: 0;

            color: #ffffff;

            font-size: 12pt;
            font-weight: 700;
        }

        .report-title {
            margin: 1.5mm 0 0;

            color: #ffffff;

            font-size: 20pt;
            line-height: 1.25;
            font-weight: 700;
        }

        .report-subtitle {
            margin-top: 1.5mm;

            color: #cbd5e1;

            font-size: 8.6pt;
        }

        .meta-label {
            color: #94a3b8;
            font-size: 7.3pt;
        }

        .meta-value {
            margin-top: 1mm;

            color: #ffffff;

            font-size: 9pt;
            font-weight: 700;
        }

        .meta-gap {
            height: 4mm;
        }

        .accent-strip {
            height: 1.8mm;

            margin: 0 4mm 5mm;

            background: {{ $theme['accent'] }};

            border-radius: 0 0 2mm 2mm;
        }

        /*
         * معلومات التقرير
         */

        .info-grid {
            width: 100%;

            margin-bottom: 5mm;

            border-collapse: separate;
            border-spacing: 1.8mm 0;
            table-layout: fixed;
        }

        .info-grid td {
            padding: 3mm 3.2mm;

            border: 1px solid #e2e8f0;

            background: #f8fafc;

            vertical-align: top;

            border-radius: 2.5mm;
        }

        .info-label {
            color: #64748b;
            font-size: 7.3pt;
        }

        .info-value {
            margin-top: 1mm;

            color: #0f172a;

            font-size: 8.6pt;
            font-weight: 700;
        }

        /*
         * عنوان الكتل
         */

        .block-heading {
            width: 100%;

            margin: 0 0 2.5mm;

            border-collapse: collapse;
        }

        .block-heading td {
            border: 0;
            vertical-align: middle;
        }

        .block-title {
            color: #0f172a;

            font-size: 12pt;
            font-weight: 700;
        }

        .block-note {
            color: #64748b;

            font-size: 7.6pt;
            text-align: left;
        }

        /*
         * بطاقات الملخص
         */

        .summary-table {
            width: 100%;

            margin: 0 0 2.3mm;

            border-collapse: separate;
            border-spacing: 1.8mm;

            table-layout: fixed;
        }

        .summary-card {
            min-height: 21mm;

            padding: 3.5mm 3.5mm 3mm;

            border: 1px solid #e2e8f0;
            border-top-width: 1.4mm;

            background: #ffffff;

            vertical-align: top;

            border-radius: 3mm;
        }

        .summary-card.empty {
            border-color: transparent;
            background: transparent;
        }

        .summary-label {
            color: #64748b;
            font-size: 7.7pt;
        }

        .summary-value {
            margin-top: 2mm;

            color: #0f172a;

            font-size: 12.5pt;
            line-height: 1.25;
            font-weight: 700;
        }

        .summary-index {
            margin-top: 2mm;

            color: #cbd5e1;

            font-size: 6.8pt;

            text-align: left;
            direction: ltr;
        }

        /*
         * أقسام التقرير
         */

        .section {
            margin-top: 6mm;
            page-break-inside: auto;
        }

        .section-heading {
            width: 100%;

            margin-bottom: 2.3mm;

            border-collapse: collapse;

            page-break-after: avoid;
        }

        .section-heading td {
            border: 0;
            vertical-align: middle;
        }

        .section-number {
            width: 11mm;
            height: 11mm;
            line-height: 11mm;

            text-align: center;

            color: #ffffff;
            background: {{ $theme['accent'] }};

            border-radius: 3mm;

            font-size: 10pt;
            font-weight: 700;
        }

        .section-title-cell {
            padding-right: 3mm;
        }

        .section-title {
            margin: 0;

            color: #0f172a;

            font-size: 11pt;
            font-weight: 700;
        }

        .section-description {
            margin-top: 0.7mm;

            color: #64748b;

            font-size: 7.4pt;
        }

        .section-count {
            width: 32mm;

            padding: 1.7mm 2.5mm;

            text-align: center;

            color: {{ $theme['accent_dark'] }};
            background: {{ $theme['soft'] }};

            border: 1px solid {{ $theme['accent'] }};
            border-radius: 5mm;

            font-size: 7.2pt;
            font-weight: 700;
        }

        /*
         * الجداول
         */

        .table-shell {
            width: 100%;

            padding: 1.2mm;

            border: 1px solid #dce5f0;

            background: #f8fafc;

            border-radius: 3mm;
        }

        .data-table {
            width: 100%;

            border-collapse: collapse;
            table-layout: auto;

            background: #ffffff;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th,
        .data-table td {
            padding: 2.5mm 2.7mm;

            border-bottom: 1px solid #e2e8f0;

            text-align: right;
            vertical-align: middle;

            word-wrap: break-word;
        }

        .data-table th {
            color: #ffffff;
            background: #172033;

            border-bottom-color: #172033;

            font-size: 7.8pt;
            font-weight: 700;
        }

        .data-table th:first-child {
            border-radius: 0 2mm 0 0;
        }

        .data-table th:last-child {
            border-radius: 2mm 0 0 0;
        }

        .data-table td {
            color: #334155;
            background: #ffffff;

            font-size: 8.1pt;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .data-table td:first-child {
            color: #0f172a;
            font-weight: 700;
        }

        .data-table.compact th,
        .data-table.compact td {
            padding: 2mm 2.1mm;
            font-size: 7.2pt;
        }

        .ltr-cell {
            direction: ltr;
            text-align: left;
            unicode-bidi: embed;
        }

        /*
         * الحالة الفارغة
         */

        .empty-state {
            margin-top: 8mm;

            padding: 10mm 8mm;

            border: 1px dashed #cbd5e1;

            background: #f8fafc;

            text-align: center;

            border-radius: 4mm;
        }

        .empty-symbol {
            display: inline-block;

            width: 14mm;
            height: 14mm;
            line-height: 14mm;

            margin-bottom: 3mm;

            border-radius: 7mm;

            color: {{ $theme['accent'] }};
            background: {{ $theme['soft'] }};

            font-size: 18pt;
            font-weight: 700;
        }

        .empty-title {
            color: #0f172a;

            font-size: 11pt;
            font-weight: 700;
        }

        .empty-text {
            margin-top: 1.5mm;

            color: #64748b;
            font-size: 8pt;
        }

        /*
         * الملاحظة النهائية
         */

        .closing-note {
            margin-top: 7mm;

            padding: 3mm 4mm;

            border-right: 1.2mm solid {{ $theme['accent'] }};

            background: {{ $theme['soft'] }};
            color: #475569;

            font-size: 7.5pt;
        }
    </style>
</head>

<body>
@php
    $storeDetails = array_values(array_filter([
        $store['store_phone'] ?? null,
        $store['store_email'] ?? null,
        $store['store_address'] ?? null,
    ]));

    $metricColors = [
        $theme['accent'],
        '#0EA5E9',
        '#10B981',
        '#8B5CF6',
        '#F59E0B',
        '#EF4444',
    ];

    $sectionCount = count($sections);
    $metricIndex = 0;
@endphp

<table class="hero">
    <tr>
        <td class="hero-main">
            <table class="brand-table">
                <tr>
                    <td class="brand-logo-cell">
                        <div class="brand-logo-wrap">
                            @if($logo_data_uri)
                                <img
                                    class="brand-logo"
                                    src="{{ $logo_data_uri }}"
                                    alt="{{ $store['store_name'] ?? 'فنانة فون' }}"
                                >
                            @else
                                <span class="brand-fallback">
                                    ف
                                </span>
                            @endif
                        </div>
                    </td>

                    <td>
                        <div class="store-name">
                            {{ $store['store_name'] ?? 'فنانة فون' }}
                        </div>

                        <h1 class="report-title">
                            {{ $title }}
                        </h1>

                        <div class="report-subtitle">
                            {{ $theme['label'] }}

                            @if($storeDetails)
                                - {{ implode(' | ', $storeDetails) }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </td>

        <td class="hero-meta">
            <div class="meta-label">
                رمز التقرير
            </div>

            <div class="meta-value ltr">
                {{ $report_code }}
            </div>

            <div class="meta-gap"></div>

            <div class="meta-label">
                تاريخ الإصدار
            </div>

            <div class="meta-value ltr">
                {{ $generated_at }}
            </div>
        </td>
    </tr>
</table>

<div class="accent-strip"></div>

<table class="info-grid">
    <tr>
        <td style="width: 40%;">
            <div class="info-label">
                نطاق التقرير
            </div>

            <div class="info-value">
                {{ $period_text }}
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

        <td style="width: 20%;">
            <div class="info-label">
                عدد المؤشرات
            </div>

            <div class="info-value">
                {{ count($summary) }}
            </div>
        </td>

        <td style="width: 20%;">
            <div class="info-label">
                عدد الأقسام
            </div>

            <div class="info-value">
                {{ $sectionCount }}
            </div>
        </td>
    </tr>
</table>

<table class="block-heading">
    <tr>
        <td class="block-title">
            الملخص التنفيذي
        </td>

        <td class="block-note">
            أهم الأرقام ضمن الفترة المحددة
        </td>
    </tr>
</table>

@foreach(array_chunk($summary, $summary_columns) as $chunk)
    <table class="summary-table">
        <tr>
            @foreach($chunk as $item)
                @php
                    $cardColor = $metricColors[
                        $metricIndex % count($metricColors)
                    ];

                    $metricIndex++;
                @endphp

                <td
                    class="summary-card"
                    style="
                        width: {{ 100 / $summary_columns }}%;
                        border-top-color: {{ $cardColor }};
                    "
                >
                    <div class="summary-label">
                        {{ $item['label'] }}
                    </div>

                    <div class="summary-value">
                        {{ $item['value'] }}
                    </div>

                    <div class="summary-index">
                        {{ str_pad(
                            (string) $metricIndex,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) }}
                    </div>
                </td>
            @endforeach

            @for(
                $i = count($chunk);
                $i < $summary_columns;
                $i++
            )
                <td
                    class="summary-card empty"
                    style="
                        width: {{ 100 / $summary_columns }}%;
                    "
                ></td>
            @endfor
        </tr>
    </table>
@endforeach

@forelse($sections as $section)
    @php
        $columnCount = count($section['columns']);
        $rowCount = count($section['rows']);
    @endphp

    <div class="section">
        <table class="section-heading">
            <tr>
                <td class="section-number">
                    {{ $loop->iteration }}
                </td>

                <td class="section-title-cell">
                    <h2 class="section-title">
                        {{ $section['title'] }}
                    </h2>

                    <div class="section-description">
                        تفاصيل مرتبة مستخرجة من بيانات النظام
                    </div>
                </td>

                <td class="section-count">
                    عدد السجلات: {{ $rowCount }}
                </td>
            </tr>
        </table>

        <div class="table-shell">
            <table
                class="data-table {{ $columnCount >= 7 ? 'compact' : '' }}"
            >
                <thead>
                    <tr>
                        @foreach($section['columns'] as $column)
                            <th>
                                {{ $column }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($section['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                @php
                                    $cellText = (string) $cell;

                                    $isPureLtr = preg_match(
                                        '/^[\s\d\-\/:.,A-Za-z_]+$/u',
                                        $cellText
                                    ) === 1;
                                @endphp

                                <td class="{{ $isPureLtr ? 'ltr-cell' : '' }}">
                                    {{ $cellText }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="empty-state">
        <div class="empty-symbol">
            -
        </div>

        <div class="empty-title">
            لا توجد بيانات تفصيلية
        </div>

        <div class="empty-text">
            لم يتم العثور على سجلات مطابقة
            للفترة أو الفلاتر المحددة.
        </div>
    </div>
@endforelse

<div class="closing-note">
    تم إنشاء هذا التقرير آلياً من بيانات منصة
    {{ $store['store_name'] ?? 'فنانة فون' }}،
    وتظهر القيم وفق البيانات المسجلة وقت الإصدار.
</div>
</body>
</html>
