<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use App\Services\SalesInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\FinancialAccount;


class SalesInvoiceController extends Controller
{
    protected $salesService;

    public function __construct(SalesInvoiceService $salesService)
    {
        $this->salesService = $salesService;
    }

    /**
     * صفحة POS
     */
    public function pos()
    {
        /*
         * الـPOS يجب أن يعرض نفس مخزن المبيعات الذي ستُسجل
         * عليه بنود الفاتورة فعلياً.
         */
        $salesWarehouse =
            $this->activeSalesWarehouse();

        $products =
            Product::active()
            ->with([
                'category:id,name',

                'stocks' =>
                fn($query) =>
                $query->where(
                    'warehouse_id',
                    $salesWarehouse->id
                ),
            ])
            ->whereHas(
                'stocks',
                fn($query) =>
                $query
                    ->where(
                        'warehouse_id',
                        $salesWarehouse->id
                    )
                    ->where(
                        'quantity',
                        '>',
                        0
                    )
            )
            ->orderBy('name')
            ->get()
            ->map(
                function (
                    Product $product
                ) use (
                    $salesWarehouse
                ) {
                    $available =
                        (int) (
                            $product
                            ->stocks
                            ->first()
                            ?->quantity
                            ?? 0
                        );

                    $threshold =
                        (int) (
                            $product
                            ->low_stock_threshold
                            ?? 5
                        );

                    $product->setAttribute(
                        'available_quantity',
                        $available
                    );

                    $product->setAttribute(
                        'sales_warehouse_id',
                        $salesWarehouse->id
                    );

                    $product->setAttribute(
                        'minimum_selling_price',
                        (float) (
                            $product
                            ->minimum_selling_price
                            ?? 0
                        )
                    );

                    $product->setAttribute(
                        'stock_status',
                        [
                            'label' =>
                            $available <= 0
                                ? 'نافد'
                                : (
                                    $available
                                    <= $threshold
                                    ? 'منخفض'
                                    : 'متوفر'
                                ),

                            'color' =>
                            $available <= 0
                                ? 'red'
                                : (
                                    $available
                                    <= $threshold
                                    ? 'orange'
                                    : 'green'
                                ),
                        ]
                    );

                    return $product;
                }
            );

        $categories =
            Category::active()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $customers =
            Customer::active()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'phone',
                'code',
            ]);

        $paymentMethods =
            \App\Enums\PaymentMethod
            ::selectableLabels();

        $financialAccounts =
            FinancialAccount::query()
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(
                function (
                    FinancialAccount $account
                ): array {
                    return [
                        'id' =>
                        $account->id,

                        'name' =>
                        $account->name,

                        'type' =>
                        $account
                            ->type
                            ?->value
                            ?? $account
                            ->type,

                        'type_label' =>
                        $account
                            ->type_label,

                        'current_balance' =>
                        (float) $account
                            ->current_balance,

                        'is_active' =>
                        (bool) $account
                            ->is_active,
                    ];
                }
            )
            ->values();

        return Inertia::render(
            'Sales/Pos',
            [
                'products' =>
                $products,

                'categories' =>
                $categories,

                'customers' =>
                $customers,

                'paymentMethods' =>
                $paymentMethods,

                'financialAccounts' =>
                $financialAccounts,

                'salesWarehouse' => [
                    'id' =>
                    $salesWarehouse->id,

                    'name' =>
                    $salesWarehouse->name,
                ],
            ]
        );
    }

    /**
     * قائمة فواتير المبيعات
     */
    public function index(Request $request)
    {
        $query = SalesInvoice::with(['customer', 'items'])
            ->search($request->search)
            ->status($request->status)
            ->paymentStatus($request->payment_status)
            ->when($request->customer_id, function ($q) use ($request) {
                return $q->where('customer_id', $request->customer_id);
            })
            ->when($request->start_date, function ($q) use ($request) {
                return $q->whereDate('sale_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($q) use ($request) {
                return $q->whereDate('sale_date', '<=', $request->end_date);
            });

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'today' => SalesInvoice::whereDate('sale_date', today())
                ->where('status', 'approved')
                ->sum('total_amount'),
            'month' => SalesInvoice::whereMonth('sale_date', now()->month)
                ->whereYear('sale_date', now()->year)
                ->where('status', 'approved')
                ->sum('total_amount'),
            'profit' => SalesInvoice::where('status', 'approved')
                ->sum('gross_profit'),
            'unpaid' => SalesInvoice::where('payment_status', 'unpaid')
                ->where('status', 'approved')
                ->sum('remaining_amount'),
            'total_due' => SalesInvoice::where('status', 'approved')
                ->sum('remaining_amount'),
            'today_count' => SalesInvoice::whereDate('sale_date', today())
                ->where('status', 'approved')
                ->count(),
        ];

        $customers = Customer::active()->get(['id', 'name', 'phone']);
        $statuses = \App\Enums\SalesInvoiceStatus::labels();
        $paymentStatuses = \App\Enums\PaymentStatus::labels();
        $paymentMethods = \App\Enums\PaymentMethod::selectableLabels();

        return Inertia::render('Sales/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'customer_id' => $request->customer_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'customers' => $customers,
            'statuses' => $statuses,
            'paymentStatuses' => $paymentStatuses,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * عرض تفاصيل الفاتورة
     */
    public function show(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load([
            'customer',
            'items.product',
            'items.warehouse',
            'payments.financialAccount',
        ]);

        $salesInvoice->can_be_edited =
            $salesInvoice->can_be_edited;

        $salesInvoice->can_be_approved =
            $salesInvoice->can_be_approved;

        $salesInvoice->can_be_cancelled =
            $salesInvoice->can_be_cancelled;

        $salesInvoice->can_add_payment =
            $salesInvoice->can_add_payment;

        /*
     * الحسابات المالية النشطة المتاحة
     * عند إضافة دفعة جديدة للفاتورة.
     */
        $financialAccounts = FinancialAccount::query()
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function (FinancialAccount $account): array {
                return [
                    'id' => $account->id,
                    'name' => $account->name,

                    'type' =>
                    $account->type?->value
                        ?? $account->type,

                    'type_label' => $account->type_label,

                    'current_balance' => (float) (
                        $account->current_balance ?? 0
                    ),

                    'is_active' => (bool) $account->is_active,
                ];
            })
            ->values();

        $statuses =
            \App\Enums\SalesInvoiceStatus::labels();

        $paymentStatuses =
            \App\Enums\PaymentStatus::labels();

        /*
     * هنا نستخدم selectableLabels وليس labels
     * حتى لا تظهر طرق دفع داخلية مثل رصيد الاستبدال.
     */
        $paymentMethods =
            \App\Enums\PaymentMethod::selectableLabels();

        /*
         * نفس فحص أقل سعر المستخدم في POS يجب أن يظهر أيضاً
         * عند اعتماد فاتورة محفوظة كمسودة، حتى لا يمكن تجاوز
         * موافقة صاحب المحل من صفحة تفاصيل الفاتورة.
         */
        $minimumPriceApproval =
            $salesInvoice->can_be_approved
            ? $this->checkMinimumSellingPrices([
                'items' => $salesInvoice->items
                    ->map(
                        fn($item): array => [
                            'product_id' => $item->product_id,
                            'quantity' => (int) $item->quantity,
                            'unit_selling_price' => (float) $item->unit_selling_price,
                            'line_discount' => (float) $item->line_discount,
                        ]
                    )
                    ->values()
                    ->all(),
                'invoice_discount' => (float) $salesInvoice->invoice_discount,
            ])
            : [
                'requires_approval' => false,
                'violations' => [],
                'minimum_total' => 0.0,
                'actual_total' => 0.0,
                'invoice_below_minimum' => false,
            ];

        return Inertia::render('Sales/Show', [
            'invoice' => $salesInvoice,

            'statuses' => $statuses,

            'paymentStatuses' => $paymentStatuses,

            'paymentMethods' => $paymentMethods,

            'financialAccounts' => $financialAccounts,

            'minimumPriceApproval' => $minimumPriceApproval,
        ]);
    }

    public function store(Request $request)
    {
        $requiresCustomerPhone = collect(
            $request->input(
                'payments',
                []
            )
        )->contains(
            fn(array $payment): bool =>
            in_array(
                $payment['payment_method'] ?? null,
                [
                    'bank_transfer',
                    'banking_app',
                ],
                true
            )
                && (float) (
                    $payment['amount']
                    ?? 0
                ) > 0
        );

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                'exists:customers,id',
            ],

            'customer_name' => [
                'required_without:customer_id',
                'string',
                'max:255',
            ],

            'customer_phone' => [
                Rule::requiredIf(
                    $requiresCustomerPhone
                ),
                'nullable',
                'string',
                'max:20',
            ],

            'save_customer' => [
                'sometimes',
                'boolean',
            ],

            'sale_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:sale_date',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists(
                    'products',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.unit_selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.line_discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'invoice_discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],

            'payments' => [
                'sometimes',
                'array',
            ],

            'payments.*.amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payments.*.payment_method' => [
                'required',
                'string',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'banking_app',
                ]),
            ],

            'payments.*.bank_or_app_name' => [
                'nullable',
                'required_if:payments.*.payment_method,bank_transfer,banking_app',
                'string',
                'max:255',
            ],

            'payments.*.transaction_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'payments.*.financial_account_id' => [
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

            'complete_sale' => [
                'sometimes',
                'boolean',
            ],

            /*
             * هذه الحقول لا تُخزّن مباشرة في الفاتورة.
             * تُستخدم فقط لتوثيق موافقة صاحب المحل عند
             * البيع تحت أقل سعر مسموح.
             */
            'minimum_price_approved' => [
                'sometimes',
                'boolean',
            ],

            'minimum_price_reason' => [
                Rule::requiredIf(
                    $request->boolean(
                        'minimum_price_approved'
                    )
                ),
                'nullable',
                'string',
                'min:3',
                'max:200',
            ],
        ]);

        $completeSale =
            $request->boolean(
                'complete_sale'
            );

        /*
         * الفحص يتم من قاعدة البيانات وليس من السعر القادم
         * من Vue، لذلك minimum_selling_price الموجود حالياً
         * في المنتج هو المصدر النهائي للحقيقة.
         */
        $minimumPriceCheck =
            $this
            ->checkMinimumSellingPrices(
                $validated
            );

        if (
            $completeSale
            && $minimumPriceCheck['requires_approval']
        ) {
            if (
                ! $request->boolean(
                    'minimum_price_approved'
                )
            ) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'error',
                        $this
                            ->minimumPriceApprovalMessage(
                                $minimumPriceCheck
                            )
                    );
            }

            $approvalReason =
                trim(
                    (string) (
                        $validated['minimum_price_reason']
                        ?? ''
                    )
                );

            if (
                mb_strlen(
                    $approvalReason
                ) < 3
            ) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'error',
                        'اكتب سبباً واضحاً لموافقة صاحب المحل على البيع تحت أقل سعر.'
                    );
            }

            /*
             * لا نحتاج Migration جديداً.
             * نوثّق الموافقة والسبب داخل ملاحظات الفاتورة.
             */
            $validated['notes'] =
                $this
                ->appendMinimumPriceApprovalNote(
                    $validated['notes'] ?? null,
                    $approvalReason,
                    $minimumPriceCheck
                );
        }

        /*
         * SalesInvoiceService لا يحتاج معرفة حقول الموافقة.
         */
        unset(
            $validated['minimum_price_approved'],
            $validated['minimum_price_reason'],
            $validated['complete_sale']
        );

        try {
            $invoice = DB::transaction(
                function () use (
                    $validated,
                    $completeSale
                ): SalesInvoice {
                    $invoice =
                        $this
                        ->salesService
                        ->create(
                            $validated
                        );

                    if ($completeSale) {
                        $invoice =
                            $this
                            ->salesService
                            ->approve(
                                $invoice
                            );
                    }

                    return $invoice;
                }
            );
        } catch (\Throwable $exception) {
            report(
                $exception
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $exception
                        ->getMessage()
                        ?: 'تعذر إنشاء فاتورة البيع.'
                );
        }

        $message =
            $completeSale
            ? (
                $minimumPriceCheck['requires_approval']
                ? 'تم اعتماد فاتورة البيع بعد موافقة صاحب المحل على السعر وخصم المخزون بنجاح.'
                : 'تم اعتماد فاتورة البيع وخصم المخزون بنجاح.'
            )
            : 'تم حفظ فاتورة البيع كمسودة بنجاح.';

        return redirect()
            ->route(
                'sales.show',
                $invoice
            )
            ->with(
                'success',
                $message
            );
    }

    /**
     * تعديل فاتورة مسودة
     */
    public function edit(SalesInvoice $salesInvoice)
    {
        if (!$salesInvoice->can_be_edited) {
            return redirect()->route('sales.show', $salesInvoice)
                ->with('error', 'لا يمكن تعديل فاتورة معتمدة');
        }

        $salesInvoice->load([
            'items.product',
            'items.warehouse',
        ]);

        $salesWarehouse =
            $this->activeSalesWarehouse();

        $customers =
            Customer::active()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'phone',
                'code',
            ]);

        $products =
            Product::active()
            ->with([
                'category:id,name',

                'stocks' =>
                fn($query) =>
                $query->where(
                    'warehouse_id',
                    $salesWarehouse->id
                ),
            ])
            ->orderBy('name')
            ->get()
            ->map(
                function (
                    Product $product
                ) use (
                    $salesWarehouse
                ) {
                    $product->setAttribute(
                        'available_quantity',
                        (int) (
                            $product
                            ->stocks
                            ->first()
                            ?->quantity
                            ?? 0
                        )
                    );

                    $product->setAttribute(
                        'sales_warehouse_id',
                        $salesWarehouse->id
                    );

                    $product->setAttribute(
                        'minimum_selling_price',
                        (float) (
                            $product
                            ->minimum_selling_price
                            ?? 0
                        )
                    );

                    return $product;
                }
            );
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);
        $paymentMethods = \App\Enums\PaymentMethod::labels();

        return Inertia::render('Sales/Edit', [
            'invoice' => $salesInvoice,
            'customers' => $customers,
            'products' => $products,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,

            'salesWarehouse' => [
                'id' => $salesWarehouse->id,
                'name' => $salesWarehouse->name,
            ],
        ]);
    }

    /**
     * تحديث فاتورة مسودة
     */
    public function update(Request $request, SalesInvoice $salesInvoice)
    {
        if (!$salesInvoice->can_be_edited) {
            return redirect()->route('sales.show', $salesInvoice)
                ->with('error', 'لا يمكن تعديل فاتورة معتمدة');
        }

        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'save_customer' => ['boolean'],
            'sale_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:sale_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id,is_active,1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_selling_price' => ['required', 'numeric', 'min:0'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
            'invoice_discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->salesService->update($salesInvoice, $validated);

        return redirect()->route('sales.show', $salesInvoice)
            ->with('success', 'تم تحديث فاتورة البيع بنجاح');
    }

    public function approve(
        Request $request,
        SalesInvoice $salesInvoice
    ) {
        $salesInvoice->load([
            'items.product',
        ]);

        if (! $salesInvoice->can_be_approved) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'لا يمكن اعتماد هذه الفاتورة.'
                );
        }

        $minimumPriceCheck =
            $this->checkMinimumSellingPrices([
                'items' => $salesInvoice->items
                    ->map(
                        fn($item): array => [
                            'product_id' => $item->product_id,
                            'quantity' => (int) $item->quantity,
                            'unit_selling_price' => (float) $item->unit_selling_price,
                            'line_discount' => (float) $item->line_discount,
                        ]
                    )
                    ->values()
                    ->all(),
                'invoice_discount' => (float) $salesInvoice->invoice_discount,
            ]);

        $approvalReason = null;

        if (
            $minimumPriceCheck['requires_approval']
        ) {
            $validated = $request->validate([
                'minimum_price_approved' => [
                    'required',
                    'accepted',
                ],
                'minimum_price_reason' => [
                    'required',
                    'string',
                    'min:3',
                    'max:200',
                ],
            ], [
                'minimum_price_approved.accepted' =>
                'يلزم تأكيد موافقة صاحب المحل على البيع تحت أقل سعر.',

                'minimum_price_reason.required' =>
                'اكتب سبب موافقة صاحب المحل على البيع تحت أقل سعر.',

                'minimum_price_reason.min' =>
                'سبب الموافقة يجب أن يكون واضحاً ومكوناً من 3 أحرف على الأقل.',
            ]);

            $approvalReason = trim(
                $validated['minimum_price_reason']
            );
        }

        try {
            DB::transaction(
                function () use (
                    $salesInvoice,
                    $minimumPriceCheck,
                    $approvalReason
                ): void {
                    if (
                        $minimumPriceCheck['requires_approval']
                    ) {
                        $salesInvoice->update([
                            'notes' =>
                            $this->appendMinimumPriceApprovalNote(
                                $salesInvoice->notes,
                                $approvalReason,
                                $minimumPriceCheck
                            ),
                        ]);
                    }

                    $this
                        ->salesService
                        ->approve(
                            $salesInvoice
                        );
                }
            );

            return redirect()
                ->route(
                    'sales.show',
                    $salesInvoice
                )
                ->with(
                    'success',
                    $minimumPriceCheck['requires_approval']
                        ? 'تم اعتماد فاتورة البيع بعد موافقة صاحب المحل على السعر.'
                        : 'تم اعتماد فاتورة البيع بنجاح.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                        ?: 'تعذر اعتماد فاتورة البيع.'
                );
        }
    }

    /**
     * إضافة دفعة
     */
    public function addPayment(
        Request $request,
        SalesInvoice $salesInvoice
    ) {
        /*
         * لا نقبل دفعة جديدة إلا على فاتورة بيع معتمدة
         * وما زال عليها مبلغ متبقٍ.
         */
        $salesInvoice->refresh();

        if (
            ($salesInvoice->status?->value
                ?? $salesInvoice->status)
            !== 'approved'
        ) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'يمكن تسجيل دفعة على فاتورة بيع معتمدة فقط.'
                );
        }

        if (
            (float) $salesInvoice
                ->remaining_amount
            <= 0.00001
        ) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'الفاتورة مدفوعة بالكامل ولا يوجد مبلغ متبقٍ.'
                );
        }

        $validated =
            $request->validate([
                'amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'lte:' . $salesInvoice
                        ->remaining_amount,
                ],

                'payment_method' => [
                    'required',
                    'string',
                    Rule::in([
                        'cash',
                        'bank_transfer',
                        'banking_app',
                    ]),
                ],

                /*
                 * سبب المشكلة السابقة كان أن هذا الحقل
                 * Required في الـBackend بينما Sales/Show
                 * القديمة لم تكن ترسله.
                 */
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

                'bank_or_app_name' => [
                    Rule::requiredIf(
                        in_array(
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
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ], [
                'amount.required' =>
                'أدخل قيمة الدفعة.',

                'amount.min' =>
                'قيمة الدفعة يجب أن تكون أكبر من صفر.',

                'amount.lte' =>
                'قيمة الدفعة لا يمكن أن تتجاوز المبلغ المتبقي على الفاتورة.',

                'payment_method.required' =>
                'اختر طريقة الدفع.',

                'financial_account_id.required' =>
                'اختر الحساب المالي الذي سيتم تحصيل الدفعة إليه.',

                'financial_account_id.exists' =>
                'الحساب المالي المحدد غير موجود أو غير نشط.',

                'bank_or_app_name.required' =>
                'أدخل اسم البنك أو التطبيق.',

                'paid_at.required' =>
                'حدد تاريخ الدفع.',

                'paid_at.before_or_equal' =>
                'تاريخ الدفع لا يمكن أن يكون في المستقبل.',
            ]);

        /*
         * لا نسمح بتاريخ دفعة يسبق تاريخ الفاتورة.
         */
        $invoiceDate =
            $salesInvoice
            ->sale_date
            ?->toDateString()
            ?? optional(
                $salesInvoice
                    ->created_at
            )->toDateString();

        if (
            $invoiceDate
            && $validated['paid_at'] < $invoiceDate
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'تاريخ الدفع لا يمكن أن يسبق تاريخ فاتورة البيع.'
                );
        }

        try {
            $this
                ->salesService
                ->addPayment(
                    $salesInvoice,
                    $validated
                );

            return redirect()
                ->route(
                    'sales.show',
                    $salesInvoice
                )
                ->with(
                    'success',
                    'تم تسجيل الدفعة وتحديث المبلغ المتبقي والحساب المالي بنجاح.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                        ?: 'تعذر تسجيل الدفعة.'
                );
        }
    }

    /**
     * إلغاء الفاتورة
     */
    public function cancel(Request $request, SalesInvoice $salesInvoice)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->salesService->cancel($salesInvoice, $validated['reason']);

            return redirect()->route('sales.show', $salesInvoice)
                ->with('success', 'تم إلغاء فاتورة البيع بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * طباعة الفاتورة
     */
    public function print(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['customer', 'items.product', 'payments.financialAccount']);

        $settings = app(\App\Services\SettingsService::class);

        return Inertia::render('Sales/Print', [
            'invoice' => $salesInvoice,
            'store' => $settings->getStoreSettings(),
            'invoiceSettings' => $settings->getInvoiceSettings(),
        ]);
    }
    /**
     * يفحص أقل سعر بيع اعتماداً على بيانات المنتج الحالية.
     *
     * ملاحظة:
     * - خصم الصنف يدخل مباشرة في صافي سعر القطعة.
     * - خصم الفاتورة يتم فحصه أيضاً على مستوى إجمالي الحدود
     *   الدنيا حتى يتطابق مع منطق الـPOS الحالي.
     */
    /**
     * مخزن المبيعات التشغيلي الحالي.
     *
     * المشروع يعتمد مخزن مبيعات رئيسياً عند إنشاء
     * فاتورة جديدة. بعد إنشاء البند يتم تثبيت warehouse_id
     * داخل البند نفسه، ويصبح هو المرجع التاريخي للعملية.
     */
    private function activeSalesWarehouse(): Warehouse
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
                'لا يوجد مخزن مبيعات نشط. فعّل مخزن المبيعات قبل استخدام نقطة البيع.'
            );
        }

        return $warehouse;
    }

    private function checkMinimumSellingPrices(
        array $data
    ): array {
        $items =
            collect(
                $data['items']
                    ?? []
            );

        if (
            $items->isEmpty()
        ) {
            return [
                'requires_approval' =>
                false,

                'violations' =>
                [],

                'minimum_total' =>
                0.0,

                'actual_total' =>
                0.0,

                'invoice_below_minimum' =>
                false,
            ];
        }

        $products =
            Product::query()
            ->whereIn(
                'id',
                $items->pluck(
                    'product_id'
                )
            )
            ->get([
                'id',
                'name',
                'minimum_selling_price',
            ])
            ->keyBy(
                'id'
            );

        $violations = [];
        $minimumTotal = 0.0;
        $linesTotal = 0.0;

        foreach (
            $items as $item
        ) {
            $product =
                $products->get(
                    (int) (
                        $item['product_id']
                        ?? 0
                    )
                );

            if (! $product) {
                continue;
            }

            $quantity =
                max(
                    1,
                    (int) (
                        $item['quantity']
                        ?? 1
                    )
                );

            $unitPrice =
                max(
                    0,
                    (float) (
                        $item['unit_selling_price']
                        ?? 0
                    )
                );

            $lineDiscount =
                max(
                    0,
                    (float) (
                        $item['line_discount']
                        ?? 0
                    )
                );

            $grossLine =
                $unitPrice
                * $quantity;

            /*
             * لا نسمح منطقياً بأن يجعل خصم السطر
             * قيمة السطر سالبة.
             */
            $netLine =
                max(
                    0,
                    $grossLine
                        - $lineDiscount
                );

            $netUnitPrice =
                $netLine
                / $quantity;

            $minimumPrice =
                max(
                    0,
                    (float) (
                        $product
                        ->minimum_selling_price
                        ?? 0
                    )
                );

            $linesTotal +=
                $netLine;

            if (
                $minimumPrice <= 0
            ) {
                continue;
            }

            $minimumTotal +=
                $minimumPrice
                * $quantity;

            if (
                $netUnitPrice
                + 0.00001
                < $minimumPrice
            ) {
                $violations[] = [
                    'product_id' =>
                    $product->id,

                    'product_name' =>
                    $product->name,

                    'quantity' =>
                    $quantity,

                    'minimum_price' =>
                    round(
                        $minimumPrice,
                        2
                    ),

                    'actual_price' =>
                    round(
                        $netUnitPrice,
                        2
                    ),

                    'difference' =>
                    round(
                        $minimumPrice
                            - $netUnitPrice,
                        2
                    ),
                ];
            }
        }

        $invoiceDiscount =
            max(
                0,
                (float) (
                    $data['invoice_discount']
                    ?? 0
                )
            );

        $actualTotal =
            max(
                0,
                $linesTotal
                    - $invoiceDiscount
            );

        /*
         * يحمي أيضاً من خصم فاتورة كبير يخفض
         * الإجمالي تحت مجموع الحدود الدنيا.
         */
        $invoiceBelowMinimum =
            $minimumTotal > 0
            && (
                $actualTotal
                + 0.00001
                < $minimumTotal
            );

        return [
            'requires_approval' =>
            ! empty($violations)
                || $invoiceBelowMinimum,

            'violations' =>
            $violations,

            'minimum_total' =>
            round(
                $minimumTotal,
                2
            ),

            'actual_total' =>
            round(
                $actualTotal,
                2
            ),

            'invoice_below_minimum' =>
            $invoiceBelowMinimum,
        ];
    }

    /**
     * رسالة واضحة في حال حاول أحد تجاوز الواجهة
     * وإتمام البيع بدون موافقة.
     */
    private function minimumPriceApprovalMessage(
        array $check
    ): string {
        $violations =
            collect(
                $check['violations'] ?? []
            );

        if (
            $violations
            ->isNotEmpty()
        ) {
            $first =
                $violations->first();

            return
                'السعر أقل من أقل سعر بيع للمنتج «'
                . $first['product_name']
                . '». السعر الفعلي '
                . number_format(
                    (float) $first['actual_price'],
                    2,
                    '.',
                    ','
                )
                . ' شيكل، والحد الأدنى '
                . number_format(
                    (float) $first['minimum_price'],
                    2,
                    '.',
                    ','
                )
                . ' شيكل. يلزم موافقة صاحب المحل لإتمام البيع.';
        }

        return
            'خصم الفاتورة سيجعل صافي البيع أقل من الحدود الدنيا المحددة للمنتجات. يلزم موافقة صاحب المحل لإتمام البيع.';
    }

    /**
     * توثيق الموافقة بدون إضافة عمود جديد حالياً.
     */
    private function appendMinimumPriceApprovalNote(
        ?string $existingNotes,
        string $reason,
        array $check
    ): string {
        $violations =
            collect(
                $check['violations'] ?? []
            );

        $productsText =
            $violations
            ->pluck(
                'product_name'
            )
            ->filter()
            ->unique()
            ->implode(
                '، '
            );

        $approvalNote =
            '[موافقة صاحب المحل على البيع تحت أقل سعر] '
            . 'السبب: '
            . $reason;

        if (
            $productsText !== ''
        ) {
            $approvalNote .=
                ' | المنتجات: '
                . $productsText;
        }

        if (
            (bool) (
                $check['invoice_below_minimum']
                ?? false
            )
        ) {
            $approvalNote .=
                ' | يوجد خصم فاتورة مؤثر على الحد الأدنى.';
        }

        $existingNotes =
            trim(
                (string) $existingNotes
            );

        return $existingNotes !== ''
            ? $existingNotes
            . PHP_EOL
            . $approvalNote
            : $approvalNote;
    }
}
