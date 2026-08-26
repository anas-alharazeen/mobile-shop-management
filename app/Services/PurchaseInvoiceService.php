<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function __construct(
        private readonly FinancialAccountService $financeService,
        private readonly ProductService $productService
    ) {}

    /**
     * إنشاء فاتورة شراء كمسودة.
     *
     * لا يتم تعديل المخزون أو الحساب المالي هنا.
     * ذلك يحدث عند اعتماد الفاتورة.
     */
    public function create(
        array $data
    ): PurchaseInvoice {
        return DB::transaction(
            function () use (
                $data
            ): PurchaseInvoice {
                $supplier =
                    Supplier::query()
                    ->findOrFail(
                        $data['supplier_id']
                    );

                if (! $supplier->is_active) {
                    throw new \RuntimeException(
                        'المورد غير نشط.'
                    );
                }

                /*
                 * نحول البنود التي تحتوي على منتج جديد إلى Product فعلي
                 * داخل نفس Transaction، لكن بدون أي رصيد افتتاحي.
                 *
                 * لذلك المنتج يظهر في بطاقة المنتجات مباشرة،
                 * بينما الكمية تبقى 0 حتى اعتماد فاتورة الشراء.
                 */
                $resolvedItems =
                    $this->resolveInlineProducts(
                        $data['items']
                            ?? []
                    );

                $this->assertUniqueProducts(
                    $resolvedItems
                );

                /*
                 * نكتب القيم الرقمية صراحة ولا نعتمد على
                 * Default قاعدة البيانات داخل نفس Model instance.
                 *
                 * هذا يمنع مشكلة paid_amount = null التي ظهرت سابقاً.
                 */
                $invoice =
                    PurchaseInvoice::create([
                        'invoice_number' =>
                        PurchaseInvoice::generateNumber(),

                        'supplier_id' =>
                        $supplier->id,

                        'supplier_invoice_number' =>
                        $data['supplier_invoice_number'] ?? null,

                        'purchase_date' =>
                        $data['purchase_date'],

                        'status' =>
                        PurchaseInvoiceStatus::DRAFT
                            ->value,

                        'subtotal' =>
                        0,

                        'discount_amount' =>
                        $this->nonNegative(
                            $data['discount_amount'] ?? 0,
                            'خصم الفاتورة'
                        ),

                        'shipping_cost' =>
                        $this->nonNegative(
                            $data['shipping_cost'] ?? 0,
                            'تكلفة الشحن'
                        ),

                        'additional_expenses' =>
                        $this->nonNegative(
                            $data['additional_expenses'] ?? 0,
                            'المصاريف الإضافية'
                        ),

                        'total_amount' =>
                        0,

                        'paid_amount' =>
                        0,

                        'remaining_amount' =>
                        0,

                        'payment_status' =>
                        PaymentStatus::UNPAID
                            ->value,

                        'due_date' =>
                        $data['due_date']
                            ?? null,

                        'notes' =>
                        $data['notes']
                            ?? null,
                    ]);

                foreach (
                    $resolvedItems as $itemData
                ) {
                    $this->addItem(
                        $invoice,
                        $itemData
                    );
                }

                $invoice =
                    $this->recalculate(
                        $invoice
                    );

                return $invoice
                    ->fresh()
                    ->load([
                        'items.product',
                        'items.warehouse',
                        'supplier',
                    ]);
            }
        );
    }

    /**
     * إضافة بند إلى مسودة فاتورة الشراء.
     */
    public function addItem(
        PurchaseInvoice $invoice,
        array $data
    ): PurchaseInvoiceItem {
        $invoice->refresh();

        if (
            ! $invoice
                ->can_be_edited
        ) {
            throw new \RuntimeException(
                'لا يمكن تعديل فاتورة شراء معتمدة أو ملغاة.'
            );
        }

        $product =
            Product::query()
            ->findOrFail(
                $data['product_id']
            );

        $warehouse =
            Warehouse::query()
            ->findOrFail(
                $data['warehouse_id']
            );

        if (! $product->is_active) {
            throw new \RuntimeException(
                'المنتج غير نشط.'
            );
        }

        if (! $warehouse->is_active) {
            throw new \RuntimeException(
                'المخزن غير نشط.'
            );
        }

        $quantity =
            (int) (
                $data['quantity']
                ?? 0
            );

        $unitPrice =
            (float) (
                $data['unit_purchase_price']
                ?? $product
                ->purchase_price
                ?? 0
            );

        $lineDiscount =
            (float) (
                $data['line_discount'] ?? 0
            );

        if ($quantity <= 0) {
            throw new \RuntimeException(
                'الكمية يجب أن تكون أكبر من صفر.'
            );
        }

        if ($unitPrice < 0) {
            throw new \RuntimeException(
                'سعر الشراء لا يمكن أن يكون سالباً.'
            );
        }

        if ($lineDiscount < 0) {
            throw new \RuntimeException(
                'خصم البند لا يمكن أن يكون سالباً.'
            );
        }

        $lineSubtotal =
            $quantity
            * $unitPrice;

        if (
            $lineDiscount
            > $lineSubtotal
        ) {
            throw new \RuntimeException(
                "خصم المنتج {$product->name} أكبر من قيمة البند."
            );
        }

        $lineTotal =
            $lineSubtotal
            - $lineDiscount;

        return PurchaseInvoiceItem::create([
            'purchase_invoice_id' =>
            $invoice->id,

            'product_id' =>
            $product->id,

            'warehouse_id' =>
            $warehouse->id,

            'quantity' =>
            $quantity,

            'unit_purchase_price' =>
            round(
                $unitPrice,
                2
            ),

            'line_discount' =>
            round(
                $lineDiscount,
                2
            ),

            'line_subtotal' =>
            round(
                $lineSubtotal,
                2
            ),

            'allocated_expenses' =>
            0,

            /*
             * يتم تصحيحه في recalculate بعد توزيع
             * خصم الفاتورة والشحن والمصاريف.
             */
            'landed_unit_cost' =>
            round(
                $lineTotal
                    / $quantity,
                2
            ),


            'landed_line_cost' =>
            round(
                $lineTotal,
                2
            ),

            'line_total' =>
            round(
                $lineTotal,
                2
            ),
        ]);
    }

    /**
     * إعادة حساب الفاتورة وتكلفة الوصول Landed Cost.
     */
    public function recalculate(
        PurchaseInvoice $invoice
    ): PurchaseInvoice {
        $invoice->refresh();
        $invoice->load('items');

        $items =
            $invoice->items;

        if ($items->isEmpty()) {
            throw new \RuntimeException(
                'فاتورة الشراء يجب أن تحتوي على منتج واحد على الأقل.'
            );
        }

        $subtotal =
            (float) $items
                ->sum(
                    'line_total'
                );

        $discountAmount =
            (float) (
                $invoice
                ->discount_amount
                ?? 0
            );

        $shippingCost =
            (float) (
                $invoice
                ->shipping_cost
                ?? 0
            );

        $additionalExpenses =
            (float) (
                $invoice
                ->additional_expenses
                ?? 0
            );

        $paidAmount =
            (float) (
                $invoice
                ->paid_amount
                ?? 0
            );

        if (
            $discountAmount < 0
            || $discountAmount
            > $subtotal
        ) {
            throw new \RuntimeException(
                'خصم الفاتورة لا يمكن أن يتجاوز إجمالي البنود.'
            );
        }

        if (
            $shippingCost < 0
            || $additionalExpenses < 0
        ) {
            throw new \RuntimeException(
                'الشحن والمصاريف الإضافية لا يمكن أن تكون سالبة.'
            );
        }

        $total =
            $subtotal
            - $discountAmount
            + $shippingCost
            + $additionalExpenses;

        if ($total < 0) {
            throw new \RuntimeException(
                'إجمالي الفاتورة غير صالح.'
            );
        }

        /*
         * إذا كان هناك دفعات على المسودة،
         * لا نسمح بتعديل الفاتورة حتى يصبح إجماليها أقل من المدفوع.
         */
        if (
            $paidAmount
            > $total
            + 0.00001
        ) {
            throw new \RuntimeException(
                'إجمالي الفاتورة لا يمكن أن يكون أقل من المبلغ المدفوع المسجل عليها.'
            );
        }

        /*
         * تكلفة الوصول الصحيحة للبند:
         *
         * صافي البند بعد Line Discount
         * - حصة البند من خصم الفاتورة
         * + حصة البند من الشحن والمصاريف.
         *
         * هذا يمنع تضخيم متوسط تكلفة المنتج.
         */
        $this->allocateLandedCosts(
            $items,
            $subtotal,
            $discountAmount,
            $shippingCost
                + $additionalExpenses
        );

        $remaining =
            max(
                0,
                $total
                    - $paidAmount
            );

        $invoice->update([
            'subtotal' =>
            round(
                $subtotal,
                2
            ),

            'total_amount' =>
            round(
                $total,
                2
            ),

            'paid_amount' =>
            round(
                $paidAmount,
                2
            ),

            'remaining_amount' =>
            round(
                $remaining,
                2
            ),

            'payment_status' =>
            $this
                ->resolvePaymentStatus(
                    $paidAmount,
                    $total
                )
                ->value,
        ]);

        return $invoice
            ->fresh();
    }

    /**
     * توزيع الخصم العام والمصاريف على البنود.
     */
    protected function allocateLandedCosts(
        Collection $items,
        float $subtotal,
        float $invoiceDiscount,
        float $totalExpenses
    ): void {
        if ($subtotal <= 0) {
            foreach (
                $items as $item
            ) {
                $item->update([
                    'allocated_expenses' =>
                    0,

                    'landed_unit_cost' =>
                    0,

                    'landed_line_cost' =>
                    0,
                ]);
            }

            return;
        }

        /*
         * كل التوزيع يتم بالسنتات، وليس Float خام.
         * هذا يضمن أن مجموع تكلفة الوصول لكل البنود يساوي
         * إجمالي الفاتورة بالضبط حتى مع كميات مثل 3 × 33.33.
         */
        $weights = [];

        foreach ($items as $item) {
            $weights[(int) $item->id] =
                $this->moneyToCents(
                    $item->line_total
                );
        }

        $discountShares =
            $this->allocateCentsByWeights(
                $weights,
                $this->moneyToCents(
                    $invoiceDiscount
                )
            );

        $expenseShares =
            $this->allocateCentsByWeights(
                $weights,
                $this->moneyToCents(
                    $totalExpenses
                )
            );

        foreach ($items as $item) {
            $id =
                (int) $item->id;

            $lineCents =
                $weights[$id]
                ?? 0;

            $discountCents =
                $discountShares[$id]
                ?? 0;

            $expenseCents =
                $expenseShares[$id]
                ?? 0;

            $landedLineCents =
                max(
                    0,
                    $lineCents
                    - $discountCents
                    + $expenseCents
                );

            $quantity =
                max(
                    1,
                    (int) $item->quantity
                );

            $landedLineCost =
                $this->centsToMoney(
                    $landedLineCents
                );

            $landedUnitCost =
                $landedLineCost
                / $quantity;

            $item->update([
                'allocated_expenses' =>
                $this->centsToMoney(
                    $expenseCents
                ),

                /*
                 * للاستخدام والعرض المتوافق مع النظام القديم.
                 * القيمة المرجعية الدقيقة أصبحت landed_line_cost.
                 */
                'landed_unit_cost' =>
                round(
                    $landedUnitCost,
                    2
                ),

                'landed_line_cost' =>
                $landedLineCost,
            ]);
        }
    }

    /**
     * توزيع عدد محدد من السنتات على البنود بنسبة أوزانها
     * مع Largest Remainder حتى يساوي المجموع الهدف حرفياً.
     *
     * @param array<int,int> $weights
     * @return array<int,int>
     */
    protected function allocateCentsByWeights(
        array $weights,
        int $totalCents
    ): array {
        $result =
            array_fill_keys(
                array_keys($weights),
                0
            );

        if ($totalCents <= 0) {
            return $result;
        }

        $weightTotal =
            array_sum($weights);

        if ($weightTotal <= 0) {
            return $result;
        }

        $remainders = [];
        $allocated = 0;

        foreach ($weights as $id => $weight) {
            $raw =
                $totalCents
                * ($weight / $weightTotal);

            $floor =
                (int) floor($raw);

            $result[$id] =
                $floor;

            $allocated +=
                $floor;

            $remainders[$id] =
                $raw - $floor;
        }

        $remaining =
            $totalCents
            - $allocated;

        arsort(
            $remainders,
            SORT_NUMERIC
        );

        foreach (
            array_keys($remainders)
            as $id
        ) {
            if ($remaining <= 0) {
                break;
            }

            $result[$id]++;
            $remaining--;
        }

        return $result;
    }

    protected function moneyToCents(
        mixed $amount
    ): int {
        return (int) round(
            (float) $amount
            * 100
        );
    }

    protected function centsToMoney(
        int $cents
    ): float {
        return round(
            $cents / 100,
            2
        );
    }

    protected function exactLandedLineCost(
        PurchaseInvoiceItem $item
    ): float {
        if ($item->landed_line_cost !== null) {
            return round(
                (float) $item->landed_line_cost,
                2
            );
        }

        /*
         * توافق مع الفواتير القديمة قبل إضافة snapshot الجديد.
         */
        return round(
            (int) $item->quantity
            * (float) $item->landed_unit_cost,
            2
        );
    }

    /**
     * اعتماد الفاتورة:
     * - قفل الفاتورة.
     * - تحديث المخزون.
     * - تحديث المتوسط المرجح باستخدام إجمالي مخزون المنتج.
     * - ترحيل دفعات المشتريات للحسابات المالية.
     */
    public function approve(
        PurchaseInvoice $invoice
    ): PurchaseInvoice {
        return DB::transaction(
            function () use (
                $invoice
            ): PurchaseInvoice {
                $lockedInvoice =
                    PurchaseInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $invoice->id
                    );

                if (
                    $lockedInvoice->status
                    !== PurchaseInvoiceStatus::DRAFT
                ) {
                    throw new \RuntimeException(
                        'لا يمكن اعتماد هذه الفاتورة لأنها ليست مسودة.'
                    );
                }

                if (
                    ! $lockedInvoice
                        ->items()
                        ->exists()
                ) {
                    throw new \RuntimeException(
                        'لا يمكن اعتماد فاتورة بدون منتجات.'
                    );
                }

                $lockedInvoice =
                    $this->recalculate(
                        $lockedInvoice
                    );

                $lockedInvoice->load([
                    'items.product',
                    'items.warehouse',
                    'payments',
                ]);

                $this->assertUniqueProducts(
                    $lockedInvoice
                        ->items
                        ->map(
                            fn($item) => [
                                'product_id' =>
                                $item
                                    ->product_id,
                            ]
                        )
                        ->all()
                );

                foreach (
                    $lockedInvoice
                        ->items as $item
                ) {
                    /*
                     * نقفل المنتج نفسه لأن purchase_price
                     * متوسط مشترك لكل المخازن.
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

                    $currentTotalStock =
                        (int) $stockRows
                            ->sum(
                                'quantity'
                            );

                    $targetStock =
                        $stockRows
                        ->firstWhere(
                            'warehouse_id',
                            $item
                                ->warehouse_id
                        );

                    $targetOldQuantity =
                        (int) (
                            $targetStock
                            ?->quantity
                            ?? 0
                        );

                    $targetNewQuantity =
                        $targetOldQuantity
                        + (int) $item
                            ->quantity;

                    $newTotalStock =
                        $currentTotalStock
                        + (int) $item
                            ->quantity;

                    $oldAverageCost =
                        (float) (
                            $product
                            ->purchase_price
                            ?? 0
                        );

                    $incomingInventoryValue =
                        $this->exactLandedLineCost(
                            $item
                        );

                    /*
                     * المتوسط المرجح يستخدم القيمة الكاملة الدقيقة
                     * للبند، وليس landed_unit_cost × quantity، حتى
                     * لا نفقد فروقات السنتات بسبب تقريب سعر الوحدة.
                     */
                    $newAverageCost =
                        $newTotalStock > 0
                        ? (
                            (
                                $currentTotalStock
                                * $oldAverageCost
                            )
                            + $incomingInventoryValue
                        )
                        / $newTotalStock
                        : (
                            $incomingInventoryValue
                            / max(
                                1,
                                (int) $item->quantity
                            )
                        );

                    if ($targetStock) {
                        $targetStock->update([
                            'quantity' =>
                            $targetNewQuantity,
                        ]);
                    } else {
                        ProductStock::create([
                            'product_id' =>
                            $product->id,

                            'warehouse_id' =>
                            $item
                                ->warehouse_id,

                            'quantity' =>
                            (int) $item
                                ->quantity,
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
                        $product->id,

                        'warehouse_id' =>
                        $item
                            ->warehouse_id,

                        'type' =>
                        StockMovementType::PURCHASE
                            ->value,

                        'quantity' =>
                        (int) $item
                            ->quantity,

                        'quantity_before' =>
                        $targetOldQuantity,

                        'quantity_after' =>
                        $targetNewQuantity,

                        'notes' =>
                        "شراء من فاتورة {$lockedInvoice->invoice_number}",

                        'reference_type' =>
                        'purchase_invoice',

                        'reference_id' =>
                        $lockedInvoice
                            ->id,
                    ]);
                }

                $lockedInvoice->update([
                    'status' =>
                    PurchaseInvoiceStatus::APPROVED
                        ->value,

                    'approved_at' =>
                    now(),
                ]);

                $lockedInvoice->refresh();
                $lockedInvoice->load(
                    'payments'
                );

                /*
                 * دفعات المسودة تصبح Outflow حقيقي فقط
                 * بعد اعتماد الفاتورة.
                 */
                foreach (
                    $lockedInvoice
                        ->payments as $payment
                ) {
                    $account =
                        $this
                        ->financeService
                        ->resolveAccountForPayment(
                            $payment
                                ->payment_method,
                            $payment
                                ->financial_account_id
                        );

                    $this
                        ->financeService
                        ->linkPaymentToAccount(
                            $payment,
                            $account,
                            'purchase',
                            "دفعة مشتريات - {$lockedInvoice->invoice_number}"
                        );
                }

                return $lockedInvoice
                    ->fresh()
                    ->load([
                        'supplier',
                        'items.product',
                        'items.warehouse',
                        'payments.financialAccount',
                    ]);
            }
        );
    }

    /**
     * تسجيل دفعة.
     *
     * في المسودة: تحفظ الدفعة فقط.
     * في الفاتورة المعتمدة: تحفظ وترحل مالياً فوراً.
     */
    public function addPayment(
        PurchaseInvoice $invoice,
        array $data
    ): PurchasePayment {
        return DB::transaction(
            function () use (
                $invoice,
                $data
            ): PurchasePayment {
                $lockedInvoice =
                    PurchaseInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $invoice->id
                    );

                if (
                    $lockedInvoice->status
                    === PurchaseInvoiceStatus::CANCELLED
                ) {
                    throw new \RuntimeException(
                        'لا يمكن إضافة دفعة لفاتورة ملغاة.'
                    );
                }

                if (
                    ! in_array(
                        $lockedInvoice->status,
                        [
                            PurchaseInvoiceStatus::DRAFT,
                            PurchaseInvoiceStatus::APPROVED,
                        ],
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        'لا يمكن إضافة دفعة لهذه الفاتورة.'
                    );
                }

                $amount =
                    (float) (
                        $data['amount']
                        ?? 0
                    );

                $remaining =
                    (float) (
                        $lockedInvoice
                        ->remaining_amount
                        ?? 0
                    );

                if ($amount <= 0) {
                    throw new \RuntimeException(
                        'المبلغ يجب أن يكون أكبر من صفر.'
                    );
                }

                if ($remaining <= 0) {
                    throw new \RuntimeException(
                        'الفاتورة مدفوعة بالكامل.'
                    );
                }

                if (
                    $amount
                    > $remaining
                    + 0.00001
                ) {
                    throw new \RuntimeException(
                        'قيمة الدفعة أكبر من المبلغ المتبقي على الفاتورة.'
                    );
                }

                $account =
                    $this
                    ->financeService
                    ->resolveAccountForPayment(
                        $data['payment_method'],
                        isset(
                            $data['financial_account_id']
                        )
                            ? (int) $data['financial_account_id']
                            : null
                    );
                $bankOrAppName = null;

                if (
                    in_array(
                        $data['payment_method'],
                        [
                            'bank_transfer',
                            'banking_app',
                        ],
                        true
                    )
                ) {
                    /*
     * لا نعتمد على أن المستخدم يكتب اسم البنك يدوياً.
     * اسم الحساب المالي هو المصدر الأساسي.
     */
                    $bankOrAppName =
                        trim(
                            (string) (
                                $data['bank_or_app_name']
                                ?? ''
                            )
                        )
                        ?: $account->name;
                }

                $payment =
                    PurchasePayment::create([
                        'purchase_invoice_id' =>
                        $lockedInvoice->id,

                        'amount' =>
                        round(
                            $amount,
                            2
                        ),

                        'financial_account_id' =>
                        $account->id,

                        'payment_method' =>
                        $data['payment_method'],

                        'bank_or_app_name' =>
                        $bankOrAppName,

                        'transaction_reference' =>
                        $data['transaction_reference'] ?? null,

                        'paid_at' =>
                        $data['paid_at']
                            ?? now(),

                        'notes' =>
                        $data['notes']
                            ?? null,
                    ]);

                $newPaidAmount =
                    (float) (
                        $lockedInvoice
                        ->paid_amount
                        ?? 0
                    )
                    + $amount;

                $lockedInvoice->update([
                    'paid_amount' =>
                    round(
                        $newPaidAmount,
                        2
                    ),

                    'remaining_amount' =>
                    round(
                        max(
                            0,
                            (float) $lockedInvoice
                                ->total_amount
                                - $newPaidAmount
                        ),
                        2
                    ),

                    'payment_status' =>
                    $this
                        ->resolvePaymentStatus(
                            $newPaidAmount,
                            (float) $lockedInvoice
                                ->total_amount
                        )
                        ->value,
                ]);

                if (
                    $lockedInvoice->status
                    === PurchaseInvoiceStatus::APPROVED
                ) {
                    $this
                        ->financeService
                        ->linkPaymentToAccount(
                            $payment,
                            $account,
                            'purchase',
                            "دفعة مشتريات - {$lockedInvoice->invoice_number}"
                        );
                }

                return $payment
                    ->fresh()
                    ->load(
                        'financialAccount'
                    );
            }
        );
    }

    /**
     * إلغاء فاتورة معتمدة.
     *
     * حفاظاً على التكلفة والمخزون:
     * لا نسمح بالإلغاء إذا حدثت حركة لاحقة للمنتج بعد اعتماد الفاتورة.
     * في هذه الحالة يجب استخدام مرتجع مشتريات.
     */
    public function cancel(
        PurchaseInvoice $invoice,
        string $reason
    ): PurchaseInvoice {
        return DB::transaction(
            function () use (
                $invoice,
                $reason
            ): PurchaseInvoice {
                $lockedInvoice =
                    PurchaseInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $invoice->id
                    );

                if (
                    $lockedInvoice->status
                    !== PurchaseInvoiceStatus::APPROVED
                ) {
                    throw new \RuntimeException(
                        'يمكن إلغاء فاتورة شراء معتمدة فقط.'
                    );
                }

                if (
                    (float) (
                        $lockedInvoice
                        ->paid_amount
                        ?? 0
                    ) > 0
                ) {
                    throw new \RuntimeException(
                        'لا يمكن إلغاء فاتورة شراء تحتوي على دفعات. استخدم مرتجع المشتريات لتسوية المخزون ومستحقات المورد بصورة صحيحة.'
                    );
                }

                $hasReturns =
                    PurchaseReturn::query()
                    ->where(
                        'purchase_invoice_id',
                        $lockedInvoice->id
                    )
                    ->where(
                        'status',
                        '!=',
                        'cancelled'
                    )
                    ->exists();

                if ($hasReturns) {
                    throw new \RuntimeException(
                        'لا يمكن إلغاء الفاتورة لوجود مرتجع مشتريات مرتبط بها.'
                    );
                }

                $lockedInvoice->load([
                    'items.product',
                ]);

                $movementCutoff =
                    $lockedInvoice->approved_at
                    ?? $lockedInvoice->updated_at
                    ?? $lockedInvoice->created_at;

                foreach (
                    $lockedInvoice
                        ->items as $item
                ) {
                    /*
                     * إذا تحرك المنتج بعد اعتماد هذه الفاتورة،
                     * الإلغاء المباشر قد يشوه متوسط التكلفة.
                     * نستخدم fallback للفواتير القديمة التي قد لا تحتوي approved_at.
                     */
                    $laterMovementExists =
                        StockMovement::query()
                        ->where(
                            'product_id',
                            $item
                                ->product_id
                        )
                        ->where(
                            'created_at',
                            '>',
                            $movementCutoff
                        )
                        ->exists();

                    if ($laterMovementExists) {
                        throw new \RuntimeException(
                            "لا يمكن إلغاء الفاتورة مباشرة لأن المنتج {$item->product->name} تحرك بعد اعتمادها. استخدم مرتجع المشتريات."
                        );
                    }
                }

                foreach (
                    $lockedInvoice
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

                    $targetStock =
                        $stockRows
                        ->firstWhere(
                            'warehouse_id',
                            $item
                                ->warehouse_id
                        );

                    if (
                        ! $targetStock
                        || $targetStock
                        ->quantity
                        < $item
                        ->quantity
                    ) {
                        throw new \RuntimeException(
                            "لا يمكن إلغاء الفاتورة لأن كمية {$item->product->name} المضافة منها لم تعد كاملة في المخزن."
                        );
                    }

                    $currentTotalStock =
                        (int) $stockRows
                            ->sum('quantity');

                    $targetOldQuantity =
                        (int) $targetStock
                            ->quantity;

                    $targetNewQuantity =
                        $targetOldQuantity
                        - (int) $item
                            ->quantity;

                    $newTotalStock =
                        $currentTotalStock
                        - (int) $item
                            ->quantity;

                    /*
                     * بما أننا منعنا أي حركة لاحقة،
                     * يمكن عكس أثر هذه الدفعة من المتوسط المرجح بأمان.
                     */
                    $currentAverageCost =
                        (float) (
                            $product
                            ->purchase_price
                            ?? 0
                        );

                    $currentInventoryCost =
                        $currentTotalStock
                        * $currentAverageCost;

                    $removedCost =
                        $this->exactLandedLineCost(
                            $item
                        );

                    $newAverageCost =
                        $newTotalStock > 0
                        ? max(
                            0,
                            (
                                $currentInventoryCost
                                - $removedCost
                            )
                                / $newTotalStock
                        )
                        : 0;

                    $targetStock->update([
                        'quantity' =>
                        $targetNewQuantity,
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
                        StockMovementType::PURCHASE_CANCELLATION
                            ->value,

                        'quantity' =>
                        (int) $item
                            ->quantity,

                        'quantity_before' =>
                        $targetOldQuantity,

                        'quantity_after' =>
                        $targetNewQuantity,

                        'notes' =>
                        "إلغاء فاتورة {$lockedInvoice->invoice_number}",

                        'reference_type' =>
                        'purchase_invoice',

                        'reference_id' =>
                        $lockedInvoice
                            ->id,
                    ]);
                }

                $lockedInvoice->update([
                    'status' =>
                    PurchaseInvoiceStatus::CANCELLED
                        ->value,

                    'cancelled_at' =>
                    now(),

                    'cancellation_reason' =>
                    trim(
                        $reason
                    ),
                ]);

                return $lockedInvoice
                    ->fresh();
            }
        );
    }

    /**
     * تحديث المسودة فقط.
     */
    public function update(
        PurchaseInvoice $invoice,
        array $data
    ): PurchaseInvoice {
        return DB::transaction(
            function () use (
                $invoice,
                $data
            ): PurchaseInvoice {
                $lockedInvoice =
                    PurchaseInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $invoice->id
                    );

                if (
                    $lockedInvoice->status
                    !== PurchaseInvoiceStatus::DRAFT
                ) {
                    throw new \RuntimeException(
                        'لا يمكن تعديل فاتورة شراء معتمدة أو ملغاة.'
                    );
                }

                $supplier =
                    Supplier::query()
                    ->findOrFail(
                        $data['supplier_id']
                    );

                if (! $supplier->is_active) {
                    throw new \RuntimeException(
                        'المورد غير نشط.'
                    );
                }

                $resolvedItems =
                    $this->resolveInlineProducts(
                        $data['items']
                            ?? []
                    );

                $this->assertUniqueProducts(
                    $resolvedItems
                );

                $lockedInvoice->update([
                    'supplier_id' =>
                    $supplier->id,

                    'supplier_invoice_number' =>
                    $data['supplier_invoice_number'] ?? null,

                    'purchase_date' =>
                    $data['purchase_date'],

                    'discount_amount' =>
                    $this->nonNegative(
                        $data['discount_amount'] ?? 0,
                        'خصم الفاتورة'
                    ),

                    'shipping_cost' =>
                    $this->nonNegative(
                        $data['shipping_cost'] ?? 0,
                        'تكلفة الشحن'
                    ),

                    'additional_expenses' =>
                    $this->nonNegative(
                        $data['additional_expenses'] ?? 0,
                        'المصاريف الإضافية'
                    ),

                    'due_date' =>
                    $data['due_date']
                        ?? null,

                    'notes' =>
                    $data['notes']
                        ?? null,
                ]);

                $lockedInvoice
                    ->items()
                    ->delete();

                foreach (
                    $resolvedItems as $itemData
                ) {
                    $this->addItem(
                        $lockedInvoice,
                        $itemData
                    );
                }

                return $this
                    ->recalculate(
                        $lockedInvoice
                    )
                    ->fresh()
                    ->load([
                        'items.product',
                        'items.warehouse',
                    ]);
            }
        );
    }

    protected function resolvePaymentStatus(
        float $paidAmount,
        float $totalAmount
    ): PaymentStatus {
        if (
            $totalAmount > 0
            && $paidAmount
            >= $totalAmount
            - 0.00001
        ) {
            return PaymentStatus::PAID;
        }

        if ($paidAmount > 0.00001) {
            return PaymentStatus::PARTIALLY_PAID;
        }

        return PaymentStatus::UNPAID;
    }


    /**
     * إنشاء المنتجات الجديدة المضافة من شاشة فاتورة الشراء.
     *
     * القاعدة المحاسبية هنا مهمة:
     * - Product يتم إنشاؤه كرأس بيانات فقط.
     * - purchase_price يبدأ = 0.
     * - لا يتم إنشاء ProductStock ولا Opening Movement.
     * - عند اعتماد الفاتورة فقط، approve() يضيف الكمية ويحسب
     *   متوسط التكلفة الحقيقي من Landed Cost.
     *
     * مثال:
     * منتج جديد + كمية فاتورة 6
     * قبل الاعتماد: stock = 0
     * بعد الاعتماد: stock = 6
     * وليس 7.
     */
    private function resolveInlineProducts(
        array $items
    ): array {
        $resolved = [];

        foreach (
            $items as $item
        ) {
            $mode =
                $item['product_mode']
                    ?? 'existing';

            if ($mode !== 'new') {
                if (
                    empty(
                        $item['product_id']
                    )
                ) {
                    throw new \RuntimeException(
                        'أحد بنود الفاتورة لا يحتوي على منتج صالح.'
                    );
                }

                $resolved[] =
                    $item;

                continue;
            }

            $newProduct =
                $item['new_product']
                    ?? [];

            $product =
                $this->productService
                    ->create(
                        [
                            'category_id' =>
                            (int) (
                                $newProduct[
                                    'category_id'
                                ]
                                ?? 0
                            ),

                            'name' =>
                            trim(
                                (string) (
                                    $newProduct[
                                        'name'
                                    ]
                                    ?? ''
                                )
                            ),

                            /*
                             * فارغ = ProductService يولد الكود.
                             */
                            'code' =>
                            $newProduct[
                                'code'
                            ]
                                ?? null,

                            'barcode' =>
                            $newProduct[
                                'barcode'
                            ]
                                ?? null,

                            'brand' =>
                            $newProduct[
                                'brand'
                            ]
                                ?? null,

                            'model' =>
                            $newProduct[
                                'model'
                            ]
                                ?? null,

                            /*
                             * لا نسجل تكلفة شراء وهمية قبل اعتماد الشراء.
                             * approve() سيحدثها إلى المتوسط المرجح الحقيقي.
                             */
                            'purchase_price' =>
                            0,

                            'selling_price' =>
                            round(
                                (float) (
                                    $newProduct[
                                        'selling_price'
                                    ]
                                    ?? 0
                                ),
                                2
                            ),

                            'minimum_selling_price' =>
                            $newProduct[
                                'minimum_selling_price'
                            ]
                                ?? null,

                            'low_stock_threshold' =>
                            (int) (
                                $newProduct[
                                    'low_stock_threshold'
                                ]
                                ?? 5
                            ),

                            'location' =>
                            $newProduct[
                                'location'
                            ]
                                ?? null,

                            /*
                             * صفر رصيد افتتاحي دائماً.
                             */
                            'opening_stocks' =>
                            [],

                            'description' =>
                            $newProduct[
                                'description'
                            ]
                                ?? null,

                            'notes' =>
                            $newProduct[
                                'notes'
                            ]
                                ?? null,

                            'is_active' =>
                            true,
                        ]
                    );

            $item['product_id'] =
                $product->id;

            /*
             * بعد الحل لا يحتاج addItem لهذه البيانات.
             */
            unset(
                $item['new_product']
            );

            $item['product_mode'] =
                'existing';

            $resolved[] =
                $item;
        }

        return $resolved;
    }

    private function assertUniqueProducts(
        array $items
    ): void {
        $productIds =
            collect($items)
            ->pluck(
                'product_id'
            )
            ->filter();

        if (
            $productIds
            ->duplicates()
            ->isNotEmpty()
        ) {
            throw new \RuntimeException(
                'لا تكرر نفس المنتج في الفاتورة. عدّل الكمية أو المخزن في البند الموجود.'
            );
        }
    }

    private function nonNegative(
        mixed $value,
        string $label
    ): float {
        $number =
            (float) (
                $value
                ?? 0
            );

        if ($number < 0) {
            throw new \RuntimeException(
                "{$label} لا يمكن أن يكون سالباً."
            );
        }

        return round(
            $number,
            2
        );
    }
}
