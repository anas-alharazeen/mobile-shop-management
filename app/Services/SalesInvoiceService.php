<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SalesInvoiceStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesPayment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SalesInvoiceService
{
    public function __construct(private readonly FinancialAccountService $financeService)
    {
    }

    protected function salesWarehouse(): Warehouse
    {
        $warehouse =
            Warehouse::query()
                ->where(
                    'type',
                    'sales'
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('id')
                ->first();

        if (! $warehouse) {
            throw new \RuntimeException(
                'لا يوجد مخزن مبيعات نشط.'
            );
        }

        return $warehouse;
    }

    /**
     * المخزن المثبت تاريخياً داخل بند فاتورة البيع.
     *
     * بعد إنشاء البند لا يجوز الرجوع إلى "أول مخزن مبيعات"
     * لأن المخزن التشغيلي قد يتغير لاحقاً. الاعتماد والإلغاء
     * والمرتجعات يجب أن تتبع warehouse_id الموجود في البند.
     */
    protected function itemWarehouse(
        SalesInvoiceItem $item,
        bool $requireActive = true
    ): Warehouse {
        $query =
            Warehouse::query()
                ->whereKey(
                    $item->warehouse_id
                )
                ->where(
                    'type',
                    'sales'
                );

        if ($requireActive) {
            $query->where(
                'is_active',
                true
            );
        }

        $warehouse =
            $query->first();

        if (! $warehouse) {
            throw new \RuntimeException(
                $requireActive
                    ? "مخزن المبيعات المرتبط بالمنتج «{$item->product_name}» غير موجود أو غير نشط."
                    : "تعذر العثور على مخزن المبيعات المرتبط بالمنتج «{$item->product_name}»."
            );
        }

        return $warehouse;
    }

    /**
     * إنشاء فاتورة مبيعات مسودة
     */
    public function create(array $data): SalesInvoice
    {
        return DB::transaction(function () use ($data) {
            // معالجة العميل
            $customer = $this->handleCustomer($data);

            $invoice = SalesInvoice::create([
                'invoice_number' => SalesInvoice::generateNumber(),
                'customer_id' => $customer?->id,
                'customer_name' => $data['customer_name'] ?? ($customer?->name ?? 'عميل نقدي'),
                'customer_phone' => $data['customer_phone'] ?? ($customer?->phone ?? null),
                'sale_date' => $data['sale_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'status' => SalesInvoiceStatus::DRAFT,
                'invoice_discount' => $data['invoice_discount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'payment_status' => PaymentStatus::UNPAID->value,
            ]);

            // إضافة العناصر
            if (isset($data['items']) && count($data['items']) > 0) {
                foreach ($data['items'] as $itemData) {
                    $this->addItem($invoice, $itemData);
                }
            }

            // إعادة الحساب
            $this->recalculate($invoice);

            // تسجيل الدفعات الأولية
            if (isset($data['payments']) && count($data['payments']) > 0) {
                foreach ($data['payments'] as $paymentData) {
                    $this->addPayment($invoice, $paymentData);
                }
            }

            return $invoice->fresh()->load('items');
        });
    }

    /**
     * معالجة بيانات العميل
     */
    protected function handleCustomer(array $data): ?Customer
    {
        if (isset($data['customer_id']) && $data['customer_id']) {
            return Customer::find($data['customer_id']);
        }

        if (isset($data['save_customer']) && $data['save_customer']) {
            $customerData = [
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? null,
                'is_active' => true,
            ];

            if ($data['customer_phone']) {
                $existing = Customer::where('phone', $data['customer_phone'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            return Customer::create([
                'code' => Customer::generateCode(),
                ...$customerData,
            ]);
        }

        return null;
    }

    /**
     * إضافة عنصر للفاتورة
     */
    public function addItem(SalesInvoice $invoice, array $data): SalesInvoiceItem
    {
        if (!$invoice->can_be_edited) {
            throw new \Exception('لا يمكن تعديل فاتورة معتمدة');
        }

        $product = Product::findOrFail($data['product_id']);

        if (!$product->is_active) {
            throw new \Exception('المنتج غير نشط');
        }

        $quantity = $data['quantity'];
        $sellingPrice = $data['unit_selling_price'] ?? $product->selling_price;
        $unitCost = $product->purchase_price;

        if ($quantity <= 0) {
            throw new \Exception('الكمية يجب أن تكون أكبر من صفر');
        }

        if ($sellingPrice < 0) {
            throw new \Exception('سعر البيع لا يمكن أن يكون سالباً');
        }

        $lineSubtotal = $quantity * $sellingPrice;
        $lineDiscount = $data['line_discount'] ?? 0;
        if ($lineDiscount > $lineSubtotal) {
            throw new \Exception('خصم البند لا يمكن أن يتجاوز إجمالي البند');
        }

        $lineTotal = $lineSubtotal - $lineDiscount;

        /*
         * نثبت مخزن البيع داخل بند الفاتورة وقت إنشائه.
         */
        $warehouse =
            $this->salesWarehouse();

        return SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'unit_selling_price' => $sellingPrice,
            'line_discount' => $lineDiscount,
            'allocated_invoice_discount' => 0,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $lineTotal,
            'line_cost' => $quantity * $unitCost,
            'line_profit' => $lineTotal - ($quantity * $unitCost),
        ]);
    }

    /**
     * إعادة حساب إجماليات الفاتورة
     */
    public function recalculate(SalesInvoice $invoice): SalesInvoice
    {
        /*
         * نعيد تحميل البنود قبل الحساب حتى لا نعتمد على
         * Relation قديمة إذا تم تعديل المسودة في نفس الطلب.
         */
        $invoice->load('items');

        $items = $invoice->items;
        $subtotal = $items->sum('line_subtotal');
        $itemsDiscount = $items->sum('line_discount');
        $invoiceDiscount = $invoice->invoice_discount ?? 0;
        $totalCost = $items->sum('line_cost');
        $netBeforeInvoiceDiscount = $subtotal - $itemsDiscount;

        if ($invoiceDiscount < 0 || $invoiceDiscount > $netBeforeInvoiceDiscount) {
            throw new \Exception('خصم الفاتورة لا يمكن أن يتجاوز إجماليها بعد خصومات البنود');
        }

        // توزيع خصم الفاتورة على العناصر
        $this->allocateInvoiceDiscount($invoice);

        /*
         * بعد التوزيع نستخدم مجموع line_total نفسه كمصدر نهائي
         * حتى تتطابق الفاتورة مع البنود والمرتجعات حرفياً.
         */
        $totalAmount =
            round(
                (float) $invoice
                    ->items()
                    ->sum('line_total'),
                2
            );

        $remainingAmount =
            $totalAmount
            - (float) (
                $invoice->paid_amount
                ?? 0
            );

        $invoice->update([
            'subtotal' =>
                round(
                    (float) $subtotal,
                    2
                ),

            'items_discount' =>
                round(
                    (float) $itemsDiscount,
                    2
                ),

            'total_amount' =>
                $totalAmount,

            'total_cost' =>
                round(
                    (float) $totalCost,
                    2
                ),

            'gross_profit' =>
                round(
                    $totalAmount
                    - (float) $totalCost,
                    2
                ),

            'remaining_amount' =>
                round(
                    max(
                        0,
                        $remainingAmount
                    ),
                    2
                ),
        ]);

        $this->updatePaymentStatus($invoice);

        return $invoice->fresh();
    }

    /**
     * توزيع خصم الفاتورة على العناصر
     */
    protected function allocateInvoiceDiscount(SalesInvoice $invoice): void
    {
        /*
         * قاعدة التوزيع الصحيحة:
         *
         * 1) خصم البند يطبق أولاً.
         * 2) خصم الفاتورة يوزع على صافي البنود بعد خصم البند،
         *    وليس على line_subtotal الخام.
         * 3) نوزع بالسنت حتى يكون مجموع الحصص مطابقاً تماماً
         *    لخصم الفاتورة ولا تظهر فروقات 0.01 بسبب التقريب.
         * 4) لا يمكن أن تصبح قيمة أي بند سالبة.
         */
        $invoice->load('items');

        $items =
            $invoice->items
                ->sortBy('id')
                ->values();

        if ($items->isEmpty()) {
            return;
        }

        $invoiceDiscountCents =
            $this->moneyToCents(
                $invoice->invoice_discount
                ?? 0
            );

        $rows = [];
        $totalNetCents = 0;

        foreach ($items as $item) {
            $subtotalCents =
                $this->moneyToCents(
                    $item->line_subtotal
                );

            $lineDiscountCents =
                $this->moneyToCents(
                    $item->line_discount
                );

            $netLineCents =
                max(
                    0,
                    $subtotalCents
                    - $lineDiscountCents
                );

            $rows[] = [
                'item' => $item,
                'net_line_cents' => $netLineCents,
                'allocated_cents' => 0,
                'remainder' => 0.0,
            ];

            $totalNetCents +=
                $netLineCents;
        }

        if (
            $invoiceDiscountCents < 0
            || $invoiceDiscountCents
                > $totalNetCents
        ) {
            throw new \RuntimeException(
                'خصم الفاتورة لا يمكن أن يتجاوز صافي إجمالي البنود بعد خصومات الأصناف.'
            );
        }

        /*
         * لا يوجد خصم فاتورة: نعيد ضبط الحقول بصورة دقيقة.
         */
        if ($invoiceDiscountCents === 0) {
            foreach ($rows as $row) {
                /** @var SalesInvoiceItem $item */
                $item = $row['item'];

                $lineTotal =
                    $this->centsToMoney(
                        $row['net_line_cents']
                    );

                $item->update([
                    'allocated_invoice_discount' => 0,
                    'line_total' => $lineTotal,
                    'line_profit' => round(
                        $lineTotal
                        - (float) $item->line_cost,
                        2
                    ),
                ]);
            }

            return;
        }

        if ($totalNetCents <= 0) {
            throw new \RuntimeException(
                'لا يمكن تطبيق خصم فاتورة على بنود صافي قيمتها صفر.'
            );
        }

        /*
         * Largest Remainder Method:
         * نوزع أولاً بالسنت باستخدام floor ثم نعطي السنتات
         * المتبقية لأكبر الكسور. بهذه الطريقة:
         * sum(allocated_invoice_discount) = invoice_discount بالضبط.
         */
        $allocatedCents = 0;

        foreach ($rows as $index => $row) {
            if ($row['net_line_cents'] <= 0) {
                continue;
            }

            $exactShare =
                $invoiceDiscountCents
                * $row['net_line_cents']
                / $totalNetCents;

            $baseShare =
                min(
                    $row['net_line_cents'],
                    (int) floor($exactShare)
                );

            $rows[$index]['allocated_cents'] =
                $baseShare;

            $rows[$index]['remainder'] =
                $exactShare
                - $baseShare;

            $allocatedCents +=
                $baseShare;
        }

        $remainingCents =
            $invoiceDiscountCents
            - $allocatedCents;

        if ($remainingCents > 0) {
            $priority =
                array_keys($rows);

            usort(
                $priority,
                function (
                    int $left,
                    int $right
                ) use ($rows): int {
                    $remainderCompare =
                        $rows[$right]['remainder']
                        <=> $rows[$left]['remainder'];

                    if ($remainderCompare !== 0) {
                        return $remainderCompare;
                    }

                    return
                        $rows[$left]['item']->id
                        <=> $rows[$right]['item']->id;
                }
            );

            while ($remainingCents > 0) {
                $distributedInPass = false;

                foreach ($priority as $index) {
                    if ($remainingCents <= 0) {
                        break;
                    }

                    if (
                        $rows[$index]['allocated_cents']
                        >= $rows[$index]['net_line_cents']
                    ) {
                        continue;
                    }

                    $rows[$index]['allocated_cents']++;
                    $remainingCents--;
                    $distributedInPass = true;
                }

                if (! $distributedInPass) {
                    throw new \RuntimeException(
                        'تعذر توزيع خصم الفاتورة على البنود بصورة صحيحة.'
                    );
                }
            }
        }

        $finalAllocatedCents = 0;

        foreach ($rows as $row) {
            /** @var SalesInvoiceItem $item */
            $item = $row['item'];

            $allocated =
                min(
                    $row['allocated_cents'],
                    $row['net_line_cents']
                );

            $lineTotalCents =
                max(
                    0,
                    $row['net_line_cents']
                    - $allocated
                );

            $allocatedAmount =
                $this->centsToMoney(
                    $allocated
                );

            $lineTotal =
                $this->centsToMoney(
                    $lineTotalCents
                );

            $item->update([
                'allocated_invoice_discount' =>
                    $allocatedAmount,

                'line_total' =>
                    $lineTotal,

                'line_profit' =>
                    round(
                        $lineTotal
                        - (float) $item->line_cost,
                        2
                    ),
            ]);

            $finalAllocatedCents +=
                $allocated;
        }

        if (
            $finalAllocatedCents
            !== $invoiceDiscountCents
        ) {
            throw new \RuntimeException(
                'مجموع خصم الفاتورة الموزع على البنود غير مطابق لقيمة الخصم.'
            );
        }
    }

    /**
     * تحويل المبلغ إلى سنتات لتجنب أخطاء float والتقريب.
     */
    protected function moneyToCents(
        mixed $value
    ): int {
        return (int) round(
            (float) $value * 100,
            0,
            PHP_ROUND_HALF_UP
        );
    }

    /**
     * إعادة السنتات إلى مبلغ عشري بدقة منزلتين.
     */
    protected function centsToMoney(
        int $cents
    ): float {
        return round(
            $cents / 100,
            2
        );
    }

    /**
     * تحديث حالة الدفع - الإصلاح
     */
    protected function updatePaymentStatus(SalesInvoice $invoice): void
    {
        $paid = $invoice->paid_amount ?? 0;
        $total = $invoice->total_amount ?? 0;

        if ($paid <= 0) {
            $status = PaymentStatus::UNPAID;
        } elseif ($paid >= $total && $total > 0) {
            $status = PaymentStatus::PAID;
        } else {
            $status = PaymentStatus::PARTIALLY_PAID;
        }

        $invoice->update([
            'payment_status' => $status->value,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, $total - $paid),
        ]);
    }

    /**
     * إضافة دفعة
     */
    public function addPayment(SalesInvoice $invoice, array $data): SalesPayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            /** @var SalesInvoice $lockedInvoice */
            $lockedInvoice = SalesInvoice::query()
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedInvoice->can_add_payment && $lockedInvoice->status !== SalesInvoiceStatus::DRAFT) {
                throw new \Exception('لا يمكن إضافة دفعة لهذه الفاتورة');
            }

            $amount = (float) $data['amount'];
            $remaining = (float) $lockedInvoice->remaining_amount;

            if ($amount <= 0) {
                throw new \Exception('المبلغ يجب أن يكون أكبر من صفر');
            }

            if ($amount > $remaining) {
                throw new \Exception('المبلغ المدفوع أكبر من المتبقي');
            }

            $method = $data['payment_method'] instanceof PaymentMethod
                ? $data['payment_method']
                : PaymentMethod::from($data['payment_method']);
            $account = $method === PaymentMethod::EXCHANGE_CREDIT
                ? null
                : $this->financeService->resolveAccountForPayment(
                    $method,
                    isset($data['financial_account_id']) ? (int) $data['financial_account_id'] : null
                );

            $payment = SalesPayment::create([
                'sales_invoice_id' => $lockedInvoice->id,
                'amount' => $amount,
                'financial_account_id' => $account?->id,
                'payment_method' => $method->value,
                'bank_or_app_name' => $data['bank_or_app_name'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedInvoice->update([
                'paid_amount' => (float) $lockedInvoice->paid_amount + $amount,
            ]);
            $this->updatePaymentStatus($lockedInvoice);

            if ($lockedInvoice->status === SalesInvoiceStatus::APPROVED && $account) {
                $this->financeService->linkPaymentToAccount(
                    $payment,
                    $account,
                    'sale',
                    "دفعة مبيعات - {$lockedInvoice->invoice_number}"
                );
            }

            return $payment;
        });
    }

    /**
     * اعتماد الفاتورة
     */
    public function approve(SalesInvoice $invoice): SalesInvoice
    {
        if (!$invoice->can_be_approved) {
            throw new \Exception('لا يمكن اعتماد هذه الفاتورة');
        }

        // التحقق من توفر الكميات
        $this->checkStockAvailability($invoice);

        return DB::transaction(function () use ($invoice) {
            // إعادة الحساب
            $this->recalculate($invoice);
            $invoice->refresh();

            foreach ($invoice->items as $item) {
                $product = $item->product;

                /*
                 * الخصم يتم من نفس المخزن المثبت في البند،
                 * وليس من مخزن آخر قد يصبح هو "الأول" لاحقاً.
                 */
                $warehouse =
                    $this->itemWarehouse(
                        $item,
                        true
                    );

                // قفل الرصيد
                $stock = ProductStock::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $item->quantity) {
                    throw new \Exception("الكمية غير متوفرة للمنتج: {$product->name}");
                }

                $oldQuantity = $stock->quantity;
                $newQuantity = $oldQuantity - $item->quantity;

                // تحديث الرصيد
                $stock->update(['quantity' => $newQuantity]);

                // تسجيل حركة المخزون
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => StockMovementType::SALE,
                    'quantity' => $item->quantity,
                    'quantity_before' => $oldQuantity,
                    'quantity_after' => $newQuantity,
                    'notes' => "بيع من فاتورة {$invoice->invoice_number}",
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                ]);
            }

            // تحديث حالة الفاتورة
            $invoice->update([
                'status' => SalesInvoiceStatus::APPROVED->value,
                'approved_at' => now(),
            ]);
            $invoice->refresh();

            foreach ($invoice->payments as $payment) {
                if ($payment->payment_method === PaymentMethod::EXCHANGE_CREDIT) {
                    continue;
                }

                $account = $this->financeService->resolveAccountForPayment(
                    $payment->payment_method,
                    $payment->financial_account_id
                );
                $this->financeService->linkPaymentToAccount(
                    $payment,
                    $account,
                    'sale',
                    "دفعة مبيعات - {$invoice->invoice_number}"
                );
            }

            return $invoice->fresh();
        });
    }

    /**
     * التحقق من توفر الكميات في المخزون
     */
    protected function checkStockAvailability(SalesInvoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            $warehouse =
                $this->itemWarehouse(
                    $item,
                    true
                );

            $stock =
                ProductStock::query()
                    ->where(
                        'product_id',
                        $item->product_id
                    )
                    ->where(
                        'warehouse_id',
                        $warehouse->id
                    )
                    ->first();

            $available =
                (int) (
                    $stock?->quantity
                    ?? 0
                );

            if (
                $available
                < $item->quantity
            ) {
                throw new \Exception(
                    "المنتج '{$item->product_name}' غير متوفر بالكمية المطلوبة في مخزن '{$warehouse->name}'. المتوفر: {$available}"
                );
            }
        }
    }

    /**
     * إلغاء الفاتورة المعتمدة
     */
    public function cancel(SalesInvoice $invoice, string $reason): SalesInvoice
    {
        if (!$invoice->can_be_cancelled) {
            throw new \Exception('لا يمكن إلغاء هذه الفاتورة');
        }

        if ((float) $invoice->paid_amount > 0) {
            throw new \Exception('لا يمكن إلغاء فاتورة تحتوي على دفعات. استخدم مرتجع المبيعات لتسوية المخزون والمبلغ بصورة صحيحة.');
        }

        return DB::transaction(function () use ($invoice, $reason) {
            /*
             * عند الإلغاء نعيد كل بند إلى نفس المخزن الذي
             * خرج منه وقت البيع، حتى لو أصبح المخزن غير نشط
             * لاحقاً. هذه حركة عكس تاريخية وليست عملية بيع جديدة.
             */
            foreach ($invoice->items as $item) {
                $warehouse =
                    $this->itemWarehouse(
                        $item,
                        false
                    );

                $stock =
                    ProductStock::query()
                        ->where(
                            'product_id',
                            $item->product_id
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
                                $item->product_id,

                            'warehouse_id' =>
                                $warehouse->id,

                            'quantity' =>
                                0,
                        ]);
                }

                $oldQuantity =
                    (int) $stock
                        ->quantity;

                $newQuantity =
                    $oldQuantity
                    + (int) $item
                        ->quantity;

                $stock->update([
                    'quantity' =>
                        $newQuantity,
                ]);

                StockMovement::create([
                    'product_id' =>
                        $item->product_id,

                    'warehouse_id' =>
                        $warehouse->id,

                    'type' =>
                        StockMovementType
                            ::SALE_CANCELLATION,

                    'quantity' =>
                        $item->quantity,

                    'quantity_before' =>
                        $oldQuantity,

                    'quantity_after' =>
                        $newQuantity,

                    'notes' =>
                        "إلغاء فاتورة {$invoice->invoice_number}",

                    'reference_type' =>
                        'sales_invoice',

                    'reference_id' =>
                        $invoice->id,
                ]);
            }

            $invoice->update([
                'status' => SalesInvoiceStatus::CANCELLED->value,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * تحديث الفاتورة (للمسودة فقط)
     */
    public function update(SalesInvoice $invoice, array $data): SalesInvoice
    {
        if (!$invoice->can_be_edited) {
            throw new \Exception('لا يمكن تعديل فاتورة معتمدة');
        }

        return DB::transaction(function () use ($invoice, $data) {
            // معالجة العميل
            $customer = $this->handleCustomer($data);

            $invoice->update([
                'customer_id' => $customer?->id ?? $invoice->customer_id,
                'customer_name' => $data['customer_name'] ?? ($customer?->name ?? $invoice->customer_name),
                'customer_phone' => $data['customer_phone'] ?? ($customer?->phone ?? $invoice->customer_phone),
                'sale_date' => $data['sale_date'] ?? $invoice->sale_date,
                'due_date' => $data['due_date'] ?? null,
                'invoice_discount' => $data['invoice_discount'] ?? $invoice->invoice_discount,
                'notes' => $data['notes'] ?? $invoice->notes,
            ]);

            // تحديث العناصر
            if (isset($data['items'])) {
                $invoice->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $this->addItem($invoice, $itemData);
                }
            }

            // إعادة الحساب
            $this->recalculate($invoice);

            return $invoice->fresh();
        });
    }
}
