<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\RepairOrder;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly FinancialFlowSummaryService $financialFlowSummary
    ) {}

    /**
     * Dashboard is operational and should always reflect the latest data.
     *
     * We intentionally do NOT cache the dashboard payload.
     * This fixes stale "today" / "yesterday" values after new sales,
     * purchases, repairs, expenses, returns, or financial transactions.
     */
    public function getDashboardData(array $filters = []): array
    {
        return $this->generateDashboardData($filters);
    }

    /**
     * Kept for backward compatibility with older services that call
     * clearDashboardCache() after mutations.
     *
     * We also remove legacy keys left by the old cached implementation.
     */
    public function clearDashboardCache(): bool
    {
        $keys = Cache::get('dashboard_cache_keys', []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget('dashboard_cache_keys');

        return true;
    }

    protected function generateDashboardData(array $filters): array
    {
        [
            $period,
            $startDate,
            $endDate,
        ] = $this->resolveDateRange($filters);

        [
            $previousStartDate,
            $previousEndDate,
        ] = $this->previousDateRange(
            $startDate,
            $endDate
        );

        $stockHealth = $this->getStockHealth();

        $repairStats = $this->getRepairStats();

        return [
            'metrics' => $this->getMetrics(
                $startDate,
                $endDate,
                $previousStartDate,
                $previousEndDate
            ),

            'sales_chart' => $this->getSalesChart(
                $startDate,
                $endDate
            ),

            'sales_distribution' =>
            $this->getSalesDistribution(
                $startDate,
                $endDate
            ),

            /*
             * Repair status is a CURRENT snapshot,
             * not a historical period metric.
             */
            'repair_stats' =>
            $repairStats,

            'top_products' =>
            $this->getTopProducts(
                $startDate,
                $endDate
            ),

            /*
             * Stock levels are CURRENT snapshots.
             */
            'low_stock_products' =>
            $stockHealth['low'],

            'out_of_stock_products' =>
            $stockHealth['out'],

            'alerts' => $this->getAlerts(
                $stockHealth,
                $repairStats
            ),

            /*
             * Latest activities are intentionally "latest overall"
             * so the owner can see what just happened even if the
             * selected reporting period is different.
             */
            'recent_activities' =>
            $this->getRecentActivities(),

            /*
             * Period-aware financial flows + current account balances.
             */
            'financial_summary' =>
            $this->getFinancialSummary(
                $startDate,
                $endDate
            ),

            /*
             * Debts are CURRENT outstanding balances.
             */
            'receivables' =>
            $this->getReceivables(),

            'entity_counts' =>
            $this->getEntityCounts(),

            'period' =>
            $period,

            'date_range' => [
                'start' =>
                $startDate->toDateString(),

                'end' =>
                $endDate->toDateString(),
            ],

            'generated_at' =>
            now()->toIso8601String(),
        ];
    }

    /**
     * Resolve the selected dashboard period.
     *
     * Custom dates take priority over the period shortcut.
     */
    protected function resolveDateRange(
        array $filters
    ): array {
        $period =
            $filters['period'] ?? 'today';

        $start =
            $filters['start_date'] ?? null;

        $end =
            $filters['end_date'] ?? null;

        if ($start || $end) {
            $period = 'custom';

            $startDate = $start
                ? Carbon::parse($start)
                ->startOfDay()
                : Carbon::parse($end)
                ->startOfDay();

            $endDate = $end
                ? Carbon::parse($end)
                ->endOfDay()
                : now()->endOfDay();

            if ($endDate->lt($startDate)) {
                $temporary = $startDate;
                $startDate = $endDate
                    ->copy()
                    ->startOfDay();
                $endDate = $temporary
                    ->copy()
                    ->endOfDay();
            }

            return [
                $period,
                $startDate,
                $endDate,
            ];
        }

        $range = match ($period) {
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

                now()->endOfDay(),
            ],

            'this_week' => [
                now()
                    ->startOfWeek()
                    ->startOfDay(),

                now()->endOfDay(),
            ],

            'this_month' => [
                now()
                    ->startOfMonth()
                    ->startOfDay(),

                now()->endOfDay(),
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

                now()->endOfDay(),
            ],

            default => [
                today()->startOfDay(),
                today()->endOfDay(),
            ],
        };

        return [
            $period,
            $range[0],
            $range[1],
        ];
    }

    /**
     * Equal-length previous range for comparisons.
     */
    protected function previousDateRange(
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $days = $startDate
            ->copy()
            ->startOfDay()
            ->diffInDays(
                $endDate
                    ->copy()
                    ->startOfDay()
            ) + 1;

        $previousEnd = $startDate
            ->copy()
            ->subDay()
            ->endOfDay();

        $previousStart = $previousEnd
            ->copy()
            ->subDays($days - 1)
            ->startOfDay();

        return [
            $previousStart,
            $previousEnd,
        ];
    }

    /**
     * Apply a business-date filter.
     *
     * For older/imported records where the business date is NULL,
     * created_at is used as a safe fallback.
     */
    protected function applyBusinessDateFilter(
        $query,
        string $dateColumn,
        Carbon $startDate,
        Carbon $endDate,
        ?string $fallbackColumn = 'created_at'
    ): void {
        $fromDate =
            $startDate->toDateString();

        $toDate =
            $endDate->toDateString();

        $query->where(
            function ($dateQuery) use (
                $dateColumn,
                $fromDate,
                $toDate,
                $startDate,
                $endDate,
                $fallbackColumn
            ): void {
                $dateQuery->whereBetween(
                    $dateColumn,
                    [
                        $fromDate,
                        $toDate,
                    ]
                );

                if ($fallbackColumn) {
                    $dateQuery->orWhere(
                        function ($fallback) use (
                            $dateColumn,
                            $fallbackColumn,
                            $startDate,
                            $endDate
                        ): void {
                            $fallback
                                ->whereNull($dateColumn)
                                ->whereBetween(
                                    $fallbackColumn,
                                    [
                                        $startDate,
                                        $endDate,
                                    ]
                                );
                        }
                    );
                }
            }
        );
    }

    protected function getMetrics(
        Carbon $startDate,
        Carbon $endDate,
        Carbon $previousStartDate,
        Carbon $previousEndDate
    ): array {
        /*
         * =========================
         * Sales
         * =========================
         */
        $salesQuery = SalesInvoice::query()
            ->where('status', 'approved');

        $this->applyBusinessDateFilter(
            $salesQuery,
            'sale_date',
            $startDate,
            $endDate
        );

        $totalSales = (float) (
            clone $salesQuery
        )->sum('total_amount');

        $grossSalesProfit = (float) (
            clone $salesQuery
        )->sum('gross_profit');

        $salesCount = (
            clone $salesQuery
        )->count();

        /*
         * Previous product sales.
         */
        $previousSalesQuery =
            SalesInvoice::query()
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $previousSalesQuery,
            'sale_date',
            $previousStartDate,
            $previousEndDate
        );

        $previousGrossSales = (float) (
            clone $previousSalesQuery
        )->sum('total_amount');

        /*
         * =========================
         * Sales returns
         * =========================
         */
        $returnsQuery =
            SalesReturn::query()
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $returnsQuery,
            'return_date',
            $startDate,
            $endDate
        );

        $totalReturns = (float) (
            clone $returnsQuery
        )->sum('total_amount');

        $previousReturnsQuery =
            SalesReturn::query()
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $previousReturnsQuery,
            'return_date',
            $previousStartDate,
            $previousEndDate
        );

        $previousReturns = (float) (
            clone $previousReturnsQuery
        )->sum('total_amount');

        /*
         * Cost of returned items.
         */
        $returnedCostQuery = DB::table(
            'sales_return_items'
        )
            ->join(
                'sales_returns',
                'sales_return_items.sales_return_id',
                '=',
                'sales_returns.id'
            )
            ->join(
                'sales_invoice_items',
                'sales_return_items.sales_invoice_item_id',
                '=',
                'sales_invoice_items.id'
            )
            ->where(
                'sales_returns.status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $returnedCostQuery,
            'sales_returns.return_date',
            $startDate,
            $endDate,
            'sales_returns.created_at'
        );

        $returnedCost = (float)
        $returnedCostQuery->sum(
            DB::raw(
                'sales_return_items.quantity * sales_invoice_items.unit_cost'
            )
        );

        /*
         * Do NOT clamp net values to zero.
         * A period can legitimately contain more returns than sales.
         */
        $returnedProfit =
            $totalReturns
            - $returnedCost;

        $netSales =
            $totalSales
            - $totalReturns;

        $productProfit =
            $grossSalesProfit
            - $returnedProfit;

        $previousNetSales =
            $previousGrossSales
            - $previousReturns;

        /*
         * =========================
         * Repairs delivered in period
         * =========================
         */
        $repairQuery =
            RepairOrder::query()
            ->where(
                'status',
                'delivered'
            )
            ->whereBetween(
                'delivered_at',
                [
                    $startDate,
                    $endDate,
                ]
            );

        $repairRevenue = (float) (
            clone $repairQuery
        )->sum('total_amount');

        $repairPartsCost = (float) (
            clone $repairQuery
        )->sum('parts_cost');

        $repairProfit =
            $repairRevenue
            - $repairPartsCost;

        /*
         * =========================
         * Purchases
         * =========================
         */
        $purchaseQuery =
            PurchaseInvoice::query()
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $purchaseQuery,
            'purchase_date',
            $startDate,
            $endDate
        );

        $grossPurchases = (float) (
            clone $purchaseQuery
        )->sum('total_amount');

        $purchaseReturnsQuery =
            PurchaseReturn::query()
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $purchaseReturnsQuery,
            'return_date',
            $startDate,
            $endDate
        );

        $purchaseReturns = (float) (
            clone $purchaseReturnsQuery
        )->sum('total_amount');

        $totalPurchases =
            $grossPurchases
            - $purchaseReturns;

        /*
         * =========================
         * Expenses
         * =========================
         */
        $expensesQuery =
            Expense::query()
            ->where(
                'status',
                'posted'
            );

        $this->applyBusinessDateFilter(
            $expensesQuery,
            'expense_date',
            $startDate,
            $endDate
        );

        $totalExpenses = (float) (
            clone $expensesQuery
        )->sum('amount');

        /*
         * =========================
         * Final metrics
         * =========================
         */
        $totalRevenue =
            $netSales
            + $repairRevenue;

        $totalProfit =
            $productProfit
            + $repairProfit;

        $netProfit =
            $totalProfit
            - $totalExpenses;

        $salesChange =
            abs($previousNetSales) > 0.00001
            ? (
                (
                    $netSales
                    - $previousNetSales
                )
                / abs($previousNetSales)
            ) * 100
            : null;

        $averageTicket =
            $salesCount > 0
            ? $totalSales / $salesCount
            : 0;

        $profitMargin =
            abs($totalRevenue) > 0.00001
            ? (
                $totalProfit
                / $totalRevenue
            ) * 100
            : null;

        return [
            'total_sales' =>
            round($totalSales, 2),

            'net_sales' =>
            round($netSales, 2),

            'total_revenue' =>
            round($totalRevenue, 2),

            'repair_revenue' =>
            round($repairRevenue, 2),

            'product_profit' =>
            round($productProfit, 2),

            'repair_profit' =>
            round($repairProfit, 2),

            'total_profit' =>
            round($totalProfit, 2),

            'net_profit' =>
            round($netProfit, 2),

            'total_purchases' =>
            round($totalPurchases, 2),

            'gross_purchases' =>
            round($grossPurchases, 2),

            'purchase_returns' =>
            round($purchaseReturns, 2),

            'total_expenses' =>
            round($totalExpenses, 2),

            'sales_count' =>
            $salesCount,

            'average_ticket' =>
            round($averageTicket, 2),

            'sales_change' =>
            $salesChange === null
                ? null
                : round(
                    $salesChange,
                    2
                ),

            'profit_margin' =>
            $profitMargin === null
                ? null
                : round(
                    $profitMargin,
                    2
                ),

            'total_returns' =>
            round($totalReturns, 2),

            'inventory_value' =>
            round(
                $this->getInventoryValue(),
                2
            ),
        ];
    }

    /**
     * Current inventory value.
     */
    protected function getInventoryValue(): float
    {
        /*
         * قيمة المخزون يجب أن تطابق تقرير المخزون.
         * تعطيل المنتج يمنع استخدامه في عمليات جديدة، لكنه لا
         * يمحو قيمة الكمية الموجودة فعلياً في المستودعات.
         * أما Soft Deleted فلا يظهر في صفحات المخزون ولا يدخل هنا.
         */
        return (float) DB::table(
            'product_stocks'
        )
            ->join(
                'products',
                'product_stocks.product_id',
                '=',
                'products.id'
            )
            ->whereNull(
                'products.deleted_at'
            )
            ->selectRaw(
                'COALESCE(SUM(product_stocks.quantity * products.purchase_price), 0) AS total'
            )
            ->value('total');
    }

    protected function getSalesChart(
        Carbon $startDate,
        Carbon $endDate
    ): array {
        /*
         * Use business date with fallback to created_at.
         */
        $salesQuery =
            SalesInvoice::query()
            ->selectRaw(
                'DATE(COALESCE(sale_date, created_at)) as day, '
                    . 'SUM(total_amount) as total, '
                    . 'SUM(gross_profit) as profit'
            )
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $salesQuery,
            'sale_date',
            $startDate,
            $endDate
        );

        $sales = $salesQuery
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $returnsQuery =
            SalesReturn::query()
            ->selectRaw(
                'DATE(COALESCE(return_date, created_at)) as day, '
                    . 'SUM(total_amount) as total'
            )
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $returnsQuery,
            'return_date',
            $startDate,
            $endDate
        );

        $returns = $returnsQuery
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $returnedCostsQuery = DB::table(
            'sales_return_items'
        )
            ->join(
                'sales_returns',
                'sales_return_items.sales_return_id',
                '=',
                'sales_returns.id'
            )
            ->join(
                'sales_invoice_items',
                'sales_return_items.sales_invoice_item_id',
                '=',
                'sales_invoice_items.id'
            )
            ->where(
                'sales_returns.status',
                'approved'
            )
            ->selectRaw(
                'DATE(COALESCE(sales_returns.return_date, sales_returns.created_at)) as day, '
                    . 'SUM(sales_return_items.quantity * sales_invoice_items.unit_cost) as cost'
            );

        $this->applyBusinessDateFilter(
            $returnedCostsQuery,
            'sales_returns.return_date',
            $startDate,
            $endDate,
            'sales_returns.created_at'
        );

        $returnedCosts =
            $returnedCostsQuery
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $repairs = RepairOrder::query()
            ->selectRaw(
                'DATE(delivered_at) as day, '
                    . 'SUM(total_amount) as total, '
                    . 'SUM(parts_cost) as cost'
            )
            ->where(
                'status',
                'delivered'
            )
            ->whereBetween(
                'delivered_at',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $expensesQuery =
            Expense::query()
            ->selectRaw(
                'DATE(COALESCE(expense_date, created_at)) as day, '
                    . 'SUM(amount) as total'
            )
            ->where(
                'status',
                'posted'
            );

        $this->applyBusinessDateFilter(
            $expensesQuery,
            'expense_date',
            $startDate,
            $endDate
        );

        $expenses = $expensesQuery
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $revenueData = [];
        $profitData = [];
        $expensesData = [];

        for (
            $current = $startDate
                ->copy()
                ->startOfDay();
            $current->lte($endDate);
            $current->addDay()
        ) {
            $day =
                $current->toDateString();

            $grossSales = (float) (
                $sales->get($day)?->total ?? 0
            );

            $grossProfit = (float) (
                $sales->get($day)?->profit ?? 0
            );

            $returnAmount = (float) (
                $returns->get($day)?->total ?? 0
            );

            $returnCost = (float) (
                $returnedCosts->get($day)?->cost ?? 0
            );

            $repairRevenue = (float) (
                $repairs->get($day)?->total ?? 0
            );

            $repairCost = (float) (
                $repairs->get($day)?->cost ?? 0
            );

            $returnedProfit =
                $returnAmount
                - $returnCost;

            $labels[] =
                $current->format('d/m');

            $revenueData[] = round(
                (
                    $grossSales
                    - $returnAmount
                )
                    + $repairRevenue,
                2
            );

            $profitData[] = round(
                (
                    $grossProfit
                    - $returnedProfit
                )
                    + (
                        $repairRevenue
                        - $repairCost
                    ),
                2
            );

            $expensesData[] = round(
                (float) (
                    $expenses->get($day)?->total
                    ?? 0
                ),
                2
            );
        }

        return [
            'labels' =>
            $labels,

            /*
             * "sales" kept for backward compatibility.
             * It now represents total operational revenue
             * (net product sales + delivered repairs).
             */
            'sales' =>
            $revenueData,

            'revenue' =>
            $revenueData,

            'profit' =>
            $profitData,

            'expenses' =>
            $expensesData,
        ];
    }

    protected function getSalesDistribution(
        Carbon $startDate,
        Carbon $endDate
    ): array {
        /*
         * Product revenue by category.
         */
        $grossByCategory = DB::table(
            'sales_invoice_items'
        )
            ->join(
                'sales_invoices',
                'sales_invoice_items.sales_invoice_id',
                '=',
                'sales_invoices.id'
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
            ->where(
                'sales_invoices.status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $grossByCategory,
            'sales_invoices.sale_date',
            $startDate,
            $endDate,
            'sales_invoices.created_at'
        );

        $grossByCategory = $grossByCategory
            ->select([
                'categories.id',

                DB::raw(
                    "COALESCE(categories.name, 'بدون فئة') as name"
                ),

                DB::raw(
                    'SUM(sales_invoice_items.line_total) as total'
                ),
            ])
            ->groupBy(
                'categories.id',
                'categories.name'
            )
            ->get()
            ->keyBy(
                fn($row) =>
                $row->id ?? 'none'
            );

        $returnsByCategory = DB::table(
            'sales_return_items'
        )
            ->join(
                'sales_returns',
                'sales_return_items.sales_return_id',
                '=',
                'sales_returns.id'
            )
            ->join(
                'products',
                'sales_return_items.product_id',
                '=',
                'products.id'
            )
            ->leftJoin(
                'categories',
                'products.category_id',
                '=',
                'categories.id'
            )
            ->where(
                'sales_returns.status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $returnsByCategory,
            'sales_returns.return_date',
            $startDate,
            $endDate,
            'sales_returns.created_at'
        );

        $returnsByCategory =
            $returnsByCategory
            ->select([
                'categories.id',

                DB::raw(
                    'SUM(sales_return_items.total_amount) as total'
                ),
            ])
            ->groupBy(
                'categories.id'
            )
            ->get()
            ->keyBy(
                fn($row) =>
                $row->id ?? 'none'
            );

        $salesByCategory =
            $grossByCategory
            ->map(
                function ($row) use (
                    $returnsByCategory
                ): array {
                    $key =
                        $row->id ?? 'none';

                    return [
                        'name' =>
                        $row->name,

                        'total' => round(
                            (float) $row->total
                                - (float) (
                                    $returnsByCategory
                                    ->get($key)
                                    ?->total
                                    ?? 0
                                ),
                            2
                        ),
                    ];
                }
            )
            ->sortByDesc('total')
            ->values();

        /*
         * Collections during the selected period.
         * This is intentionally based on paid_at,
         * not invoice sale_date.
         */
        $paymentsByMethod =
            DB::table('sales_payments')
            ->join(
                'sales_invoices',
                'sales_payments.sales_invoice_id',
                '=',
                'sales_invoices.id'
            )
            ->where(
                'sales_invoices.status',
                'approved'
            )
            ->whereBetween(
                'sales_payments.paid_at',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->where(
                'sales_payments.payment_method',
                '!=',
                'exchange_credit'
            )
            ->select([
                'sales_payments.payment_method',

                DB::raw(
                    'SUM(sales_payments.amount) as total'
                ),

                DB::raw(
                    'COUNT(*) as payment_count'
                ),
            ])
            ->groupBy(
                'sales_payments.payment_method'
            )
            ->orderByDesc('total')
            ->get();

        $salesQuery =
            SalesInvoice::query()
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $salesQuery,
            'sale_date',
            $startDate,
            $endDate
        );

        $returnsQuery =
            SalesReturn::query()
            ->where(
                'status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $returnsQuery,
            'return_date',
            $startDate,
            $endDate
        );

        $netProductSales =
            (float) (
                clone $salesQuery
            )->sum('total_amount')
            - (float) (
                clone $returnsQuery
            )->sum('total_amount');

        $repairRevenue = (float)
        RepairOrder::query()
            ->where(
                'status',
                'delivered'
            )
            ->whereBetween(
                'delivered_at',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->sum('total_amount');

        return [
            'by_category' =>
            $salesByCategory,

            'by_payment_method' =>
            $paymentsByMethod,

            'revenue_sources' => [
                'sales' =>
                round(
                    $netProductSales,
                    2
                ),

                'repairs' =>
                round(
                    $repairRevenue,
                    2
                ),
            ],
        ];
    }

    protected function getRepairStats(): array
    {
        $counts = RepairOrder::query()
            ->selectRaw(
                'status, COUNT(*) as total'
            )
            ->groupBy('status')
            ->pluck(
                'total',
                'status'
            );

        $statuses = [
            'received' => (int) (
                $counts['received'] ?? 0
            ),

            'in_progress' => (int) (
                $counts['in_progress'] ?? 0
            ),

            'ready' => (int) (
                $counts['ready'] ?? 0
            ),

            'delivered' => (int) (
                $counts['delivered'] ?? 0
            ),

            'cancelled' => (int) (
                $counts['cancelled'] ?? 0
            ),
        ];

        $overdue = RepairOrder::query()
            ->whereNotIn(
                'status',
                [
                    'delivered',
                    'cancelled',
                ]
            )
            ->whereNotNull(
                'expected_delivery_date'
            )
            ->whereDate(
                'expected_delivery_date',
                '<',
                today()
            )
            ->count();

        $waitingApproval =
            RepairOrder::query()
            ->whereNotIn(
                'status',
                [
                    'delivered',
                    'cancelled',
                ]
            )
            ->where(
                'customer_approval_status',
                'pending'
            )
            ->count();

        $waitingParts =
            RepairOrder::query()
            ->whereNotIn(
                'status',
                [
                    'delivered',
                    'cancelled',
                ]
            )
            ->where(
                'sub_status',
                'waiting_part'
            )
            ->count();

        $recentOrders =
            RepairOrder::query()
            ->with('customer')
            ->where(
                'status',
                '!=',
                'cancelled'
            )
            ->latest('created_at')
            ->limit(5)
            ->get();

        return [
            'statuses' =>
            $statuses,

            'active_total' =>
            $statuses['received']
                + $statuses['in_progress']
                + $statuses['ready'],

            'overdue' =>
            $overdue,

            'waiting_approval' =>
            $waitingApproval,

            'waiting_parts' =>
            $waitingParts,

            'recent_orders' =>
            $recentOrders,

            'ready_orders' =>
            $statuses['ready'],
        ];
    }

    protected function getTopProducts(
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $sold = DB::table(
            'sales_invoice_items'
        )
            ->join(
                'sales_invoices',
                'sales_invoice_items.sales_invoice_id',
                '=',
                'sales_invoices.id'
            )
            ->join(
                'products',
                'sales_invoice_items.product_id',
                '=',
                'products.id'
            )
            ->where(
                'sales_invoices.status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $sold,
            'sales_invoices.sale_date',
            $startDate,
            $endDate,
            'sales_invoices.created_at'
        );

        $sold = $sold
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.image_path',

                DB::raw(
                    'SUM(sales_invoice_items.quantity) as total_quantity'
                ),

                DB::raw(
                    'SUM(sales_invoice_items.line_total) as total_revenue'
                ),

                DB::raw(
                    'SUM(sales_invoice_items.line_profit) as total_profit'
                ),
            ])
            ->groupBy(
                'products.id',
                'products.name',
                'products.code',
                'products.image_path'
            )
            ->get()
            ->keyBy('id');

        $returned = DB::table(
            'sales_return_items'
        )
            ->join(
                'sales_returns',
                'sales_return_items.sales_return_id',
                '=',
                'sales_returns.id'
            )
            ->join(
                'sales_invoice_items',
                'sales_return_items.sales_invoice_item_id',
                '=',
                'sales_invoice_items.id'
            )
            ->where(
                'sales_returns.status',
                'approved'
            );

        $this->applyBusinessDateFilter(
            $returned,
            'sales_returns.return_date',
            $startDate,
            $endDate,
            'sales_returns.created_at'
        );

        $returned = $returned
            ->select([
                'sales_return_items.product_id',

                DB::raw(
                    'SUM(sales_return_items.quantity) as returned_quantity'
                ),

                DB::raw(
                    'SUM(sales_return_items.total_amount) as returned_revenue'
                ),

                DB::raw(
                    'SUM(sales_return_items.total_amount - (sales_return_items.quantity * sales_invoice_items.unit_cost)) as returned_profit'
                ),
            ])
            ->groupBy(
                'sales_return_items.product_id'
            )
            ->get()
            ->keyBy('product_id');

        return $sold
            ->map(
                function ($row) use (
                    $returned
                ) {
                    $return =
                        $returned->get(
                            $row->id
                        );

                    $row->total_quantity =
                        (int) $row->total_quantity
                        - (int) (
                            $return
                            ?->returned_quantity
                            ?? 0
                        );

                    $row->total_revenue =
                        round(
                            (float) $row->total_revenue
                                - (float) (
                                    $return
                                    ?->returned_revenue
                                    ?? 0
                                ),
                            2
                        );

                    $row->total_profit =
                        round(
                            (float) $row->total_profit
                                - (float) (
                                    $return
                                    ?->returned_profit
                                    ?? 0
                                ),
                            2
                        );

                    return $row;
                }
            )
            ->filter(
                fn($row) =>
                $row->total_quantity > 0
            )
            ->sortByDesc(
                'total_quantity'
            )
            ->take(6)
            ->values();
    }

    /**
     * Load active products once and derive both low/out-of-stock lists.
     */
    protected function getStockHealth(): array
    {
        $products = Product::query()
            ->where(
                'is_active',
                true
            )
            ->with([
                'category',
                'stocks.warehouse',
            ])
            ->get();

        $products->each(
            function (Product $product): void {
                $product->setAttribute(
                    'total_stock',
                    (int) $product
                        ->stocks
                        ->sum('quantity')
                );
            }
        );

        $low = $products
            ->filter(
                function (Product $product): bool {
                    $threshold = (int) (
                        $product
                        ->low_stock_threshold
                        ?? 5
                    );

                    return $product
                        ->total_stock > 0
                        && $product
                        ->total_stock
                        <= $threshold;
                }
            )
            ->sortBy('total_stock')
            ->values();

        $out = $products
            ->filter(
                fn(Product $product): bool =>
                $product->total_stock <= 0
            )
            ->values();

        return [
            'low' => $low,
            'out' => $out,
        ];
    }

    protected function getAlerts(
        array $stockHealth,
        array $repairStats
    ): array {
        $alerts = [];

        if ($stockHealth['low']->isNotEmpty()) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'high',
                'title' => 'منتجات منخفضة المخزون',
                'description' =>
                'هناك '
                    . $stockHealth['low']->count()
                    . ' منتج منخفض المخزون',
                'link' => route(
                    'inventory.index',
                    [
                        'stock_status' =>
                        'منخفض',
                    ]
                ),
            ];
        }

        if ($stockHealth['out']->isNotEmpty()) {
            $alerts[] = [
                'type' => 'danger',
                'priority' => 'high',
                'title' => 'منتجات نافدة',
                'description' =>
                'هناك '
                    . $stockHealth['out']->count()
                    . ' منتج نافد',
                'link' => route(
                    'inventory.index',
                    [
                        'stock_status' =>
                        'نافد',
                    ]
                ),
            ];
        }

        if ($repairStats['overdue'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'priority' => 'high',
                'title' => 'أجهزة صيانة متأخرة',
                'description' =>
                'هناك '
                    . $repairStats['overdue']
                    . ' جهاز صيانة متأخر',
                'link' => route(
                    'repairs.index',
                    [
                        'overdue' =>
                        true,
                    ]
                ),
            ];
        }

        if ($repairStats['ready_orders'] > 0) {
            $alerts[] = [
                'type' => 'info',
                'priority' => 'medium',
                'title' => 'أجهزة جاهزة للاستلام',
                'description' =>
                'هناك '
                    . $repairStats['ready_orders']
                    . ' جهاز جاهز للاستلام',
                'link' => route(
                    'repairs.index',
                    [
                        'status' =>
                        'ready',
                    ]
                ),
            ];
        }

        $overdueSalesReceivables =
            SalesInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->count();

        $overdueRepairReceivables =
            RepairOrder::query()
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
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->count();

        $overdueCustomerDocuments =
            $overdueSalesReceivables
            + $overdueRepairReceivables;

        if ($overdueCustomerDocuments > 0) {
            $parts = [];

            if ($overdueSalesReceivables > 0) {
                $parts[] =
                    $overdueSalesReceivables
                    . ' بيع';
            }

            if ($overdueRepairReceivables > 0) {
                $parts[] =
                    $overdueRepairReceivables
                    . ' صيانة';
            }

            $alerts[] = [
                'type' => 'warning',
                'priority' => 'medium',
                'title' => 'مستحقات عملاء متأخرة',
                'description' =>
                'هناك '
                    . $overdueCustomerDocuments
                    . ' مستحق متأخر ('
                    . implode(' + ', $parts)
                    . ')',
                'link' => route(
                    'payments.customer-receivables',
                    [
                        'overdue_only' =>
                        true,
                    ]
                ),
            ];
        }

        $overdueSupplierInvoices =
            PurchaseInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->count();

        if ($overdueSupplierInvoices > 0) {
            $alerts[] = [
                'type' => 'warning',
                'priority' => 'medium',
                'title' => 'مستحقات موردين متأخرة',
                'description' =>
                'هناك '
                    . $overdueSupplierInvoices
                    . ' فاتورة شراء متأخرة السداد',
                'link' => route(
                    'payments.supplier-payables',
                    [
                        'overdue_only' =>
                        true,
                    ]
                ),
            ];
        }

        $inventoryCounts =
            InventoryCount::query()
            ->where(
                'status',
                'in_progress'
            )
            ->count();

        if ($inventoryCounts > 0) {
            $alerts[] = [
                'type' => 'info',
                'priority' => 'low',
                'title' => 'جلسات جرد قيد التنفيذ',
                'description' =>
                'هناك '
                    . $inventoryCounts
                    . ' جلسة جرد قيد التنفيذ',
                'link' => route(
                    'inventory-counts.index',
                    [
                        'status' =>
                        'in_progress',
                    ]
                ),
            ];
        }

        return $alerts;
    }

    protected function getRecentActivities(): array
    {
        /*
         * بطاقات "أحدث العمليات" تعرض مستندات فعلية فقط؛
         * Draft/Cancelled لا يجب أن تبدو كأنها بيع أو شراء مكتمل.
         */
        return [
            'sales' => SalesInvoice::query()
                ->with('customer')
                ->where(
                    'status',
                    'approved'
                )
                ->latest('created_at')
                ->limit(5)
                ->get(),

            'purchases' =>
            PurchaseInvoice::query()
                ->with('supplier')
                ->where(
                    'status',
                    'approved'
                )
                ->latest('created_at')
                ->limit(5)
                ->get(),

            'repairs' =>
            RepairOrder::query()
                ->with('customer')
                ->where(
                    'status',
                    '!=',
                    'cancelled'
                )
                ->latest('created_at')
                ->limit(5)
                ->get(),

            'expenses' =>
            Expense::query()
                ->with('category')
                ->where(
                    'status',
                    'posted'
                )
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ];
    }

    protected function getFinancialSummary(
        Carbon $startDate,
        Carbon $endDate
    ): array {
        /*
         * الحساب غير النشط لا يختفي مالياً؛ هو فقط غير متاح
         * للعمليات الجديدة. لذلك إجمالي الأرصدة يشمل الجميع.
         */
        $accounts =
            FinancialAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $totalBalance = (float)
        $accounts->sum(
            'current_balance'
        );

        $cashBalance = (float)
        $accounts
            ->filter(
                fn(
                    FinancialAccount $account
                ): bool => (
                    $account->type?->value
                    ?? $account->type
                ) === 'cash'
            )
            ->sum(
                'current_balance'
            );

        /*
         * كل أرقام حركة الفترة تأتي من Ledger المالي نفسه،
         * مع ربط الـREVERSAL بنوع الحركة الأصلية.
         */
        $flowSummary =
            $this
            ->financialFlowSummary
            ->summarize(
                $startDate,
                $endDate
            );

        $recentTransactions =
            FinancialTransaction::query()
            ->with('account')
            ->latest('transaction_date')
            ->latest('id')
            ->limit(6)
            ->get();

        return [
            'accounts' =>
            $accounts,

            'total_balance' =>
            round(
                $totalBalance,
                2
            ),

            'cash_balance' =>
            round(
                $cashBalance,
                2
            ),

            'period_inflows' =>
            (float) $flowSummary['total_inflows'],

            'period_outflows' =>
            (float) $flowSummary['total_outflows'],

            'net_flow' =>
            (float) $flowSummary['net_cash_flow'],

            'period_expenses' =>
            (float) $flowSummary['expenses'],

            'collected_sales' =>
            (float) $flowSummary['collected_sales'],

            'collected_repairs' =>
            (float) $flowSummary['collected_repairs'],

            'paid_to_suppliers' =>
            (float) $flowSummary['paid_to_suppliers'],

            'sales_refunds' =>
            (float) $flowSummary['sales_refunds'],

            'purchase_refunds' =>
            (float) $flowSummary['purchase_refunds'],

            'reversal_count' =>
            (int) $flowSummary['reversal_count'],

            /*
             * Legacy aliases for old UI compatibility.
             */
            'today_inflows' =>
            (float) $flowSummary['total_inflows'],

            'today_outflows' =>
            (float) $flowSummary['total_outflows'],

            'today_expenses' =>
            (float) $flowSummary['expenses'],

            'recent_transactions' =>
            $recentTransactions,
        ];
    }

    protected function getReceivables(): array
    {
        $salesQuery =
            SalesInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            );

        $repairsQuery =
            RepairOrder::query()
            ->where(
                'status',
                '!=',
                'cancelled'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            );

        $supplierQuery =
            PurchaseInvoice::query()
            ->where(
                'status',
                'approved'
            )
            ->where(
                'remaining_amount',
                '>',
                0
            );

        $salesDebts =
            (float) (
                clone $salesQuery
            )->sum(
                'remaining_amount'
            );

        $repairDebts =
            (float) (
                clone $repairsQuery
            )->sum(
                'remaining_amount'
            );

        $supplierDebts =
            (float) (
                clone $supplierQuery
            )->sum(
                'remaining_amount'
            );

        $overdueSales =
            (clone $salesQuery)
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->count();

        $overdueRepairs =
            (clone $repairsQuery)
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->count();

        $overdueSuppliers =
            (clone $supplierQuery)
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->count();

        $customersWithDue =
            Customer::query()
            ->where(
                function ($query): void {
                    $query
                        ->whereHas(
                            'salesInvoices',
                            fn($invoiceQuery) =>
                            $invoiceQuery
                                ->where(
                                    'status',
                                    'approved'
                                )
                                ->where(
                                    'remaining_amount',
                                    '>',
                                    0
                                )
                        )
                        ->orWhereHas(
                            'repairOrders',
                            fn($repairQuery) =>
                            $repairQuery
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
                        );
                }
            )
            ->count();

        $suppliersWithDue =
            Supplier::query()
            ->whereHas(
                'purchaseInvoices',
                fn($invoiceQuery) =>
                $invoiceQuery
                    ->where(
                        'status',
                        'approved'
                    )
                    ->where(
                        'remaining_amount',
                        '>',
                        0
                    )
            )
            ->count();

        return [
            'customer_debts' =>
            round(
                $salesDebts
                    + $repairDebts,
                2
            ),

            'sales_debts' =>
            round(
                $salesDebts,
                2
            ),

            'repair_debts' =>
            round(
                $repairDebts,
                2
            ),

            'supplier_debts' =>
            round(
                $supplierDebts,
                2
            ),

            'customer_due_documents' => (clone $salesQuery)->count()
                + (clone $repairsQuery)->count(),

            'supplier_due_documents' => (clone $supplierQuery)->count(),

            'customers_with_due' =>
            $customersWithDue,

            'suppliers_with_due' =>
            $suppliersWithDue,

            'overdue_customer_documents' =>
            $overdueSales
                + $overdueRepairs,

            'overdue_sales_documents' =>
            $overdueSales,

            'overdue_repair_documents' =>
            $overdueRepairs,

            'overdue_supplier_documents' =>
            $overdueSuppliers,
        ];
    }

    protected function getEntityCounts(): array
    {
        return [
            'products' =>
            Product::query()
                ->where(
                    'is_active',
                    true
                )
                ->count(),

            'customers' =>
            Customer::query()
                ->where(
                    'is_active',
                    true
                )
                ->count(),

            'suppliers' =>
            Supplier::query()
                ->where(
                    'is_active',
                    true
                )
                ->count(),

            'financial_accounts' =>
            FinancialAccount::query()
                ->active()
                ->count(),
        ];
    }
}
