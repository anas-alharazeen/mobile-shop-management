<?php

namespace App\Services\Reports;

use App\Services\SettingsService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use Throwable;

class ReportPdfService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function download(
        array $presentation,
        string $fileName,
        string $orientation = 'P',
    ): Response {
        if (! class_exists(Mpdf::class)) {
            throw new RuntimeException(
                'مكتبة mPDF غير مثبتة. نفّذ: composer require mpdf/mpdf:^8.2.1'
            );
        }

        $orientation = strtoupper($orientation) === 'L' ? 'L' : 'P';
        $type = (string) ($presentation['type'] ?? 'default');
        $theme = $this->theme($type);
        $store = $this->settings->getStoreSettings();

        $tempDirectory = storage_path('framework/cache/mpdf');

        File::ensureDirectoryExists(
            $tempDirectory,
            0775,
            true
        );

        $viewData = array_merge($presentation, [
            'store' => $store,
            'theme' => $theme,
            'orientation' => $orientation,
            'summary_columns' => $orientation === 'L' ? 4 : 3,
            'period_text' => $this->periodText(
                (array) ($presentation['filters'] ?? [])
            ),
            'report_code' => $this->reportCode($type),
            'logo_data_uri' => $this->logoDataUri(
                $store['store_logo'] ?? null
            ),
        ]);

        $mpdf = new Mpdf([
            'mode' => 'ar',
            'format' => 'A4',
            'orientation' => $orientation,
            'tempDir' => $tempDirectory,
            'default_font' => 'dejavusans',

            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 17,

            'margin_header' => 0,
            'margin_footer' => 5,

            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);

        $mpdf->SetDirectionality('rtl');

        $mpdf->SetTitle(
            (string) ($presentation['title'] ?? 'تقرير فنانة فون')
        );

        $mpdf->SetAuthor(
            (string) ($store['store_name'] ?? config('app.name'))
        );

        $mpdf->SetCreator(
            (string) ($store['store_name'] ?? config('app.name'))
        );

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetCompression(true);

        /*
         * يمنع تمدد الجداول خارج الصفحة،
         * خصوصاً تقارير المخزون والمبيعات.
         */
        $mpdf->shrink_tables_to_fit = 1;

        $storeName = htmlspecialchars(
            (string) ($store['store_name'] ?? 'فنانة فون'),
            ENT_QUOTES,
            'UTF-8'
        );

        $accent = $theme['accent'];

        $footerStoreName = htmlspecialchars(
            (string) (
                $store['store_name']
                ?? 'فنانة فون'
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $mpdf->SetHTMLFooter(
            '<table width="100%" style="
        width: 100%;
        border-collapse: collapse;
        font-family: dejavusans;
        font-size: 7.5pt;
        color: #64748b;
        direction: rtl;
    ">
        <tr>
            <td colspan="3" style="
                height: 1px;
                background: #0F0F75;
            "></td>
        </tr>

        <tr>
            <td width="40%" style="
                padding-top: 5px;
                text-align: right;
            ">
                ' . $footerStoreName . '
            </td>

            <td width="35%" style="
                padding-top: 5px;
                text-align: center;
            ">
                تقرير المبيعات والأرباح
            </td>

            <td width="25%" style="
                padding-top: 5px;
                text-align: left;
                direction: ltr;
            ">
                {PAGENO} / {nbpg}
            </td>
        </tr>
    </table>'
        );

        $viewName = match ((string) ($presentation['type'] ?? '')) {
            'sales' => 'reports.pdf.sales',
            default => 'reports.generic',
        };

        $html = view($viewName, $viewData)->render();
        $mpdf->WriteHTML($html);

        $content = $mpdf->Output(
            '',
            Destination::STRING_RETURN
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',

            'Content-Disposition' => $this->contentDisposition(
                $fileName
            ),

            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function theme(string $type): array
    {
        return match ($type) {
            'sales' => [
                'accent' => '#2563EB',
                'accent_dark' => '#1D4ED8',
                'soft' => '#EFF6FF',
                'label' => 'المبيعات والأرباح',
            ],

            'purchases' => [
                'accent' => '#7C3AED',
                'accent_dark' => '#6D28D9',
                'soft' => '#F5F3FF',
                'label' => 'المشتريات والموردون',
            ],

            'inventory' => [
                'accent' => '#0891B2',
                'accent_dark' => '#0E7490',
                'soft' => '#ECFEFF',
                'label' => 'المخزون والمستودعات',
            ],

            'repairs' => [
                'accent' => '#D97706',
                'accent_dark' => '#B45309',
                'soft' => '#FFFBEB',
                'label' => 'الصيانة وقطع الغيار',
            ],

            'finance' => [
                'accent' => '#059669',
                'accent_dark' => '#047857',
                'soft' => '#ECFDF5',
                'label' => 'الحسابات والتدفقات المالية',
            ],

            'returns' => [
                'accent' => '#E11D48',
                'accent_dark' => '#BE123C',
                'soft' => '#FFF1F2',
                'label' => 'المرتجعات والاستبدال',
            ],

            default => [
                'accent' => '#2563EB',
                'accent_dark' => '#1D4ED8',
                'soft' => '#EFF6FF',
                'label' => 'التقارير',
            ],
        };
    }

    private function periodText(array $filters): string
    {
        $labels = [
            'today' => 'اليوم',
            'yesterday' => 'أمس',
            'last_7_days' => 'آخر 7 أيام',
            'this_week' => 'هذا الأسبوع',
            'this_month' => 'هذا الشهر',
            'last_month' => 'الشهر السابق',
            'this_year' => 'هذه السنة',
            'custom' => 'فترة مخصصة',
        ];

        $period = (string) ($filters['period'] ?? '');

        $text = $labels[$period]
            ?? 'كامل البيانات المتاحة';

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($startDate || $endDate) {
            $text .= ' - من '
                . ($startDate ?: 'البداية')
                . ' إلى '
                . ($endDate ?: 'اليوم');
        }

        return $text;
    }

    private function reportCode(string $type): string
    {
        $prefix = match ($type) {
            'sales' => 'SAL',
            'purchases' => 'PUR',
            'inventory' => 'INV',
            'repairs' => 'REP',
            'finance' => 'FIN',
            'returns' => 'RET',
            default => 'RPT',
        };

        return $prefix . '-' . now()->format('Ymd-His');
    }

    private function contentDisposition(string $fileName): string
    {
        $fallback = 'fanana-report-'
            . now()->format('Y-m-d')
            . '.pdf';

        return 'attachment; filename="'
            . $fallback
            . '"; filename*=UTF-8\'\''
            . rawurlencode($fileName);
    }

    private function logoDataUri(
        ?string $relativePath
    ): ?string {
        if (! $relativePath) {
            return null;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($relativePath)) {
                return null;
            }

            $absolutePath = $disk->path($relativePath);

            $mimeType = File::mimeType($absolutePath)
                ?: 'image/png';

            $allowedTypes = [
                'image/png',
                'image/jpeg',
                'image/jpg',
                'image/gif',
                'image/webp',
            ];

            if (! in_array(
                $mimeType,
                $allowedTypes,
                true
            )) {
                return null;
            }

            $content = file_get_contents($absolutePath);

            if ($content === false) {
                return null;
            }

            return 'data:'
                . $mimeType
                . ';base64,'
                . base64_encode($content);
        } catch (Throwable) {
            return null;
        }
    }
}
