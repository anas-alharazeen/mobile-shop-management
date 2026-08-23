<?php

namespace App\Services;

use App\Enums\InventoryCountStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryCountService
{
    /**
     * إنشاء جلسة جرد جديدة
     */
    public function create(array $data): InventoryCount
    {
        return DB::transaction(function () use ($data) {
            $referenceNumber = $this->generateReferenceNumber();

            $inventoryCount = InventoryCount::create([
                'reference_number' => $referenceNumber,
                'warehouse_id' => $data['warehouse_id'],
                'category_id' => $data['category_id'] ?? null,
                'status' => InventoryCountStatus::IN_PROGRESS,
                'count_date' => $data['count_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            // جلب المنتجات المناسبة
            $products = $this->getProductsForCount(
                $data['warehouse_id'],
                $data['category_id'] ?? null
            );

            // إنشاء عناصر الجرد
            foreach ($products as $product) {
                $stock = ProductStock::where('product_id', $product->id)
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                InventoryCountItem::create([
                    'inventory_count_id' => $inventoryCount->id,
                    'product_id' => $product->id,
                    'system_quantity' => $stock ? $stock->quantity : 0,
                    'unit_cost' => $product->purchase_price,
                ]);
            }

            return $inventoryCount->load('items.product');
        });
    }

    /**
     * جلب المنتجات المناسبة للجرد
     */
    protected function getProductsForCount($warehouseId, $categoryId = null)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->whereHas('stocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * توليد رقم جرد فريد
     */
    protected function generateReferenceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = Str::upper(Str::random(6));

        $reference = "{$prefix}-{$date}-{$random}";

        // التأكد من عدم التكرار
        while (InventoryCount::where('reference_number', $reference)->exists()) {
            $random = Str::upper(Str::random(6));
            $reference = "{$prefix}-{$date}-{$random}";
        }

        return $reference;
    }

    /**
     * تحديث الكمية الفعلية لمنتج في الجرد
     */
    public function updateItem(InventoryCountItem $item, int $actualQuantity, ?string $notes = null): InventoryCountItem
    {
        if ($item->inventoryCount->status === InventoryCountStatus::COMPLETED) {
            throw new \Exception('لا يمكن تعديل جرد معتمد');
        }

        if ($actualQuantity < 0) {
            throw new \Exception('الكمية الفعلية لا يمكن أن تكون سالبة');
        }

        $item->actual_quantity = $actualQuantity;
        $item->notes = $notes ?? $item->notes;
        $item->calculateDifference();

        return $item->fresh();
    }

    /**
     * اعتماد الجرد
     */
    public function complete(InventoryCount $inventoryCount): InventoryCount
    {
        if ($inventoryCount->status === InventoryCountStatus::COMPLETED) {
            throw new \Exception('تم اعتماد هذا الجرد مسبقاً');
        }

        // التحقق من اكتمال جميع العناصر
        $uncompleted = $inventoryCount->items()->whereNull('actual_quantity')->exists();
        if ($uncompleted) {
            throw new \Exception('يجب إدخال الكمية الفعلية لجميع المنتجات قبل الاعتماد');
        }

        return DB::transaction(function () use ($inventoryCount) {
            // قفل جميع الأرصدة
            $warehouseId = $inventoryCount->warehouse_id;

            // التحقق من تغير الأرصدة أثناء الجرد
            $changedItems = $this->checkForChanges($inventoryCount, $warehouseId);

            if ($changedItems->isNotEmpty()) {
                throw new \Exception(
                    'تغير رصيد بعض المنتجات بعد بدء الجرد. يرجى مراجعة المنتجات المتأثرة قبل الاعتماد.'
                );
            }

            // تحديث الأرصدة وإنشاء الحركات
            foreach ($inventoryCount->items as $item) {
                if ($item->difference == 0) {
                    continue;
                }

                // قفل الرصيد
                $stock = ProductStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    $stock = ProductStock::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouseId,
                        'quantity' => 0,
                    ]);
                }

                $quantityBefore = $stock->quantity;
                $quantityAfter = $item->actual_quantity;

                // تحديث الرصيد
                $stock->update(['quantity' => $quantityAfter]);

                // تحديد نوع الحركة
                $type = $item->difference > 0
                    ? StockMovementType::INVENTORY_SURPLUS
                    : StockMovementType::INVENTORY_SHORTAGE;

                // تسجيل الحركة
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouseId,
                    'type' => $type,
                    'quantity' => abs($item->difference),
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'notes' => "جرد {$inventoryCount->reference_number} - " . ($item->difference > 0 ? 'زيادة' : 'عجز'),
                    'reference_type' => 'inventory_count',
                    'reference_id' => $inventoryCount->id,
                ]);
            }

            // تحديث حالة الجرد
            $inventoryCount->update([
                'status' => InventoryCountStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            return $inventoryCount->fresh();
        });
    }

    /**
     * التحقق من تغير الأرصدة أثناء الجرد
     */
    protected function checkForChanges(InventoryCount $inventoryCount, int $warehouseId)
    {
        $changedItems = collect();

        foreach ($inventoryCount->items as $item) {
            $currentStock = ProductStock::where('product_id', $item->product_id)
                ->where('warehouse_id', $warehouseId)
                ->first();

            $currentQuantity = $currentStock ? $currentStock->quantity : 0;

            if ($currentQuantity != $item->system_quantity) {
                $changedItems->push([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'system_quantity' => $item->system_quantity,
                    'current_quantity' => $currentQuantity,
                ]);
            }
        }

        return $changedItems;
    }

    /**
     * إلغاء الجرد
     */
    public function cancel(InventoryCount $inventoryCount, ?string $notes = null): InventoryCount
    {
        if ($inventoryCount->status === InventoryCountStatus::COMPLETED) {
            throw new \Exception('لا يمكن إلغاء جرد معتمد');
        }

        $inventoryCount->update([
            'status' => InventoryCountStatus::CANCELLED,
            'notes' => $notes ? $inventoryCount->notes . ' - ' . $notes : $inventoryCount->notes,
        ]);

        return $inventoryCount->fresh();
    }

    /**
     * الحصول على تفاصيل الجرد
     */
    public function getDetails(InventoryCount $inventoryCount): InventoryCount
    {
        return $inventoryCount->load(['items.product', 'warehouse', 'category']);
    }
}
