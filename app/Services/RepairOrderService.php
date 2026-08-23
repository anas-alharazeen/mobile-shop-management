<?php

namespace App\Services;

use App\Enums\CustomerApprovalStatus;
use App\Enums\PaymentStatus;
use App\Enums\RepairMode;
use App\Enums\RepairOrderStatus;
use App\Enums\RepairSubStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\RepairOrder;
use App\Models\RepairPayment;
use App\Models\RepairPart;
use App\Models\RepairStatusHistory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RepairOrderService
{
    public function __construct(
        private readonly FinancialAccountService $financeService,
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * تحديث بيانات لوحة التحكم بعد اكتمال العملية بنجاح.
     */
    protected function invalidateDashboardCache(): void
    {
        /*
     * إذا كنا داخل Transaction، ننتظر حتى نجاح Commit.
     * بهذا لا يتم مسح الكاش عند حدوث Rollback.
     */
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function (): void {
                $this->dashboardService->clearDashboardCache();
            });

            return;
        }

        $this->dashboardService->clearDashboardCache();
    }
    protected function maintenanceWarehouse(): Warehouse
    {
        $warehouse =
            Warehouse::query()
                ->where(
                    'type',
                    'maintenance'
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('id')
                ->first();

        if (! $warehouse) {
            throw new \RuntimeException(
                'لا يوجد مخزن صيانة نشط. فعّل مخزن الصيانة قبل استخدام قطع الغيار.'
            );
        }

        return $warehouse;
    }

    /**
     * المخزن المثبت تاريخياً داخل قطعة الصيانة.
     *
     * عند الاعتماد نطلب أن يبقى المخزن نشطاً،
     * أما الإرجاع والإلغاء فهما حركتان عكسيتان ويجب
     * أن تعود الكمية إلى نفس المخزن الأصلي حتى لو
     * أصبح غير نشط لاحقاً.
     */
    protected function partWarehouse(
        RepairPart $part,
        bool $requireActive = true
    ): Warehouse {
        $query =
            Warehouse::query()
                ->whereKey(
                    $part->warehouse_id
                )
                ->where(
                    'type',
                    'maintenance'
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
                    ? "مخزن الصيانة المرتبط بالقطعة «{$part->product_name}» غير موجود أو غير نشط."
                    : "تعذر العثور على مخزن الصيانة الأصلي للقطعة «{$part->product_name}»."
            );
        }

        return $warehouse;
    }

    /**
     * إنشاء طلب صيانة جديد
     */
    public function create(array $data): RepairOrder
    {
        return DB::transaction(function () use ($data) {
            $initialTotal = max(
                (float) ($data['estimated_cost'] ?? 0),
                (float) ($data['inspection_fee'] ?? 0),
                (float) ($data['payment_amount'] ?? 0)
            );

            // معالجة العميل
            $customer = $this->handleCustomer($data);

            $order = RepairOrder::create([
                'order_number' => RepairOrder::generateNumber(),
                'customer_id' => $customer?->id,
                'customer_name' => $data['customer_name'] ?? ($customer?->name ?? ''),
                'customer_phone' => $data['customer_phone'] ?? ($customer?->phone ?? ''),
                'device_type' => $data['device_type'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'color' => $data['color'] ?? null,
                'problem_description' => $data['problem_description'],
                'device_condition' => $data['device_condition'] ?? null,
                'received_accessories' => $data['received_accessories'] ?? null,
                'lock_code' => $data['lock_code'] ?? null,
                'technician_name' => $data['technician_name'] ?? null,
                'status' => RepairOrderStatus::RECEIVED,
                'sub_status' => RepairSubStatus::WAITING_INSPECTION,
                'customer_approval_status' => CustomerApprovalStatus::NOT_REQUIRED,
                'estimated_cost' => $data['estimated_cost'] ?? null,
                'inspection_fee' => $data['inspection_fee'] ?? 0,
                'total_amount' => $initialTotal,
                'paid_amount' => 0,
                'remaining_amount' => $initialTotal,
                'payment_status' => PaymentStatus::UNPAID->value,
                'received_at' => $data['received_at'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            // تسجيل الدفعة الأولى
            if (isset($data['payment_amount']) && $data['payment_amount'] > 0) {
                $this->addPayment($order, [
                    'amount' => $data['payment_amount'],
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'financial_account_id' => $data['financial_account_id'] ?? null,
                    'bank_or_app_name' => $data['bank_or_app_name'] ?? null,
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            // تسجيل الحالة الأولية
            RepairStatusHistory::create([
                'repair_order_id' => $order->id,
                'to_status' => RepairOrderStatus::RECEIVED->value,
                'notes' => 'تم استلام الجهاز',
            ]);
            $this->invalidateDashboardCache();
            // إرفاق الصور (ستتم في الـ Controller)

            return $order->fresh()->load(['customer', 'attachments', 'parts', 'payments', 'statusHistories']);
        });
    }

    /**
     * إنشاء صيانة سريعة من شاشة واحدة.
     *
     * العملية كاملة داخل Transaction واحدة:
     * Order + Parts + Stock + Payment + Ready/Delivered.
     */
    public function createQuick(
        array $data
    ): RepairOrder {
        return DB::transaction(
            function () use (
                $data
            ) {
                $customer =
                    $this->handleCustomer(
                        $data
                    );

                $receivedAt =
                    Carbon::parse(
                        $data['received_at']
                        ?? now()
                    );

                if (
                    $receivedAt->isFuture()
                ) {
                    throw new \RuntimeException(
                        'وقت تسجيل الصيانة لا يمكن أن يكون في المستقبل.'
                    );
                }

                $serviceTotal =
                    round(
                        (float) (
                            $data[
                                'inspection_fee'
                            ] ?? 0
                        )
                        + (float) (
                            $data[
                                'labor_cost'
                            ] ?? 0
                        ),
                        2
                    );

                $order =
                    RepairOrder::create([
                        'order_number' =>
                            RepairOrder
                                ::generateNumber(),

                        'repair_mode' =>
                            RepairMode
                                ::QUICK,

                        'customer_id' =>
                            $customer?->id,

                        'customer_name' =>
                            $data[
                                'customer_name'
                            ]
                            ?? (
                                $customer?->name
                                ?? ''
                            ),

                        'customer_phone' =>
                            $data[
                                'customer_phone'
                            ]
                            ?? (
                                $customer?->phone
                                ?? ''
                            ),

                        'device_type' =>
                            $data[
                                'device_type'
                            ],

                        'brand' =>
                            $data[
                                'brand'
                            ],

                        'model' =>
                            $data[
                                'model'
                            ],

                        'color' =>
                            $data[
                                'color'
                            ] ?? null,

                        'problem_description' =>
                            $data[
                                'problem_description'
                            ],

                        'device_condition' =>
                            $data[
                                'device_condition'
                            ] ?? null,

                        'received_accessories' =>
                            null,

                        'lock_code' =>
                            null,

                        'technician_name' =>
                            $data[
                                'technician_name'
                            ] ?? null,

                        /*
                         * السريع يدخل مباشرة قيد التنفيذ؛
                         * التشخيص معروف في نفس اللحظة.
                         */
                        'status' =>
                            RepairOrderStatus
                                ::IN_PROGRESS,

                        'sub_status' =>
                            RepairSubStatus
                                ::NONE,

                        'inspection_result' =>
                            $data[
                                'inspection_result'
                            ],

                        'fault_cause' =>
                            $data[
                                'fault_cause'
                            ] ?? null,

                        'repair_action' =>
                            $data[
                                'repair_action'
                            ],

                        'customer_approval_status' =>
                            CustomerApprovalStatus
                                ::NOT_REQUIRED,

                        'estimated_cost' =>
                            $serviceTotal,

                        'inspection_fee' =>
                            (float) (
                                $data[
                                    'inspection_fee'
                                ] ?? 0
                            ),

                        'labor_cost' =>
                            (float) (
                                $data[
                                    'labor_cost'
                                ] ?? 0
                            ),

                        'parts_cost' =>
                            0,

                        'total_amount' =>
                            $serviceTotal,

                        'paid_amount' =>
                            0,

                        'remaining_amount' =>
                            $serviceTotal,

                        'payment_status' =>
                            $serviceTotal <= 0
                                ? PaymentStatus
                                    ::PAID
                                    ->value
                                : PaymentStatus
                                    ::UNPAID
                                    ->value,

                        'received_at' =>
                            $receivedAt,

                        'due_date' =>
                            null,

                        'expected_delivery_date' =>
                            null,

                        'customer_notes' =>
                            $data[
                                'customer_notes'
                            ] ?? null,

                        'internal_notes' =>
                            $data[
                                'internal_notes'
                            ] ?? null,
                    ]);

                RepairStatusHistory::create([
                    'repair_order_id' =>
                        $order->id,

                    'from_status' =>
                        null,

                    'to_status' =>
                        RepairOrderStatus
                            ::IN_PROGRESS
                            ->value,

                    'notes' =>
                        'تم تسجيل صيانة سريعة وبدء التنفيذ مباشرة.',
                ]);

                /*
                 * إضافة قطع الغيار كمسودات أولاً.
                 * commitParts يعيد فحص المخزون تحت Lock.
                 */
                foreach (
                    $data['parts']
                    ?? []
                    as $partData
                ) {
                    $this->addPart(
                        $order,
                        [
                            'product_id' =>
                                (int) $partData[
                                    'product_id'
                                ],

                            'quantity' =>
                                (int) $partData[
                                    'quantity'
                                ],

                            'unit_price' =>
                                (float) $partData[
                                    'unit_price'
                                ],
                        ]
                    );
                }

                if (
                    $order
                        ->parts()
                        ->exists()
                ) {
                    $order =
                        $this->commitParts(
                            $order
                        );
                } else {
                    $this->recalculateCosts(
                        $order
                    );

                    $order =
                        $order->fresh();
                }

                /*
                 * الصيانة السريعة تعتبر منجزة فوراً،
                 * ثم نحدد هل الجهاز جاهز أم تم تسليمه.
                 */
                $order =
                    $this->markAsReady(
                        $order,
                        [
                            'inspection_result' =>
                                $data[
                                    'inspection_result'
                                ],

                            'repair_action' =>
                                $data[
                                    'repair_action'
                                ],

                            'labor_cost' =>
                                (float) (
                                    $data[
                                        'labor_cost'
                                    ] ?? 0
                                ),

                            'internal_notes' =>
                                $data[
                                    'internal_notes'
                                ] ?? null,
                        ]
                    );

                /*
                 * تسجيل الدفعة بعد تثبيت القيمة النهائية
                 * حتى لا يقبل مبلغاً أكبر من الإجمالي.
                 */
                $paymentAmount =
                    round(
                        (float) (
                            $data[
                                'payment_amount'
                            ] ?? 0
                        ),
                        2
                    );

                if (
                    $paymentAmount > 0
                ) {
                    $this->addPayment(
                        $order,
                        [
                            'amount' =>
                                $paymentAmount,

                            'payment_method' =>
                                $data[
                                    'payment_method'
                                ],

                            'financial_account_id' =>
                                (int) $data[
                                    'financial_account_id'
                                ],

                            'bank_or_app_name' =>
                                $data[
                                    'bank_or_app_name'
                                ] ?? null,

                            'transaction_reference' =>
                                $data[
                                    'transaction_reference'
                                ] ?? null,

                            'paid_at' =>
                                $data[
                                    'paid_at'
                                ] ?? now(),

                            'notes' =>
                                'دفعة صيانة سريعة',
                        ]
                    );

                    $order =
                        $order->fresh();
                }

                if (
                    (
                        $data[
                            'finish_status'
                        ] ?? 'delivered'
                    )
                    === RepairOrderStatus
                        ::DELIVERED
                        ->value
                ) {
                    $order =
                        $this->deliver(
                            $order,
                            [
                                'payment_amount' =>
                                    0,

                                'allow_partial_payment' =>
                                    (bool) (
                                        $data[
                                            'allow_partial_payment'
                                        ] ?? false
                                    ),
                            ]
                        );
                }

                $this
                    ->invalidateDashboardCache();

                return $order
                    ->fresh()
                    ->load([
                        'customer',
                        'parts.product',
                        'parts.warehouse',
                        'payments.financialAccount',
                        'statusHistories',
                    ]);
            }
        );
    }

    /**
     * معالجة العميل
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
     * تحديث بيانات الفحص
     */
    public function updateInspection(RepairOrder $order, array $data): RepairOrder
    {
        if (!$order->can_be_edited) {
            throw new \Exception('لا يمكن تعديل طلب في هذه الحالة');
        }

        $order->update([
            'inspection_result' => $data['inspection_result'] ?? $order->inspection_result,
            'fault_cause' => $data['fault_cause'] ?? $order->fault_cause,
            'repair_action' => $data['repair_action'] ?? $order->repair_action,
            'estimated_cost' => $data['estimated_cost'] ?? $order->estimated_cost,
            'labor_cost' => $data['labor_cost'] ?? $order->labor_cost,
            'technician_name' => $data['technician_name'] ?? $order->technician_name,
            'customer_approval_status' => $data['customer_approval_status'] ?? $order->customer_approval_status,
        ]);

        // أول فحص فعلي ينقل الطلب تلقائياً إلى قيد التنفيذ.
        if ($order->status === RepairOrderStatus::RECEIVED) {
            $order = $this->updateStatus($order, RepairOrderStatus::IN_PROGRESS->value, 'بدء فحص وصيانة الجهاز');
        }

        if (isset($data['sub_status'])) {
            $order->update(['sub_status' => $data['sub_status']]);
        }

        return $order->fresh();
    }

    /**
     * إضافة قطعة غيار للطلب (غير معتمدة)
     */
    public function addPart(RepairOrder $order, array $data): RepairPart
    {
        if (!$order->can_add_parts) {
            throw new \Exception('لا يمكن إضافة قطع غيار في هذه الحالة');
        }

        $product = Product::findOrFail($data['product_id']);

        if (!$product->is_active) {
            throw new \Exception('المنتج غير نشط');
        }

        // التحقق من توفر الكمية في مخزن الصيانة التشغيلي.
        $warehouse =
            $this->maintenanceWarehouse();

        $stock = ProductStock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $available = $stock ? $stock->quantity : 0;

        if ($available < $data['quantity']) {
            throw new \Exception("الكمية غير متوفرة. المتوفر: {$available}");
        }

        $quantity = $data['quantity'];
        $unitCost = $product->purchase_price;
        $unitPrice = $data['unit_price'] ?? $product->selling_price;

        return RepairPart::create([
            'repair_order_id' => $order->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'total_cost' => $quantity * $unitCost,
            'total_price' => $quantity * $unitPrice,
            'is_committed' => false,
        ]);
    }

    /**
     * إزالة قطعة غيار غير معتمدة
     */
    public function removePart(RepairPart $part): void
    {
        if ($part->is_committed) {
            throw new \Exception('لا يمكن إزالة قطعة معتمدة');
        }

        $part->delete();
    }

    /**
     * اعتماد قطع الغيار وخصمها من المخزون
     */
    public function commitParts(
        RepairOrder $order
    ): RepairOrder {
        if (! $order->can_add_parts) {
            throw new \Exception(
                'لا يمكن اعتماد قطع الغيار في هذه الحالة'
            );
        }

        return DB::transaction(
            function () use (
                $order
            ) {
                /** @var RepairOrder $lockedOrder */
                $lockedOrder =
                    RepairOrder::query()
                        ->whereKey(
                            $order->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    ! $lockedOrder
                        ->can_add_parts
                ) {
                    throw new \RuntimeException(
                        'لم يعد من الممكن اعتماد قطع الغيار لهذا الطلب.'
                    );
                }

                $parts =
                    $lockedOrder
                        ->parts()
                        ->where(
                            'is_committed',
                            false
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                if ($parts->isEmpty()) {
                    throw new \Exception(
                        'لا توجد قطع غيار لاعتمادها'
                    );
                }

                foreach (
                    $parts
                    as $part
                ) {
                    /*
                     * الخصم يتم من نفس المخزن الذي تم تثبيته
                     * داخل القطعة وقت إضافتها للطلب.
                     */
                    $warehouse =
                        $this
                            ->partWarehouse(
                                $part,
                                true
                            );

                    $stock =
                        ProductStock::query()
                            ->where(
                                'product_id',
                                $part->product_id
                            )
                            ->where(
                                'warehouse_id',
                                $warehouse->id
                            )
                            ->lockForUpdate()
                            ->first();

                    if (
                        ! $stock
                        || $stock->quantity
                            < $part->quantity
                    ) {
                        $available =
                            (int) (
                                $stock?->quantity
                                ?? 0
                            );

                        throw new \Exception(
                            "الكمية غير متوفرة للمنتج: {$part->product_name}. المتوفر في {$warehouse->name}: {$available}"
                        );
                    }

                    $oldQuantity =
                        (int) $stock->quantity;

                    $newQuantity =
                        $oldQuantity
                        - (int) $part
                            ->quantity;

                    $stock->update([
                        'quantity' =>
                            $newQuantity,
                    ]);

                    StockMovement::create([
                        'product_id' =>
                            $part->product_id,

                        'warehouse_id' =>
                            $warehouse->id,

                        'type' =>
                            StockMovementType
                                ::REPAIR_PART,

                        'quantity' =>
                            $part->quantity,

                        'quantity_before' =>
                            $oldQuantity,

                        'quantity_after' =>
                            $newQuantity,

                        'notes' =>
                            "استخدام في طلب صيانة {$lockedOrder->order_number}",

                        'reference_type' =>
                            'repair_order',

                        'reference_id' =>
                            $lockedOrder->id,
                    ]);

                    $part->update([
                        'is_committed' =>
                            true,

                        'committed_at' =>
                            now(),
                    ]);
                }

                $this
                    ->recalculateCosts(
                        $lockedOrder
                    );

                $this
                    ->invalidateDashboardCache();

                return $lockedOrder
                    ->fresh();
            }
        );
    }

    /**
     * إعادة قطعة غيار معتمدة
     */
    public function revertPart(
        RepairPart $part,
        string $reason
    ): RepairPart {
        return DB::transaction(
            function () use (
                $part,
                $reason
            ) {
                /** @var RepairPart $lockedPart */
                $lockedPart =
                    RepairPart::query()
                        ->whereKey(
                            $part->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    ! $lockedPart
                        ->is_committed
                ) {
                    throw new \Exception(
                        'القطعة غير معتمدة'
                    );
                }

                /*
                 * الإرجاع حركة عكس تاريخية، لذلك ترجع
                 * القطعة إلى نفس المخزن المثبت فيها حتى لو
                 * تم تعطيل المخزن بعد اعتمادها.
                 */
                $warehouse =
                    $this
                        ->partWarehouse(
                            $lockedPart,
                            false
                        );

                $stock =
                    ProductStock::query()
                        ->where(
                            'product_id',
                            $lockedPart
                                ->product_id
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
                                $lockedPart
                                    ->product_id,

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
                    + (int) $lockedPart
                        ->quantity;

                $stock->update([
                    'quantity' =>
                        $newQuantity,
                ]);

                StockMovement::create([
                    'product_id' =>
                        $lockedPart
                            ->product_id,

                    'warehouse_id' =>
                        $warehouse->id,

                    'type' =>
                        StockMovementType
                            ::REPAIR_PART_REVERSAL,

                    'quantity' =>
                        $lockedPart
                            ->quantity,

                    'quantity_before' =>
                        $oldQuantity,

                    'quantity_after' =>
                        $newQuantity,

                    'notes' =>
                        "إعادة قطعة من طلب صيانة {$lockedPart->repairOrder->order_number} - {$reason}",

                    'reference_type' =>
                        'repair_order',

                    'reference_id' =>
                        $lockedPart
                            ->repair_order_id,
                ]);

                $lockedPart->update([
                    'is_committed' =>
                        false,

                    'committed_at' =>
                        null,
                ]);

                $this
                    ->recalculateCosts(
                        $lockedPart
                            ->repairOrder
                    );

                $this
                    ->invalidateDashboardCache();

                return $lockedPart
                    ->fresh();
            }
        );
    }

    /**
     * إعادة حساب التكاليف
     */
    protected function recalculateCosts(RepairOrder $order): void
    {
        $committedParts = $order->parts()->where('is_committed', true)->get();

        $partsCost = $committedParts->sum('total_cost');
        $partsPrice = $committedParts->sum('total_price');

        $totalAmount = ($order->inspection_fee ?? 0) + ($order->labor_cost ?? 0) + $partsPrice;

        $order->update([
            'parts_cost' => $partsCost,
            'total_amount' => $totalAmount,
            'remaining_amount' => $totalAmount - $order->paid_amount,
        ]);

        $this->updatePaymentStatus($order);
    }

    /**
     * تحديث حالة الدفع
     */
    protected function updatePaymentStatus(RepairOrder $order): void
    {
        $paid = $order->paid_amount;
        $total = $order->total_amount;

        if ($total <= 0) {
            $status = PaymentStatus::PAID;
        } elseif ($paid <= 0) {
            $status = PaymentStatus::UNPAID;
        } elseif ($paid >= $total) {
            $status = PaymentStatus::PAID;
        } else {
            $status = PaymentStatus::PARTIALLY_PAID;
        }

        $order->update([
            'payment_status' => $status->value,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, $total - $paid),
        ]);
    }

    /**
     * تحديث حالة الطلب
     */
    public function updateStatus(RepairOrder $order, string $newStatus, ?string $notes = null): RepairOrder
    {
        $oldStatus = $order->status->value;

        // التحقق من صحة الانتقال
        $this->validateStatusTransition($order, $newStatus);

        $order->update(['status' => $newStatus]);

        // تحديث الحالة الفرعية
        if ($newStatus === RepairOrderStatus::IN_PROGRESS->value) {
            $order->update(['sub_status' => RepairSubStatus::WAITING_INSPECTION]);
        } elseif ($newStatus === RepairOrderStatus::READY->value) {
            $order->update(['sub_status' => RepairSubStatus::NONE, 'completed_at' => now()]);
        } elseif ($newStatus === RepairOrderStatus::DELIVERED->value) {
            $order->update(['sub_status' => RepairSubStatus::NONE, 'delivered_at' => now()]);
        }

        // تسجيل الحالة
        RepairStatusHistory::create([
            'repair_order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
        ]);

        $this->invalidateDashboardCache();

        return $order->fresh();
    }

    /**
     * التحقق من صحة انتقال الحالة
     */
    protected function validateStatusTransition(RepairOrder $order, string $newStatus): void
    {
        $current = $order->status->value;

        $transitions = [
            RepairOrderStatus::RECEIVED->value => [
                RepairOrderStatus::IN_PROGRESS->value,
                RepairOrderStatus::CANCELLED->value,
            ],
            RepairOrderStatus::IN_PROGRESS->value => [
                RepairOrderStatus::READY->value,
                RepairOrderStatus::CANCELLED->value,
            ],
            RepairOrderStatus::READY->value => [
                RepairOrderStatus::DELIVERED->value,
                RepairOrderStatus::CANCELLED->value,
            ],
        ];

        if (!isset($transitions[$current]) || !in_array($newStatus, $transitions[$current])) {
            throw new \Exception("لا يمكن الانتقال من {$order->status->label()} إلى " . RepairOrderStatus::from($newStatus)->label());
        }

        // تحقق إضافي: لا يمكن تجهيز بدون فحص
        if ($newStatus === RepairOrderStatus::READY->value && !$order->inspection_result) {
            throw new \Exception('يجب تسجيل نتيجة الفحص قبل تجهيز الجهاز');
        }

        // تحقق: لا يمكن تجهيز إذا كانت الموافقة مطلوبة ولم تتم
        if ($newStatus === RepairOrderStatus::READY->value && $order->customer_approval_status === CustomerApprovalStatus::PENDING) {
            throw new \Exception('يجب الحصول على موافقة العميل قبل تجهيز الجهاز');
        }

        // تحقق: لا يمكن تسليم بدون تجهيز
        if ($newStatus === RepairOrderStatus::DELIVERED->value && $current !== RepairOrderStatus::READY->value) {
            throw new \Exception('يجب تجهيز الجهاز قبل تسليمه');
        }
    }

    /**
     * تجهيز الجهاز للاستلام
     */
    public function markAsReady(RepairOrder $order, array $data): RepairOrder
    {
        if ($order->status !== RepairOrderStatus::IN_PROGRESS) {
            throw new \Exception('لا يمكن تجهيز جهاز غير قيد التنفيذ');
        }

        if (!$order->inspection_result) {
            throw new \Exception('يجب تسجيل نتيجة الفحص قبل التجهيز');
        }

        if ($order->customer_approval_status === CustomerApprovalStatus::PENDING) {
            throw new \Exception('يجب الحصول على موافقة العميل');
        }

        if ($order->parts()->where('is_committed', false)->exists()) {
            throw new \Exception('يجب اعتماد قطع الغيار أو حذفها قبل تجهيز الجهاز للاستلام');
        }

        // تحديث البيانات
        $order->update([
            'inspection_result' => $data['inspection_result'] ?? $order->inspection_result,
            'repair_action' => $data['repair_action'] ?? $order->repair_action,
            'labor_cost' => $data['labor_cost'] ?? $order->labor_cost,
            'internal_notes' => $data['internal_notes'] ?? $order->internal_notes,
        ]);

        // إعادة حساب التكاليف
        $this->recalculateCosts($order);

        // تحديث الحالة
        return $this->updateStatus($order, RepairOrderStatus::READY->value, 'تم تجهيز الجهاز للاستلام');
    }

    /**
     * تسليم الجهاز للعميل
     */
    public function deliver(RepairOrder $order, array $data): RepairOrder
    {
        return DB::transaction(function () use ($order, $data) {
            /** @var RepairOrder $lockedOrder */
            $lockedOrder = RepairOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== RepairOrderStatus::READY) {
                throw new \Exception('لا يمكن تسليم جهاز غير جاهز');
            }

            if (($data['payment_amount'] ?? 0) > 0) {
                $this->addPayment($lockedOrder, [
                    'amount' => $data['payment_amount'],
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'financial_account_id' => $data['financial_account_id'] ?? null,
                    'bank_or_app_name' => $data['bank_or_app_name'] ?? null,
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'paid_at' => now(),
                    'notes' => $data['payment_notes'] ?? null,
                ]);

                $lockedOrder->refresh();
            }

            if ((float) $lockedOrder->remaining_amount > 0 && !($data['allow_partial_payment'] ?? false)) {
                throw new \Exception('يجب تسديد المبلغ المتبقي أو تأكيد السماح بالدفع الجزئي');
            }

            $lockedOrder = $this->updateStatus(
                $lockedOrder,
                RepairOrderStatus::DELIVERED->value,
                'تم تسليم الجهاز للعميل'
            );

            if ($lockedOrder->lock_code) {
                $lockedOrder->update(['lock_code' => null]);
            }

            return $lockedOrder->fresh();
        });
    }

    /**
     * إضافة دفعة
     */
    public function addPayment(RepairOrder $order, array $data): \App\Models\RepairPayment
    {
        return DB::transaction(function () use ($order, $data) {
            /** @var RepairOrder $lockedOrder */
            $lockedOrder = RepairOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedOrder->can_add_payment) {
                throw new \Exception('لا يمكن إضافة دفعة لهذا الطلب');
            }

            $amount = (float) $data['amount'];
            $remaining = (float) $lockedOrder->remaining_amount;

            if ($amount <= 0) {
                throw new \Exception('المبلغ يجب أن يكون أكبر من صفر');
            }

            if ($amount > $remaining) {
                throw new \Exception('المبلغ المدفوع أكبر من المتبقي');
            }

            $paidAt =
                Carbon::parse(
                    $data['paid_at']
                    ?? now()
                );

            if ($paidAt->isFuture()) {
                throw new \RuntimeException(
                    'تاريخ الدفعة لا يمكن أن يكون في المستقبل.'
                );
            }

            if (
                $lockedOrder->received_at
                && $paidAt->lt(
                    Carbon::parse(
                        $lockedOrder->received_at
                    )
                )
            ) {
                throw new \RuntimeException(
                    'تاريخ الدفعة لا يمكن أن يسبق تاريخ استلام الجهاز.'
                );
            }

            $account = $this->financeService->resolveAccountForPayment(
                $data['payment_method'],
                isset($data['financial_account_id']) ? (int) $data['financial_account_id'] : null
            );

            $payment = \App\Models\RepairPayment::create([
                'repair_order_id' => $lockedOrder->id,
                'amount' => $amount,
                'financial_account_id' => $account->id,
                'payment_method' => $data['payment_method'],
                'bank_or_app_name' => $data['bank_or_app_name'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'paid_at' => $paidAt,
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedOrder->update([
                'paid_amount' => (float) $lockedOrder->paid_amount + $amount,
            ]);

            $this->updatePaymentStatus($lockedOrder);

            $this->financeService->linkPaymentToAccount(
                $payment,
                $account,
                'repair',
                "دفعة صيانة - {$lockedOrder->order_number}"
            );

            $this->invalidateDashboardCache();

            return $payment;
        });
    }

    /**
     * إلغاء طلب الصيانة
     */
    public function cancel(
        RepairOrder $order,
        string $reason
    ): RepairOrder {
        return DB::transaction(
            function () use (
                $order,
                $reason
            ) {
                /** @var RepairOrder $lockedOrder */
                $lockedOrder =
                    RepairOrder::query()
                        ->whereKey(
                            $order->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedOrder->status
                    === RepairOrderStatus::DELIVERED
                ) {
                    throw new \Exception(
                        'لا يمكن إلغاء طلب تم تسليمه'
                    );
                }

                if (
                    $lockedOrder->status
                    === RepairOrderStatus::CANCELLED
                ) {
                    throw new \Exception(
                        'طلب الصيانة ملغي مسبقاً'
                    );
                }

                $oldStatus =
                    $lockedOrder
                        ->status
                        ->value;

                /*
                 * أولاً: نعكس جميع الدفعات المالية المسجلة.
                 *
                 * Payment يبقى كسجل تاريخي، لكن أثره على
                 * الحساب المالي يتم عكسه بحركة REVERSAL.
                 */
                $payments =
                    $lockedOrder
                        ->payments()
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                foreach (
                    $payments
                    as $payment
                ) {
                    $transaction =
                        FinancialTransaction::query()
                            ->where(
                                'reference_type',
                                $payment
                                    ->getMorphClass()
                            )
                            ->where(
                                'reference_id',
                                $payment->id
                            )
                            ->orderBy('id')
                            ->first();

                    /*
                     * للبيانات القديمة التي تم تسجيل دفعتها
                     * قبل ربط نظام الحسابات: ننشئ الربط أولاً
                     * ثم نعكسه، وبذلك يبقى الرصيد الحالي سليماً
                     * مع وجود سجل مالي واضح للعملية.
                     */
                    if (! $transaction) {
                        $transaction =
                            $this
                                ->financeService
                                ->linkPaymentAutomatically(
                                    $payment,
                                    'repair',
                                    "دفعة صيانة - {$lockedOrder->order_number}",
                                    $payment
                                        ->financial_account_id
                                );
                    }

                    $this
                        ->financeService
                        ->reverseTransaction(
                            $transaction,
                            "إلغاء طلب الصيانة {$lockedOrder->order_number}: {$reason}"
                        );
                }

                /*
                 * ثانياً: إعادة القطع المعتمدة إلى نفس
                 * المخزن الأصلي لكل قطعة.
                 */
                $committedParts =
                    $lockedOrder
                        ->parts()
                        ->where(
                            'is_committed',
                            true
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                foreach (
                    $committedParts
                    as $part
                ) {
                    $warehouse =
                        $this
                            ->partWarehouse(
                                $part,
                                false
                            );

                    $stock =
                        ProductStock::query()
                            ->where(
                                'product_id',
                                $part->product_id
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
                                    $part
                                        ->product_id,

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
                        + (int) $part
                            ->quantity;

                    $stock->update([
                        'quantity' =>
                            $newQuantity,
                    ]);

                    StockMovement::create([
                        'product_id' =>
                            $part->product_id,

                        'warehouse_id' =>
                            $warehouse->id,

                        'type' =>
                            StockMovementType
                                ::REPAIR_PART_REVERSAL,

                        'quantity' =>
                            $part->quantity,

                        'quantity_before' =>
                            $oldQuantity,

                        'quantity_after' =>
                            $newQuantity,

                        'notes' =>
                            "إلغاء طلب صيانة {$lockedOrder->order_number}",

                        'reference_type' =>
                            'repair_order',

                        'reference_id' =>
                            $lockedOrder->id,
                    ]);

                    $part->update([
                        'is_committed' =>
                            false,

                        'committed_at' =>
                            null,
                    ]);
                }

                /*
                 * الطلب الملغي لا يبقى عليه رصيد مستحق،
                 * والدفعات أصبحت مردودة بحركات عكس.
                 */
                $lockedOrder->update([
                    'status' =>
                        RepairOrderStatus
                            ::CANCELLED,

                    'sub_status' =>
                        RepairSubStatus
                            ::NONE,

                    'cancellation_reason' =>
                        $reason,

                    'paid_amount' =>
                        0,

                    'remaining_amount' =>
                        0,

                    'payment_status' =>
                        PaymentStatus
                            ::UNPAID
                            ->value,
                ]);

                RepairStatusHistory::create([
                    'repair_order_id' =>
                        $lockedOrder->id,

                    'from_status' =>
                        $oldStatus,

                    'to_status' =>
                        RepairOrderStatus
                            ::CANCELLED
                            ->value,

                    'notes' =>
                        "إلغاء: {$reason}",
                ]);

                $this
                    ->invalidateDashboardCache();

                return $lockedOrder
                    ->fresh();
            }
        );
    }

}
