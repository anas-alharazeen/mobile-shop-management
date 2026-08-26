<?php

namespace App\Services\Reports;

use App\Models\DailyAccountClosing;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\PurchaseInvoice;
use App\Models\RepairOrder;
use App\Models\SalesInvoice;
use App\Services\FinancialFlowSummaryService;
use Carbon\Carbon;

class FinanceReportService
{
    public function __construct(
        private readonly FinancialFlowSummaryService $financialFlowSummary
    ) {
    }

    /**
     * التقرير المالي.
     *
     * حافظنا على نفس منطق الأرقام السابق:
     * - المقبوضات من المبيعات والصيانة.
     * - مدفوعات الموردين.
     * - المصروفات.
     * - الديون الحالية.
     * - أعمار ديون العملاء.
     * - الإغلاقات اليومية.
     *
     * وأضفنا الحركات المالية التفصيلية اللازمة
     * لقالب الطباعة الجديد.
     */
    public function getReport(array $filters = []): array
    {
        $resolvedFilters =
            $this->resolveDateFilters(
                $filters
            );

        /*
         * المصدر الوحيد للحركة النقدية هو financial_transactions.
         * هذه الخدمة تحسب أثر الـREVERSAL على نوع الحركة الأصلية،
         * فلا تبقى دفعة ملغاة ضمن المقبوضات أو المدفوعات.
         */
        $flowStartDate =
            ! empty(
                $resolvedFilters['start_date']
            )
                ? Carbon::parse(
                    $resolvedFilters['start_date']
                )->startOfDay()
                : null;

        $flowEndDate =
            ! empty(
                $resolvedFilters['end_date']
            )
                ? Carbon::parse(
                    $resolvedFilters['end_date']
                )->endOfDay()
                : null;

        $flowSummary =
            $this
                ->financialFlowSummary
                ->summarize(
                    $flowStartDate,
                    $flowEndDate
                );

        $collectedSales =
            (float) $flowSummary[
                'collected_sales'
            ];

        $collectedRepairs =
            (float) $flowSummary[
                'collected_repairs'
            ];

        $totalCollected =
            (float) $flowSummary[
                'total_collected'
            ];

        $paidToSuppliers =
            (float) $flowSummary[
                'paid_to_suppliers'
            ];

        $expenses =
            (float) $flowSummary[
                'expenses'
            ];

        $repairPartPurchases =
            (float) $flowSummary[
                'repair_part_purchases'
            ];

        /*
         * الحسابات المالية النشطة.
         *
         * هذا يحافظ على منطق الخدمة السابقة.
         */
        /*
         * الحساب غير النشط يبقى مالاً حقيقياً ومصدراً تاريخياً.
         * التعطيل يمنع العمليات الجديدة فقط، لذلك لا نخفي رصيده
         * من التقرير المالي.
         */
        $accounts = FinancialAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        /*
         * type_label Accessor غير موجود ضمن $appends،
         * لذلك نضيفه صراحة ليظهر في Vue والطباعة.
         */
        $accounts->each(
            function (
                FinancialAccount $account
            ): void {
                $account->setAttribute(
                    'type_label',
                    $account->type_label
                );

                $account->setAttribute(
                    'current_balance',
                    (float) $account->current_balance
                );

                $account->setAttribute(
                    'opening_balance',
                    (float) $account->opening_balance
                );
            }
        );

        /*
         * الديون الحالية.
         *
         * تبقى Snapshot حالية مثل منطق التقرير القديم،
         * ولا نقيدها بالفترة.
         */
        $customerDebts = (float) SalesInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            )
            ->sum('remaining_amount');

        $repairDebts = (float) RepairOrder::query()
            ->where(
                'status',
                '!=',
                'cancelled'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            )
            ->sum('remaining_amount');

        $totalCustomerDebts =
            $customerDebts
            + $repairDebts;

