<?php

namespace App\Exports;

use App\Services\Reports\PurchaseReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PurchaseReportExport implements FromArray, WithStyles, WithTitle
{
    protected $data;

    public function __construct($filters)
    {
        $this->data = app(PurchaseReportService::class)->getReport($filters);
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['تقرير المشتريات'];
        $rows[] = [''];

        $rows[] = ['ملخص التقرير'];
        $rows[] = ['إجمالي المشتريات', number_format($this->data['summary']['total_purchases'], 2) . ' شيكل'];
        $rows[] = ['صافي المشتريات', number_format($this->data['summary']['net_purchases'], 2) . ' شيكل'];
        $rows[] = ['المدفوع للموردين', number_format($this->data['summary']['total_paid'], 2) . ' شيكل'];
        $rows[] = ['المتبقي للموردين', number_format($this->data['summary']['total_remaining'], 2) . ' شيكل'];
        $rows[] = ['عدد الفواتير', $this->data['summary']['invoice_count']];
        $rows[] = [''];

        $rows[] = ['المشتريات حسب المورد'];
        $rows[] = ['المورد', 'الإجمالي'];
        foreach ($this->data['purchases_by_supplier'] as $item) {
            $rows[] = [$item->name, number_format($item->total, 2)];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A3:B3')->getFont()->setBold(true);
        $sheet->getStyle('A10:B10')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }

    public function title(): string
    {
        return 'تقرير المشتريات';
    }
}
