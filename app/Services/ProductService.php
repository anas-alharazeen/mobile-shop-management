<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function create(
        array $data,
        $image = null
    ): Product {
        $imagePath = null;

        if ($image) {
            $imagePath =
                $image->store(
                    'products',
                    'public'
                );
        }

        try {
            return DB::transaction(
                function () use (
                    $data,
                    $imagePath
                ): Product {
                    $openingStocks =
                        collect(
                            $data['opening_stocks'] ?? []
                        )
                        ->map(
                            fn(array $row): array => [
                                'warehouse_id' =>
                                (int) $row['warehouse_id'],

                                'quantity' =>
                                (int) $row['quantity'],
                            ]
                        )
                        ->filter(
                            fn(array $row): bool =>
                            $row['quantity']
                                > 0
                        )
                        ->values();

                    if (
                        $openingStocks
                        ->sum('quantity')
                        > 0
                        && (float) $data['purchase_price'] <= 0
                    ) {
                        throw new \RuntimeException(
                            'لا يمكن تسجيل رصيد افتتاحي بسعر شراء يساوي صفراً.'
                        );
                    }

                    $product =
                        Product::create([
                            'category_id' =>
                            (int) $data['category_id'],

                            'name' =>
                            trim(
                                $data['name']
                            ),

                            'code' =>
                            $this
                                ->resolveCode(
                                    $data['code']
                                        ?? null
                                ),

                            'barcode' =>
                            $this
                                ->nullableString(
                                    $data['barcode']
                                        ?? null
                                ),

                            'brand' =>
                            $this
                                ->nullableString(
                                    $data['brand']
                                        ?? null
                                ),

                            'model' =>
                            $this
                                ->nullableString(
                                    $data['model']
                                        ?? null
                                ),

                            'purchase_price' =>
                            round(
                                (float) $data['purchase_price'],
                                2
                            ),

                            'selling_price' =>
                            round(
                                (float) $data['selling_price'],
                                2
                            ),

                            'minimum_selling_price' =>
                            isset(
                                $data['minimum_selling_price']
                            )
                                && $data['minimum_selling_price'] !== ''
                                ? round(
                                    (float) $data['minimum_selling_price'],
                                    2
                                )
                                : null,

                            'low_stock_threshold' =>
                            (int) (
                                $data['low_stock_threshold'] ?? 5
                            ),

                            'location' =>
                            $this
                                ->nullableString(
                                    $data['location']
                                        ?? null
                                ),

                            'image_path' =>
                            $imagePath,

                            'description' =>
                            $this
                                ->nullableString(
                                    $data['description'] ?? null
                                ),

                            'notes' =>
                            $this
                                ->nullableString(
                                    $data['notes']
                                        ?? null
                                ),

                            'is_active' =>
                            (bool) (
                                $data['is_active']
                                ?? true
                            ),
                        ]);

                    if (
                        $openingStocks
                        ->isNotEmpty()
                    ) {
                        $warehouses =
                            Warehouse::query()
                            ->whereIn(
                                'id',
                                $openingStocks
                                    ->pluck(
                                        'warehouse_id'
                                    )
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->lockForUpdate()
                            ->get()
                            ->keyBy('id');

                        if (
                            $warehouses->count()
                            !== $openingStocks
                            ->pluck(
                                'warehouse_id'
                            )
                            ->unique()
                            ->count()
                        ) {
                            throw new \RuntimeException(
                                'تعذر العثور على أحد المخازن النشطة المحددة للرصيد الافتتاحي.'
                            );
                        }

                        foreach (
                            $openingStocks as $row
                        ) {
                            $warehouse =
                                $warehouses->get(
                                    $row['warehouse_id']
                                );

                            ProductStock::create([
                                'product_id' =>
                                $product->id,

                                'warehouse_id' =>
                                $warehouse->id,

                                'quantity' =>
                                $row['quantity'],
                            ]);

                            StockMovement::create([
                                'product_id' =>
                                $product->id,

                                'warehouse_id' =>
                                $warehouse->id,

                                'type' =>
                                StockMovementType::OPENING,

                                'quantity' =>
                                $row['quantity'],

                                'quantity_before' =>
                                0,

                                'quantity_after' =>
                                $row['quantity'],

                                'notes' =>
                                "رصيد افتتاحي عند إنشاء المنتج - {$warehouse->name}",
                            ]);
                        }
                    }

                    return $product
                        ->fresh()
                        ->load([
                            'category',
                            'stocks.warehouse',
                        ]);
                }
            );
        } catch (\Throwable $e) {
            /*
             * قاعدة البيانات تعمل داخل Transaction،
             * أما الملف على storage فلا يتم Rollback تلقائياً.
             */
            if ($imagePath) {
                Storage::disk(
                    'public'
                )->delete(
                    $imagePath
                );
            }

            throw $e;
        }
    }

    public function update(
        Product $product,
        array $data,
        $image = null
    ): Product {
        $newImagePath = null;

        if ($image) {
            $newImagePath =
                $image->store(
                    'products',
                    'public'
                );
        }

        $oldImagePath =
            $product->image_path;

        try {
            $updated =
                DB::transaction(
                    function () use (
                        $product,
                        $data,
                        $newImagePath
                    ): Product {
                        $lockedProduct =
                            Product::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $product->id
                            );

                        /*
                         * purchase_price هو متوسط تكلفة حالي للمنتج.
                         * بمجرد وجود أي حركة مخزون، لا نسمح بتغييره
                         * يدوياً من صفحة المنتج حتى لا تتغير قيمة
                         * المخزون والأرباح بلا مستند مالي حقيقي.
                         */
                        $purchasePriceLocked =
                            $lockedProduct
                                ->movements()
                                ->exists();

                        $purchasePrice =
                            $purchasePriceLocked
                                ? (float) $lockedProduct
                                    ->purchase_price
                                : round(
                                    (float) $data[
                                        'purchase_price'
                                    ],
                                    2
                                );

                        $lockedProduct->update([
                            'category_id' =>
                            (int) $data['category_id'],

                            'name' =>
                            trim(
                                $data['name']
                            ),

                            'code' =>
                            mb_strtoupper(
                                trim(
                                    $data['code']
                                )
                            ),

                            'barcode' =>
                            $this
                                ->nullableString(
                                    $data['barcode']
                                        ?? null
                                ),

                            'brand' =>
                            $this
                                ->nullableString(
                                    $data['brand']
                                        ?? null
                                ),

                            'model' =>
                            $this
                                ->nullableString(
                                    $data['model']
                                        ?? null
                                ),

                            'purchase_price' =>
                            $purchasePrice,

                            'selling_price' =>
                            round(
                                (float) $data['selling_price'],
                                2
                            ),

                            'minimum_selling_price' =>
                            isset(
                                $data['minimum_selling_price']
                            )
                                && $data['minimum_selling_price'] !== ''
                                ? round(
                                    (float) $data['minimum_selling_price'],
                                    2
                                )
                                : null,

                            'low_stock_threshold' =>
                            (int) (
                                $data['low_stock_threshold'] ?? 5
                            ),

                            'location' =>
                            $this
                                ->nullableString(
                                    $data['location']
                                        ?? null
                                ),

                            'image_path' =>
                            $newImagePath
                                ?? $lockedProduct
                                ->image_path,

                            'description' =>
                            $this
                                ->nullableString(
                                    $data['description'] ?? null
                                ),

                            'notes' =>
                            $this
                                ->nullableString(
                                    $data['notes']
                                        ?? null
                                ),

                            'is_active' =>
                            (bool) (
                                $data['is_active']
                                ?? true
                            ),
                        ]);

                        return $lockedProduct
                            ->fresh();
                    }
                );

            if (
                $newImagePath
                && $oldImagePath
                && $oldImagePath
                !== $newImagePath
            ) {
                Storage::disk(
                    'public'
                )->delete(
                    $oldImagePath
                );
            }

            return $updated;
        } catch (\Throwable $e) {
            if ($newImagePath) {
                Storage::disk(
                    'public'
                )->delete(
                    $newImagePath
                );
            }

            throw $e;
        }
    }

    public function delete(
        Product $product
    ): void {
        DB::transaction(
            function () use (
                $product
            ): void {
                /*
                 * Soft Delete يبقي السجلات المرتبطة تاريخياً.
                 * لا نحذف الصورة هنا حتى تظل صفحات التاريخ
                 * قادرة على عرضها ما دام المنتج Soft Deleted.
                 */
                $product->delete();
            }
        );
    }

    public function toggleStatus(
        Product $product
    ): Product {
        $product->update([
            'is_active' =>
            ! $product->is_active,
        ]);

        return $product;
    }

    private function resolveCode(
        ?string $code
    ): string {
        $normalized =
            $this->nullableString(
                $code
            );

        if ($normalized) {
            return mb_strtoupper(
                $normalized
            );
        }

        do {
            $generated =
                'PRD-'
                . now()->format(
                    'ymd'
                )
                . '-'
                . Str::upper(
                    Str::random(5)
                );
        } while (
            Product::withTrashed()
            ->where(
                'code',
                $generated
            )
            ->exists()
        );

        return $generated;
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}