        $supplierDebts = (float) PurchaseInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            )
            ->sum('remaining_amount');

        /*
         * أعمار ديون فواتير المبيعات الحالية.
         */
        $aging =
            $this->getAgingReport();

        /*
         * الإغلاقات اليومية ضمن الفترة.
         */
        $closings = DailyAccountClosing::query()
            ->with('account')
            ->when(
                ! empty(
                    $resolvedFilters['start_date']
                ),
                fn ($query) => $query->whereDate(
                    'closing_date',
                    '>=',
                    $resolvedFilters['start_date']
                )
            )
            ->when(
                ! empty(
                    $resolvedFilters['end_date']
                ),
                fn ($query) => $query->whereDate(
                    'closing_date',
                    '<=',
                    $resolvedFilters['end_date']
                )
            )
            ->orderByDesc(
                'closing_date'
            )
            ->orderByDesc('id')
            ->get();

        $totalDifference = (float) $closings
            ->sum('difference');

        /*
         * الحركات المالية التفصيلية.
         *
         * هذا هو الجزء المطلوب للطباعة الجديدة،
         * ويعتمد نفس الفترة المستخدمة في التقرير.
         */
        $transactions = FinancialTransaction::query()
            ->with('account')
            ->when(
                ! empty(
                    $resolvedFilters['start_date']
                ),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $resolvedFilters['start_date']
                )
            )
            ->when(
                ! empty(
                    $resolvedFilters['end_date']
                ),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $resolvedFilters['end_date']
                )
            )
            ->orderByDesc(
                'transaction_date'
            )
            ->orderByDesc('id')
            ->get();

        return [
            'summary' => [
                'total_collected' => round(
                    $totalCollected,
                    2
                ),

                'collected_sales' => round(
                    $collectedSales,
                    2
                ),

                'collected_repairs' => round(
                    $collectedRepairs,
                    2
                ),

                'paid_to_suppliers' => round(
                    $paidToSuppliers,
                    2
                ),

                'expenses' => round(
                    $expenses,
                    2
                ),

                'repair_part_purchases' => round(
                    $repairPartPurchases,
                    2
                ),

                /*
                 * صافي التدفق هنا هو صافي كل الحركات المالية
                 * الفعلية: مبيعات، صيانة، مشتريات، مصروفات،
                 * مرتجعات، استبدال، إيداع/سحب، وتسويات وعكوس.
                 */
                'net_cash_flow' =>
                    (float) $flowSummary[
                        'net_cash_flow'
                    ],

                'total_inflows' =>
                    (float) $flowSummary[
                        'total_inflows'
                    ],

                'total_outflows' =>
                    (float) $flowSummary[
                        'total_outflows'
                    ],

                'sales_refunds' =>
                    (float) $flowSummary[
                        'sales_refunds'
                    ],

                'purchase_refunds' =>
                    (float) $flowSummary[
                        'purchase_refunds'
                    ],

                'exchange_net' =>
                    (float) $flowSummary[
                        'exchange_net'
                    ],

                'other_net_flow' =>
                    (float) $flowSummary[
                        'other_net_flow'
                    ],

                'reversal_count' =>
                    (int) $flowSummary[
                        'reversal_count'
                    ],

                'customer_debts' => round(
                    $totalCustomerDebts,
                    2
                ),

                'supplier_debts' => round(
                    $supplierDebts,
                    2
                ),

                'total_difference' => round(
                    $totalDifference,
                    2
                ),

                'transaction_count' =>
                    $transactions->count(),

                'account_count' =>
                    $accounts->count(),
            ],

            'accounts' =>
                $accounts,

            'aging' =>
                $aging,

            'closings' =>
                $closings,

            'transactions' =>
                $transactions,
        ];
    }

    /**
     * تحويل period إلى start_date / end_date.
     *
     * إذا كان المستخدم قد اختار تاريخاً مخصصاً
     * فإننا لا نستبدله بالفترة.
     */
    protected function resolveDateFilters(
        array $filters
    ): array {
        if (
            ! empty($filters['start_date'])
            || ! empty($filters['end_date'])
        ) {
            return $filters;
        }

        $period =
            $filters['period'] ?? null;

        if (! $period) {
            return $filters;
        }

        switch ($period) {
            case 'today':
                $filters['start_date'] =
                    today()->toDateString();

                $filters['end_date'] =
                    today()->toDateString();

                break;

            case 'yesterday':
                $date = today()->subDay();

                $filters['start_date'] =
                    $date->toDateString();

                $filters['end_date'] =
                    $date->toDateString();

                break;

            case 'last_7_days':
                $filters['start_date'] = now()
                    ->subDays(6)
                    ->toDateString();

                $filters['end_date'] =
                    now()->toDateString();

                break;

            case 'this_week':
                $filters['start_date'] = now()
                    ->startOfWeek()
                    ->toDateString();

                $filters['end_date'] = now()
                    ->endOfWeek()
                    ->toDateString();

                break;

            case 'this_month':
                $filters['start_date'] = now()
                    ->startOfMonth()
                    ->toDateString();

                $filters['end_date'] = now()
                    ->endOfMonth()
                    ->toDateString();

                break;

            case 'last_month':
                $lastMonth = now()
                    ->subMonthNoOverflow();

                $filters['start_date'] =
                    $lastMonth
                        ->copy()
                        ->startOfMonth()
                        ->toDateString();

                $filters['end_date'] =
                    $lastMonth
                        ->copy()
                        ->endOfMonth()
                        ->toDateString();

                break;

            case 'this_year':
                $filters['start_date'] = now()
                    ->startOfYear()
                    ->toDateString();

                $filters['end_date'] = now()
                    ->endOfYear()
                    ->toDateString();

                break;

            default:
                /*
                 * custom بدون تاريخ لا يضيف فلتر.
                 */
                break;
        }

        return $filters;
    }

    /**
     * تقرير أعمار ديون العملاء.
     *
     * يحافظ على نفس التصنيفات الموجودة سابقاً.
     */
    protected function getAgingReport(): array
    {
        $today = today();

        $aging = [
            'not_due' => 0.0,
            '1_7_days' => 0.0,
            '8_30_days' => 0.0,
            '31_60_days' => 0.0,
            'over_60_days' => 0.0,
        ];

        $invoices = SalesInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            )
            ->get();

        foreach ($invoices as $invoice) {
            $remaining = (float) (
                $invoice->remaining_amount ?? 0
            );

            /*
             * الفاتورة بلا تاريخ استحقاق لا نعتبرها
             * متأخرة تلقائياً.
             */
            if (! $invoice->due_date) {
                $aging['not_due'] +=
                    $remaining;

                continue;
            }

            $dueDate = Carbon::parse(
                $invoice->due_date
            )->startOfDay();

            if (
                $dueDate->greaterThanOrEqualTo(
                    $today
                )
            ) {
                $aging['not_due'] +=
                    $remaining;

                continue;
            }

            $daysOverdue = $dueDate
                ->diffInDays($today);

            if ($daysOverdue <= 7) {
                $aging['1_7_days'] +=
                    $remaining;
            } elseif ($daysOverdue <= 30) {
                $aging['8_30_days'] +=
                    $remaining;
            } elseif ($daysOverdue <= 60) {
                $aging['31_60_days'] +=
                    $remaining;
            } else {
                $aging['over_60_days'] +=
                    $remaining;
            }
        }

        foreach ($aging as $key => $value) {
            $aging[$key] = round(
                (float) $value,
                2
            );
        }

        return $aging;
    }
}
