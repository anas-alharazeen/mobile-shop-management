<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use App\Enums\StockMovementType;
use App\Enums\TransactionType;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private readonly FinancialAccountService $financialService
    ) {}

    public function create(
        array $data
    ): PurchaseReturn {
        return DB::transaction(
            function () use (
                $data
            ): PurchaseReturn {
                $invoice =
                    PurchaseInvoice::query()
                    ->with('items')
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['purchase_invoice_id']
                    );

                if (
                    $invoice->status
                    !== PurchaseInvoiceStatus::APPROVED
                ) {
                    throw new \RuntimeException(
                        'يمكن إنشاء مرتجع لفاتورة شراء معتمدة فقط.'
                    );
                }

                $this->validateReturnDate(
                    $invoice,
                    $data['return_date']
                        ?? now()
                );

                $items =
                    collect(
                        $data['items']
                            ?? []
                    );

                if ($items->isEmpty()) {
                    throw new \RuntimeException(
                        'اختر منتجاً واحداً على الأقل للإرجاع إلى المورد.'
                    );
                }

                if (
                    $items
                    ->pluck(
                        'purchase_invoice_item_id'
                    )
                    ->duplicates()
                    ->isNotEmpty()
                ) {
                    throw new \RuntimeException(
                        'لا يمكن تكرار نفس بند فاتورة الشراء داخل المرتجع.'
                    );
                }

                $returnType =
                    $this->determineReturnType(
                        $invoice,
                        $items
                    );

                $return =
                    PurchaseReturn::create([
                        'return_number' =>
                        PurchaseReturn::generateNumber(),

                        'purchase_invoice_id' =>
                        $invoice->id,

                        'supplier_id' =>
                        $invoice->supplier_id,

                        'return_date' =>
                        Carbon::parse(
                            $data['return_date'] ?? now()
                        )
                            ->toDateString(),

                        'status' =>
                        'draft',

                        'return_type' =>
                        $returnType,

                        'financial_account_id' =>
                        $data['financial_account_id'] ?? null,

                        'refund_method' =>
                        $data['refund_method'] ?? null,

                        'reason' =>
                        trim(
                            (string) (
                                $data['reason'] ?? ''
                            )
                        ),

                        'notes' =>
                        $data['notes']
                            ?? null,
                    ]);

                foreach (
                    $items as $itemData
                ) {
                    $this->addItem(
                        $return,
                        $itemData
                    );
                }

                $return->update([
                    'total_amount' =>
                    round(
                        (float) $return
                            ->items()
                            ->sum(
                                'total_amount'
                            ),
                        2
                    ),
                ]);

                return $return
                    ->fresh()
                    ->load([
                        'items.product',
                        'items.warehouse',
                        'invoice',
                        'supplier',
                        'account',
                    ]);
            }
        );
    }

    public function addItem(
        PurchaseReturn $return,
        array $data
    ): PurchaseReturnItem {
        $quantity =
            (int) (
                $data['quantity']
                ?? 0
            );

        if ($quantity <= 0) {
            throw new \RuntimeException(
                'كمية المرتجع يجب أن تكون أكبر من صفر.'
            );
        }

        /*
         * قفل بند الفاتورة يمنع مرتجعين متزامنين من قراءة
         * نفس الكمية القابلة للإرجاع قبل تسجيل أحدهما.
         */
        $invoiceItem =
            PurchaseInvoiceItem::query()
                ->lockForUpdate()
                ->findOrFail(
                    $data['purchase_invoice_item_id']
                );

        if (
            (int) $invoiceItem
                ->purchase_invoice_id
            !== (int) $return
                ->purchase_invoice_id
        ) {
            throw new \RuntimeException(
                'المنتج المختار لا ينتمي إلى فاتورة الشراء الأصلية.'
            );
        }

        $returnedSummary =
            PurchaseReturnItem::query()
                ->where(
                    'purchase_invoice_item_id',
                    $invoiceItem->id
                )
                ->whereHas(
                    'return',
                    fn($query) =>
                    $query->where(
                        'status',
                        '!=',
                        'cancelled'
                    )
                )
                ->selectRaw(
                    'COALESCE(SUM(quantity), 0) as returned_quantity'
                )
                ->selectRaw(
                    'COALESCE(SUM(total_amount), 0) as returned_amount'
                )
                ->first();

        $returnedQty =
            (int) (
                $returnedSummary
                    ?->returned_quantity
                ?? 0
            );

        $returnedAmount =
            round(
                (float) (
                    $returnedSummary
                        ?->returned_amount
                    ?? 0
                ),
                2
            );

        $availableQty =
            (int) $invoiceItem
                ->quantity
            - $returnedQty;

        if (
            $availableQty <= 0
            || $quantity > $availableQty
        ) {
            throw new \RuntimeException(
                "الكمية المتاحة للإرجاع هي "
                    . max(
                        0,
                        $availableQty
                    )
                    . ' فقط.'
            );
        }

        $warehouseId =
            (int) (
                $data['warehouse_id'] ?? 0
            );

        /*
         * المرتجع عملية تشغيلية جديدة، لذلك لا نسمح بخصم
         * الكمية من مخزن غير موجود أو غير نشط.
         */
        $this->activeWarehouse(
            $warehouseId
        );

        $stock =
            ProductStock::query()
            ->where(
                'product_id',
                $invoiceItem
                    ->product_id
            )
            ->where(
                'warehouse_id',
                $warehouseId
            )
            ->first();

        if (
            ! $stock
            || $stock->quantity
            < $quantity
        ) {
            throw new \RuntimeException(
                'الكمية المطلوبة غير متوفرة في المخزن المحدد.'
            );
        }

        /*
         * المصدر المالي الدقيق هو landed_line_cost.
         * للفواتير القديمة نعود إلى unit cost × quantity.
         */
        $sourceLineCost =
            $invoiceItem
                ->landed_line_cost
            !== null
                ? round(
                    (float) $invoiceItem
                        ->landed_line_cost,
                    2
                )
                : round(
                    (int) $invoiceItem
                        ->quantity
                    * (float) (
                        $invoiceItem
                            ->landed_unit_cost
                        ?: $invoiceItem
                            ->unit_purchase_price
                    ),
                    2
                );

        $remainingCost =
            round(
                max(
                    0,
                    $sourceLineCost
                    - $returnedAmount
                ),
                2
            );

        /*
         * إذا كان هذا المرتجع يأخذ كل الكمية المتبقية،
         * يأخذ كامل القيمة المتبقية حتى لا نخسر قرش التقريب.
         */
        if ($quantity === $availableQty) {
            $returnTotal =
                $remainingCost;
        } else {
            $returnTotal =
                round(
                    $sourceLineCost
                    * (
                        $quantity
                        / max(
                            1,
                            (int) $invoiceItem
                                ->quantity
                        )
                    ),
                    2
                );

            $returnTotal =
                min(
                    $remainingCost,
                    $returnTotal
                );
        }

        $unitCost =
            $quantity > 0
                ? round(
                    $returnTotal
                    / $quantity,
                    2
                )
                : 0;

        return PurchaseReturnItem::create([
            'purchase_return_id' =>
            $return->id,

            'purchase_invoice_item_id' =>
            $invoiceItem->id,

            'product_id' =>
            $invoiceItem
                ->product_id,

            'warehouse_id' =>
            $warehouseId,

            'quantity' =>
            $quantity,

            'unit_cost' =>
            round(
                $unitCost,
                2
            ),

            'total_amount' =>
            $returnTotal,

            'reason' =>
            $data['reason']
                ?? null,

            'notes' =>
            $data['notes']
                ?? null,
        ]);
    }

    public function approve(
        PurchaseReturn $return
    ): PurchaseReturn {
        return DB::transaction(
            function () use (
                $return
            ): PurchaseReturn {
                $lockedReturn =
                    PurchaseReturn::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $return->id
                    );

                if (
                    $lockedReturn->status
                    !== 'draft'
                    || ! $lockedReturn
                        ->items()
                        ->exists()
                ) {
                    throw new \RuntimeException(
                        'لا يمكن اعتماد هذا المرتجع.'
                    );
                }

                $invoice =
                    PurchaseInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $lockedReturn
                            ->purchase_invoice_id
                    );

                if (
                    $invoice->status
                    !== PurchaseInvoiceStatus::APPROVED
                ) {
                    throw new \RuntimeException(
                        'فاتورة الشراء الأصلية غير معتمدة أو ملغاة.'
                    );
                }

                $lockedReturn->load([
                    'items.product',
                    'items.invoiceItem',
                ]);

                foreach (
                    $lockedReturn
                        ->items as $item
                ) {
                    $otherReturned =
                        (int) PurchaseReturnItem::query()
                            ->where(
                                'purchase_invoice_item_id',
                                $item
                                    ->purchase_invoice_item_id
                            )
                            ->where(
                                'purchase_return_id',
                                '!=',
                                $lockedReturn
                                    ->id
                            )
                            ->whereHas(
                                'return',
                                fn ($query) =>
                                    $query->where(
                                        'status',
                                        '!=',
                                        'cancelled'
                                    )
                            )
                            ->sum(
                                'quantity'
                            );

                    if (
                        $otherReturned
                        + (int) $item
                            ->quantity
                        > (int) $item
                            ->invoiceItem
                            ->quantity
                    ) {
                        throw new \RuntimeException(
                            "تجاوزت الكمية المتاحة للمنتج {$item->product->name}."
                        );
                    }

                    /*
                     * نفس ترتيب القفل المستخدم في المشتريات:
                     * Product أولاً ثم جميع أرصدة المنتج.
                     * هذا يجعل تعديل المتوسط والمخزون عملية ذرية.
                     */
                    $product =
                        Product::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $item
                                    ->product_id
                            );

                    $stockRows =
                        ProductStock::query()
                            ->where(
                                'product_id',
                                $product->id
                            )
                            ->lockForUpdate()
                            ->get();

                    $stock =
                        $stockRows
                            ->firstWhere(
                                'warehouse_id',
                                $item
                                    ->warehouse_id
                            );

                    if (
                        ! $stock
                        || (int) $stock
                            ->quantity
                        < (int) $item
                            ->quantity
                    ) {
                        throw new \RuntimeException(
                            "الكمية غير متوفرة للمنتج {$item->product->name} في المخزن المحدد."
                        );
                    }

                    $this->activeWarehouse(
                        (int) $item
                            ->warehouse_id
                    );

                    $currentTotalStock =
                        (int) $stockRows
                            ->sum(
                                'quantity'
                            );

                    $returnQuantity =
                        (int) $item
                            ->quantity;

                    $newTotalStock =
                        $currentTotalStock
                        - $returnQuantity;

                    if ($newTotalStock < 0) {
                        throw new \RuntimeException(
                            "إجمالي مخزون المنتج {$item->product->name} غير كافٍ لتنفيذ المرتجع."
                        );
                    }

                    $currentAverageCost =
                        max(
                            0,
                            (float) (
                                $product
                                    ->purchase_price
                                ?? 0
                            )
                        );

                    $currentInventoryValue =
                        $currentTotalStock
                        * $currentAverageCost;

                    /*
                     * قيمة الإزالة تعتمد نفس تكلفة البند التي
                     * سُجل بها مرتجع المورد (Landed Cost).
                     */
                    $returnInventoryValue =
                        (float) $item
                            ->total_amount;

                    $remainingInventoryValue =
                        $currentInventoryValue
                        - $returnInventoryValue;

                    /*
                     * في نظام Moving Average بدون Lot Tracking قد
                     * تصبح القيمة النظرية سالبة إذا حدثت مبيعات/
                     * مشتريات كثيرة بعد الفاتورة الأصلية. لا نخفي
                     * هذه الحالة لأنها ستشوّه المتوسط؛ نوقف العملية
                     * برسالة واضحة بدلاً من تسجيل تكلفة سالبة.
                     */
                    if (
                        $newTotalStock > 0
                        && $remainingInventoryValue
                            < -0.01
                    ) {
                        throw new \RuntimeException(
                            "تعذر إعادة حساب متوسط تكلفة {$item->product->name}: تكلفة المرتجع أكبر من قيمة المخزون الحالية بعد الحركات اللاحقة. راجع حركات المنتج قبل اعتماد المرتجع."
                        );
                    }

                    $newAverageCost =
                        $newTotalStock > 0
                            ? max(
                                0,
                                $remainingInventoryValue
                                / $newTotalStock
                            )
                            : 0;

                    $before =
                        (int) $stock
                            ->quantity;

                    $after =
                        $before
                        - $returnQuantity;

                    $stock->update([
                        'quantity' =>
                            $after,
                    ]);

                    $product->update([
                        'purchase_price' =>
                            round(
                                $newAverageCost,
                                2
                            ),
                    ]);

                    StockMovement::create([
                        'product_id' =>
                            $item
                                ->product_id,

                        'warehouse_id' =>
                            $item
                                ->warehouse_id,

                        'type' =>
                            StockMovementType::PURCHASE_RETURN
                                ->value,

                        'quantity' =>
                            $returnQuantity,

                        'quantity_before' =>
                            $before,

                        'quantity_after' =>
                            $after,

                        'notes' =>
                            "مرتجع مشتريات - {$lockedReturn->return_number}",

                        'reference_type' =>
                            PurchaseReturn::class,

                        'reference_id' =>
                            $lockedReturn
                                ->id,
                    ]);
                }

                $returnAmount =
                    (float) $lockedReturn
                        ->total_amount;

                $currentDebt =
                    (float) $invoice
                        ->remaining_amount;

                /*
                 * المرتجع يقلل مستحق المورد أولاً،
                 * ثم أي زيادة تصبح مبلغاً مسترداً فعلياً.
                 */
                $usedForDebt =
                    min(
                        $returnAmount,
                        $currentDebt
                    );

                $refund =
                    max(
                        0,
                        $returnAmount
                            - $usedForDebt
                    );

                $newRemaining =
                    max(
                        0,
                        $currentDebt
                            - $usedForDebt
                    );

                $invoice->update([
                    'remaining_amount' =>
                    round(
                        $newRemaining,
                        2
                    ),

                    'payment_status' =>
                    $this
                        ->paymentStatusValue(
                            (float) $invoice
                                ->paid_amount,
                            $newRemaining
                        ),
                ]);

                if (
                    $refund > 0.00001
                ) {
                    if (
                        ! $lockedReturn
                            ->financial_account_id
                        || ! $lockedReturn
                            ->refund_method
                        || $lockedReturn
                        ->refund_method
                        === 'debt'
                    ) {
                        throw new \RuntimeException(
                            'اختر الحساب المالي وطريقة استلام المبلغ من المورد.'
                        );
                    }

                    $account =
                        $this
                        ->financialService
                        ->resolveAccountForPayment(
                            $lockedReturn
                                ->refund_method,
                            $lockedReturn
                                ->financial_account_id
                        );

                    $this
                        ->financialService
                        ->addInflow(
                            $account,
                            TransactionType::PURCHASE_REFUND,
                            $refund,
                            "استرداد مرتجع مشتريات - {$lockedReturn->return_number}",
                            $lockedReturn
                        );
                }

                $lockedReturn->update([
                    'amount_used_for_debt' =>
                    round(
                        $usedForDebt,
                        2
                    ),

                    'amount_refunded' =>
                    round(
                        $refund,
                        2
                    ),

                    'status' =>
                    'approved',

                    'approved_at' =>
                    now(),
                ]);

                return $lockedReturn
                    ->fresh()
                    ->load([
                        'items.product',
                        'items.warehouse',
                        'invoice',
                        'supplier',
                        'account',
                    ]);
            }
        );
    }

    /**
     * إلغاء المسودة أو عكس مرتجع مشتريات معتمد.
     */
    public function cancel(
        PurchaseReturn $return,
        string $reason
    ): PurchaseReturn {
        return DB::transaction(
            function () use (
                $return,
                $reason
            ): PurchaseReturn {
                $lockedReturn =
                    PurchaseReturn::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $return->id
                    );

                if (
                    ! in_array(
                        $lockedReturn->status,
                        [
                            'draft',
                            'approved',
                        ],
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        'لا يمكن إلغاء هذا المرتجع.'
                    );
                }

                if (
                    $lockedReturn->status
                    === 'approved'
                ) {
                    $lockedReturn->load(
                        'items.product'
                    );

                    /*
                     * اعتماد المرتجع خصم المخزون.
                     * العكس يعيد نفس الكمية لنفس المخزن.
                     */
                    foreach (
                        $lockedReturn
                            ->items as $item
                    ) {
                        $product =
                            Product::query()
                                ->lockForUpdate()
                                ->findOrFail(
                                    $item
                                        ->product_id
                                );

                        $stockRows =
                            ProductStock::query()
                                ->where(
                                    'product_id',
                                    $product->id
                                )
                                ->lockForUpdate()
                                ->get();

                        $stock =
                            $stockRows
                                ->firstWhere(
                                    'warehouse_id',
                                    $item
                                        ->warehouse_id
                                );

                        $currentTotalStock =
                            (int) $stockRows
                                ->sum(
                                    'quantity'
                                );

                        $before =
                            (int) (
                                $stock
                                    ?->quantity
                                ?? 0
                            );

                        $returnQuantity =
                            (int) $item
                                ->quantity;

                        $after =
                            $before
                            + $returnQuantity;

                        $newTotalStock =
                            $currentTotalStock
                            + $returnQuantity;

                        $currentAverageCost =
                            max(
                                0,
                                (float) (
                                    $product
                                        ->purchase_price
                                    ?? 0
                                )
                            );

                        $currentInventoryValue =
                            $currentTotalStock
                            * $currentAverageCost;

                        /*
                         * إلغاء المرتجع يعيد نفس التكلفة التي
                         * أُزيلت عند اعتماد المرتجع.
                         */
                        $restoredValue =
                            (float) $item
                                ->total_amount;

                        $newAverageCost =
                            $newTotalStock > 0
                                ? (
                                    $currentInventoryValue
                                    + $restoredValue
                                ) / $newTotalStock
                                : 0;

                        if ($stock) {
                            $stock->update([
                                'quantity' =>
                                    $after,
                            ]);
                        } else {
                            ProductStock::create([
                                'product_id' =>
                                    $item
                                        ->product_id,

                                'warehouse_id' =>
                                    $item
                                        ->warehouse_id,

                                'quantity' =>
                                    $after,
                            ]);
                        }

                        $product->update([
                            'purchase_price' =>
                                round(
                                    $newAverageCost,
                                    2
                                ),
                        ]);

                        StockMovement::create([
                            'product_id' =>
                                $item
                                    ->product_id,

                            'warehouse_id' =>
                                $item
                                    ->warehouse_id,

                            'type' =>
                                StockMovementType::PURCHASE_RETURN_REVERSAL
                                    ->value,

                            'quantity' =>
                                $returnQuantity,

                            'quantity_before' =>
                                $before,

                            'quantity_after' =>
                                $after,

                            'notes' =>
                                "عكس مرتجع مشتريات - {$lockedReturn->return_number}",

                            'reference_type' =>
                                PurchaseReturn::class,

                            'reference_id' =>
                                $lockedReturn
                                    ->id,
                        ]);
                    }

                    /*
                     * إذا استلمنا مبلغاً فعلياً من المورد،
                     * نعكس الحركة المالية أيضاً.
                     */
                    $transaction =
                        $lockedReturn
                        ->account
                        ?->transactions()
                        ->where(
                            'reference_type',
                            PurchaseReturn::class
                        )
                        ->where(
                            'reference_id',
                            $lockedReturn
                                ->id
                        )
                        ->where(
                            'type',
                            TransactionType::PURCHASE_REFUND
                                ->value
                        )
                        ->first();

                    if ($transaction) {
                        $this
                            ->financialService
                            ->reverseTransaction(
                                $transaction,
                                $reason
                            );
                    }

                    /*
                     * نعيد الجزء الذي خُصم من مستحق المورد.
                     */
                    if (
                        (float) $lockedReturn
                            ->amount_used_for_debt
                        > 0
                    ) {
                        $invoice =
                            PurchaseInvoice::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $lockedReturn
                                    ->purchase_invoice_id
                            );

                        $remaining =
                            (float) $invoice
                                ->remaining_amount
                            + (float) $lockedReturn
                                ->amount_used_for_debt;

                        $invoice->update([
                            'remaining_amount' =>
                            round(
                                $remaining,
                                2
                            ),

                            'payment_status' =>
                            $this
                                ->paymentStatusValue(
                                    (float) $invoice
                                        ->paid_amount,
                                    $remaining
                                ),
                        ]);
                    }
                }

                $lockedReturn->update([
                    'status' =>
                    'cancelled',

                    'cancelled_at' =>
                    now(),

                    'cancellation_reason' =>
                    trim(
                        $reason
                    ),
                ]);

                return $lockedReturn
                    ->fresh();
            }
        );
    }

    private function activeWarehouse(
        int $warehouseId
    ): Warehouse {
        $warehouse =
            Warehouse::query()
                ->whereKey(
                    $warehouseId
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $warehouse) {
            throw new \RuntimeException(
                'المخزن المحدد غير موجود أو غير نشط.'
            );
        }

        return $warehouse;
    }

    private function determineReturnType(
        PurchaseInvoice $invoice,
        Collection $items
    ): string {
        $itemIds =
            $invoice->items
            ->pluck('id');

        $returned =
            PurchaseReturnItem::query()
            ->selectRaw(
                'purchase_invoice_item_id, SUM(quantity) as returned_quantity'
            )
            ->whereIn(
                'purchase_invoice_item_id',
                $itemIds
            )
            ->whereHas(
                'return',
                fn($query) =>
                $query->where(
                    'status',
                    '!=',
                    'cancelled'
                )
            )
            ->groupBy(
                'purchase_invoice_item_id'
            )
            ->pluck(
                'returned_quantity',
                'purchase_invoice_item_id'
            );

        $totalAvailable =
            $invoice->items
            ->sum(
                function (
                    $invoiceItem
                ) use (
                    $returned
                ): int {
                    return max(
                        0,
                        (int) $invoiceItem
                            ->quantity
                            - (int) (
                                $returned[$invoiceItem
                                    ->id] ?? 0
                            )
                    );
                }
            );

        $selectedQuantity =
            (int) $items->sum(
                fn(array $item) =>
                (int) (
                    $item['quantity']
                    ?? 0
                )
            );

        if (
            $totalAvailable <= 0
        ) {
            throw new \RuntimeException(
                'لا توجد كميات متبقية قابلة للإرجاع في فاتورة الشراء.'
            );
        }

        return $selectedQuantity
            === $totalAvailable
            ? 'full'
            : 'partial';
    }

    private function validateReturnDate(
        PurchaseInvoice $invoice,
        mixed $returnDate
    ): void {
        $date =
            Carbon::parse(
                $returnDate
            )->startOfDay();

        $invoiceDate =
            Carbon::parse(
                $invoice->purchase_date
                    ?? $invoice->created_at
            )->startOfDay();

        if ($date->lt($invoiceDate)) {
            throw new \RuntimeException(
                'تاريخ المرتجع لا يمكن أن يسبق تاريخ فاتورة الشراء.'
            );
        }

        if ($date->gt(today())) {
            throw new \RuntimeException(
                'تاريخ المرتجع لا يمكن أن يكون في المستقبل.'
            );
        }
    }

    private function paymentStatusValue(
        float $paid,
        float $remaining
    ): string {
        if ($remaining <= 0.00001) {
            return PaymentStatus::PAID
                ->value;
        }

        return $paid > 0.00001
            ? PaymentStatus::PARTIALLY_PAID
            ->value
            : PaymentStatus::UNPAID
            ->value;
    }
}
