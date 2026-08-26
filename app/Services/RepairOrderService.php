<?php

namespace App\Services;

use App\Enums\CustomerApprovalStatus;
use App\Enums\PaymentStatus;
use App\Enums\RepairOrderStatus;
use App\Enums\RepairExternalPartStatus;
use App\Enums\TransactionType;
use App\Enums\RepairSubStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\RepairExternalPart;
use App\Models\RepairOrder;
use App\Models\RepairPayment;
use App\Models\RepairPart;
use App\Models\RepairStatusHistory;
use App\Models\StockMovement;
use App\Models\Supplier;
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
            $customer = $this->handleCustomer($data);

            $receivedAt = Carbon::parse($data['received_at'] ?? now());
            if ($receivedAt->isFuture()) {
                throw new \RuntimeException('وقت استلام الجهاز لا يمكن أن يكون في المستقبل.');
            }

            $agreedPrice = round((float) $data['agreed_price'], 2);

            $order = RepairOrder::create([
                'order_number' => RepairOrder::generateNumber(),
                'repair_mode' => 'normal',
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
                'status' => RepairOrderStatus::IN_PROGRESS,
                'sub_status' => RepairSubStatus::NONE,
                'inspection_result' => $data['inspection_result'],
                'fault_cause' => $data['fault_cause'] ?? null,
                'repair_action' => $data['repair_action'],
                'customer_approval_status' => CustomerApprovalStatus::APPROVED,
                'estimated_cost' => $agreedPrice,
                'agreed_price' => $agreedPrice,
                'inspection_fee' => 0,
                'labor_cost' => 0,
                'parts_cost' => 0,
                'total_amount' => $agreedPrice,
                'paid_amount' => 0,
                'remaining_amount' => $agreedPrice,
                'payment_status' => $agreedPrice <= 0
                    ? PaymentStatus::PAID->value
                    : PaymentStatus::UNPAID->value,
                'received_at' => $receivedAt,
                'due_date' => $data['due_date'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            RepairStatusHistory::create([
                'repair_order_id' => $order->id,
                'from_status' => null,
                'to_status' => RepairOrderStatus::IN_PROGRESS->value,
                'notes' => 'تم استلام الجهاز وفحصه وموافقة العميل على تكلفة الصيانة.',
            ]);

            foreach ($data['stock_parts'] ?? [] as $partData) {
                $this->addStockPart($order, $partData);
            }

            foreach ($data['external_parts'] ?? [] as $partData) {
                $part = $this->addExternalPartDraft($order, $partData);

                if (($partData['purchase_mode'] ?? 'draft') === 'purchased') {
                    $this->completeExternalPartPurchase($part, $partData);
                }
            }

            $this->syncWaitingPartStatus($order);
            $this->recalculateCosts($order);
            $this->invalidateDashboardCache();

            return $order->fresh()->load([
                'customer',
                'attachments',
                'parts.product',
                'parts.warehouse',
                'externalParts.supplier',
                'externalParts.financialAccount',
                'payments.financialAccount',
                'statusHistories',
            ]);
        });
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
     * تحديث البيانات الأساسية والسعر المتفق عليه بدون المرور بمراحل معقدة.
     */
    public function updateDetails(RepairOrder $order, array $data): RepairOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $lockedOrder = RepairOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedOrder->status, [RepairOrderStatus::DELIVERED, RepairOrderStatus::CANCELLED], true)) {
                throw new \RuntimeException('لا يمكن تعديل طلب تم تسليمه أو إلغاؤه.');
            }

            $agreedPrice = array_key_exists('agreed_price', $data)
                ? round((float) $data['agreed_price'], 2)
                : (float) ($lockedOrder->agreed_price ?? $lockedOrder->total_amount);

            if ($agreedPrice < (float) $lockedOrder->paid_amount) {
                throw new \RuntimeException('السعر المتفق عليه لا يمكن أن يكون أقل من المبلغ المدفوع بالفعل.');
            }

            $lockedOrder->update([
                'inspection_result' => $data['inspection_result'] ?? $lockedOrder->inspection_result,
                'fault_cause' => array_key_exists('fault_cause', $data) ? $data['fault_cause'] : $lockedOrder->fault_cause,
                'repair_action' => $data['repair_action'] ?? $lockedOrder->repair_action,
                'technician_name' => array_key_exists('technician_name', $data) ? $data['technician_name'] : $lockedOrder->technician_name,
                'agreed_price' => $agreedPrice,
                'estimated_cost' => $agreedPrice,
                'expected_delivery_date' => array_key_exists('expected_delivery_date', $data)
                    ? $data['expected_delivery_date']
                    : $lockedOrder->expected_delivery_date,
                'internal_notes' => array_key_exists('internal_notes', $data)
                    ? $data['internal_notes']
                    : $lockedOrder->internal_notes,
            ]);

            $this->recalculateCosts($lockedOrder);
            $this->invalidateDashboardCache();

            return $lockedOrder->fresh();
        });
    }

    /**
     * إضافة قطعة موجودة في مخزن الصيانة وخصمها فوراً.
     */
    public function addStockPart(RepairOrder $order, array $data): RepairPart
    {
        return DB::transaction(function () use ($order, $data) {
            $lockedOrder = RepairOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedOrder->status, [RepairOrderStatus::RECEIVED, RepairOrderStatus::IN_PROGRESS], true)) {
                throw new \RuntimeException('لا يمكن إضافة قطعة مخزون في حالة الطلب الحالية.');
            }

            $product = Product::query()
                ->whereKey((int) $data['product_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $warehouse = $this->maintenanceWarehouse();
            $quantity = (int) $data['quantity'];

            $stock = ProductStock::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            $available = (int) ($stock?->quantity ?? 0);
            if ($quantity <= 0 || $available < $quantity) {
                throw new \RuntimeException("الكمية غير متوفرة للقطعة {$product->name}. المتوفر: {$available}");
            }

            $unitCost = round((float) $product->purchase_price, 2);
            $unitPrice = round((float) ($data['unit_price'] ?? $product->selling_price), 2);

            $part = RepairPart::create([
                'repair_order_id' => $lockedOrder->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'unit_price' => $unitPrice,
                'total_cost' => round($unitCost * $quantity, 2),
                'total_price' => round($unitPrice * $quantity, 2),
                'is_committed' => true,
                'committed_at' => now(),
            ]);

            $before = $available;
            $after = $before - $quantity;
            $stock->update(['quantity' => $after]);

            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => StockMovementType::REPAIR_PART,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'notes' => "استخدام في طلب صيانة {$lockedOrder->order_number}",
                'reference_type' => 'repair_order',
                'reference_id' => $lockedOrder->id,
            ]);

            $this->recalculateCosts($lockedOrder);
            $this->invalidateDashboardCache();

            return $part->fresh(['product', 'warehouse']);
        });
    }

    /**
     * إضافة قطعة خارجية. لا يحدث أي خصم مالي وهي Draft.
     */
    public function addExternalPartDraft(RepairOrder $order, array $data): RepairExternalPart
    {
        return DB::transaction(function () use ($order, $data) {
            $lockedOrder = RepairOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedOrder->status, [RepairOrderStatus::RECEIVED, RepairOrderStatus::IN_PROGRESS], true)) {
                throw new \RuntimeException('لا يمكن إضافة قطعة خارجية في حالة الطلب الحالية.');
            }

            $quantity = max(1, (int) ($data['quantity'] ?? 1));
            $customerUnitPrice = round((float) ($data['customer_unit_price'] ?? 0), 2);

            $part = RepairExternalPart::create([
                'repair_order_id' => $lockedOrder->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'part_name' => trim((string) $data['part_name']),
                'quantity' => $quantity,
                'status' => RepairExternalPartStatus::DRAFT,
                'purchase_from' => $data['purchase_from'] ?? null,
                'supplier_phone' => $data['supplier_phone'] ?? null,
                'customer_unit_price' => $customerUnitPrice,
                'total_customer_price' => round($customerUnitPrice * $quantity, 2),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncWaitingPartStatus($lockedOrder);
            $this->recalculateCosts($lockedOrder);
            $this->invalidateDashboardCache();

            return $part->fresh(['supplier']);
        });
    }

    /**
     * تسجيل شراء القطعة الخارجية وخصم قيمتها من الحساب المحدد.
     */
    public function completeExternalPartPurchase(RepairExternalPart $part, array $data): RepairExternalPart
    {
        return DB::transaction(function () use ($part, $data) {
            $lockedPart = RepairExternalPart::query()
                ->whereKey($part->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPart->status !== RepairExternalPartStatus::DRAFT) {
                throw new \RuntimeException('هذه القطعة لم تعد بانتظار الشراء.');
            }

            $order = RepairOrder::query()
                ->whereKey($lockedPart->repair_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($order->status, [RepairOrderStatus::READY, RepairOrderStatus::DELIVERED, RepairOrderStatus::CANCELLED], true)) {
                throw new \RuntimeException('لا يمكن تسجيل شراء قطعة لطلب منتهٍ أو ملغي.');
            }

            $quantity = (int) $lockedPart->quantity;
            $unitPurchasePrice = round((float) $data['unit_purchase_price'], 2);
            if ($unitPurchasePrice <= 0) {
                throw new \RuntimeException('سعر شراء القطعة يجب أن يكون أكبر من صفر.');
            }

            $purchasedAt = Carbon::parse($data['purchased_at'] ?? now());
            if ($purchasedAt->isFuture()) {
                throw new \RuntimeException('تاريخ شراء القطعة لا يمكن أن يكون في المستقبل.');
            }
            if ($order->received_at && $purchasedAt->lt(Carbon::parse($order->received_at))) {
                throw new \RuntimeException('تاريخ شراء القطعة لا يمكن أن يسبق استلام الجهاز.');
            }

            $supplier = null;
            if (! empty($data['supplier_id'])) {
                $supplier = Supplier::query()->findOrFail((int) $data['supplier_id']);
            }

            $purchaseFrom = trim((string) ($data['purchase_from'] ?? ''));
            if ($purchaseFrom === '' && $supplier) {
                $purchaseFrom = (string) ($supplier->company_name ?: $supplier->name);
            }
            if ($purchaseFrom === '') {
                throw new \RuntimeException('حدد من أين تم شراء القطعة.');
            }

            $account = FinancialAccount::query()
                ->active()
                ->findOrFail((int) $data['financial_account_id']);

            $totalCost = round($unitPurchasePrice * $quantity, 2);

            $transaction = $this->financeService->addOutflow(
                $account,
                TransactionType::REPAIR_PART_PURCHASE,
                $totalCost,
                "شراء قطعة صيانة خارجية - {$order->order_number} - {$lockedPart->part_name}",
                $lockedPart,
                $data['notes'] ?? null,
                $purchasedAt
            );

            $lockedPart->update([
                'supplier_id' => $supplier?->id,
                'financial_account_id' => $account->id,
                'financial_transaction_id' => $transaction->id,
                'status' => RepairExternalPartStatus::PURCHASED,
                'purchase_from' => $purchaseFrom,
                'supplier_phone' => $data['supplier_phone'] ?? $lockedPart->supplier_phone,
                'purchase_reference' => $data['purchase_reference'] ?? null,
                'unit_purchase_price' => $unitPurchasePrice,
                'total_purchase_cost' => $totalCost,
                'customer_unit_price' => array_key_exists('customer_unit_price', $data)
                    ? round((float) $data['customer_unit_price'], 2)
                    : $lockedPart->customer_unit_price,
                'total_customer_price' => round(
                    (float) (array_key_exists('customer_unit_price', $data)
                        ? $data['customer_unit_price']
                        : $lockedPart->customer_unit_price) * $quantity,
                    2
                ),
                'purchased_at' => $purchasedAt,
                'notes' => $data['notes'] ?? $lockedPart->notes,
            ]);

            $this->syncWaitingPartStatus($order);
            $this->recalculateCosts($order);
            $this->invalidateDashboardCache();

            return $lockedPart->fresh(['supplier', 'financialAccount', 'financialTransaction']);
        });
    }

    /**
     * إزالة مسودة قطعة خارجية قبل شرائها.
     */
    public function removeExternalPartDraft(RepairExternalPart $part): void
    {
        DB::transaction(function () use ($part): void {
            $lockedPart = RepairExternalPart::query()
                ->whereKey($part->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPart->status !== RepairExternalPartStatus::DRAFT) {
                throw new \RuntimeException('لا يمكن حذف قطعة تم شراؤها. استخدم إرجاع القطعة إذا تم ردها للمحل الخارجي.');
            }

            $order = RepairOrder::query()
                ->whereKey($lockedPart->repair_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPart->delete();
            $this->syncWaitingPartStatus($order);
            $this->recalculateCosts($order);
            $this->invalidateDashboardCache();
        });
    }

    /**
     * إرجاع قطعة خارجية إلى مصدرها وعكس حركة الشراء المالية.
     */
    public function returnExternalPart(RepairExternalPart $part, string $reason): RepairExternalPart
    {
        return DB::transaction(function () use ($part, $reason) {
            $lockedPart = RepairExternalPart::query()
                ->whereKey($part->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPart->status !== RepairExternalPartStatus::PURCHASED) {
                throw new \RuntimeException('يمكن إرجاع القطعة الخارجية بعد تسجيل شرائها فقط.');
            }

            $order = RepairOrder::query()
                ->whereKey($lockedPart->repair_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === RepairOrderStatus::DELIVERED) {
                throw new \RuntimeException('لا يمكن إرجاع قطعة خارجية بعد تسليم الجهاز.');
            }

            $transaction = $lockedPart->financialTransaction;
            if (! $transaction) {
                $transaction = FinancialTransaction::query()
                    ->where('reference_type', $lockedPart->getMorphClass())
                    ->where('reference_id', $lockedPart->id)
                    ->where('type', TransactionType::REPAIR_PART_PURCHASE->value)
                    ->orderBy('id')
                    ->first();
            }

            if (! $transaction) {
                throw new \RuntimeException('تعذر العثور على الحركة المالية الأصلية لشراء القطعة.');
            }

            $this->financeService->reverseTransaction(
                $transaction,
                "إرجاع قطعة صيانة خارجية {$lockedPart->part_name}: {$reason}"
            );

            $lockedPart->update([
                'status' => RepairExternalPartStatus::RETURNED,
                'returned_at' => now(),
                'notes' => trim(($lockedPart->notes ? $lockedPart->notes . "\n" : '') . "إرجاع: {$reason}"),
            ]);

            $this->syncWaitingPartStatus($order);
            $this->recalculateCosts($order);
            $this->invalidateDashboardCache();

            return $lockedPart->fresh(['financialAccount', 'financialTransaction']);
        });
    }

    protected function syncWaitingPartStatus(RepairOrder $order): void
    {
        $order->refresh();

        if (! in_array($order->status, [RepairOrderStatus::RECEIVED, RepairOrderStatus::IN_PROGRESS], true)) {
            return;
        }

        $hasPendingExternal = $order->externalParts()
            ->where('status', RepairExternalPartStatus::DRAFT->value)
            ->exists();

        $order->update([
            'sub_status' => $hasPendingExternal
                ? RepairSubStatus::WAITING_PART
                : RepairSubStatus::NONE,
        ]);
    }

    /**
     * إضافة قطعة غيار للطلب (Legacy: غير معتمدة).
     * الطلبات الجديدة تستخدم addStockPart() ويتم الخصم فوراً.
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
        $order->refresh();

        $committedStockParts = $order->parts()
            ->where('is_committed', true)
            ->get();

        $purchasedExternalParts = $order->externalParts()
            ->where('status', RepairExternalPartStatus::PURCHASED->value)
            ->get();

        $stockCost = (float) $committedStockParts->sum('total_cost');
        $stockPrice = (float) $committedStockParts->sum('total_price');
        $externalCost = (float) $purchasedExternalParts->sum('total_purchase_cost');
        $externalPrice = (float) $purchasedExternalParts->sum('total_customer_price');

        $partsCost = round($stockCost + $externalCost, 2);

        /*
         * الطلبات الجديدة تعتمد السعر المتفق عليه مع العميل كإجمالي نهائي.
         * الطلبات القديمة التي لا تملك agreed_price تستمر بالمعادلة القديمة.
         */
        $totalAmount = $order->agreed_price !== null
            ? round((float) $order->agreed_price, 2)
            : round(
                (float) ($order->inspection_fee ?? 0)
                + (float) ($order->labor_cost ?? 0)
                + $stockPrice
                + $externalPrice,
                2
            );

        $order->update([
            'parts_cost' => $partsCost,
            'total_amount' => $totalAmount,
            'remaining_amount' => max(0, $totalAmount - (float) $order->paid_amount),
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
    public function markAsReady(RepairOrder $order, array $data = []): RepairOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $lockedOrder = RepairOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== RepairOrderStatus::IN_PROGRESS) {
                throw new \RuntimeException('يمكن تجهيز جهاز قيد التنفيذ فقط.');
            }

            if (! filled($lockedOrder->inspection_result) || ! filled($lockedOrder->repair_action)) {
                throw new \RuntimeException('أكمل التشخيص والإصلاح المطلوب قبل تجهيز الجهاز.');
            }

            if ($lockedOrder->externalParts()
                ->where('status', RepairExternalPartStatus::DRAFT->value)
                ->exists()) {
                throw new \RuntimeException('يوجد قطع خارجية ما زالت بانتظار الشراء. سجّل شراءها أو احذف المسودة أولاً.');
            }

            if ($lockedOrder->parts()->where('is_committed', false)->exists()) {
                throw new \RuntimeException('يوجد قطع قديمة غير معتمدة. اعتمدها أو أعدها قبل تجهيز الجهاز.');
            }

            if (array_key_exists('internal_notes', $data)) {
                $lockedOrder->update(['internal_notes' => $data['internal_notes']]);
            }

            $this->recalculateCosts($lockedOrder);

            return $this->updateStatus(
                $lockedOrder,
                RepairOrderStatus::READY->value,
                'اكتملت الصيانة والجهاز جاهز للاستلام'
            );
        });
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
                    'paid_at' =>
                        $data['paid_at']
                        ?? now(),

                    'notes' =>
                        $data['payment_notes']
                        ?? null,
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
