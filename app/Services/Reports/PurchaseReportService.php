<?php

namespace App\Services\Reports;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PurchaseReportService
{
    /**
     * تقرير المشتريات.
     *
     * حافظنا على نفس منطق التقرير الأصلي، مع:
     * - تحميل العلاقات المطلوبة للطباعة الجديدة.
     * - استخدام purchase_date بدلاً من created_at.
     * - جعل التحليلات تطابق نفس فلاتر الفواتير.
     * - دعم الفترات الزمنية الموجودة في صفحة التقارير.
     */
    public function getReport(array $filters = []): array
    {
        $invoiceQuery = PurchaseInvoice::query()
            ->with([
                'supplier',
                'items.product.category',
                'items.warehouse',
                'payments.financialAccount',
            ])
            ->where('status', 'approved');

        $this->applyInvoiceFilters(
            $invoiceQuery,
            $filters
        );

        $invoices = $invoiceQuery
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->get();

        $invoiceIds = $invoices
            ->pluck('id')
            ->filter()
            ->values();

        /*
         * مرتجعات المشتريات.
         */
        $returnsQuery = PurchaseReturn::query()
            ->with([
                'invoice',
                'supplier',
                'items.product.category',
            ])
            ->where('status', 'approved');

        $this->applyReturnFilters(
            $returnsQuery,
            $filters
        );

        $returns = $returnsQuery
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();

        /*
         * الملخص.
         */
        $totalPurchases = (float) $invoices
            ->sum('total_amount');

        $totalPaid = (float) $invoices
            ->sum('paid_amount');

        $totalRemaining = (float) $invoices
            ->sum('remaining_amount');

        $totalShipping = (float) $invoices
            ->sum('shipping_cost');

        $totalExpenses = (float) $invoices
            ->sum('additional_expenses');

        $totalReturns = (float) $returns
            ->sum('total_amount');

        $netPurchases = max(
            0,
            $totalPurchases - $totalReturns
        );

        /*
         * المشتريات حسب المورد.
         *
         * نستخدم IDs الفواتير التي ظهرت فعلياً في التقرير،
         * لذلك النتيجة تحترم فلاتر المورد والمنتج والفئة والتاريخ.
         */
        $purchasesBySupplier = collect();

        if ($invoiceIds->isNotEmpty()) {
            $purchasesBySupplier = DB::table(
                'purchase_invoices'
            )
                ->join(
                    'suppliers',
                    'purchase_invoices.supplier_id',
                    '=',
                    'suppliers.id'
                )
                ->whereIn(
                    'purchase_invoices.id',
                    $invoiceIds
                )
                ->select([
                    'suppliers.id',
                    'suppliers.name',

                    DB::raw(
                        'SUM(purchase_invoices.total_amount) as total'
                    ),

                    DB::raw(
                        'COUNT(purchase_invoices.id) as invoice_count'
                    ),
                ])
                ->groupBy(
                    'suppliers.id',
                    'suppliers.name'
                )
                ->orderByDesc('total')
                ->get();
        }

        /*
         * أكثر المنتجات شراءً.
         */
        $topProducts = collect();

        if ($invoiceIds->isNotEmpty()) {
            $topProducts = DB::table(
                'purchase_invoice_items'
            )
                ->join(
                    'products',
                    'purchase_invoice_items.product_id',
                    '=',
                    'products.id'
                )
                ->whereIn(
                    'purchase_invoice_items.purchase_invoice_id',
                    $invoiceIds
                )
                ->select([
                    'products.id',
                    'products.name',
                    'products.code',

                    DB::raw(
                        'SUM(purchase_invoice_items.quantity) '
                        .'as total_quantity'
                    ),

                    DB::raw(
                        'SUM(purchase_invoice_items.line_total) '
                        .'as total_amount'
                    ),
                ])
                ->groupBy(
                    'products.id',
                    'products.name',
                    'products.code'
                )
                ->orderByDesc(
                    'total_quantity'
                )
                ->limit(10)
                ->get();
        }

        /*
         * الفواتير المتأخرة ضمن نفس مجموعة الفواتير المفلترة.
         */
        $overdueInvoices = $invoices
            ->filter(function (
                PurchaseInvoice $invoice
            ): bool {
                return (float) $invoice->remaining_amount > 0
                    && $invoice->due_date
                    && $invoice->due_date->isBefore(
                        today()
                    );
            })
            ->values();

        return [
            'summary' => [
                'total_purchases' => round(
                    $totalPurchases,
                    2
                ),

                'net_purchases' => round(
                    $netPurchases,
                    2
                ),

                'total_paid' => round(
                    $totalPaid,
                    2
                ),

                'total_remaining' => round(
                    $totalRemaining,
                    2
                ),

                'total_shipping' => round(
                    $totalShipping,
                    2
                ),

                'total_expenses' => round(
                    $totalExpenses,
                    2
                ),

                'total_returns' => round(
                    $totalReturns,
                    2
                ),

                'invoice_count' =>
                    $invoices->count(),

                'purchased_items_count' =>
                    (int) $invoices->sum(
                        fn (PurchaseInvoice $invoice) =>
                            $invoice->items->sum(
                                'quantity'
                            )
                    ),

                'average_invoice_value' =>
                    $invoices->isNotEmpty()
                        ? round(
                            $totalPurchases
                            / $invoices->count(),
                            2
                        )
                        : 0,
            ],

            'purchases_by_supplier' =>
                $purchasesBySupplier,

            'top_products' =>
                $topProducts,

            'overdue_invoices' =>
                $overdueInvoices,

            'invoices' =>
                $invoices,

            'returns' =>
                $returns,
        ];
    }

