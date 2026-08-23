<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\StockMovementType;
use App\Enums\TransactionType;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReturnService
{
    public function __construct(
        private readonly FinancialAccountService $financialService
    ) {}

    public function create(
        array $data
    ): SalesReturn {
        return DB::transaction(
            function () use (
                $data
            ): SalesReturn {
                $invoice =
                    SalesInvoice::query()
                    ->with('items')
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['sales_invoice_id']
                    );

                if (
                    $invoice->status
                    !== SalesInvoiceStatus::APPROVED
                ) {
                    throw new \RuntimeException(
                        'يمكن إنشاء مرتجع لفاتورة بيع معتمدة فقط.'
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
                        'اختر منتجاً واحداً على الأقل للإرجاع.'
                    );
                }

                if (
                    $items
                    ->pluck(
                        'sales_invoice_item_id'
                    )
                    ->duplicates()
                    ->isNotEmpty()
                ) {
                    throw new \RuntimeException(
                        'لا يمكن تكرار نفس بند الفاتورة داخل المرتجع.'
                    );
                }

                $returnType =
                    $this->determineReturnType(
                        $invoice,
                        $items
                    );

                $return =
                    SalesReturn::create([
                        'return_number' =>
                        SalesReturn::generateNumber(),

                        'sales_invoice_id' =>
                        $invoice->id,

                        'customer_id' =>
                        $invoice->customer_id,

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

                $this->recalculate(
                    $return
                );

                return $return
                    ->fresh()
                    ->load([
                        'items.product',
                        'items.warehouse',
                        'invoice',
                        'customer',
                        'account',
                    ]);
            }
        );
    }

    public function addItem(
        SalesReturn $return,
        array $data
    ): SalesReturnItem {
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
         * نقفل بند الفاتورة أثناء حساب الكمية والقيمة
         * حتى لا تتعارض عمليتا مرتجع متزامنتان.
         */
        $invoiceItem =
            SalesInvoiceItem::query()
            ->lockForUpdate()
            ->findOrFail(
                $data['sales_invoice_item_id']
            );

        if (
            (int) $invoiceItem
                ->sales_invoice_id
            !== (int) $return
                ->sales_invoice_id
        ) {
            throw new \RuntimeException(
                'المنتج المختار لا ينتمي إلى الفاتورة الأصلية.'
            );
        }

        /*
         * نحسب ما تم إرجاعه فعلياً من مرتجعات غير ملغاة،
         * كميةً وقيمةً، حتى يكون آخر مرتجع قادراً على أخذ
         * فرق التقريب المتبقي بدقة.
         */
        $returnedSummary =
            SalesReturnItem::query()
            ->where(
                'sales_invoice_item_id',
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

        $availableQty =
            (int) $invoiceItem
                ->quantity
            - $returnedQty;

        if (
            $availableQty <= 0
            || $quantity > $availableQty
        ) {
            throw new \RuntimeException(
                'الكمية المتاحة للإرجاع هي '
                    . max(
                        0,
                        $availableQty
                    )
                    . ' فقط.'
            );
        }

        $condition =
            $data['product_condition'] ?? 'sellable';

        $restock =
            (bool) (
                $data['restock']
                ?? false
            );

        if (
            $restock
            && $condition !== 'sellable'
        ) {
            throw new \RuntimeException(
                'لا يمكن إعادة منتج تالف أو يحتاج فحصاً إلى الرصيد المتاح للبيع.'
            );
        }

        $warehouseId = null;

        if ($restock) {
            $warehouseId =
                (int) (
                    $data['warehouse_id']
                    ?? $invoiceItem
                    ->warehouse_id
                );

            if (! $warehouseId) {
                throw new \RuntimeException(
                    'تعذر تحديد مخزن استلام المنتج المرتجع.'
                );
            }

            /*
             * المرتجع القابل للبيع يرجع فقط إلى مخزن مبيعات
             * نشط، وليس إلى مخزن صيانة أو مخزن غير فعال.
             */
            $this
                ->activeSalesWarehouse(
                    $warehouseId
                );
        }

        /*
         * لا نعتمد على:
         * round(line_total / quantity, 2) * returned_quantity
         * لأن هذا قد يجعل المرتجع الكامل 99.99 بدل 100.00.
         *
         * نستخدم السنتات، وإذا كان هذا المرتجع يأخذ كل
         * الكمية المتبقية نعطيه بالضبط القيمة المالية المتبقية.
         */
        $lineTotalCents =
            $this->moneyToCents(
                $invoiceItem
                    ->line_total
            );

        $alreadyReturnedCents =
            $this->moneyToCents(
                $returnedSummary
                    ?->returned_amount
                    ?? 0
            );

        $remainingValueCents =
            max(
                0,
                $lineTotalCents
                    - $alreadyReturnedCents
            );

        if (
            $quantity
            === $availableQty
        ) {
            $returnTotalCents =
                $remainingValueCents;
        } else {
            $proportionalCents =
                (int) round(
                    $lineTotalCents
                        * (
                            $quantity
                            / max(
                                1,
                                (int) $invoiceItem
                                    ->quantity
                            )
                        )
                );

            $returnTotalCents =
                min(
                    $remainingValueCents,
                    max(
                        0,
                        $proportionalCents
                    )
                );
        }

        $totalAmount =
            $this->centsToMoney(
                $returnTotalCents
            );

        $unitPrice =
            $quantity > 0
            ? round(
                $totalAmount
                    / $quantity,
                2
            )
            : 0;

        return SalesReturnItem::create([
            'sales_return_id' =>
            $return->id,

            'sales_invoice_item_id' =>
            $invoiceItem->id,

            'product_id' =>
            $invoiceItem
                ->product_id,

            'quantity' =>
            $quantity,

            /*
             * unit_price للعرض فقط، أما total_amount فهو
             * القيمة المالية الدقيقة المعتمدة للمرتجع.
             */
            'unit_price' =>
            $unitPrice,

            'total_amount' =>
            $totalAmount,

            'product_condition' =>
            $condition,

            'restock' =>
            $restock,

            'warehouse_id' =>
            $warehouseId,

            'notes' =>
            $data['notes']
                ?? null,
        ]);
    }

    public function recalculate(
        SalesReturn $return
    ): void {
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
    }

    public function approve(
        SalesReturn $return,
        string $settlementMode = 'normal'
    ): SalesReturn {
        return DB::transaction(
            function () use (
                $return,
                $settlementMode
            ): SalesReturn {
                $lockedReturn =
                    SalesReturn::query()
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
                    SalesInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $lockedReturn
                            ->sales_invoice_id
                    );

                if (
                    $invoice->status
                    !== SalesInvoiceStatus::APPROVED
                ) {
                    throw new \RuntimeException(
                        'الفاتورة الأصلية غير معتمدة أو ملغاة.'
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
                        (int) SalesReturnItem::query()
                            ->where(
                                'sales_invoice_item_id',
                                $item
                                    ->sales_invoice_item_id
                            )
                            ->where(
                                'sales_return_id',
                                '!=',
                                $lockedReturn
                                    ->id
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
                            ->sum(
                                'quantity'
                            );

                    if (
                        $otherReturned
                        + $item->quantity
                        > $item
                        ->invoiceItem
                        ->quantity
                    ) {
                        throw new \RuntimeException(
                            "تجاوزت الكمية المتاحة للمنتج {$item->product->name}."
                        );
                    }

                    if (! $item->restock) {
                        continue;
                    }

                    /*
                     * قد يكون المرتجع حُفظ كمسودة ثم تغيرت
                     * حالة المخزن قبل الاعتماد؛ نعيد التحقق.
                     */
                    $this
                        ->activeSalesWarehouse(
                            (int) $item
                                ->warehouse_id
                        );

                    $stock =
                        ProductStock::query()
                        ->where(
                            'product_id',
                            $item
                                ->product_id
                        )
                        ->where(
                            'warehouse_id',
                            $item
                                ->warehouse_id
                        )
                        ->lockForUpdate()
                        ->first();

                    $before =
                        (int) (
                            $stock
                            ?->quantity
                            ?? 0
                        );

                    $after =
                        $before
                        + (int) $item
                            ->quantity;

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

                    StockMovement::create([
                        'product_id' =>
                        $item
                            ->product_id,

                        'warehouse_id' =>
                        $item
                            ->warehouse_id,

                        'type' =>
                        StockMovementType::SALES_RETURN
                            ->value,

                        'quantity' =>
                        $item->quantity,

                        'quantity_before' =>
                        $before,

                        'quantity_after' =>
                        $after,

                        'notes' =>
                        "مرتجع مبيعات - {$lockedReturn->return_number}",

                        'reference_type' =>
                        SalesReturn::class,

                        'reference_id' =>
                        $lockedReturn->id,
                    ]);
                }

                /*
                 * نعيد التجميع قبل التسوية المالية لضمان أن
                 * إجمالي المرتجع يساوي مجموع بنوده الحالي.
                 */
                $this->recalculate(
                    $lockedReturn
                );

                $lockedReturn->refresh();

                $returnAmount =
                    (float) $lockedReturn
                        ->total_amount;

                $currentDebt =
                    (float) $invoice
                        ->remaining_amount;

                /*
                 * قيمة المرتجع تسدد أولاً ما تبقى على نفس الفاتورة.
                 * فقط الزيادة بعد ذلك تصبح مبلغاً مستحقاً للعميل.
                 */
                $usedForDebt =
                    min(
                        $returnAmount,
                        $currentDebt
                    );

                $credit =
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

                $updates = [
                    'amount_used_for_debt' =>
                    round(
                        $usedForDebt,
                        2
                    ),

                    'amount_refunded' =>
                    0,
                ];

                if (
                    $settlementMode
                    === 'exchange'
                ) {
                    $updates['amount_refunded'] = round(
                        $credit,
                        2
                    );

                    $updates['refund_method'] =
                        PaymentMethod::EXCHANGE_CREDIT
                        ->value;

                    $updates['financial_account_id'] = null;
                } elseif (
                    $credit > 0.00001
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
                            'اختر الحساب المالي وطريقة رد المبلغ للعميل.'
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
                        ->addOutflow(
                            $account,
                            TransactionType::SALES_REFUND,
                            $credit,
                            "استرداد مرتجع مبيعات - {$lockedReturn->return_number}",
                            $lockedReturn
                        );

                    $updates['amount_refunded'] = round(
                        $credit,
                        2
                    );
                }

                $lockedReturn->update(
                    $updates + [
                        'status' =>
                        'approved',

                        'approved_at' =>
                        now(),
                    ]
                );

                return $lockedReturn
                    ->fresh()
                    ->load([
                        'items.product',
                        'items.warehouse',
                        'invoice',
                        'customer',
                        'account',
                    ]);
            }
        );
    }

    public function cancel(
        SalesReturn $return,
        string $reason
    ): SalesReturn {
        return DB::transaction(
            function () use (
                $return,
                $reason
            ): SalesReturn {
                $lockedReturn =
                    SalesReturn::query()
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
                    $lockedReturn
                    ->exchange()
                    ->exists()
                ) {
                    throw new \RuntimeException(
                        'لا يمكن إلغاء مرتجع مرتبط بعملية استبدال مستقلة.'
                    );
                }

                if (
                    $lockedReturn->status
                    === 'approved'
                ) {
                    $lockedReturn->load(
                        'items.product'
                    );

                    foreach (
                        $lockedReturn
                            ->items as $item
                    ) {
                        if (! $item->restock) {
                            continue;
                        }

                        $stock =
                            ProductStock::query()
                            ->where(
                                'product_id',
                                $item
                                    ->product_id
                            )
                            ->where(
                                'warehouse_id',
                                $item
                                    ->warehouse_id
                            )
                            ->lockForUpdate()
                            ->first();

                        if (
                            ! $stock
                            || $stock->quantity
                            < $item->quantity
                        ) {
                            throw new \RuntimeException(
                                "لا يمكن عكس المرتجع لأن كمية {$item->product->name} لم تعد متوفرة."
                            );
                        }

                        $before =
                            (int) $stock
                                ->quantity;

                        $after =
                            $before
                            - (int) $item
                                ->quantity;

                        $stock->update([
                            'quantity' =>
                            $after,
                        ]);

                        StockMovement::create([
                            'product_id' =>
                            $item
                                ->product_id,

                            'warehouse_id' =>
                            $item
                                ->warehouse_id,

                            'type' =>
                            StockMovementType::SALES_RETURN_REVERSAL
                                ->value,

                            'quantity' =>
                            $item->quantity,

                            'quantity_before' =>
                            $before,

                            'quantity_after' =>
                            $after,

                            'notes' =>
                            "عكس مرتجع - {$lockedReturn->return_number}",

                            'reference_type' =>
                            SalesReturn::class,

                            'reference_id' =>
                            $lockedReturn
                                ->id,
                        ]);
                    }

                    $transaction =
                        $lockedReturn
                        ->account
                        ?->transactions()
                        ->where(
                            'reference_type',
                            SalesReturn::class
                        )
                        ->where(
                            'reference_id',
                            $lockedReturn
                                ->id
                        )
                        ->where(
                            'type',
                            TransactionType::SALES_REFUND
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

                    if (
                        (float) $lockedReturn
                            ->amount_used_for_debt
                        > 0
                    ) {
                        $invoice =
                            SalesInvoice::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $lockedReturn
                                    ->sales_invoice_id
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

    private function activeSalesWarehouse(
        int $warehouseId
    ): Warehouse {
        $warehouse =
            Warehouse::query()
            ->whereKey(
                $warehouseId
            )
            ->where(
                'type',
                'sales'
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (! $warehouse) {
            throw new \RuntimeException(
                'مخزن استلام المرتجع يجب أن يكون مخزن مبيعات نشطاً.'
            );
        }

        return $warehouse;
    }

    private function moneyToCents(
        mixed $amount
    ): int {
        return (int) round(
            (float) $amount
                * 100
        );
    }

    private function centsToMoney(
        int $cents
    ): float {
        return round(
            $cents / 100,
            2
        );
    }

    private function determineReturnType(
        SalesInvoice $invoice,
        Collection $items
    ): string {
        $itemIds =
            $invoice->items
            ->pluck('id');

        $returned =
            SalesReturnItem::query()
            ->selectRaw(
                'sales_invoice_item_id, SUM(quantity) as returned_quantity'
            )
            ->whereIn(
                'sales_invoice_item_id',
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
                'sales_invoice_item_id'
            )
            ->pluck(
                'returned_quantity',
                'sales_invoice_item_id'
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
                'لا توجد كميات متبقية قابلة للإرجاع في الفاتورة.'
            );
        }

        return $selectedQuantity
            === $totalAvailable
            ? 'full'
            : 'partial';
    }

    private function validateReturnDate(
        SalesInvoice $invoice,
        mixed $returnDate
    ): void {
        $date =
            Carbon::parse(
                $returnDate
            )->startOfDay();

        $invoiceDate =
            Carbon::parse(
                $invoice->sale_date
                    ?? $invoice->created_at
            )->startOfDay();

        if ($date->lt($invoiceDate)) {
            throw new \RuntimeException(
                'تاريخ المرتجع لا يمكن أن يسبق تاريخ الفاتورة.'
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
