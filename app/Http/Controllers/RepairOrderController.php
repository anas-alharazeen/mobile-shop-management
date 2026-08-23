<?php

namespace App\Http\Controllers;

use App\Enums\RepairMode;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\RepairOrder;
use App\Models\RepairPart;
use App\Services\RepairOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RepairOrderController extends Controller
{
    protected $repairService;

    public function __construct(RepairOrderService $repairService)
    {
        $this->repairService = $repairService;
    }

    /**
     * قائمة طلبات الصيانة
     */
    public function index(Request $request)
    {
        $query = RepairOrder::with(['customer'])
            ->search($request->search)
            ->status($request->status)
            ->paymentStatus($request->payment_status)
            ->when(
                $request->repair_mode,
                fn ($q) =>
                    $q->where(
                        'repair_mode',
                        $request->repair_mode
                    )
            )
            ->when($request->customer_id, function ($q) use ($request) {
                return $q->where('customer_id', $request->customer_id);
            })
            ->when($request->start_date, function ($q) use ($request) {
                return $q->whereDate('received_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                return $q->whereDate('received_at', '<=', $request->end_date);
            })
            ->when($request->overdue, function ($q) {
                return $q->overdue();
            });

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'received' => RepairOrder::where('status', 'received')->count(),
            'in_progress' => RepairOrder::where('status', 'in_progress')->count(),
            'ready' => RepairOrder::where('status', 'ready')->count(),
            'delivered_today' => RepairOrder::whereDate('delivered_at', today())->count(),
            'quick_today' => RepairOrder::where('repair_mode', RepairMode::QUICK->value)
                ->whereDate('received_at', today())
                ->count(),
            'overdue' => RepairOrder::overdue()->count(),
            'total_revenue' => RepairOrder::where('status', 'delivered')->sum('total_amount'),
            'remaining' => RepairOrder::where('status', '!=', 'cancelled')
                ->sum('remaining_amount'),
        ];

        $customers = Customer::active()->get(['id', 'name', 'phone']);
        $statuses = \App\Enums\RepairOrderStatus::labels();

        return Inertia::render('Repairs/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'repair_mode' => $request->repair_mode,
                'customer_id' => $request->customer_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'overdue' => $request->boolean('overdue'),
            ],
            'customers' => $customers,
            'statuses' => $statuses,
            'repairModes' => RepairMode::labels(),
        ]);
    }

    /**
     * مخزن الصيانة التشغيلي المستخدم للعمليات الجديدة.
     * كل RepairPart تحفظ warehouse_id الخاص بها تاريخياً.
     */
    private function maintenanceWarehouse()
    {
        $warehouse =
            \App\Models\Warehouse::query()
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
                'لا يوجد مخزن صيانة نشط.'
            );
        }

        return $warehouse;
    }

    /**
     * استقبال جهاز جديد
     */
    public function create()
    {
        $customers = Customer::active()->get(['id', 'name', 'phone', 'code']);

        $maintenanceWarehouse =
            $this->maintenanceWarehouse();

        $products = Product::active()
            ->with([
                'stocks' =>
                    fn ($query) =>
                        $query->where(
                            'warehouse_id',
                            $maintenanceWarehouse->id
                        ),
            ])
            ->get()
            ->map(
                function (
                    $product
                ) use (
                    $maintenanceWarehouse
                ) {
                    $product->available_quantity =
                        (int) (
                            $product
                                ->stocks
                                ->first()
                                ?->quantity
                            ?? 0
                        );

                    $product->maintenance_warehouse_id =
                        $maintenanceWarehouse->id;

                    return $product;
                }
            );

        $paymentMethods = \App\Enums\PaymentMethod::selectableLabels();
        $attachmentStages = \App\Enums\AttachmentStage::labels();

        return Inertia::render('Repairs/Create', [
            'customers' => $customers,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'attachmentStages' => $attachmentStages,
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    /**
     * شاشة الصيانة السريعة.
     */
    public function quickCreate()
    {
        $customers =
            Customer::query()
                ->active()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'phone',
                    'code',
                ]);

        $maintenanceWarehouse =
            $this->maintenanceWarehouse();

        $products =
            Product::query()
                ->active()
                ->with([
                    'stocks' =>
                        fn ($query) =>
                            $query->where(
                                'warehouse_id',
                                $maintenanceWarehouse->id
                            ),
                ])
                ->orderBy('name')
                ->get()
                ->map(
                    function (
                        Product $product
                    ) use (
                        $maintenanceWarehouse
                    ): Product {
                        $product
                            ->setAttribute(
                                'available_quantity',
                                (int) (
                                    $product
                                        ->stocks
                                        ->first()
                                        ?->quantity
                                    ?? 0
                                )
                            );

                        $product
                            ->setAttribute(
                                'maintenance_warehouse_id',
                                $maintenanceWarehouse->id
                            );

                        return $product;
                    }
                );

        $financialAccounts =
            FinancialAccount::query()
                ->active()
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->map(
                    fn (
                        FinancialAccount $account
                    ): array => [
                        'id' =>
                            $account->id,

                        'name' =>
                            $account->name,

                        'type' =>
                            $account
                                ->type
                                ?->value
                            ?? (string) $account
                                ->type,

                        'current_balance' =>
                            (float) $account
                                ->current_balance,
                    ]
                )
                ->values();

        return Inertia::render(
            'Repairs/QuickCreate',
            [
                'customers' =>
                    $customers,

                'products' =>
                    $products,

                'financialAccounts' =>
                    $financialAccounts,

                'paymentMethods' =>
                    \App\Enums\PaymentMethod
                        ::selectableLabels(),
            ]
        );
    }

    /**
     * حفظ صيانة سريعة.
     */
    public function quickStore(
        Request $request
    ) {
        $paymentAmount =
            (float) $request->input(
                'payment_amount',
                0
            );

        $validated =
            $request->validate([
                'customer_id' => [
                    'nullable',
                    Rule::exists(
                        'customers',
                        'id'
                    )->where(
                        'is_active',
                        true
                    ),
                ],

                'customer_name' => [
                    'required_without:customer_id',
                    'nullable',
                    'string',
                    'max:255',
                ],

                'customer_phone' => [
                    'required_without:customer_id',
                    'nullable',
                    'string',
                    'max:20',
                ],

                'save_customer' => [
                    'boolean',
                ],

                'device_type' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'brand' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'model' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'color' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'problem_description' => [
                    'required',
                    'string',
                    'max:1000',
                ],

                'device_condition' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'inspection_result' => [
                    'required',
                    'string',
                    'max:1000',
                ],

                'fault_cause' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'repair_action' => [
                    'required',
                    'string',
                    'max:1000',
                ],

                'technician_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'inspection_fee' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999999999.99',
                ],

                'labor_cost' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:999999999.99',
                ],

                'parts' => [
                    'nullable',
                    'array',
                    'max:30',
                ],

                'parts.*.product_id' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists(
                        'products',
                        'id'
                    )->where(
                        'is_active',
                        true
                    ),
                ],

                'parts.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:9999',
                ],

                'parts.*.unit_price' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:999999999.99',
                ],

                'payment_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:999999999.99',
                ],

                'payment_method' => [
                    Rule::requiredIf(
                        $paymentAmount > 0
                    ),
                    'nullable',
                    Rule::in(
                        array_keys(
                            \App\Enums\PaymentMethod
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

                'finish_status' => [
                    'required',
                    Rule::in([
                        'ready',
                        'delivered',
                    ]),
                ],

                'allow_partial_payment' => [
                    'boolean',
                ],

                'received_at' => [
                    'nullable',
                    'date',
                    'before_or_equal:now',
                ],

                'customer_notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'internal_notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

        $order =
            $this->repairService
                ->createQuick(
                    $validated
                );

        return redirect()
            ->route(
                'repairs.show',
                $order
            )
            ->with(
                'success',
                $order->status->value
                    === 'delivered'
                        ? 'تم تسجيل الصيانة السريعة وتسليم الجهاز بنجاح.'
                        : 'تم تسجيل الصيانة السريعة والجهاز جاهز للاستلام.'
            );
    }

    /**
     * حفظ طلب صيانة جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'string', 'max:255'],
            'customer_phone' => ['required_without:customer_id', 'string', 'max:20'],
            'save_customer' => ['boolean'],
            'device_type' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'problem_description' => ['required', 'string', 'max:1000'],
            'device_condition' => ['nullable', 'string', 'max:500'],
            'received_accessories' => ['nullable', 'string', 'max:500'],
            'lock_code' => ['nullable', 'string', 'max:10'],
            'technician_name' => ['nullable', 'string', 'max:255'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'inspection_fee' => ['nullable', 'numeric', 'min:0'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:received_at'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => [Rule::requiredIf((float) $request->input('payment_amount', 0) > 0), 'nullable', 'string', 'in:cash,bank_transfer,banking_app'],
            'financial_account_id' => [
                Rule::requiredIf(
                    (float) $request->input(
                        'payment_amount',
                        0
                    ) > 0
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
            'bank_or_app_name' => [Rule::requiredIf((float) $request->input('payment_amount', 0) > 0 && in_array($request->input('payment_method'), ['bank_transfer', 'banking_app'], true)), 'nullable', 'string', 'max:255'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:received_at'],
            'attachments' => ['array'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $order = $this->repairService->create($validated);

        // حفظ الصور
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

        return redirect()->route('repairs.show', $order)
            ->with('success', 'تم استقبال الجهاز بنجاح');
    }

    /**
     * عرض تفاصيل الطلب
     */
    public function show(RepairOrder $repairOrder)
    {
        $repairOrder->load([
            'customer',
            'attachments',
            'parts.product',
            'parts.warehouse',
            'payments.financialAccount',
            'statusHistories',
        ]);

        $repairOrder->append('lock_code_decrypted');
        $repairOrder->profit = $repairOrder->profit;
        $repairOrder->is_overdue = $repairOrder->is_overdue;
        $repairOrder->can_be_edited = $repairOrder->can_be_edited;
        $repairOrder->can_add_parts = $repairOrder->can_add_parts;
        $repairOrder->can_be_completed = $repairOrder->can_be_completed;
        $repairOrder->can_be_delivered = $repairOrder->can_be_delivered;
        $repairOrder->can_add_payment = $repairOrder->can_add_payment;

        $statuses = \App\Enums\RepairOrderStatus::labels();
        $subStatuses = \App\Enums\RepairSubStatus::labels();
        $approvalStatuses = \App\Enums\CustomerApprovalStatus::labels();
        $paymentMethods = \App\Enums\PaymentMethod::labels();

        $maintenanceWarehouse =
            $this->maintenanceWarehouse();

        $products = Product::active()
            ->with([
                'stocks' =>
                    fn ($query) =>
                        $query->where(
                            'warehouse_id',
                            $maintenanceWarehouse->id
                        ),
            ])
            ->get()
            ->map(
                function (
                    $product
                ) use (
                    $maintenanceWarehouse
                ) {
                    $product->available_quantity =
                        (int) (
                            $product
                                ->stocks
                                ->first()
                                ?->quantity
                            ?? 0
                        );

                    $product->maintenance_warehouse_id =
                        $maintenanceWarehouse->id;

                    return $product;
                }
            );

        return Inertia::render('Repairs/Show', [
            'order' => $repairOrder,
            'statuses' => $statuses,
            'subStatuses' => $subStatuses,
            'approvalStatuses' => $approvalStatuses,
            'paymentMethods' => $paymentMethods,
            'attachmentStages' => \App\Enums\AttachmentStage::labels(),
            'financialAccounts' => FinancialAccount::query()->active()->orderBy('name')->get(['id', 'name', 'type']),
            'products' => $products,
        ]);
    }

    /**
     * تحديث الفحص
     */
    public function updateInspection(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'inspection_result' => ['nullable', 'string', 'max:1000'],
            'fault_cause' => ['nullable', 'string', 'max:1000'],
            'repair_action' => ['nullable', 'string', 'max:1000'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'technician_name' => ['nullable', 'string', 'max:255'],
            'customer_approval_status' => ['nullable', 'string', 'in:pending,approved,rejected,not_required'],
            'sub_status' => ['nullable', 'string', 'in:waiting_inspection,waiting_customer_approval,waiting_part,under_testing,unrepairable,returned_for_repair,none'],
        ]);

        $this->repairService->updateInspection($repairOrder, $validated);

        return redirect()->route('repairs.show', $repairOrder)
            ->with('success', 'تم تحديث بيانات الفحص بنجاح');
    }

    /**
     * إضافة قطعة غيار
     */
    public function addPart(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id,is_active,1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->repairService->addPart($repairOrder, $validated);

            return redirect()->route('repairs.show', $repairOrder)
                ->with('success', 'تم إضافة القطعة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * إزالة قطعة غيار
     */
    public function removePart(RepairPart $part)
    {
        try {
            $this->repairService->removePart($part);

            return redirect()->route('repairs.show', $part->repair_order_id)
                ->with('success', 'تم إزالة القطعة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * اعتماد قطع الغيار
     */
    public function commitParts(RepairOrder $repairOrder)
    {
        try {
            $this->repairService->commitParts($repairOrder);

            return redirect()->route('repairs.show', $repairOrder)
                ->with('success', 'تم اعتماد قطع الغيار بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * إعادة قطعة معتمدة
     */
    public function revertPart(Request $request, RepairPart $part)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->revertPart($part, $validated['reason']);

            return redirect()->route('repairs.show', $part->repair_order_id)
                ->with('success', 'تم إعادة القطعة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * تغيير الحالة
     */
    public function updateStatus(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:received,in_progress,ready,delivered,cancelled'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->updateStatus(
                $repairOrder,
                $validated['status'],
                $validated['notes'] ?? null
            );

            return redirect()->route('repairs.show', $repairOrder)
                ->with('success', 'تم تحديث الحالة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * تجهيز الجهاز للاستلام
     */
    public function markAsReady(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'inspection_result' => ['nullable', 'string', 'max:1000'],
            'repair_action' => ['nullable', 'string', 'max:1000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->markAsReady($repairOrder, $validated);

            return redirect()->route('repairs.show', $repairOrder)
                ->with('success', 'تم تجهيز الجهاز للاستلام');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * تسليم الجهاز
     */
    public function deliver(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => [Rule::requiredIf((float) $request->input('payment_amount', 0) > 0), 'nullable', 'string', 'in:cash,bank_transfer,banking_app'],
            'financial_account_id' => [
                Rule::requiredIf(
                    (float) $request->input(
                        'payment_amount',
                        0
                    ) > 0
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
            'bank_or_app_name' => [Rule::requiredIf((float) $request->input('payment_amount', 0) > 0 && in_array($request->input('payment_method'), ['bank_transfer', 'banking_app'], true)), 'nullable', 'string', 'max:255'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
            'allow_partial_payment' => ['boolean'],
        ]);

        try {
            $this->repairService->deliver($repairOrder, $validated);

            return redirect()->route('repairs.show', $repairOrder)
                ->with('success', 'تم تسليم الجهاز بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * إضافة دفعة
     */
    public function addPayment(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,banking_app'],
            'financial_account_id' => [
                'required',
                'integer',
                Rule::exists(
                    'financial_accounts',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],
            'bank_or_app_name' => [Rule::requiredIf(in_array($request->input('payment_method'), ['bank_transfer', 'banking_app'], true)), 'nullable', 'string', 'max:255'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->addPayment($repairOrder, $validated);

            return redirect()->route('repairs.show', $repairOrder)
                ->with('success', 'تم تسجيل الدفعة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * إلغاء الطلب
     */
    public function cancel(Request $request, RepairOrder $repairOrder)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->repairService->cancel($repairOrder, $validated['reason']);

            return redirect()->route('repairs.show', $repairOrder)
                ->with('success', 'تم إلغاء طلب الصيانة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * طباعة فاتورة الصيانة
     */
    public function print(RepairOrder $repairOrder)
    {
        $repairOrder->load(['customer', 'attachments', 'parts.product', 'parts.warehouse', 'payments']);

        return Inertia::render('Repairs/Print', [
            'order' => $repairOrder,
        ]);
    }
}