    /**
     * فلاتر فواتير الشراء.
     */
    protected function applyInvoiceFilters(
        Builder $query,
        array $filters
    ): void {
        $this->applyDateRange(
            $query,
            $filters,
            'purchase_date'
        );

        if (! empty(
            $filters['supplier_id']
        )) {
            $query->where(
                'supplier_id',
                $filters['supplier_id']
            );
        }

        if (! empty(
            $filters['product_id']
        )) {
            $query->whereHas(
                'items',
                fn (Builder $itemsQuery) =>
                    $itemsQuery->where(
                        'product_id',
                        $filters['product_id']
                    )
            );
        }

        if (! empty(
            $filters['category_id']
        )) {
            $query->whereHas(
                'items.product',
                fn (Builder $productQuery) =>
                    $productQuery->where(
                        'category_id',
                        $filters['category_id']
                    )
            );
        }

        if (! empty(
            $filters['payment_status']
        )) {
            $query->where(
                'payment_status',
                $filters['payment_status']
            );
        }

        if (! empty(
            $filters['payment_method']
        )) {
            $query->whereHas(
                'payments',
                fn (Builder $paymentQuery) =>
                    $paymentQuery->where(
                        'payment_method',
                        $filters['payment_method']
                    )
            );
        }

        /*
         * التقرير الأساسي يعرض الفواتير المعتمدة فقط.
         */
        if (
            ! empty($filters['status'])
            && $filters['status'] !== 'approved'
        ) {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * فلاتر مرتجعات الشراء.
     */
    protected function applyReturnFilters(
        Builder $query,
        array $filters
    ): void {
        $this->applyDateRange(
            $query,
            $filters,
            'return_date'
        );

        if (! empty(
            $filters['supplier_id']
        )) {
            $query->where(
                'supplier_id',
                $filters['supplier_id']
            );
        }

        if (! empty(
            $filters['product_id']
        )) {
            $query->whereHas(
                'items',
                fn (Builder $itemsQuery) =>
                    $itemsQuery->where(
                        'product_id',
                        $filters['product_id']
                    )
            );
        }

        if (! empty(
            $filters['category_id']
        )) {
            $query->whereHas(
                'items.product',
                fn (Builder $productQuery) =>
                    $productQuery->where(
                        'category_id',
                        $filters['category_id']
                    )
            );
        }
    }

    /**
     * تطبيق التاريخ المخصص أو الفترة الجاهزة.
     */
    protected function applyDateRange(
        Builder $query,
        array $filters,
        string $column
    ): void {
        $startDate =
            $filters['start_date'] ?? null;

        $endDate =
            $filters['end_date'] ?? null;

        /*
         * التاريخ اليدوي له الأولوية على period.
         */
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

        if (! empty(
            $filters['period']
        )) {
            $this->applyPeriodFilter(
                $query,
                $filters['period'],
                $column
            );
        }
    }

    protected function applyPeriodFilter(
        Builder $query,
        string $period,
        string $column
    ): void {
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
                        now()
                            ->subDays(6)
                            ->startOfDay(),

                        now()
                            ->endOfDay(),
                    ]
                ),

            'this_week' =>
                $query->whereBetween(
                    $column,
                    [
                        now()
                            ->startOfWeek()
                            ->startOfDay(),

                        now()
                            ->endOfWeek()
                            ->endOfDay(),
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
