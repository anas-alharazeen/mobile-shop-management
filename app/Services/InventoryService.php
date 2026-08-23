<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function addStock(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        string $reason,
        ?string $notes = null
    ): ProductStock {
        if ($quantity <= 0) {
            throw new \RuntimeException(
                'الكمية يجب أن تكون أكبر من صفر.'
            );
        }

        if (! $product->is_active) {
            throw new \RuntimeException(
                'لا يمكن إضافة كمية لمنتج غير نشط.'
            );
        }

        if (! $warehouse->is_active) {
            throw new \RuntimeException(
                'لا يمكن إضافة كمية إلى مخزن غير نشط.'
            );
        }

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new \RuntimeException(
                'سبب الإضافة مطلوب.'
            );
        }

        return DB::transaction(
            function () use (
                $product,
                $warehouse,
                $quantity,
                $reason,
                $notes
            ): ProductStock {
                /*
                 * قفل المنتج أولاً يمنع سباق إنشاء ProductStock
                 * إذا لم يكن للمنتج سجل سابق في المخزن.
                 */
                Product::query()
                    ->whereKey(
                        $product->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $stock =
                    ProductStock::query()
                        ->where(
                            'product_id',
                            $product->id
                        )
                        ->where(
                            'warehouse_id',
                            $warehouse->id
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $stock) {
                    $stock =
                        ProductStock::create([
                            'product_id' =>
                                $product->id,

                            'warehouse_id' =>
                                $warehouse->id,

                            'quantity' =>
                                0,
                        ]);
                }

                $before =
                    (int) $stock
                        ->quantity;

                $after =
                    $before
                    + $quantity;

                $stock->update([
                    'quantity' =>
                        $after,
                ]);

                StockMovement::create([
                    'product_id' =>
                        $product->id,

                    'warehouse_id' =>
                        $warehouse->id,

                    'type' =>
                        StockMovementType::MANUAL_ADDITION,

                    'quantity' =>
                        $quantity,

                    'quantity_before' =>
                        $before,

                    'quantity_after' =>
                        $after,

                    'notes' =>
                        $this
                            ->movementNotes(
                                $reason,
                                $notes
                            ),
                ]);

                return $stock
                    ->fresh();
            }
        );
    }

    public function deductStock(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        string $reason,
        StockMovementType $type,
        ?string $notes = null
    ): ProductStock {
        if ($quantity <= 0) {
            throw new \RuntimeException(
                'الكمية يجب أن تكون أكبر من صفر.'
            );
        }

        if (! $product->is_active) {
            throw new \RuntimeException(
                'لا يمكن خصم كمية من منتج غير نشط.'
            );
        }

        if (! $warehouse->is_active) {
            throw new \RuntimeException(
                'لا يمكن الخصم من مخزن غير نشط.'
            );
        }

        if (
            ! in_array(
                $type,
                [
                    StockMovementType::MANUAL_DEDUCTION,
                    StockMovementType::DAMAGED,
                    StockMovementType::LOST,
                ],
                true
            )
        ) {
            throw new \RuntimeException(
                'نوع الخصم اليدوي غير مسموح.'
            );
        }

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new \RuntimeException(
                'سبب الخصم مطلوب.'
            );
        }

        return DB::transaction(
            function () use (
                $product,
                $warehouse,
                $quantity,
                $reason,
                $type,
                $notes
            ): ProductStock {
                Product::query()
                    ->whereKey(
                        $product->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $stock =
                    ProductStock::query()
                        ->where(
                            'product_id',
                            $product->id
                        )
                        ->where(
                            'warehouse_id',
                            $warehouse->id
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $stock) {
                    throw new \RuntimeException(
                        'لا يوجد رصيد لهذا المنتج في المخزن.'
                    );
                }

                $before =
                    (int) $stock
                        ->quantity;

                if (
                    $before
                    < $quantity
                ) {
                    throw new \RuntimeException(
                        "الكمية المطلوبة ({$quantity}) أكبر من المتوفرة ({$before})."
                    );
                }

                $after =
                    $before
                    - $quantity;

                $stock->update([
                    'quantity' =>
                        $after,
                ]);

                StockMovement::create([
                    'product_id' =>
                        $product->id,

                    'warehouse_id' =>
                        $warehouse->id,

                    'type' =>
                        $type,

                    'quantity' =>
                        $quantity,

                    'quantity_before' =>
                        $before,

                    'quantity_after' =>
                        $after,

                    'notes' =>
                        $this
                            ->movementNotes(
                                $reason,
                                $notes
                            ),
                ]);

                return $stock
                    ->fresh();
            }
        );
    }

    public function transferStock(
        Product $product,
        Warehouse $sourceWarehouse,
        Warehouse $destinationWarehouse,
        int $quantity,
        string $reason,
        ?string $notes = null
    ): array {
        if ($quantity <= 0) {
            throw new \RuntimeException(
                'الكمية يجب أن تكون أكبر من صفر.'
            );
        }

        if (! $product->is_active) {
            throw new \RuntimeException(
                'لا يمكن نقل مخزون منتج غير نشط.'
            );
        }

        if (
            (int) $sourceWarehouse->id
            === (int) $destinationWarehouse->id
        ) {
            throw new \RuntimeException(
                'لا يمكن نقل المنتج إلى نفس المخزن.'
            );
        }

        if (
            ! $sourceWarehouse->is_active
            || ! $destinationWarehouse->is_active
        ) {
            throw new \RuntimeException(
                'لا يمكن تنفيذ النقل باستخدام مخزن غير نشط.'
            );
        }

        $reason =
            trim(
                $reason
            );

        if ($reason === '') {
            throw new \RuntimeException(
                'سبب النقل مطلوب.'
            );
        }

        return DB::transaction(
            function () use (
                $product,
                $sourceWarehouse,
                $destinationWarehouse,
                $quantity,
                $reason,
                $notes
            ): array {
                Product::query()
                    ->whereKey(
                        $product->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $warehouseIds =
                    collect([
                        (int) $sourceWarehouse
                            ->id,

                        (int) $destinationWarehouse
                            ->id,
                    ])
                        ->sort()
                        ->values();

                $stocks =
                    ProductStock::query()
                        ->where(
                            'product_id',
                            $product->id
                        )
                        ->whereIn(
                            'warehouse_id',
                            $warehouseIds
                        )
                        ->orderBy(
                            'warehouse_id'
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            'warehouse_id'
                        );

                $sourceStock =
                    $stocks->get(
                        $sourceWarehouse->id
                    );

                if (! $sourceStock) {
                    throw new \RuntimeException(
                        'لا يوجد رصيد لهذا المنتج في المخزن المصدر.'
                    );
                }

                $sourceBefore =
                    (int) $sourceStock
                        ->quantity;

                if (
                    $sourceBefore <= 0
                ) {
                    throw new \RuntimeException(
                        'رصيد المنتج في المخزن المصدر يساوي صفراً.'
                    );
                }

                if (
                    $quantity
                    > $sourceBefore
                ) {
                    throw new \RuntimeException(
                        "الكمية المطلوبة ({$quantity}) أكبر من الرصيد المتوفر ({$sourceBefore})."
                    );
                }

                $destinationStock =
                    $stocks->get(
                        $destinationWarehouse
                            ->id
                    );

                if (! $destinationStock) {
                    $destinationStock =
                        ProductStock::create([
                            'product_id' =>
                                $product->id,

                            'warehouse_id' =>
                                $destinationWarehouse
                                    ->id,

                            'quantity' =>
                                0,
                        ]);
                }

                $destinationBefore =
                    (int) $destinationStock
                        ->quantity;

                $sourceAfter =
                    $sourceBefore
                    - $quantity;

                $destinationAfter =
                    $destinationBefore
                    + $quantity;

                $pairTotalBefore =
                    $sourceBefore
                    + $destinationBefore;

                $sourceStock->update([
                    'quantity' =>
                        $sourceAfter,
                ]);

                $destinationStock->update([
                    'quantity' =>
                        $destinationAfter,
                ]);

                $pairTotalAfter =
                    $sourceAfter
                    + $destinationAfter;

                if (
                    $pairTotalBefore
                    !== $pairTotalAfter
                ) {
                    throw new \RuntimeException(
                        'حدث عدم تطابق في إجمالي المخزون أثناء النقل.'
                    );
                }

                $outMovement =
                    StockMovement::create([
                        'product_id' =>
                            $product->id,

                        'warehouse_id' =>
                            $sourceWarehouse->id,

                        'type' =>
                            StockMovementType::TRANSFER_OUT,

                        'quantity' =>
                            $quantity,

                        'quantity_before' =>
                            $sourceBefore,

                        'quantity_after' =>
                            $sourceAfter,

                        'notes' =>
                            $this
                                ->movementNotes(
                                    "تحويل إلى {$destinationWarehouse->name} - {$reason}",
                                    $notes
                                ),

                        'reference_type' =>
                            'stock_transfer',

                        'reference_id' =>
                            null,
                    ]);

                $referenceId =
                    (int) $outMovement
                        ->id;

                $outMovement->update([
                    'reference_id' =>
                        $referenceId,
                ]);

                $inMovement =
                    StockMovement::create([
                        'product_id' =>
                            $product->id,

                        'warehouse_id' =>
                            $destinationWarehouse
                                ->id,

                        'type' =>
                            StockMovementType::TRANSFER_IN,

                        'quantity' =>
                            $quantity,

                        'quantity_before' =>
                            $destinationBefore,

                        'quantity_after' =>
                            $destinationAfter,

                        'notes' =>
                            $this
                                ->movementNotes(
                                    "استلام من {$sourceWarehouse->name} - {$reason}",
                                    $notes
                                ),

                        'reference_type' =>
                            'stock_transfer',

                        'reference_id' =>
                            $referenceId,
                    ]);

                return [
                    'source_stock' =>
                        $sourceStock
                            ->fresh(),

                    'destination_stock' =>
                        $destinationStock
                            ->fresh(),

                    'out_movement' =>
                        $outMovement
                            ->fresh(),

                    'in_movement' =>
                        $inMovement
                            ->fresh(),

                    'reference' =>
                        'TRF-'
                        .str_pad(
                            (string) $referenceId,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),
                ];
            }
        );
    }

    public function markAsDamaged(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        string $reason,
        ?string $notes = null
    ): ProductStock {
        return $this
            ->deductStock(
                $product,
                $warehouse,
                $quantity,
                $reason,
                StockMovementType::DAMAGED,
                $notes
            );
    }

    public function markAsLost(
        Product $product,
        Warehouse $warehouse,
        int $quantity,
        string $reason,
        ?string $notes = null
    ): ProductStock {
        return $this
            ->deductStock(
                $product,
                $warehouse,
                $quantity,
                $reason,
                StockMovementType::LOST,
                $notes
            );
    }

    private function movementNotes(
        string $reason,
        ?string $notes
    ): string {
        $reason =
            trim(
                $reason
            );

        $notes =
            $notes !== null
                ? trim(
                    $notes
                )
                : '';

        return $notes !== ''
            ? "{$reason} - {$notes}"
            : $reason;
    }
}
