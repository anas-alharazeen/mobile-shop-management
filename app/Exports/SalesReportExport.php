<?php

namespace App\Exports;

use App\Services\Reports\SalesReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalesReportExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($filters)
    {
        $this->data = app(SalesReportService::class)->getReport($filters);
    }

    public function array(): array
    {
        $rows = [];

        // إضافة عنوان التقرير
        $rows[] = ['تقرير المبيعات والأرباح'];
        $rows[] = [''];

        // إضافة ملخص
        $rows[] = ['ملخص التقرير'];
        $rows[] = ['إجمالي المبيعات', number_format($this->data['summary']['total_sales'], 2) . ' شيكل'];
        $rows[] = ['صافي المبيعات', number_format($this->data['summary']['net_sales'], 2) . ' شيكل'];
        $rows[] = ['إجمالي الأرباح', number_format($this->data['summary']['total_profit'], 2) . ' شيكل'];
        $rows[] = ['نسبة الربح', number_format($this->data['summary']['profit_margin'], 2) . '%'];
        $rows[] = ['عدد الفواتير', $this->data['summary']['invoice_count']];
        $rows[] = ['متوسط قيمة الفاتورة', number_format($this->data['summary']['average_invoice_value'], 2) . ' شيكل'];
        $rows[] = [''];

        // إضافة المنتجات الأكثر مبيعاً
        $rows[] = ['المنتجات الأكثر مبيعاً'];
        $rows[] = ['المنتج', 'الكود', 'الكمية المباعة', 'الإيراد', 'الربح'];
        foreach ($this->data['top_products'] as $product) {
            $rows[] = [
                $product->name,
                $product->code,
                $product->total_quantity,
                number_format($product->total_revenue, 2),
                number_format($product->total_profit, 2),
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        // دمج الخلايا للعنوان
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // تنسيق الملخص
        $sheet->getStyle('A3:E3')->getFont()->setBold(true);
        $sheet->getStyle('A4:E8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // تنسيق المنتجات
        $sheet->getStyle('A10:E10')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);

        // ضبط اتجاه RTL
        $sheet->getStyle('A1:E100')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }

    public function title(): string
    {
        return 'تقرير المبيعات';
    }
}
