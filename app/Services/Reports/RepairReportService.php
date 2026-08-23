<?php

namespace App\Services\Reports;

use App\Enums\RepairOrderStatus;
use App\Models\RepairOrder;
use App\Models\RepairPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepairReportService
{
    /**
     * تقرير الصيانة.
     *
     * حافظنا على منطق التقرير الأصلي، مع تحميل
     * التفاصيل اللازمة لقالب الطباعة الجديد وجعل
     * التحليلات تطابق نفس الطلبات المفلترة.
     */
    public function getReport(array $filters = []): array
    {
        $query = RepairOrder::query()
            ->with([
                'customer',
                'parts.product',
                'parts.warehouse',
                'payments.financialAccount',
            ]);

        $this->applyFilters(
            $query,
            $filters
        );

        $orders = $query
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();

        /*
         * قالب الطباعة الجديد يستخدم device_brand
         * و device_model، بينما نموذج المشروع الحالي
         * يستخدم brand و model.
         *
         * نضيف Alias فقط دون تغيير قاعدة البيانات.
         */
        $orders->each(
            function (RepairOrder $order): void {
                $order->setAttribute(
                    'device_brand',
                    $order->brand
                );

                $order->setAttribute(
                    'device_model',
                    $order->model
                );
            }
        );

        /*
         * إحصائيات الحالات.
         *
         * status في الموديل Cast إلى Enum لذلك
         * لا نعتمد على Collection::where مع String.
         */
        $received = $this->countByStatus(
            $orders,
            RepairOrderStatus::RECEIVED->value
        );

        $inProgress = $this->countByStatus(
            $orders,
            RepairOrderStatus::IN_PROGRESS->value
        );

        $ready = $this->countByStatus(
            $orders,
            RepairOrderStatus::READY->value
        );

        $delivered = $this->countByStatus(
            $orders,
            RepairOrderStatus::DELIVERED->value
        );

        $cancelled = $this->countByStatus(
            $orders,
            RepairOrderStatus::CANCELLED->value
        );

        /*
         * الأجهزة المتأخرة.
         */
        $overdue = $orders
            ->filter(
                fn (RepairOrder $order): bool =>
                    (bool) $order->is_overdue
            )
            ->count();

        /*
         * الإيرادات والأرباح للطلبات المسلمة فقط
         * مثل المنطق السابق.
         */
        $deliveredOrders = $orders
            ->filter(
                fn (RepairOrder $order): bool =>
                    $this->statusValue(
                        $order
                    )
                    === RepairOrderStatus::DELIVERED->value
            );

        $totalRevenue = (float) $deliveredOrders
            ->sum('total_amount');

        $totalCost = (float) $deliveredOrders
            ->sum('parts_cost');

        $totalProfit = (float) $deliveredOrders
            ->sum(
                fn (RepairOrder $order) =>
                    (float) $order->profit
            );

        /*
         * أكثر أنواع الأجهزة.
         */
        $deviceTypes = $orders
            ->filter(
                fn (RepairOrder $order): bool =>
                    filled($order->device_type)
            )
            ->groupBy(
                fn (RepairOrder $order): string =>
                    (string) $order->device_type
            )
            ->map(
                fn (Collection $group): int =>
                    $group->count()
            )
            ->sortDesc()
            ->take(10);

        /*
         * أكثر الأعطال تكراراً.
         */
        $faults = $orders
            ->filter(
                fn (RepairOrder $order): bool =>
                    filled($order->fault_cause)
            )
            ->groupBy(
                fn (RepairOrder $order): string =>
                    trim(
                        (string) $order->fault_cause
                    )
            )
            ->map(
                fn (Collection $group): int =>
                    $group->count()
            )
            ->sortDesc()
            ->take(10);

        /*
         * أكثر قطع الغيار استخداماً ضمن طلبات
         * الصيانة الموجودة فعلياً في التقرير.
         */
        $orderIds = $orders
            ->pluck('id')
            ->filter()
            ->values();

        $topParts = collect();

        if ($orderIds->isNotEmpty()) {
            $topParts = RepairPart::query()
                ->with('product')
                ->whereIn(
                    'repair_order_id',
                    $orderIds
                )
                ->select([
                    'product_id',

                    DB::raw(
                        'SUM(quantity) as total_quantity'
                    ),
                ])
                ->groupBy('product_id')
                ->orderByDesc(
                    'total_quantity'
                )
                ->limit(10)
                ->get();
        }

        /*
         * متوسط مدة الصيانة للطلبات المكتملة.
         */
        $completedOrders = $orders
            ->filter(
                fn (RepairOrder $order): bool =>
                    $order->completed_at !== null
                    && $order->received_at !== null
            );

        $averageDuration =
            $completedOrders->isNotEmpty()
                ? $completedOrders->avg(
                    fn (RepairOrder $order): float =>
                        (float) $order
                            ->received_at
                            ->diffInDays(
                                $order->completed_at
                            )
                )
                : 0;

        return [
            'summary' => [
                'received' => $received,

                'in_progress' =>
                    $inProgress,

                'ready' =>
                    $ready,

                'delivered' =>
                    $delivered,

                'cancelled' =>
                    $cancelled,

                'overdue' =>
                    $overdue,

                'total_revenue' => round(
                    $totalRevenue,
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

                'profit_margin' =>
                    $totalRevenue > 0
                        ? round(
                            ($totalProfit / $totalRevenue)
                            * 100,
                            2
                        )
                        : 0,

                'average_duration' => round(
                    (float) $averageDuration,
                    1
                ),

                'order_count' =>
                    $orders->count(),
            ],

            'device_types' =>
                $deviceTypes,

            'faults' =>
                $faults,

            'top_parts' =>
                $topParts,

            'orders' =>
                $orders,
        ];
    }

    /**
     * تطبيق جميع الفلاتر على طلبات الصيانة.
     */
    protected function applyFilters(
        Builder $query,
        array $filters
    ): void {
        $this->applyDateRange(
            $query,
            $filters,
            'received_at'
        );

        if (! empty(
            $filters['status']
        )) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (! empty(
            $filters['customer_id']
        )) {
            $query->where(
                'customer_id',
                $filters['customer_id']
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

    protected function applyDateRange(
        Builder $query,
        array $filters,
        string $column
    ): void {
        $startDate =
            $filters['start_date'] ?? null;

        $endDate =
            $filters['end_date'] ?? null;

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

    /**
     * القيمة النصية لحالة الطلب حتى لو كانت Cast إلى Enum.
     */
    protected function statusValue(
        RepairOrder $order
    ): string {
        return $order->status instanceof RepairOrderStatus
            ? $order->status->value
            : (string) $order->status;
    }

    protected function countByStatus(
        Collection $orders,
        string $status
    ): int {
        return $orders
            ->filter(
                fn (RepairOrder $order): bool =>
                    $this->statusValue(
                        $order
                    ) === $status
            )
            ->count();
    }
}
