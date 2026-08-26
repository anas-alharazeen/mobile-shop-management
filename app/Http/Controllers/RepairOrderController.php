<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\RepairOrderStatus;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\RepairExternalPart;
use App\Models\RepairOrder;
use App\Models\RepairPart;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\RepairOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RepairOrderController extends Controller
{
    public function __construct(
        protected RepairOrderService $repairService
    ) {
    }

    public function index(Request $request)
    {
        $query = RepairOrder::query()
            ->with(['customer'])
            ->search($request->search)
            ->status($request->status)
            ->paymentStatus($request->payment_status)
            ->when(
                $request->customer_id,
                fn ($q) => $q->where('customer_id', $request->customer_id)
            )
            ->when(
                $request->start_date,
                fn ($q) => $q->whereDate('received_at', '>=', $request->start_date)
            )
            ->when(
                $request->end_date,
                fn ($q) => $q->whereDate('received_at', '<=', $request->end_date)
            )
            ->when(
                $request->boolean('waiting_part'),
                fn ($q) => $q->where('sub_status', 'waiting_part')
            )
            ->when(
                $request->boolean('overdue'),
                fn ($q) => $q->overdue()
            );

        $orders = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'active' => RepairOrder::query()
                ->whereIn('status', ['received', 'in_progress'])
                ->count(),
            'waiting_parts' => RepairOrder::query()
                ->whereNotIn('status', ['ready', 'delivered', 'cancelled'])
                ->where('sub_status', 'waiting_part')
                ->count(),
            'ready' => RepairOrder::query()
                ->where('status', RepairOrderStatus::READY->value)
                ->count(),
            'delivered_today' => RepairOrder::query()
                ->whereDate('delivered_at', today())
                ->count(),
            'overdue' => RepairOrder::overdue()->count(),
            'remaining' => (float) RepairOrder::query()
                ->where('status', '!=', RepairOrderStatus::CANCELLED->value)
                ->sum('remaining_amount'),
        ];

        return Inertia::render('Repairs/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'customer_id' => $request->customer_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'waiting_part' => $request->boolean('waiting_part'),
                'overdue' => $request->boolean('overdue'),
            ],
            'customers' => Customer::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
            'statuses' => RepairOrderStatus::labels(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Repairs/Create', $this->formOptions());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')->where('is_active', true),
            ],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_without:customer_id', 'nullable', 'string', 'max:20'],
            'save_customer' => ['boolean'],

            'device_type' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'problem_description' => ['required', 'string', 'max:1000'],
            'device_condition' => ['nullable', 'string', 'max:500'],
            'received_accessories' => ['nullable', 'string', 'max:500'],
            'lock_code' => ['nullable', 'string', 'max:100'],

            'inspection_result' => ['required', 'string', 'max:1000'],
            'fault_cause' => ['nullable', 'string', 'max:1000'],
            'repair_action' => ['required', 'string', 'max:1000'],
            'technician_name' => ['nullable', 'string', 'max:255'],
            'agreed_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
            'received_at' => ['nullable', 'date', 'before_or_equal:now'],

            'stock_parts' => ['nullable', 'array', 'max:30'],
            'stock_parts.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'stock_parts.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'stock_parts.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],

            'external_parts' => ['nullable', 'array', 'max:30'],
            'external_parts.*.part_name' => ['required', 'string', 'max:255'],
            'external_parts.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'external_parts.*.purchase_mode' => ['required', Rule::in(['draft', 'purchased'])],
            'external_parts.*.supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'external_parts.*.purchase_from' => ['nullable', 'string', 'max:255'],
            'external_parts.*.supplier_phone' => ['nullable', 'string', 'max:30'],
            'external_parts.*.purchase_reference' => ['nullable', 'string', 'max:255'],
            'external_parts.*.unit_purchase_price' => ['nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
            'external_parts.*.customer_unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'external_parts.*.financial_account_id' => [
                'nullable',
                'integer',
                Rule::exists('financial_accounts', 'id')->where('is_active', true),
            ],
            'external_parts.*.purchased_at' => ['nullable', 'date', 'before_or_equal:now'],
            'external_parts.*.notes' => ['nullable', 'string', 'max:500'],

            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->validateExternalPartPurchases($validated['external_parts'] ?? []);

        try {
            $order = $this->repairService->create($validated);
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('repairs/' . $order->id, 'public');
                $order->attachments()->create([
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'stage' => 'received',
                    'notes' => 'صورة عند الاستلام',
                ]);
            }
        }

        return redirect()
            ->route('repairs.show', $order)
            ->with('success', 'تم تسجيل طلب الصيانة بنجاح.');
    }

    public function show(RepairOrder $repairOrder)
    {
        $repairOrder->load([
            'customer',
            'attachments',
            'parts.product',
            'parts.warehouse',
            'externalParts.supplier',
            'externalParts.financialAccount',
            'externalParts.financialTransaction',
            'payments.financialAccount',
            'statusHistories',
        ]);

        $repairOrder->append([
            'lock_code_decrypted',
            'has_pending_external_parts',
            'can_be_marked_ready',
        ]);
        $repairOrder->setAttribute('profit', $repairOrder->profit);
        $repairOrder->setAttribute('is_overdue', $repairOrder->is_overdue);
        $repairOrder->setAttribute('can_add_payment', $repairOrder->can_add_payment);

        return Inertia::render('Repairs/Show', [
            'order' => $repairOrder,
            ...$this->formOptions(),
        ]);
    }

    public function updateDetails(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'inspection_result' => ['required', 'string', 'max:1000'],
            'fault_cause' => ['nullable', 'string', 'max:1000'],
            'repair_action' => ['required', 'string', 'max:1000'],
            'technician_name' => ['nullable', 'string', 'max:255'],
            'agreed_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'expected_delivery_date' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->updateDetails($repairOrder, $validated);
            return back()->with('success', 'تم تحديث تفاصيل الصيانة والسعر المتفق عليه.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function addStockPart(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        try {
            $this->repairService->addStockPart($repairOrder, $validated);
            return back()->with('success', 'تمت إضافة القطعة وخصمها من مخزن الصيانة.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function revertStockPart(Request $request, RepairPart $part)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->revertPart($part, $validated['reason']);
            return back()->with('success', 'تمت إعادة القطعة إلى مخزن الصيانة.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function addExternalPart(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'part_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'purchase_from' => ['nullable', 'string', 'max:255'],
            'supplier_phone' => ['nullable', 'string', 'max:30'],
            'customer_unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->addExternalPartDraft($repairOrder, $validated);
            return back()->with('success', 'تم حفظ القطعة الخارجية كمسودة بانتظار الشراء.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function purchaseExternalPart(Request $request, RepairExternalPart $externalPart)
    {
        $validated = $request->validate([
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'purchase_from' => ['nullable', 'string', 'max:255'],
            'supplier_phone' => ['nullable', 'string', 'max:30'],
            'purchase_reference' => ['nullable', 'string', 'max:255'],
            'unit_purchase_price' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'customer_unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'financial_account_id' => [
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id')->where('is_active', true),
            ],
            'purchased_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (blank($validated['purchase_from'] ?? null) && empty($validated['supplier_id'])) {
            throw ValidationException::withMessages([
                'purchase_from' => 'اختر مورداً أو اكتب اسم المحل الذي اشتريت منه القطعة.',
            ]);
        }

        try {
            $this->repairService->completeExternalPartPurchase($externalPart, $validated);
            return back()->with('success', 'تم تسجيل شراء القطعة وخصم قيمتها من الحساب المالي.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function removeExternalPart(RepairExternalPart $externalPart)
    {
        try {
            $this->repairService->removeExternalPartDraft($externalPart);
            return back()->with('success', 'تم حذف مسودة القطعة الخارجية.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function returnExternalPart(Request $request, RepairExternalPart $externalPart)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->returnExternalPart($externalPart, $validated['reason']);
            return back()->with('success', 'تم تسجيل إرجاع القطعة وعكس قيمة الشراء إلى الحساب المالي.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function markAsReady(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->markAsReady($repairOrder, $validated);
            return back()->with('success', 'تم تحديد الجهاز كجاهز للاستلام. يمكنك الآن التواصل مع العميل.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function addPayment(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(array_keys(PaymentMethod::selectableLabels()))],
            'financial_account_id' => [
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id')->where('is_active', true),
            ],
            'bank_or_app_name' => [
                Rule::requiredIf(in_array($request->input('payment_method'), ['bank_transfer', 'banking_app'], true)),
                'nullable',
                'string',
                'max:255',
            ],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->addPayment($repairOrder, $validated);
            return back()->with('success', 'تم تسجيل دفعة الصيانة.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function deliver(Request $request, RepairOrder $repairOrder)
    {
        $paymentAmount =
            round(
                (float) $request->input(
                    'payment_amount',
                    0
                ),
                2
            );

        $remainingAmount =
            round(
                (float) $repairOrder
                    ->remaining_amount,
                2
            );

        $validated =
            $request->validate([
                'payment_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:' . $remainingAmount,
                ],

                'payment_method' => [
                    Rule::requiredIf(
                        $paymentAmount > 0
                    ),
                    'nullable',
                    Rule::in(
                        array_keys(
                            PaymentMethod
                                ::selectableLabels()
                        )
                    ),
                ],

                'financial_account_id' => [
                    Rule::requiredIf(
                        $paymentAmount > 0
                    ),
                    'nullable',
                    'integer',
                    Rule::exists(
                        'financial_accounts',
                        'id'
                    )->where(
                        'is_active',
                        true
                    ),
                ],

                'bank_or_app_name' => [
                    Rule::requiredIf(
                        $paymentAmount > 0
                        && in_array(
                            $request->input(
                                'payment_method'
                            ),
                            [
                                'bank_transfer',
                                'banking_app',
                            ],
                            true
                        )
                    ),
                    'nullable',
                    'string',
                    'max:255',
                ],

                'transaction_reference' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'paid_at' => [
                    Rule::requiredIf(
                        $paymentAmount > 0
                    ),
                    'nullable',
                    'date',
                    'before_or_equal:now',
                ],

                'payment_notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'allow_partial_payment' => [
                    'boolean',
                ],
            ]);

        /*
         * لا نرجع Service exceptions كـFlash success response.
         * Inertia يحتاج ValidationException حتى تظهر المشكلة
         * داخل نافذة الدفع بدلاً من أن يبدو الزر وكأنه لا يعمل.
         */
        try {
            $this->repairService
                ->deliver(
                    $repairOrder,
                    $validated
                );
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'delivery' =>
                    $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'repairs.show',
                $repairOrder->id
            )
            ->with(
                'success',
                'تم تسجيل الدفع وتسليم الجهاز للعميل بنجاح.'
            );
    }

    public function cancel(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->cancel($repairOrder, $validated['reason']);
            return back()->with('success', 'تم إلغاء طلب الصيانة. تمت إعادة قطع المخزون وعكس دفعات العميل.');
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function print(RepairOrder $repairOrder)
    {
        $repairOrder->load([
            'customer',
            'parts.product',
            'parts.warehouse',
            'externalParts.supplier',
            'externalParts.financialAccount',
            'payments.financialAccount',
        ]);

        return Inertia::render('Repairs/Print', [
            'order' => $repairOrder,
        ]);
    }

    private function formOptions(): array
    {
        $maintenanceWarehouse = Warehouse::query()
            ->where('type', 'maintenance')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $products = collect();

        if ($maintenanceWarehouse) {
            $products = Product::query()
                ->active()
                ->with([
                    'stocks' => fn ($query) => $query->where('warehouse_id', $maintenanceWarehouse->id),
                ])
                ->orderBy('name')
                ->get()
                ->map(function (Product $product) use ($maintenanceWarehouse): array {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'barcode' => $product->barcode,
                        'brand' => $product->brand,
                        'model' => $product->model,
                        'purchase_price' => (float) $product->purchase_price,
                        'selling_price' => (float) $product->selling_price,
                        'available_quantity' => (int) ($product->stocks->first()?->quantity ?? 0),
                        'warehouse_id' => $maintenanceWarehouse->id,
                    ];
                })
                ->values();
        }

        $financialAccounts = FinancialAccount::query()
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancialAccount $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type?->value ?? (string) $account->type,
                'type_label' => $account->type_label,
                'current_balance' => (float) $account->current_balance,
            ])
            ->values();

        return [
            'customers' => Customer::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'code']),
            'products' => $products,
            'suppliers' => Supplier::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'company_name', 'phone', 'code']),
            'financialAccounts' => $financialAccounts,
            'paymentMethods' => PaymentMethod::selectableLabels(),
            'maintenanceWarehouse' => $maintenanceWarehouse
                ? [
                    'id' => $maintenanceWarehouse->id,
                    'name' => $maintenanceWarehouse->name,
                ]
                : [
                    'id' => null,
                    'name' => 'لا يوجد مخزن صيانة نشط',
                ],
        ];
    }

    private function validateExternalPartPurchases(array $parts): void
    {
        $errors = [];

        foreach ($parts as $index => $part) {
            if (($part['purchase_mode'] ?? 'draft') !== 'purchased') {
                continue;
            }

            if (empty($part['supplier_id']) && blank($part['purchase_from'] ?? null)) {
                $errors["external_parts.{$index}.purchase_from"] = 'حدد من أين تم شراء القطعة الخارجية.';
            }

            if (empty($part['unit_purchase_price'])) {
                $errors["external_parts.{$index}.unit_purchase_price"] = 'أدخل سعر شراء القطعة الخارجية.';
            }

            if (empty($part['financial_account_id'])) {
                $errors["external_parts.{$index}.financial_account_id"] = 'اختر الحساب الذي تم الدفع منه.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
