<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use App\Http\Requests\SavePurchaseInvoiceRequest;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private readonly PurchaseInvoiceService $purchaseService
    ) {}

    public function index(
        Request $request
    ): Response {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'period' => [
                'nullable',
                'string',
                'in:today,yesterday,last_7_days,this_week,this_month,last_month,this_year',
            ],

            'status' => [
                'nullable',
                'string',
                Rule::in(
                    array_column(
                        PurchaseInvoiceStatus::cases(),
                        'value'
                    )
                ),
            ],

            'payment_status' => [
                'nullable',
                'string',
                Rule::in(
                    array_column(
                        PaymentStatus::cases(),
                        'value'
                    )
                ),
            ],

            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ]);

        [
            $startDate,
            $endDate,
        ] = $this->resolveDateRange(
            $filters
        );

        $query = PurchaseInvoice::query()
            ->with([
                'supplier:id,name,company_name',
            ])
            ->withCount('items')
            ->withSum(
                'items as total_pieces',
                'quantity'
            )
            ->search(
                $filters['search']
                    ?? null
            )
            ->status(
                $filters['status']
                    ?? null
            )
            ->paymentStatus(
                $filters['payment_status']
                    ?? null
            )
            ->when(
                ! empty($filters['supplier_id']),
                fn($query) =>
                $query->where(
                    'supplier_id',
                    $filters['supplier_id']
                )
            )
            ->when(
                $startDate,
                fn($query) =>
                $query->whereDate(
                    'purchase_date',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $endDate,
                fn($query) =>
                $query->whereDate(
                    'purchase_date',
                    '<=',
                    $endDate
                )
            );

        $invoices = $query
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        /*
         * الإحصائيات المالية تعتمد الفواتير المعتمدة فقط.
         * المسودات والملغاة لا تدخل في أرقام المشتريات الفعلية.
         */
        $approvedBase = PurchaseInvoice::query()
            ->where(
                'status',
                PurchaseInvoiceStatus::APPROVED
                    ->value
            );

        $stats = [
            'today_amount' =>
            (float) (
                clone $approvedBase
            )
                ->whereDate(
                    'purchase_date',
                    today()
                )
                ->sum('total_amount'),

            'today_count' => (
                clone $approvedBase
            )
                ->whereDate(
                    'purchase_date',
                    today()
                )
                ->count(),

            'month_amount' =>
            (float) (
                clone $approvedBase
            )
                ->whereBetween(
                    'purchase_date',
                    [
                        now()
                            ->startOfMonth()
                            ->toDateString(),

                        now()
                            ->endOfMonth()
                            ->toDateString(),
                    ]
                )
                ->sum('total_amount'),

            'month_count' => (
                clone $approvedBase
            )
                ->whereBetween(
                    'purchase_date',
                    [
                        now()
                            ->startOfMonth()
                            ->toDateString(),

                        now()
                            ->endOfMonth()
                            ->toDateString(),
                    ]
                )
                ->count(),

            'total_due' =>
            (float) (
                clone $approvedBase
            )
                ->where(
                    'remaining_amount',
                    '>',
                    0
                )
                ->sum(
                    'remaining_amount'
                ),

            'overdue_due' =>
            (float) (
                clone $approvedBase
            )
                ->where(
                    'remaining_amount',
                    '>',
                    0
                )
                ->whereNotNull(
                    'due_date'
                )
                ->whereDate(
                    'due_date',
                    '<',
                    today()
                )
                ->sum(
                    'remaining_amount'
                ),

            'overdue_count' => (
                clone $approvedBase
            )
                ->where(
                    'remaining_amount',
                    '>',
                    0
                )
                ->whereNotNull(
                    'due_date'
                )
                ->whereDate(
                    'due_date',
                    '<',
                    today()
                )
                ->count(),

            'draft_count' =>
            PurchaseInvoice::query()
                ->where(
                    'status',
                    PurchaseInvoiceStatus::DRAFT
                        ->value
                )
                ->count(),

            'approved_count' =>
            PurchaseInvoice::query()
                ->where(
                    'status',
                    PurchaseInvoiceStatus::APPROVED
                        ->value
                )
                ->count(),
        ];

        return Inertia::render(
            'Purchases/Index',
            [
                'invoices' =>
                $invoices,

                'stats' =>
                $stats,

                'filters' => [
                    'search' =>
                    $filters['search']
                        ?? '',

                    'period' =>
                    $filters['period']
                        ?? '',

                    'status' =>
                    $filters['status']
                        ?? '',

                    'payment_status' =>
                    $filters['payment_status'] ?? '',

                    'supplier_id' =>
                    $filters['supplier_id']
                        ?? '',

                    'start_date' =>
                    $filters['start_date']
                        ?? '',

                    'end_date' =>
                    $filters['end_date']
                        ?? '',
                ],

                'suppliers' =>
                Supplier::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        'company_name',
                    ]),

                'statuses' =>
                PurchaseInvoiceStatus::labels(),

                'paymentStatuses' =>
                PaymentStatus::labels(),

                'paymentMethods' =>
                PaymentMethod::selectableLabels(),

                'financialAccounts' =>
                $this->financialAccounts(),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Purchases/Create',
            [
                'suppliers' =>
                Supplier::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        'company_name',
                        'phone',
                        'email',
                        'address',
                    ]),

                /*
                 * لا نعرض المخازن غير النشطة.
                 */
                'warehouses' =>
                Warehouse::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy('type')
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        'type',
                        'is_active',
                    ]),

                'products' =>
                Product::query()
                    ->active()
                    ->with([
                        'category:id,name',
                        'stocks:id,product_id,warehouse_id,quantity',
                    ])
                    ->orderBy('name')
                    ->get(),

                'categories' =>
                Category::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ]),

                'paymentMethods' =>
                PaymentMethod::selectableLabels(),

                'financialAccounts' =>
                $this->financialAccounts(),
            ]
        );
    }

    public function store(
        SavePurchaseInvoiceRequest $request
    ) {
        $validated =
            $request->validated();

        try {
            /*
             * إنشاء المنتجات الجديدة + الفاتورة + الدفعة الأولية
             * كلها تحت Transaction واحدة.
             *
             * إذا فشلت أي خطوة لا يبقى Product ناقص
             * أو فاتورة جزئية في قاعدة البيانات.
             */
            $invoice =
                DB::transaction(
                    function () use (
                        $validated
                    ): PurchaseInvoice {
                        $invoice =
                            $this->purchaseService
                                ->create(
                                    $validated
                                );

                        if (
                            (float) (
                                $validated[
                                    'payment_amount'
                                ]
                                ?? 0
                            ) > 0
                        ) {
                            $this->purchaseService
                                ->addPayment(
                                    $invoice,
                                    [
                                        'amount' =>
                                        (float) $validated[
                                            'payment_amount'
                                        ],

                                        'payment_method' =>
                                        $validated[
                                            'payment_method'
                                        ],

                                        'financial_account_id' =>
                                        (int) $validated[
                                            'financial_account_id'
                                        ],

                                        'bank_or_app_name' =>
                                        $validated[
                                            'bank_or_app_name'
                                        ]
                                            ?? null,

                                        'transaction_reference' =>
                                        $validated[
                                            'transaction_reference'
                                        ]
                                            ?? null,

                                        /*
                                         * الدفعة الأولية التاريخية
                                         * تتبع تاريخ الفاتورة.
                                         * ترحيلها المالي الحقيقي يحدث
                                         * عند اعتماد الفاتورة.
                                         */
                                        'paid_at' =>
                                        $validated[
                                            'purchase_date'
                                        ],

                                        'notes' =>
                                        'دفعة أولية عند إنشاء فاتورة الشراء',
                                    ]
                                );
                        }

                        return $invoice
                            ->fresh();
                    }
                );
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException
                ::withMessages([
                    'purchase' =>
                    $e->getMessage()
                        ?: 'تعذر إنشاء فاتورة الشراء.',
                ]);
        }

        return redirect()
            ->route(
                'purchases.show',
                $invoice
            )
            ->with(
                'success',
                'تم إنشاء فاتورة الشراء كمسودة. المنتجات الجديدة أضيفت تلقائياً برصيد صفر، وستدخل الكميات للمخزون فقط عند اعتماد الفاتورة.'
            );
    }

    public function show(
        PurchaseInvoice $purchaseInvoice
    ): Response {
        $purchaseInvoice->load([
            'supplier',
            'items.product',
            'items.warehouse',
            'payments.financialAccount',
        ]);

        $purchaseInvoice->can_be_edited =
            $purchaseInvoice->can_be_edited;

        $purchaseInvoice->can_be_approved =
            $purchaseInvoice->can_be_approved;

        $purchaseInvoice->can_be_cancelled =
            $purchaseInvoice->can_be_cancelled;

        $purchaseInvoice->can_add_payment =
            $purchaseInvoice->can_add_payment;

        return Inertia::render(
            'Purchases/Show',
            [
                'invoice' =>
                $purchaseInvoice,

                'statuses' =>
                PurchaseInvoiceStatus::labels(),

                'paymentStatuses' =>
                PaymentStatus::labels(),

                'paymentMethods' =>
                PaymentMethod::selectableLabels(),

                'financialAccounts' =>
                $this->financialAccounts(),
            ]
        );
    }

