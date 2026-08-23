<?php

namespace App\Services\Reports;

use App\Models\PurchaseReturn;
use App\Models\SalesExchange;
use App\Models\SalesReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReturnReportService
{
    public function getReport(array $filters = []): array
    {
        $salesReturnsQuery = SalesReturn::query()
            ->with([
                'customer',
                'invoice',
                'items.product',
                'items.warehouse',
            ])
            ->where('status', 'approved');

        $purchaseReturnsQuery = PurchaseReturn::query()
            ->with([
                'supplier',
                'invoice',
                'items.product',
            ])
            ->where('status', 'approved');

        $exchangesQuery = SalesExchange::query()
            ->with([
                'salesReturn',
                'newInvoice',
            ]);

        $this->applyDateRange(
            $salesReturnsQuery,
            $filters,
            'return_date'
        );

        $this->applyDateRange(
            $purchaseReturnsQuery,
            $filters,
            'return_date'
        );

        /*
         * نحافظ على created_at في الاستبدال لأن الملف المرسل
         * لا يؤكد وجود exchanged_at كعمود قاعدة بيانات.
         */
        $this->applyDateRange(
            $exchangesQuery,
            $filters,
            'created_at'
        );

        $salesReturns = $salesReturnsQuery
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();

        $purchaseReturns = $purchaseReturnsQuery
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();

        $exchanges = $exchangesQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $totalSalesReturns = (float) $salesReturns
            ->sum('total_amount');

        $refundedToCustomers = (float) $salesReturns
            ->sum('amount_refunded');

        $debtReduced = (float) $salesReturns
            ->sum('amount_used_for_debt');

        $totalPurchaseReturns = (float) $purchaseReturns
            ->sum('total_amount');

        $refundedFromSuppliers = (float) $purchaseReturns
            ->sum('amount_refunded');

        $supplierDebtReduced = (float) $purchaseReturns
            ->sum('amount_used_for_debt');

        $restockedItems = 0;
        $damagedItems = 0;
        $needsInspection = 0;

        foreach ($salesReturns as $return) {
            foreach ($return->items as $item) {
                if ($item->restock) {
                    $restockedItems += (int) $item->quantity;
                }

                if ($item->product_condition === 'damaged') {
                    $damagedItems += (int) $item->quantity;
                }

                if ($item->product_condition === 'needs_inspection') {
                    $needsInspection += (int) $item->quantity;
                }
            }
        }

        $exchangeCount = $exchanges->count();

        $totalPriceDifference = (float) $exchanges
            ->sum('price_difference');

        $customerPays = (float) $exchanges
            ->where('settlement_type', 'customer_pays')
            ->sum('price_difference');

        $customerGetsRefund = abs(
            (float) $exchanges
                ->where(
                    'settlement_type',
                    'customer_gets_refund'
                )
                ->sum('price_difference')
        );

        $salesReturnIds = $salesReturns
            ->pluck('id')
            ->filter()
            ->values();

        $topReturnedProducts = collect();

        if ($salesReturnIds->isNotEmpty()) {
            $topReturnedProducts = DB::table(
                'sales_return_items'
            )
                ->join(
                    'products',
                    'sales_return_items.product_id',
                    '=',
                    'products.id'
                )
                ->whereIn(
                    'sales_return_items.sales_return_id',
                    $salesReturnIds
                )
                ->select([
                    'products.id',
                    'products.name',
                    'products.code',
                    DB::raw(
                        'SUM(sales_return_items.quantity) '
                        .'as total_quantity'
                    ),
                    DB::raw(
                        'SUM(sales_return_items.total_amount) '
                        .'as total_amount'
                    ),
                ])
                ->groupBy(
                    'products.id',
                    'products.name',
                    'products.code'
                )
                ->orderByDesc('total_quantity')
                ->limit(10)
                ->get();
        }

        return [
            'summary' => [
                'total_sales_returns' =>
                    round($totalSalesReturns, 2),
                'refunded_to_customers' =>
                    round($refundedToCustomers, 2),
                'debt_reduced' =>
                    round($debtReduced, 2),
                'total_purchase_returns' =>
                    round($totalPurchaseReturns, 2),
                'refunded_from_suppliers' =>
                    round($refundedFromSuppliers, 2),
                'supplier_debt_reduced' =>
                    round($supplierDebtReduced, 2),
                'restocked_items' =>
                    $restockedItems,
                'damaged_items' =>
                    $damagedItems,
                'needs_inspection' =>
                    $needsInspection,
                'exchange_count' =>
                    $exchangeCount,
                'total_price_difference' =>
                    round($totalPriceDifference, 2),
                'customer_pays' =>
                    round($customerPays, 2),
                'customer_gets_refund' =>
                    round($customerGetsRefund, 2),
            ],

            'sales_returns' =>
                $salesReturns->values(),

            'purchase_returns' =>
                $purchaseReturns->values(),

            'exchanges' =>
                $exchanges->values(),

            'top_returned_products' =>
                $topReturnedProducts->values(),
        ];
    }

    protected function applyDateRange(
        Builder $query,
        array $filters,
        string $column
    ): void {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($startDate || $endDate) {
            if ($startDate) {
                $query->whereDate(
                    $column,
                    '>=',
                    $startDate
                );
            }

            if ($endDate) {
                $query->whereDate(
                    $column,
                    '<=',
                    $endDate
                );
            }

            return;
        }

        $period = $filters['period'] ?? null;

        if (! $period) {
            return;
        }

        match ($period) {
            'today' =>
                $query->whereDate(
                    $column,
                    today()
                ),

            'yesterday' =>
                $query->whereDate(
                    $column,
                    today()->subDay()
                ),

            'last_7_days' =>
                $query->whereBetween(
                    $column,
                    [
                        now()->subDays(6)->startOfDay(),
                        now()->endOfDay(),
                    ]
                ),

            'this_week' =>
                $query->whereBetween(
                    $column,
                    [
                        now()->startOfWeek()->startOfDay(),
                        now()->endOfWeek()->endOfDay(),
                    ]
                ),

            'this_month' =>
                $query
                    ->whereMonth(
                        $column,
                        now()->month
                    )
                    ->whereYear(
                        $column,
                        now()->year
                    ),

            'last_month' =>
                $query
                    ->whereMonth(
                        $column,
                        now()
                            ->subMonthNoOverflow()
                            ->month
                    )
                    ->whereYear(
                        $column,
                        now()
                            ->subMonthNoOverflow()
                            ->year
                    ),

            'this_year' =>
                $query->whereYear(
                    $column,
                    now()->year
                ),

            default => null,
        };
    }
}
