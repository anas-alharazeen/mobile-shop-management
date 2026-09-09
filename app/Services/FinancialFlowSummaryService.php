<?php

namespace App\Services;

use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Models\FinancialTransaction;
use Carbon\Carbon;

class FinancialFlowSummaryService
{
    /**
     * يلخص الحركة النقدية الفعلية من Ledger الحسابات المالية.
     *
     * المبدأ:
     * - كل Inflow موجب.
     * - كل Outflow سالب.
     * - حركة REVERSAL تُنسب إلى نوع الحركة الأصلية حتى لا تبقى
     *   دفعة ملغاة ظاهرة كمقبوضات/مدفوعات في التقارير.
     *
     * مثال:
     * SALE_PAYMENT +300 ثم REVERSAL -300 => صافي مبيعات محصلة = 0.
     */
    public function summarize(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $transactions =
            FinancialTransaction::query()
                ->when(
                    $startDate,
                    fn ($query) =>
                        $query->where(
                            'transaction_date',
                            '>=',
                            $startDate
                        )
                )
                ->when(
                    $endDate,
                    fn ($query) =>
                        $query->where(
                            'transaction_date',
                            '<=',
                            $endDate
                        )
                )
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

        $reversalIds =
            $transactions
                ->filter(
                    fn (FinancialTransaction $transaction): bool =>
                        $this->typeValue($transaction)
                        === TransactionType::REVERSAL->value
                )
                ->pluck('reference_id')
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

        $originalTransactions =
            $reversalIds->isEmpty()
                ? collect()
                : FinancialTransaction::query()
                    ->whereIn(
                        'id',
                        $reversalIds
                    )
                    ->get()
                    ->keyBy('id');

        $byType = [];

        foreach (
            TransactionType::cases()
            as $type
        ) {
            $byType[$type->value] = 0.0;
        }

        $totalInflows = 0.0;
        $totalOutflows = 0.0;
        $netCashFlow = 0.0;
        $reversalCount = 0;

        foreach (
            $transactions
            as $transaction
        ) {
            $amount =
                (float) $transaction->amount;

            $direction =
                $this->directionValue(
                    $transaction
                );

            $signedAmount =
                $direction
                === TransactionDirection::INFLOW->value
                    ? $amount
                    : -$amount;

            $effectiveType =
                $this->typeValue(
                    $transaction
                );

            if (
                $effectiveType
                === TransactionType::REVERSAL->value
            ) {
                $reversalCount++;

                $original =
                    $originalTransactions->get(
                        (int) $transaction
                            ->reference_id
                    );

                if ($original) {
                    $originalType =
                        $this->typeValue(
                            $original
                        );

                    /*
                     * عكس حركة عكس أخرى حالة غير اعتيادية؛
                     * نتركها ضمن REVERSAL بدل إسنادها بشكل خاطئ.
                     */
                    if (
                        $originalType
                        !== TransactionType::REVERSAL->value
                    ) {
                        $effectiveType =
                            $originalType;
                    }
                }
            }

            /*
             * التحويل بين حسابين تابعين للمحل لا يمثل دخلاً أو مصروفاً
             * على مستوى المنشأة. يظهر في كشف كل حساب، لكن نستبعد طرفيه
             * من إجمالي الوارد/الصادر وصافي التدفق العام حتى لا تتضخم
             * الأرقام لمجرد نقل المال من صندوق إلى بنك أو العكس.
             *
             * عكس التحويل يرث ACCOUNT_TRANSFER من الحركة الأصلية، لذلك
             * يُستبعد أيضاً من التدفق العام مع بقائه محفوظاً في الـLedger.
             */
            $isInternalTransfer =
                $effectiveType
                === TransactionType::ACCOUNT_TRANSFER->value;

            if (! $isInternalTransfer) {
                if (
                    $direction
                    === TransactionDirection::INFLOW->value
                ) {
                    $totalInflows += $amount;
                } else {
                    $totalOutflows += $amount;
                }

                $netCashFlow +=
                    $signedAmount;
            }

            if (
                ! array_key_exists(
                    $effectiveType,
                    $byType
                )
            ) {
                $byType[$effectiveType] =
                    0.0;
            }

            $byType[$effectiveType] +=
                $signedAmount;
        }

        $collectedSales =
            $byType[
                TransactionType::SALE_PAYMENT->value
            ] ?? 0.0;

        $collectedRepairs =
            $byType[
                TransactionType::REPAIR_PAYMENT->value
            ] ?? 0.0;

        /*
         * PURCHASE_PAYMENT و EXPENSE حركتهما الطبيعية Outflow،
         * لذلك نعكس الإشارة لعرض الرقم كـ "مدفوع" موجب.
         * إذا حدث عكس أكبر ضمن الفترة قد يصبح الرقم سالباً،
         * وهذا يعبر عن صافي استرداد نقدي حقيقي في تلك الفترة.
         */
        $paidToSuppliers =
            -(
                $byType[
                    TransactionType::PURCHASE_PAYMENT->value
                ] ?? 0.0
            );

        $expenses =
            -(
                $byType[
                    TransactionType::EXPENSE->value
                ] ?? 0.0
            );

        $repairPartPurchases =
            -(
                $byType[
                    TransactionType::REPAIR_PART_PURCHASE->value
                ] ?? 0.0
            );

        $salesRefunds =
            -(
                $byType[
                    TransactionType::SALES_REFUND->value
                ] ?? 0.0
            );

        $purchaseRefunds =
            $byType[
                TransactionType::PURCHASE_REFUND->value
            ] ?? 0.0;

        $exchangeNet =
            $byType[
                TransactionType::EXCHANGE_DIFFERENCE->value
            ] ?? 0.0;

        $manualNet =
            ($byType[
                TransactionType::MANUAL_DEPOSIT->value
            ] ?? 0.0)
            + ($byType[
                TransactionType::MANUAL_WITHDRAWAL->value
            ] ?? 0.0)
            + ($byType[
                TransactionType::BALANCE_ADJUSTMENT->value
            ] ?? 0.0);

        $coreOperatingNet =
            $collectedSales
            + $collectedRepairs
            - $paidToSuppliers
            - $expenses
            - $repairPartPurchases;

        return [
            'total_inflows' =>
                round(
                    $totalInflows,
                    2
                ),

            'total_outflows' =>
                round(
                    $totalOutflows,
                    2
                ),

            'net_cash_flow' =>
                round(
                    $netCashFlow,
                    2
                ),

            'collected_sales' =>
                round(
                    $collectedSales,
                    2
                ),

            'collected_repairs' =>
                round(
                    $collectedRepairs,
                    2
                ),

            'total_collected' =>
                round(
                    $collectedSales
                    + $collectedRepairs,
                    2
                ),

            'paid_to_suppliers' =>
                round(
                    $paidToSuppliers,
                    2
                ),

            'expenses' =>
                round(
                    $expenses,
                    2
                ),

            'repair_part_purchases' =>
                round(
                    $repairPartPurchases,
                    2
                ),

            'sales_refunds' =>
                round(
                    $salesRefunds,
                    2
                ),

            'purchase_refunds' =>
                round(
                    $purchaseRefunds,
                    2
                ),

            'exchange_net' =>
                round(
                    $exchangeNet,
                    2
                ),

            'manual_net' =>
                round(
                    $manualNet,
                    2
                ),

            'other_net_flow' =>
                round(
                    $netCashFlow
                    - $coreOperatingNet,
                    2
                ),

            'transaction_count' =>
                $transactions->count(),

            'reversal_count' =>
                $reversalCount,

            'by_type' =>
                collect($byType)
                    ->map(
                        fn ($amount): float =>
                            round(
                                (float) $amount,
                                2
                            )
                    )
                    ->all(),
        ];
    }

    private function typeValue(
        FinancialTransaction $transaction
    ): string {
        return $transaction->type?->value
            ?? (string) $transaction->type;
    }

    private function directionValue(
        FinancialTransaction $transaction
    ): string {
        return $transaction->direction?->value
            ?? (string) $transaction->direction;
    }
}