public function edit(
    PurchaseInvoice $purchaseInvoice
): Response|RedirectResponse {
    if (
        ! $purchaseInvoice
            ->can_be_edited
    ) {
        return redirect()
            ->route(
                'purchases.show',
                $purchaseInvoice
            )
            ->with(
                'error',
                'لا يمكن تعديل فاتورة معتمدة.'
            );
    }

    $purchaseInvoice->load([
        'items.product',
        'items.warehouse',
    ]);

        return Inertia::render(
            'Purchases/Edit',
            [
                'invoice' =>
                $purchaseInvoice,

                'suppliers' =>
                Supplier::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        'company_name',
                        'phone',
                        'email',
                        'address',
                    ]),

                'warehouses' =>
                Warehouse::query()
                    ->where(
                        'is_active',
                        true
                    )
                    ->orderBy('type')
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        'type',
                    ]),

                'products' =>
                Product::query()
                    ->active()
                    ->with([
                        'category',
                        'stocks',
                    ])
                    ->orderBy('name')
                    ->get(),

                'categories' =>
                Category::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ]),

                'paymentMethods' =>
                PaymentMethod::selectableLabels(),

                'financialAccounts' =>
                $this->financialAccounts(),
            ]
        );
    }

    public function update(
        SavePurchaseInvoiceRequest $request,
        PurchaseInvoice $purchaseInvoice
    ) {
        if (
            ! $purchaseInvoice
                ->can_be_edited
        ) {
            return redirect()
                ->route(
                    'purchases.show',
                    $purchaseInvoice
                )
                ->with(
                    'error',
                    'لا يمكن تعديل فاتورة معتمدة.'
                );
        }

        try {
            $this->purchaseService
                ->update(
                    $purchaseInvoice,
                    $request->validated()
                );
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException
                ::withMessages([
                    'purchase' =>
                    $e->getMessage()
                        ?: 'تعذر تحديث فاتورة الشراء.',
                ]);
        }

        return redirect()
            ->route(
                'purchases.show',
                $purchaseInvoice
            )
            ->with(
                'success',
                'تم تحديث فاتورة الشراء بنجاح.'
            );
    }

    public function approve(
        PurchaseInvoice $purchaseInvoice
    ) {
        try {
            $this->purchaseService
                ->approve(
                    $purchaseInvoice
                );

            return redirect()
                ->route(
                    'purchases.show',
                    $purchaseInvoice
                )
                ->with(
                    'success',
                    'تم اعتماد فاتورة الشراء وإضافة الكميات للمخزون وترحيل دفعاتها مالياً.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

public function addPayment(
    Request $request,
    PurchaseInvoice $purchaseInvoice
) {
    $purchaseInvoice->refresh();

    /*
     * الدفعات اليدوية من صفحة المشتريات
     * مسموحة فقط للفواتير المعتمدة.
     *
     * الدفعة الأولية الخاصة بالمسودة
     * يتم التعامل معها أثناء إنشاء الفاتورة.
     */
    if (
        $purchaseInvoice->status
        !== PurchaseInvoiceStatus::APPROVED
    ) {
        return redirect()
            ->back()
            ->with(
                'error',
                'يمكن تسجيل دفعة يدوية على فاتورة شراء معتمدة فقط.'
            );
    }

    if (
        (float) $purchaseInvoice->remaining_amount
        <= 0.00001
    ) {
        return redirect()
            ->back()
            ->with(
                'error',
                'هذه الفاتورة مدفوعة بالكامل ولا يوجد مبلغ متبقٍ.'
            );
    }

    $validated = $request->validate([
        'amount' => [
            'required',
            'numeric',
            'min:0.01',
            'max:'.(float) $purchaseInvoice->remaining_amount,
        ],

        'payment_method' => [
            'required',
            'string',

            Rule::in(
                array_keys(
                    PaymentMethod::selectableLabels()
                )
            ),
        ],

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

            function (
                string $attribute,
                mixed $value,
                \Closure $fail
            ) use (
                $purchaseInvoice
            ): void {
                $paymentDate = Carbon::parse(
                    $value
                )->startOfDay();

                $invoiceDate = Carbon::parse(
                    $purchaseInvoice->purchase_date
                )->startOfDay();

                if (
                    $paymentDate->lt(
                        $invoiceDate
                    )
                ) {
                    $fail(
                        'تاريخ الدفع لا يمكن أن يسبق تاريخ فاتورة الشراء.'
                    );
                }
            },
        ],

        'notes' => [
            'nullable',
            'string',
            'max:500',
        ],
    ]);

    try {
        $payment = $this
            ->purchaseService
            ->addPayment(
                $purchaseInvoice,
                $validated
            );

        $purchaseInvoice->refresh();

        return redirect()
            ->back()
            ->with(
                'success',
                'تم تسجيل دفعة بقيمة '
                .number_format(
                    (float) $payment->amount,
                    2,
                    '.',
                    ','
                )
                .' شيكل بنجاح. المتبقي على الفاتورة: '
                .number_format(
                    (float) $purchaseInvoice->remaining_amount,
                    2,
                    '.',
                    ','
                )
                .' شيكل.'
            );
    } catch (\Throwable $e) {
        report($e);

        return redirect()
            ->back()
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

    public function cancel(
        Request $request,
        PurchaseInvoice $purchaseInvoice
    ) {
        $validated =
            $request->validate([
                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],
            ]);

        try {
            $this->purchaseService
                ->cancel(
                    $purchaseInvoice,
                    $validated['reason']
                );

            return redirect()
                ->route(
                    'purchases.show',
                    $purchaseInvoice
                )
                ->with(
                    'success',
                    'تم إلغاء فاتورة الشراء وعكس الكميات المضافة منها.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    private function resolveDateRange(
        array $filters
    ): array {
        if (
            ! empty($filters['start_date'])
            || ! empty($filters['end_date'])
        ) {
            return [
                $filters['start_date']
                    ?? null,

                $filters['end_date']
                    ?? null,
            ];
        }

        $period =
            $filters['period']
            ?? null;

        return match ($period) {
            'today' => [
                today()->toDateString(),
                today()->toDateString(),
            ],

            'yesterday' => [
                today()
                    ->subDay()
                    ->toDateString(),

                today()
                    ->subDay()
                    ->toDateString(),
            ],

            'last_7_days' => [
                now()
                    ->subDays(6)
                    ->toDateString(),

                now()
                    ->toDateString(),
            ],

            'this_week' => [
                now()
                    ->startOfWeek()
                    ->toDateString(),

                now()
                    ->endOfWeek()
                    ->toDateString(),
            ],

            'this_month' => [
                now()
                    ->startOfMonth()
                    ->toDateString(),

                now()
                    ->endOfMonth()
                    ->toDateString(),
            ],

            'last_month' => [
                now()
                    ->subMonthNoOverflow()
                    ->startOfMonth()
                    ->toDateString(),

                now()
                    ->subMonthNoOverflow()
                    ->endOfMonth()
                    ->toDateString(),
            ],

            'this_year' => [
                now()
                    ->startOfYear()
                    ->toDateString(),

                now()
                    ->endOfYear()
                    ->toDateString(),
            ],

            default => [
                null,
                null,
            ],
        };
    }

    private function financialAccounts()
    {
        return FinancialAccount::query()
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
                        $account->type?->value
                            ?? $account->type,

                        'type_label' =>
                        $account->type_label,

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
    }
}
