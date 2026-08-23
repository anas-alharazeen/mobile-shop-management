<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Requests\AddStockRequest;
use App\Http\Requests\DeductStockRequest;
use App\Http\Requests\TransferStockRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    public function index(
        Request $request
    ): Response {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'warehouses',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'stock_status' => [
                'nullable',
                'string',
                Rule::in([
                    'available',
                    'low',
                    'out',
                    'متوفر',
                    'منخفض',
                    'نافد',
                ]),
            ],
        ]);

        $warehouseId =
            ! empty(
                $filters['warehouse_id']
            )
                ? (int) $filters[
                    'warehouse_id'
                ]
                : null;

        $stockStatus = match (
            $filters['stock_status']
            ?? null
        ) {
            'available',
            'متوفر' =>
                'available',

            'low',
            'منخفض' =>
                'low',

            'out',
            'نافد' =>
                'out',

            default =>
                null,
        };

        $query = Product::query()
            ->with([
                'category:id,name',
                'stocks.warehouse:id,name,type,is_active',
            ])
            ->withSum(
                'stocks as total_stock_quantity',
                'quantity'
            )
            ->when(
                $warehouseId,
                fn (Builder $query) =>
                    $query->withSum(
                        [
                            'stocks as filtered_warehouse_stock' =>
                                fn (Builder $stockQuery) =>
                                    $stockQuery->where(
                                        'warehouse_id',
                                        $warehouseId
                                    ),
                        ],
                        'quantity'
                    )
            )
            ->search(
                $filters['search']
                ?? null
            )
            ->category(
                $filters['category_id']
                ?? null
            );

        /*
         * إذا اختار المستخدم مخزناً:
         * حالة "متوفر / منخفض / نافد" تُحسب بالنسبة لذلك المخزن.
         *
         * لا نستخدم whereHas(quantity > 0) لأن ذلك كان يمنع
         * ظهور المنتجات النافدة داخل المخزن المحدد.
         */
        if ($stockStatus) {
            if ($warehouseId) {
                $stockExpression =
                    'COALESCE((
                        SELECT SUM(ps.quantity)
                        FROM product_stocks ps
                        WHERE ps.product_id = products.id
                          AND ps.warehouse_id = ?
                    ), 0)';

                $bindings = [
                    $warehouseId,
                ];
            } else {
                $stockExpression =
                    'COALESCE((
                        SELECT SUM(ps.quantity)
                        FROM product_stocks ps
                        WHERE ps.product_id = products.id
                    ), 0)';

                $bindings = [];
            }

            if (
                $stockStatus
                === 'available'
            ) {
                $query->whereRaw(
                    "{$stockExpression} > COALESCE(products.low_stock_threshold, 5)",
                    $bindings
                );
            }

            if (
                $stockStatus
                === 'low'
            ) {
                $query
                    ->whereRaw(
                        "{$stockExpression} > 0",
                        $bindings
                    )
                    ->whereRaw(
                        "{$stockExpression} <= COALESCE(products.low_stock_threshold, 5)",
                        $bindings
                    );
            }

            if (
                $stockStatus
                === 'out'
            ) {
                $query->whereRaw(
                    "{$stockExpression} <= 0",
                    $bindings
                );
            }
        }

        $products = $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $products
            ->getCollection()
            ->transform(
                function (
                    Product $product
                ) use (
                    $warehouseId
                ): Product {
                    /*
                     * لا نستخدم first() لأن النظام قد يحتوي
                     * أكثر من مخزن من نفس النوع.
                     */
                    $salesStock =
                        (int) $product
                            ->stocks
                            ->filter(
                                fn ($stock): bool =>
                                    $this
                                        ->warehouseTypeValue(
                                            $stock
                                                ->warehouse
                                                ?->type
                                        )
                                    === 'sales'
                            )
                            ->sum(
                                'quantity'
                            );

                    $maintenanceStock =
                        (int) $product
                            ->stocks
                            ->filter(
                                fn ($stock): bool =>
                                    $this
                                        ->warehouseTypeValue(
                                            $stock
                                                ->warehouse
                                                ?->type
                                        )
                                    === 'maintenance'
                            )
                            ->sum(
                                'quantity'
                            );

                    $totalStock =
                        (int) $product
                            ->stocks
                            ->sum(
                                'quantity'
                            );

                    $warehouseStock =
                        $warehouseId
                            ? (int) $product
                                ->stocks
                                ->where(
                                    'warehouse_id',
                                    $warehouseId
                                )
                                ->sum(
                                    'quantity'
                                )
                            : null;

                    $displayStock =
                        $warehouseId
                            ? $warehouseStock
                            : $totalStock;

                    $threshold =
                        (int) (
                            $product
                                ->low_stock_threshold
                            ?? 5
                        );

                    $displayStatus =
                        $this->stockStatus(
                            $displayStock,
                            $threshold
                        );

                    $product->setAttribute(
                        'sales_stock',
                        $salesStock
                    );

                    $product->setAttribute(
                        'maintenance_stock',
                        $maintenanceStock
                    );

                    $product->setAttribute(
                        'total_stock',
                        $totalStock
                    );

                    $product->setAttribute(
                        'filtered_warehouse_stock',
                        $warehouseStock
                    );

                    $product->setAttribute(
                        'display_stock_status',
                        $displayStatus
                    );

                    $product->setAttribute(
                        'inventory_value',
                        round(
                            (float) $product
                                ->purchase_price
                            * $totalStock,
                            2
                        )
                    );

                    return $product;
                }
            );

        /*
         * إحصائيات الصفحة يجب أن تجمع كل المخازن من نفس النوع،
         * لا أول مخزن فقط.
         */
        $allProducts = Product::query()
            ->with([
                'stocks.warehouse:id,name,type,is_active',
            ])
            ->get();

        $salesValue = 0.0;
        $maintenanceValue = 0.0;
        $totalValue = 0.0;
        $totalPieces = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $productsWithStock = 0;

        foreach (
            $allProducts as $product
        ) {
            $salesQty =
                (int) $product
                    ->stocks
                    ->filter(
                        fn ($stock): bool =>
                            $this
                                ->warehouseTypeValue(
                                    $stock
                                        ->warehouse
                                        ?->type
                                )
                            === 'sales'
                    )
                    ->sum('quantity');

            $maintenanceQty =
                (int) $product
                    ->stocks
                    ->filter(
                        fn ($stock): bool =>
                            $this
                                ->warehouseTypeValue(
                                    $stock
                                        ->warehouse
                                        ?->type
                                )
                            === 'maintenance'
                    )
                    ->sum('quantity');

            $totalQty =
                (int) $product
                    ->stocks
                    ->sum('quantity');

            $cost =
                (float) (
                    $product
                        ->purchase_price
                    ?? 0
                );

            $salesValue +=
                $cost
                * $salesQty;

            $maintenanceValue +=
                $cost
                * $maintenanceQty;

            $totalValue +=
                $cost
                * $totalQty;

            $totalPieces +=
                $totalQty;

            if ($totalQty > 0) {
                $productsWithStock++;
            }

            $threshold =
                (int) (
                    $product
                        ->low_stock_threshold
                    ?? 5
                );

            if ($totalQty <= 0) {
                $outOfStockCount++;
            } elseif (
                $totalQty
                <= $threshold
            ) {
                $lowStockCount++;
            }
        }

        $activeWarehouses =
            Warehouse::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('type')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'type',
                    'is_active',
                ]);

        $warehouseSummary =
            $activeWarehouses
                ->map(
                    function (
                        Warehouse $warehouse
                    ) use (
                        $allProducts
                    ): array {
                        $pieces = 0;
                        $value = 0.0;
                        $skuCount = 0;

                        foreach (
                            $allProducts as $product
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

                            if ($quantity > 0) {
                                $skuCount++;
                            }

                            $pieces +=
                                $quantity;

                            $value +=
                                (float) (
                                    $product
                                        ->purchase_price
                                    ?? 0
                                )
                                * $quantity;
                        }

                        return [
                            'id' =>
                                $warehouse->id,

                            'name' =>
                                $warehouse->name,

                            'type' =>
                                $this
                                    ->warehouseTypeValue(
                                        $warehouse->type
                                    ),

                            'type_label' =>
                                $this
                                    ->warehouseTypeLabel(
                                        $warehouse->type
                                    ),

                            'pieces' =>
                                $pieces,

                            'sku_count' =>
                                $skuCount,

                            'value' =>
                                round(
                                    $value,
                                    2
                                ),
                        ];
                    }
                )
                ->values();

        $recentMovements =
            StockMovement::query()
                ->with([
                    'product:id,name,code',
                    'warehouse:id,name,type',
                ])
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(
                    function (
                        StockMovement $movement
                    ): StockMovement {
                        $type =
                            $movement->type;

                        $movement->setAttribute(
                            'type_value',
                            $type
                            instanceof StockMovementType
                                ? $type->value
                                : (string) $type
                        );

                        $movement->setAttribute(
                            'type_label',
                            $type
                            instanceof StockMovementType
                                ? $type->label()
                                : (string) $type
                        );

                        $movement->setAttribute(
                            'is_addition',
                            $type
                            instanceof StockMovementType
                                ? $type->isAddition()
                                : false
                        );

                        return $movement;
                    }
                );

        /*
         * المنتجات المستخدمة في العمليات اليدوية:
         * نرسل المنتجات النشطة فقط.
         */
        $operationProducts =
            Product::query()
                ->active()
                ->with([
                    'category:id,name',
                    'stocks.warehouse:id,name,type,is_active',
                ])
                ->orderBy('name')
                ->get()
                ->map(
                    function (
                        Product $product
                    ): Product {
                        $product->setAttribute(
                            'total_stock',
                            (int) $product
                                ->stocks
                                ->sum(
                                    'quantity'
                                )
                        );

                        return $product;
                    }
                )
                ->values();

        $transferProducts =
            $operationProducts
                ->filter(
                    fn (Product $product): bool =>
                        (int) $product
                            ->total_stock
                        > 0
                )
                ->values();

        $selectedWarehouse =
            $warehouseId
                ? $activeWarehouses
                    ->firstWhere(
                        'id',
                        $warehouseId
                    )
                : null;

        return Inertia::render(
            'Inventory/Index',
            [
                'products' =>
                    $products,

                'stats' => [
                    'total_value' =>
                        round(
                            $totalValue,
                            2
                        ),

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

                    'total_pieces' =>
                        $totalPieces,

                    'products_with_stock' =>
                        $productsWithStock,

                    'low_stock' =>
                        $lowStockCount,

                    'out_of_stock' =>
                        $outOfStockCount,

                    'today_movements' =>
                        StockMovement::query()
                            ->whereDate(
                                'created_at',
                                today()
                            )
                            ->count(),
                ],

                'filters' => [
                    'search' =>
                        $filters['search']
                        ?? '',

                    'category_id' =>
                        $filters['category_id']
                        ?? '',

                    'warehouse_id' =>
                        $warehouseId
                        ?? '',

                    'stock_status' =>
                        $stockStatus
                        ?? '',
                ],

                'warehouses' =>
                    $activeWarehouses,

                'selectedWarehouse' =>
                    $selectedWarehouse,

                'warehouseSummary' =>
                    $warehouseSummary,

                'recentMovements' =>
                    $recentMovements,

                'operationProducts' =>
                    $operationProducts,

                'transferProducts' =>
                    $transferProducts,

                'categories' =>
                    Category::query()
                        ->active()
                        ->orderBy('name')
                        ->get([
                            'id',
                            'name',
                        ]),
            ]
        );
    }

    public function movements(
        Request $request
    ): Response {
        $query =
            StockMovement::query()
                ->with([
                    'product',
                    'warehouse',
                ])
                ->latest('id');

        if (
            $request->filled(
                'product_id'
            )
        ) {
            $query->where(
                'product_id',
                $request->integer(
                    'product_id'
                )
            );
        }

        if (
            $request->filled(
                'warehouse_id'
            )
        ) {
            $query->where(
                'warehouse_id',
                $request->integer(
                    'warehouse_id'
                )
            );
        }

        if (
            $request->filled(
                'type'
            )
        ) {
            $query->where(
                'type',
                $request->input(
                    'type'
                )
            );
        }

        if (
            $request->filled(
                'start_date'
            )
        ) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->input(
                    'start_date'
                )
            );
        }

        if (
            $request->filled(
                'end_date'
            )
        ) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->input(
                    'end_date'
                )
            );
        }

        return Inertia::render(
            'Inventory/Movements',
            [
                'movements' =>
                    $query
                        ->paginate(20)
                        ->withQueryString(),

                'filters' => [
                    'product_id' =>
                        $request->input(
                            'product_id'
                        ),

                    'warehouse_id' =>
                        $request->input(
                            'warehouse_id'
                        ),

                    'type' =>
                        $request->input(
                            'type'
                        ),

                    'start_date' =>
                        $request->input(
                            'start_date'
                        ),

                    'end_date' =>
                        $request->input(
                            'end_date'
                        ),
                ],

                'products' =>
                    Product::query()
                        ->orderBy('name')
                        ->get([
                            'id',
                            'name',
                            'code',
                        ]),

                'warehouses' =>
                    Warehouse::query()
                        ->orderByDesc(
                            'is_active'
                        )
                        ->orderBy('name')
                        ->get(),

                'types' =>
                    StockMovementType::labels(),
            ]
        );
    }

    public function addStock(
        AddStockRequest $request
    ) {
        $validated =
            $request->validated();

        $product =
            Product::query()
                ->active()
                ->findOrFail(
                    $validated[
                        'product_id'
                    ]
                );

        $warehouse =
            Warehouse::query()
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail(
                    $validated[
                        'warehouse_id'
                    ]
                );

        try {
            $stock =
                $this
                    ->inventoryService
                    ->addStock(
                        $product,
                        $warehouse,
                        (int) $validated[
                            'quantity'
                        ],
                        $validated[
                            'reason'
                        ],
                        $validated[
                            'notes'
                        ] ?? null
                    );

            return redirect()
                ->back()
                ->with(
                    'success',
                    "تمت إضافة {$validated['quantity']} قطعة إلى {$warehouse->name}. الرصيد الجديد: {$stock->quantity}."
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function deductStock(
        DeductStockRequest $request
    ) {
        $validated =
            $request->validated();

        $product =
            Product::query()
                ->active()
                ->findOrFail(
                    $validated[
                        'product_id'
                    ]
                );

        $warehouse =
            Warehouse::query()
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail(
                    $validated[
                        'warehouse_id'
                    ]
                );

        $type =
            StockMovementType::from(
                $validated['type']
            );

        try {
            $stock =
                $this
                    ->inventoryService
                    ->deductStock(
                        $product,
                        $warehouse,
                        (int) $validated[
                            'quantity'
                        ],
                        $validated[
                            'reason'
                        ],
                        $type,
                        $validated[
                            'notes'
                        ] ?? null
                    );

            return redirect()
                ->back()
                ->with(
                    'success',
                    "تم خصم {$validated['quantity']} قطعة من {$warehouse->name}. الرصيد الجديد: {$stock->quantity}."
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function transferStock(
        TransferStockRequest $request
    ) {
        $validated =
            $request->validated();

        $product =
            Product::query()
                ->active()
                ->findOrFail(
                    $validated[
                        'product_id'
                    ]
                );

        $sourceWarehouse =
            Warehouse::query()
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail(
                    $validated[
                        'source_warehouse_id'
                    ]
                );

        $destinationWarehouse =
            Warehouse::query()
                ->where(
                    'is_active',
                    true
                )
                ->findOrFail(
                    $validated[
                        'destination_warehouse_id'
                    ]
                );

        try {
            $result =
                $this
                    ->inventoryService
                    ->transferStock(
                        $product,
                        $sourceWarehouse,
                        $destinationWarehouse,
                        (int) $validated[
                            'quantity'
                        ],
                        $validated[
                            'reason'
                        ],
                        $validated[
                            'notes'
                        ] ?? null
                    );

            return redirect()
                ->back()
                ->with(
                    'success',
                    sprintf(
                        'تم نقل %s قطعة من %s إلى %s بنجاح. مرجع النقل: %s',
                        number_format(
                            (int) $validated[
                                'quantity'
                            ]
                        ),
                        $sourceWarehouse
                            ->name,
                        $destinationWarehouse
                            ->name,
                        $result['reference']
                    )
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    private function stockStatus(
        int $quantity,
        int $threshold
    ): array {
        if ($quantity <= 0) {
            return [
                'label' =>
                    'نافد',

                'color' =>
                    'red',
            ];
        }

        if (
            $quantity
            <= $threshold
        ) {
            return [
                'label' =>
                    'منخفض',

                'color' =>
                    'orange',
            ];
        }

        return [
            'label' =>
                'متوفر',

            'color' =>
                'green',
        ];
    }

    private function warehouseTypeValue(
        mixed $type
    ): string {
        if (
            is_object($type)
            && property_exists(
                $type,
                'value'
            )
        ) {
            return (string) $type->value;
        }

        return (string) (
            $type
            ?? ''
        );
    }

    private function warehouseTypeLabel(
        mixed $type
    ): string {
        return match (
            $this->warehouseTypeValue(
                $type
            )
        ) {
            'sales' =>
                'مبيعات',

            'maintenance' =>
                'صيانة',

            default =>
                'مخزن',
        };
    }
}
