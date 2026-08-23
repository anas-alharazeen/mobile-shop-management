<?php

namespace App\Services\Reports;

use App\Enums\WarehouseType;
use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;

class InventoryReportService
{
    /**
     * تقرير المخزون.
     *
     * - أرصدة وقيمة المخزون هي Snapshot حالي.
     * - الفترة الزمنية تؤثر على الحركات وعمليات الجرد فقط.
     * - عند اختيار مخزن، الحالة والقيمة والكمية تُحسب
     *   بالنسبة لذلك المخزن فقط.
     */
    public function getReport(
        array $filters = []
    ): array {
        $warehouseId =
            ! empty($filters['warehouse_id'])
            ? (int) $filters['warehouse_id']
            : null;

        /*
         * نظهر المخازن النشطة دائماً، ونبقي أي مخزن غير نشط
         * ما زال يحتوي رصيداً حتى لا تختفي قيمة مخزون حقيقية.
         */
        $warehouses =
            Warehouse::query()
            ->where(
                function (
                    Builder $query
                ): void {
                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->orWhereHas(
                            'stocks',
                            fn(Builder $stockQuery) =>
                            $stockQuery->where(
                                'quantity',
                                '!=',
                                0
                            )
                        );
                }
            )
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $selectedWarehouse =
            $warehouseId
            ? $warehouses
            ->firstWhere(
                'id',
                $warehouseId
            )
            : null;

        $products =
            Product::query()
            ->with([
                'category',
                'stocks.warehouse',
            ])
            ->when(
                ! empty($filters['category_id']),
                fn(Builder $query) =>
                $query->where(
                    'category_id',
                    $filters['category_id']
                )
            )
            ->when(
                ! empty($filters['product_id']),
                fn(Builder $query) =>
                $query->where(
                    'id',
                    $filters['product_id']
                )
            )
            ->orderBy('name')
            ->get();

        $totalInventoryValue = 0.0;
        $salesWarehouseValue = 0.0;
        $maintenanceWarehouseValue = 0.0;
        $totalPieces = 0;

        $inventoryData = [];

        foreach (
            $products
            as $product
        ) {
            $stocks =
                $warehouseId
                ? $product
                ->stocks
                ->where(
                    'warehouse_id',
                    $warehouseId
                )
                ->values()
                : $product
                ->stocks;

            $salesQty =
                (int) $stocks
                    ->filter(
                        fn($stock): bool =>
                        $this
                            ->warehouseTypeValue(
                                $stock
                                    ->warehouse
                                    ?->type
                            )
                            === WarehouseType
                            ::SALES
                            ->value
                    )
                    ->sum(
                        'quantity'
                    );

            $maintenanceQty =
                (int) $stocks
                    ->filter(
                        fn($stock): bool =>
                        $this
                            ->warehouseTypeValue(
                                $stock
                                    ->warehouse
                                    ?->type
                            )
                            === WarehouseType
                            ::MAINTENANCE
                            ->value
                    )
                    ->sum(
                        'quantity'
                    );

            /*
             * مجموع الرصيد الفعلي في نطاق التقرير.
             * لا نحصره في sales + maintenance فقط حتى يبقى
             * الكود آمناً إذا أضيف نوع مخزن جديد مستقبلاً.
             */
            $totalQty =
                (int) $stocks
                    ->sum(
                        'quantity'
                    );

            $purchasePrice =
                (float) (
                    $product
                    ->purchase_price
                    ?? 0
                );

            $salesValue =
                $purchasePrice
                * $salesQty;

            $maintenanceValue =
                $purchasePrice
                * $maintenanceQty;

            $totalValue =
                $purchasePrice
                * $totalQty;

            $totalInventoryValue +=
                $totalValue;

            $salesWarehouseValue +=
                $salesValue;

            $maintenanceWarehouseValue +=
                $maintenanceValue;

            $totalPieces +=
                $totalQty;

            $threshold =
                (int) (
                    $product
                    ->low_stock_threshold
                    ?? 5
                );

            $stockStatus =
                $this->stockStatus(
                    $totalQty,
                    $threshold
                );

            /*
             * هذه الخصائص هي كمية/قيمة نطاق التقرير نفسه.
             * تستخدمها الواجهة في منخفض/نافد حتى لا تعود
             * وتجمع مخازن خارج الفلتر المحدد.
             */
            $product->setAttribute(
                'report_stock_quantity',
                $totalQty
            );

            $product->setAttribute(
                'report_inventory_value',
                round(
                    $totalValue,
                    2
                )
            );

            $product->setAttribute(
                'report_stock_status',
                $stockStatus
            );

            $inventoryData[] = [
                'product' =>
                $product,

                'sales_qty' =>
                $salesQty,

                'maintenance_qty' =>
                $maintenanceQty,

                'total_qty' =>
                $totalQty,

                'sales_value' =>
                round(
                    $salesValue,
                    2
                ),

                'maintenance_value' =>
                round(
                    $maintenanceValue,
                    2
                ),

                'total_value' =>
                round(
                    $totalValue,
                    2
                ),

                'stock_status' =>
                $stockStatus,
            ];
        }

        $lowStockProducts =
            $products
            ->filter(
                fn(Product $product): bool =>
                $product
                    ->getAttribute(
                        'report_stock_status'
                    )
                    === 'low'
            )
            ->values();

        $outOfStockProducts =
            $products
            ->filter(
                fn(Product $product): bool =>
                $product
                    ->getAttribute(
                        'report_stock_status'
                    )
                    === 'out'
            )
            ->values();

        /*
         * تفصيل المخزون حسب كل مستودع.
         * يحترم فلاتر المنتج والفئة الحالية.
         */
        $warehouseBreakdown =
            $warehouses
            ->when(
                $warehouseId,
                fn($collection) =>
                $collection->where(
                    'id',
                    $warehouseId
                )
            )
            ->map(
                function (
                    Warehouse $warehouse
                ) use (
                    $products
                ): array {
                    $pieces = 0;
                    $value = 0.0;
                    $productsWithStock = 0;

                    foreach (
                        $products
                        as $product
                    ) {
                        $quantity =
                            (int) $product
                                ->stocks
                                ->where(
                                    'warehouse_id',
                                    $warehouse->id
                                )
                                ->sum(
                                    'quantity'
                                );

                        if ($quantity !== 0) {
                            $productsWithStock++;
                        }

                        $pieces +=
                            $quantity;

                        $value +=
                            $quantity
                            * (float) (
                                $product
                                ->purchase_price
                                ?? 0
                            );
                    }

                    return [
                        'id' =>
                        $warehouse->id,

                        'name' =>
                        $warehouse->name,

                        'type' =>
                        $this
                            ->warehouseTypeValue(
                                $warehouse
                                    ->type
                            ),

                        'is_active' =>
                        (bool) $warehouse
                            ->is_active,

                        'pieces' =>
                        $pieces,

                        'value' =>
                        round(
                            $value,
                            2
                        ),

                        'products_with_stock' =>
                        $productsWithStock,
                    ];
                }
            )
            ->values();

        /*
         * حركات المخزون:
         * الفترة + الفئة + المنتج + المستودع.
         */
        $recentMovementsQuery =
            StockMovement::query()
            ->with([
                'product.category',
                'warehouse',
            ])
            ->when(
                ! empty($filters['product_id']),
                fn(Builder $query) =>
                $query->where(
                    'product_id',
                    $filters['product_id']
                )
            )
            ->when(
                ! empty($filters['category_id']),
                fn(Builder $query) =>
                $query->whereHas(
                    'product',
                    fn(Builder $productQuery) =>
                    $productQuery->where(
                        'category_id',
                        $filters['category_id']
                    )
                )
            )
            ->when(
                $warehouseId,
                fn(Builder $query) =>
                $query->where(
                    'warehouse_id',
                    $warehouseId
                )
            );

        $this->applyDateFilter(
            $recentMovementsQuery,
            $filters,
            'created_at'
        );

        $movementTotalCount =
            (clone $recentMovementsQuery)
            ->count();

        $recentMovements =
            $recentMovementsQuery
            ->orderByDesc(
                'created_at'
            )
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $recentMovements->each(
            function (
                StockMovement $movement
            ): void {
                $movement->setAttribute(
                    'balance_before',
                    (int) (
                        $movement
                        ->quantity_before
                        ?? 0
                    )
                );

                $movement->setAttribute(
                    'balance_after',
                    (int) (
                        $movement
                        ->quantity_after
                        ?? 0
                    )
                );
            }
        );

        /*
         * عمليات الجرد تستخدم count_date كتاريخ العملية.
         */
        $recentCountsQuery =
            InventoryCount::query()
            ->with('warehouse')
            ->where(
                'status',
                'completed'
            )
            ->when(
                $warehouseId,
                fn(Builder $query) =>
                $query->where(
                    'warehouse_id',
                    $warehouseId
                )
            )
            ->when(
                ! empty($filters['category_id']),
                fn(Builder $query) =>
                $query->where(
                    'category_id',
                    $filters['category_id']
                )
            )
            ->when(
                ! empty($filters['product_id']),
                fn(Builder $query) =>
                $query->whereHas(
                    'items',
                    fn(Builder $itemQuery) =>
                    $itemQuery->where(
                        'product_id',
                        $filters['product_id']
                    )
                )
            );

        $this->applyDateFilter(
            $recentCountsQuery,
            $filters,
            'count_date'
        );

        $recentCounts =
            $recentCountsQuery
            ->orderByDesc(
                'count_date'
            )
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            'summary' => [
                'total_inventory_value' =>
                round(
                    $totalInventoryValue,
                    2
                ),

                'sales_warehouse_value' =>
                round(
                    $salesWarehouseValue,
                    2
                ),

                'maintenance_warehouse_value' =>
                round(
                    $maintenanceWarehouseValue,
                    2
                ),

                'total_pieces' =>
                $totalPieces,

                'low_stock_count' =>
                $lowStockProducts
                    ->count(),

                'out_of_stock_count' =>
                $outOfStockProducts
                    ->count(),

                'product_count' =>
                $products->count(),

                'movement_count' =>
                $movementTotalCount,
            ],

            'inventory_data' =>
            $inventoryData,

            'low_stock_products' =>
            $lowStockProducts,

            'out_of_stock_products' =>
            $outOfStockProducts,

            'recent_movements' =>
            $recentMovements,

            'recent_counts' =>
            $recentCounts,

            'warehouse_breakdown' =>
            $warehouseBreakdown,

            'warehouses' =>
            $warehouses,

            'selected_warehouse' =>
            $selectedWarehouse,

            'inventory_snapshot_at' =>
            now()->toIso8601String(),
        ];
    }

    private function stockStatus(
        int $quantity,
        int $threshold
    ): string {
        if ($quantity <= 0) {
            return 'out';
        }

        if ($quantity <= $threshold) {
            return 'low';
        }

        return 'available';
    }

    protected function applyDateFilter(
        Builder $query,
        array $filters,
        string $column
    ): void {
        $startDate =
            $filters['start_date']
            ?? null;

        $endDate =
            $filters['end_date']
            ?? null;

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

        $period =
            $filters['period']
            ?? null;

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

            default =>
            null,
        };
    }

    protected function warehouseTypeValue(
        mixed $type
    ): ?string {
        if (
            $type
            instanceof WarehouseType
        ) {
            return $type->value;
        }

        return $type !== null
            ? (string) $type
            : null;
    }
}
