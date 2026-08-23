<?php

namespace App\Exports;

use App\Services\Reports\ReportPresentationService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $type,
        private readonly array $data,
        private readonly array $filters = [],
    ) {}

    public function array(): array
    {
        return app(ReportPresentationService::class)->excelRows($this->type, $this->data, $this->filters);
    }

    public function styles(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $sheet->setRightToLeft(true);
        $sheet->freezePane('A2');
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true)->setSize(16);
        $sheet->getRowDimension(1)->setRowHeight(26);

        return [1 => ['font' => ['bold' => true, 'size' => 16]]];
    }

    public function title(): string
    {
        return mb_substr(app(ReportPresentationService::class)->title($this->type), 0, 31);
    }
}
