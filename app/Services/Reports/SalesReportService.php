<?php

namespace App\Services\Reports;

use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * مدة كاش التقرير بالثواني.
     */
    protected int $cacheTTL = 600;

    /**
     * الحصول على تقرير المبيعات مع Cache.
     */
    public function getReport(array $filters = []): array
    {
        /*
         * تقارير المبيعات يجب أن تكون Real-Time.
         *
         * كانت الخدمة تستخدم Cache لمدة 10 دقائق، وهذا يعني:
         * - فتح تقرير "اليوم" قبل إنشاء فاتورة = نتيجة فارغة تُحفظ.
         * - إنشاء فاتورة جديدة بعد ذلك = التقرير يبقى فارغاً حتى انتهاء الكاش.
         *
         * لذلك لا نستخدم Cache هنا.
         * clearCache() يبقى موجوداً للتوافق مع أي كود قديم يستدعيه.
         */
        $normalizedFilters = $this->normalizeFilters($filters);

        return $this->generateReport(
            $normalizedFilters
        );
    }

    /**
     * توليد تقرير المبيعات.
     *
     * التقرير يعيد البيانات التفصيلية المطلوبة لصفحة:
     * resources/js/Pages/Reports/Print.vue
     */
    protected function generateReport(
        array $filters = []
    ): array {
        /*
         * الفواتير التفصيلية.
         *
         * نحمل العلاقات المستخدمة مباشرة في الطباعة:
         * - بيانات العميل
         * - المنتجات والمخزن
         * - الدفعات والحساب المالي
         */
        $invoiceQuery = SalesInvoice::query()
            ->with([
                'customer',
                'items.product.category',
                'items.warehouse',
                'payments.financialAccount',
            ])
            ->where(
                'status',
                'approved'
            );

        $this->applyInvoiceFilters(
            $invoiceQuery,
            $filters
        );

        $invoices = $invoiceQuery
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get();

        /*
         * نستعمل نفس IDs الفواتير التي ظهرت في التقرير
         * لضمان أن تحليلات:
         * - أكثر المنتجات مبيعاً
         * - المبيعات حسب الفئة
         * - الدفعات حسب الطريقة
         *
         * تطابق الفلاتر تماماً.
         */
        $invoiceIds = $invoices
            ->pluck('id')
            ->filter()
            ->values();

        /*
         * مرتجعات المبيعات.
         *
         * الفلاتر الخاصة بها تستخدم return_date
         * وليس created_at.
         */
        $returnsQuery = SalesReturn::query()
            ->with([
                'invoice',
                'customer',
                'items.product.category',
                'items.warehouse',
            ])
            ->where(
                'status',
                'approved'
            );

        $this->applyReturnFilters(
            $returnsQuery,
            $filters
        );

        $returns = $returnsQuery
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->get();

        /*
         * الملخص المالي.
         */
        $totalSales = (float) $invoices
            ->sum('total_amount');

        $totalCost = (float) $invoices
            ->sum('total_cost');

        $totalProfit = (float) $invoices
            ->sum('gross_profit');

        $totalDiscounts =
            (float) $invoices->sum('items_discount')
            + (float) $invoices->sum('invoice_discount');

        $totalPaid = (float) $invoices
            ->sum('paid_amount');

        $totalRemaining = (float) $invoices
            ->sum('remaining_amount');

        $totalReturns = (float) $returns
            ->sum('total_amount');

        $netSales = max(
            0,
            $totalSales - $totalReturns
        );

        /*
         * أكثر المنتجات مبيعاً.
         *
         * لا ننفذ الاستعلام إذا لم توجد فواتير مطابقة،
         * حتى لا يعرض التقرير منتجات من خارج الفلاتر.
         */
        $topProducts = collect();

        if ($invoiceIds->isNotEmpty()) {
            $topProducts = DB::table(
                'sales_invoice_items'
            )
                ->join(
                    'products',
                    'sales_invoice_items.product_id',
                    '=',
                    'products.id'
                )
                ->whereIn(
                    'sales_invoice_items.sales_invoice_id',
                    $invoiceIds
                )
                ->select([
                    'products.id',
                    'products.name',
                    'products.code',

                    DB::raw(
                        'SUM(sales_invoice_items.quantity) '
                        .'as total_quantity'
                    ),

                    DB::raw(
                        'SUM(sales_invoice_items.line_total) '
                        .'as total_revenue'
                    ),

                    DB::raw(
                        'SUM(sales_invoice_items.line_profit) '
                        .'as total_profit'
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
         * المقبوضات حسب طريقة الدفع.
         *
         * نربطها بالفواتير الظاهرة في التقرير حتى لا
         * تختلط دفعات لفواتير خارج الفلاتر.
         */
        $paymentsByMethod = collect();

        if ($invoiceIds->isNotEmpty()) {
            $paymentsByMethod = DB::table(
                'sales_payments'
            )
                ->whereIn(
                    'sales_invoice_id',
                    $invoiceIds
                )
                ->select([
                    'payment_method',

                    DB::raw(
                        'SUM(amount) as total'
                    ),

                    DB::raw(
                        'COUNT(*) as payment_count'
                    ),
                ])
                ->groupBy(
                    'payment_method'
                )
                ->orderByDesc(
                    'total'
                )
                ->get();
        }

        /*
         * المبيعات حسب الفئة.
         */
        $salesByCategory = collect();

        if ($invoiceIds->isNotEmpty()) {
            $salesByCategory = DB::table(
                'sales_invoice_items'
            )
                ->join(
                    'products',
                    'sales_invoice_items.product_id',
                    '=',
                    'products.id'
                )
                ->leftJoin(
                    'categories',
                    'products.category_id',
                    '=',
                    'categories.id'
                )
                ->whereIn(
                    'sales_invoice_items.sales_invoice_id',
                    $invoiceIds
                )
                ->select([
                    DB::raw(
                        "COALESCE(categories.name, 'بدون فئة') as name"
                    ),

                    DB::raw(
                        'SUM(sales_invoice_items.line_total) '
                        .'as total'
                    ),

                    DB::raw(
                        'SUM(sales_invoice_items.quantity) '
                        .'as total_quantity'
                    ),
                ])
                ->groupBy(
                    'categories.id',
                    'categories.name'
                )
                ->orderByDesc(
                    'total'
                )
                ->get();
        }

        /*
         * المقارنة مع الفترة السابقة.
         */
        $previousPeriodComparison =
            $this->getPeriodComparison(
                $filters
            );

        return [
            'summary' => [
                'total_sales' => round(
                    $totalSales,
                    2
                ),

                'net_sales' => round(
                    $netSales,
                    2
                ),

                'total_cost' => round(
                    $totalCost,
                    2
                ),

                'total_profit' => round(
                    $totalProfit,
                    2
                ),

                'profit_margin' => $totalSales > 0
                    ? round(
                        ($totalProfit / $totalSales) * 100,
                        2
                    )
                    : 0,

                'total_discounts' => round(
                    $totalDiscounts,
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

                'total_returns' => round(
                    $totalReturns,
                    2
                ),

                'invoice_count' =>
                    $invoices->count(),

                'sold_items_count' =>
                    (int) $invoices->sum(
                        fn (SalesInvoice $invoice) =>
                            $invoice->items->sum(
                                'quantity'
                            )
                    ),

                'average_invoice_value' =>
                    $invoices->isNotEmpty()
                        ? round(
                            $totalSales
                            / $invoices->count(),
                            2
                        )
                        : 0,
            ],

            /*
             * هذه البيانات تستخدمها صفحة التقرير
             * وكذلك صفحة الطباعة الجديدة.
             */
            'top_products' => $topProducts->values(),

            'payments_by_method' =>
                $paymentsByMethod->values(),

            'sales_by_category' =>
                $salesByCategory->values(),

            'invoices' => $invoices->values(),

            'returns' => $returns->values(),

            'comparison' =>
                $previousPeriodComparison,
        ];
    }

    /**
     * تطبيق الفلاتر على فواتير المبيعات.
     */
    protected function applyInvoiceFilters(
        Builder $query,
        array $filters
    ): void {
        $this->applyDateRange(
            $query,
            $filters,
            'sale_date'
        );

        if (! empty(
            $filters['customer_id']
        )) {
            $query->where(
                'customer_id',
                $filters['customer_id']
            );
        }

        if (! empty(
            $filters['product_id']
        )) {
            $query->whereHas(
                'items',
                function (
                    Builder $itemsQuery
                ) use ($filters): void {
                    $itemsQuery->where(
                        'product_id',
                        $filters['product_id']
                    );
                }
            );
        }

        if (! empty(
            $filters['category_id']
        )) {
            $query->whereHas(
                'items.product',
                function (
                    Builder $productQuery
                ) use ($filters): void {
                    $productQuery->where(
                        'category_id',
                        $filters['category_id']
                    );
                }
            );
        }

        /*
         * فلاتر إضافية يدعمها ReportController.
         */
        if (! empty(
            $filters['payment_status']
        )) {
            $query->where(
                'payment_status',
                $filters['payment_status']
            );
        }

        if (! empty(
            $filters['status']
        )) {
            /*
             * تقرير المبيعات الأساسي يعرض المعتمد فقط،
             * لذلك لا نسمح لفلتر status بتجاوز ذلك.
             */
            if (
                $filters['status']
                !== 'approved'
            ) {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty(
            $filters['payment_method']
        )) {
            $query->whereHas(
                'payments',
                function (
                    Builder $paymentQuery
                ) use ($filters): void {
                    $paymentQuery->where(
                        'payment_method',
                        $filters['payment_method']
                    );
                }
            );
        }
    }

    /**
     * تطبيق الفلاتر المنطقية على المرتجعات.
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
            $filters['customer_id']
        )) {
            $query->where(
                'customer_id',
                $filters['customer_id']
            );
        }

        if (! empty(
            $filters['product_id']
        )) {
            $query->whereHas(
                'items',
                function (
                    Builder $itemsQuery
                ) use ($filters): void {
                    $itemsQuery->where(
                        'product_id',
                        $filters['product_id']
                    );
                }
            );
        }

        if (! empty(
            $filters['category_id']
        )) {
            $query->whereHas(
                'items.product',
                function (
                    Builder $productQuery
                ) use ($filters): void {
                    $productQuery->where(
                        'category_id',
                        $filters['category_id']
                    );
                }
            );
        }
    }

    /**
     * تطبيق الفترة أو التاريخ المخصص.
     *
     * إذا تم إرسال start_date / end_date فإنهما
     * يأخذان الأولوية على period.
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
         * في المبيعات نعتمد sale_date كتاريخ أساسي.
         * لو كانت فاتورة قديمة/مستوردة ولا تحتوي sale_date،
         * نستخدم created_at كـ fallback.
         */
        $applyRange = function (
            Builder $builder,
            ?string $from,
            ?string $to
        ) use ($column): void {
            $builder->where(
                function (
                    Builder $dateQuery
                ) use (
                    $column,
                    $from,
                    $to
                ): void {
                    $dateQuery->where(
                        function (
                            Builder $primary
                        ) use (
                            $column,
                            $from,
                            $to
                        ): void {
                            if ($from) {
                                $primary->whereDate(
                                    $column,
                                    '>=',
                                    $from
                                );
                            }

                            if ($to) {
                                $primary->whereDate(
                                    $column,
                                    '<=',
                                    $to
                                );
                            }
                        }
                    );

                    if ($column === 'sale_date') {
                        $dateQuery->orWhere(
                            function (
                                Builder $fallback
                            ) use (
                                $column,
                                $from,
                                $to
                            ): void {
                                $fallback->whereNull(
                                    $column
                                );

                                if ($from) {
                                    $fallback->whereDate(
                                        'created_at',
                                        '>=',
                                        $from
                                    );
                                }

                                if ($to) {
                                    $fallback->whereDate(
                                        'created_at',
                                        '<=',
                                        $to
                                    );
                                }
                            }
                        );
                    }
                }
            );
        };

        /*
         * التاريخ اليدوي له الأولوية على period.
         */
        if ($startDate || $endDate) {
            $applyRange(
                $query,
                $startDate,
                $endDate
            );

            return;
        }

        $period =
            $filters['period'] ?? null;

        if (! $period) {
            return;
        }

        [$from, $to] =
            $this->periodDates(
                $period
            );

        if (! $from && ! $to) {
            return;
        }

        $applyRange(
            $query,
            $from,
            $to
        );
    }

    /**
     * فلاتر الفترات الجاهزة.
     */
    protected function applyPeriodFilter(
        Builder $query,
        string $period,
        string $column
    ): void {
        [$from, $to] =
            $this->periodDates(
                $period
            );

        if (! $from && ! $to) {
            return;
        }

        $query->where(
            function (
                Builder $dateQuery
            ) use (
                $column,
                $from,
                $to
            ): void {
                $dateQuery->whereDate(
                    $column,
                    '>=',
                    $from
                )->whereDate(
                    $column,
                    '<=',
                    $to
                );

                if ($column === 'sale_date') {
                    $dateQuery->orWhere(
                        function (
                            Builder $fallback
                        ) use (
                            $column,
                            $from,
                            $to
                        ): void {
                            $fallback
                                ->whereNull($column)
                                ->whereDate(
                                    'created_at',
                                    '>=',
                                    $from
                                )
                                ->whereDate(
                                    'created_at',
                                    '<=',
                                    $to
                                );
                        }
                    );
                }
            }
        );
    }

    /**
     * تحويل period إلى تاريخين صريحين.
     */
    protected function periodDates(
        string $period
    ): array {
        return match ($period) {
            'today' => [
                today()->toDateString(),
                today()->toDateString(),
            ],

            'yesterday' => [
                today()
                    ->subDay()
                    ->toDateString(),

                today()
                    ->subDay()
                    ->toDateString(),
            ],

            'last_7_days' => [
                now()
                    ->subDays(6)
                    ->toDateString(),

                now()->toDateString(),
            ],

            'this_week' => [
                now()
                    ->startOfWeek()
                    ->toDateString(),

                now()
                    ->endOfWeek()
                    ->toDateString(),
            ],

            'this_month' => [
                now()
                    ->startOfMonth()
                    ->toDateString(),

                now()
                    ->endOfMonth()
                    ->toDateString(),
            ],

            'last_month' => [
                now()
                    ->subMonthNoOverflow()
                    ->startOfMonth()
                    ->toDateString(),

                now()
                    ->subMonthNoOverflow()
                    ->endOfMonth()
                    ->toDateString(),
            ],

            'this_year' => [
                now()
                    ->startOfYear()
                    ->toDateString(),

                now()
                    ->endOfYear()
                    ->toDateString(),
            ],

            default => [
                null,
                null,
            ],
        };
    }

    /**
     * المقارنة مع الفترة السابقة.
     *
     * تعتمد sale_date، وتطبق أيضاً فلاتر:
     * العميل والمنتج والفئة وطريقة الدفع وحالة الدفع.
     */
    protected function getPeriodComparison(
        array $filters
    ): ?array {
        [
            $currentStart,
            $currentEnd,
        ] = $this->resolveDateRange(
            $filters
        );

        if (! $currentStart || ! $currentEnd) {
            return null;
        }

        /*
         * عدد الأيام شاملاً يوم البداية والنهاية.
         */
        $days = $currentStart
            ->copy()
            ->startOfDay()
            ->diffInDays(
                $currentEnd
                    ->copy()
                    ->startOfDay()
            ) + 1;

        $previousStart = $currentStart
            ->copy()
            ->subDays($days);

        $previousEnd = $currentStart
            ->copy()
            ->subDay();

        $currentQuery = SalesInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->whereDate(
                'sale_date',
                '>=',
                $currentStart->toDateString()
            )
            ->whereDate(
                'sale_date',
                '<=',
                $currentEnd->toDateString()
            );

        $previousQuery = SalesInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->whereDate(
                'sale_date',
                '>=',
                $previousStart->toDateString()
            )
            ->whereDate(
                'sale_date',
                '<=',
                $previousEnd->toDateString()
            );

        $this->applyNonDateInvoiceFilters(
            $currentQuery,
            $filters
        );

        $this->applyNonDateInvoiceFilters(
            $previousQuery,
            $filters
        );

        $currentSales = (float) $currentQuery
            ->sum('total_amount');

        $previousSales = (float) $previousQuery
            ->sum('total_amount');

        $change = $previousSales > 0
            ? (
                ($currentSales - $previousSales)
                / $previousSales
            ) * 100
            : (
                $currentSales > 0
                    ? 100
                    : 0
            );

        return [
            'previous_period_sales' => round(
                $previousSales,
                2
            ),

            'current_period_sales' => round(
                $currentSales,
                2
            ),

            'change_percentage' => round(
                $change,
                2
            ),

            'current_start' =>
                $currentStart->toDateString(),

            'current_end' =>
                $currentEnd->toDateString(),

            'previous_start' =>
                $previousStart->toDateString(),

            'previous_end' =>
                $previousEnd->toDateString(),
        ];
    }

    /**
     * الفلاتر غير الزمنية المستخدمة في المقارنة.
     */
    protected function applyNonDateInvoiceFilters(
        Builder $query,
        array $filters
    ): void {
        if (! empty(
            $filters['customer_id']
        )) {
            $query->where(
                'customer_id',
                $filters['customer_id']
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
    }

    /**
     * تحويل period إلى تاريخ بداية ونهاية للمقارنة.
     */
    protected function resolveDateRange(
        array $filters
    ): array {
        if (
            ! empty($filters['start_date'])
            || ! empty($filters['end_date'])
        ) {
            if (
                empty($filters['start_date'])
                || empty($filters['end_date'])
            ) {
                return [
                    null,
                    null,
                ];
            }

            return [
                Carbon::parse(
                    $filters['start_date']
                )->startOfDay(),

                Carbon::parse(
                    $filters['end_date']
                )->endOfDay(),
            ];
        }

        return match (
            $filters['period'] ?? null
        ) {
            'today' => [
                today()->startOfDay(),
                today()->endOfDay(),
            ],

            'yesterday' => [
                today()
                    ->subDay()
                    ->startOfDay(),

                today()
                    ->subDay()
                    ->endOfDay(),
            ],

            'last_7_days' => [
                now()
                    ->subDays(6)
                    ->startOfDay(),

                now()
                    ->endOfDay(),
            ],

            'this_week' => [
                now()
                    ->startOfWeek()
                    ->startOfDay(),

                now()
                    ->endOfWeek()
                    ->endOfDay(),
            ],

            'this_month' => [
                now()
                    ->startOfMonth()
                    ->startOfDay(),

                now()
                    ->endOfMonth()
                    ->endOfDay(),
            ],

            'last_month' => [
                now()
                    ->subMonthNoOverflow()
                    ->startOfMonth()
                    ->startOfDay(),

                now()
                    ->subMonthNoOverflow()
                    ->endOfMonth()
                    ->endOfDay(),
            ],

            'this_year' => [
                now()
                    ->startOfYear()
                    ->startOfDay(),

                now()
                    ->endOfYear()
                    ->endOfDay(),
            ],

            default => [
                null,
                null,
            ],
        };
    }

    /**
     * توحيد شكل الفلاتر قبل بناء مفتاح الكاش.
     */
    protected function normalizeFilters(
        array $filters
    ): array {
        $allowedKeys = [
            'period',
            'start_date',
            'end_date',
            'customer_id',
            'product_id',
            'category_id',
            'payment_status',
            'payment_method',
            'status',
        ];

        $normalized = [];

        foreach ($allowedKeys as $key) {
            if (
                array_key_exists(
                    $key,
                    $filters
                )
                && $filters[$key] !== null
                && $filters[$key] !== ''
            ) {
                $normalized[$key] =
                    $filters[$key];
            }
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * مسح كل كاش تقرير المبيعات.
     */
    public function clearCache(): bool
    {
        $keys = Cache::get(
            'sales_report_keys',
            []
        );

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget(
            'sales_report_keys'
        );

        return true;
    }

    /**
     * تسجيل مفتاح الكاش.
     */
    protected function storeCacheKey(
        string $key
    ): void {
        $keys = Cache::get(
            'sales_report_keys',
            []
        );

        if (! in_array(
            $key,
            $keys,
            true
        )) {
            $keys[] = $key;

            /*
             * نحافظ على عدد معقول من المفاتيح القديمة.
             */
            if (count($keys) > 100) {
                $keys = array_slice(
                    $keys,
                    -100
                );
            }

            Cache::put(
                'sales_report_keys',
                $keys,
                86400
            );
        }
    }
}
