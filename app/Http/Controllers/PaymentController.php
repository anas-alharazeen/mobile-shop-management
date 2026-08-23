<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\PurchaseInvoice;
use App\Models\RepairOrder;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Services\PaymentManagementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PurchasePayment;
use App\Models\RepairPayment;
use App\Models\SalesPayment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentManagementService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * مركز الدفعات - نظرة عامة
     */
    public function index()
    {
        $stats = $this->paymentService->getStats();

        return Inertia::render('Payments/Index', [
            'stats' => $stats,
            'activeTab' => 'overview',
        ]);
    }

    /**
     * مستحقات العملاء
     */
    public function customerReceivables(
        Request $request
    ) {
        $salesQuery =
            SalesInvoice::query()
                ->with('customer')
                ->where('status', 'approved')
                ->where('remaining_amount', '>', 0)
                ->search($request->search)
                ->when(
                    $request->customer_id,
                    fn ($query) =>
                        $query->where(
                            'customer_id',
                            $request->customer_id
                        )
                )
                ->when(
                    $request->boolean('overdue_only'),
                    fn ($query) =>
                        $query
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', today())
                );

        $repairQuery =
            RepairOrder::query()
                ->with('customer')
                ->where('status', '!=', 'cancelled')
                ->where('remaining_amount', '>', 0)
                ->search($request->search)
                ->when(
                    $request->customer_id,
                    fn ($query) =>
                        $query->where(
                            'customer_id',
                            $request->customer_id
                        )
                )
                ->when(
                    $request->boolean('overdue_only'),
                    fn ($query) =>
                        $query
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', today())
                );

        $salesDue =
            (float) (clone $salesQuery)
                ->sum('remaining_amount');

        $repairDue =
            (float) (clone $repairQuery)
                ->sum('remaining_amount');

        /*
         * لكل جدول Page Name مستقل.
         * سابقاً الجدولان كانا يستخدمان ?page=،
         * لذلك Pagination الصيانة كانت تحرك جدول المبيعات أيضاً.
         */
        $salesInvoices =
            $salesQuery
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderByDesc('sale_date')
                ->paginate(
                    15,
                    ['*'],
                    'sales_page'
                )
                ->withQueryString();

        $repairOrders =
            $repairQuery
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderByDesc('received_at')
                ->paginate(
                    15,
                    ['*'],
                    'repairs_page'
                )
                ->withQueryString();

        $customers =
            Customer::query()
                ->active()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'phone',
                ]);

        return Inertia::render(
            'Payments/CustomerReceivables',
            [
                'salesInvoices' => $salesInvoices,
                'repairOrders' => $repairOrders,
                'customers' => $customers,
                'financialAccounts' =>
                    $this->activeFinancialAccounts(),

                'summary' => [
                    'sales_due' =>
                        round($salesDue, 2),

                    'repair_due' =>
                        round($repairDue, 2),

                    'total_due' =>
                        round(
                            $salesDue + $repairDue,
                            2
                        ),
                ],

                'filters' => [
                    'search' =>
                        $request->search,

                    'customer_id' =>
                        $request->customer_id,

                    'overdue_only' =>
                        $request->boolean('overdue_only'),
                ],
            ]
        );
    }

    /**
     * مستحقات الموردين
     */
    public function supplierPayables(
        Request $request
    ) {
        $query =
            PurchaseInvoice::query()
                ->with('supplier')
                ->where('status', 'approved')
                ->where('remaining_amount', '>', 0)
                ->search($request->search)
                ->when(
                    $request->supplier_id,
                    fn ($builder) =>
                        $builder->where(
                            'supplier_id',
                            $request->supplier_id
                        )
                )
                ->when(
                    $request->boolean('overdue_only'),
                    fn ($builder) =>
                        $builder
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', today())
                );

        $totalDue =
            (float) (clone $query)
                ->sum('remaining_amount');

        $invoiceCount =
            (int) (clone $query)
                ->count();

        $invoices =
            $query
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderByDesc('purchase_date')
                ->paginate(15)
                ->withQueryString();

        $suppliers =
            Supplier::query()
                ->active()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'company_name',
                ]);

        return Inertia::render(
            'Payments/SupplierPayables',
            [
                'invoices' => $invoices,
                'suppliers' => $suppliers,
                'financialAccounts' =>
                    $this->activeFinancialAccounts(),

                'summary' => [
                    'total_due' =>
                        round($totalDue, 2),

                    'invoice_count' =>
                        $invoiceCount,
                ],

                'filters' => [
                    'search' =>
                        $request->search,

                    'supplier_id' =>
                        $request->supplier_id,

                    'overdue_only' =>
                        $request->boolean('overdue_only'),
                ],
            ]
        );
    }

/**
 * سجل جميع الدفعات.
 */
public function allPayments(Request $request)
{
    $search = trim((string) $request->input('search', ''));
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $paymentMethodValue = static function ($method): string {
        if ($method instanceof PaymentMethod) {
            return $method->value;
        }

        return (string) $method;
    };

    /*
     * دفعات المبيعات.
     */
    $salesPayments = SalesPayment::query()
        ->with([
            'invoice.customer',
            'financialAccount',
        ])
        ->when($search !== '', function ($query) use ($search) {
            $query->whereHas('invoice', function ($invoiceQuery) use ($search) {
                $invoiceQuery
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        })
        ->when($startDate, function ($query) use ($startDate) {
            $query->whereDate('paid_at', '>=', $startDate);
        })
        ->when($endDate, function ($query) use ($endDate) {
            $query->whereDate('paid_at', '<=', $endDate);
        })
        ->get()
        ->map(function (SalesPayment $payment) use ($paymentMethodValue) {
            $invoice = $payment->invoice;

            /*
             * تجاهل أي دفعة يتيمة لا توجد فاتورتها.
             */
            if (! $invoice) {
                return null;
            }

            return [
                'id' => $payment->id,
                'type' => 'sales',

                'source_id' => $invoice->id,
                'reference_number' => $invoice->invoice_number,

                'party_name' => $invoice->customer_name
                    ?: $invoice->customer?->name
                    ?: 'عميل نقدي',

                'amount' => (string) $payment->amount,

                'payment_method' => $paymentMethodValue(
                    $payment->payment_method
                ),

                'financial_account_name' =>
                    $payment->financialAccount?->name,

                'bank_or_app_name' => $payment->bank_or_app_name,
                'transaction_reference' =>
                    $payment->transaction_reference,

                'paid_at' => $payment->paid_at?->toIso8601String(),
                'notes' => $payment->notes,
            ];
        })
        ->filter()
        ->values();

    /*
     * دفعات المشتريات.
     */
    $purchasePayments = PurchasePayment::query()
        ->with([
            'invoice.supplier',
            'financialAccount',
        ])
        ->when($search !== '', function ($query) use ($search) {
            $query->whereHas('invoice', function ($invoiceQuery) use ($search) {
                $invoiceQuery
                    ->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        })
        ->when($startDate, function ($query) use ($startDate) {
            $query->whereDate('paid_at', '>=', $startDate);
        })
        ->when($endDate, function ($query) use ($endDate) {
            $query->whereDate('paid_at', '<=', $endDate);
        })
        ->get()
        ->map(function (PurchasePayment $payment) use ($paymentMethodValue) {
            $invoice = $payment->invoice;

            if (! $invoice) {
                return null;
            }

            return [
                'id' => $payment->id,
                'type' => 'purchase',

                'source_id' => $invoice->id,
                'reference_number' => $invoice->invoice_number,

                'party_name' => $invoice->supplier?->name
                    ?: $invoice->supplier?->company_name
                    ?: 'مورد غير محدد',

                'amount' => (string) $payment->amount,

                'payment_method' => $paymentMethodValue(
                    $payment->payment_method
                ),

                'financial_account_name' =>
                    $payment->financialAccount?->name,

                'bank_or_app_name' => $payment->bank_or_app_name,
                'transaction_reference' =>
                    $payment->transaction_reference,

                'paid_at' => $payment->paid_at?->toIso8601String(),
                'notes' => $payment->notes,
            ];
        })
        ->filter()
        ->values();

    /*
     * دفعات الصيانة.
     *
     * withTrashed يضمن بقاء الدفعة في السجل حتى إذا
     * كانت عملية الصيانة محذوفة حذفاً ناعماً.
     */
    $repairPayments = RepairPayment::query()
        ->with([
            'repairOrder' => function ($query) {
                $query->withTrashed();
            },
            'financialAccount',
        ])
        ->when($search !== '', function ($query) use ($search) {
            $query->whereHas('repairOrder', function ($repairQuery) use ($search) {
                $repairQuery
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        })
        ->when($startDate, function ($query) use ($startDate) {
            $query->whereDate('paid_at', '>=', $startDate);
        })
        ->when($endDate, function ($query) use ($endDate) {
            $query->whereDate('paid_at', '<=', $endDate);
        })
        ->get()
        ->map(function (RepairPayment $payment) use ($paymentMethodValue) {
            $repairOrder = $payment->repairOrder;

            if (! $repairOrder) {
                return null;
            }

            return [
                'id' => $payment->id,
                'type' => 'repair',

                'source_id' => $repairOrder->id,
                'reference_number' => $repairOrder->order_number,

                'party_name' => $repairOrder->customer_name
                    ?: $repairOrder->customer?->name
                    ?: 'عميل غير محدد',

                'amount' => (string) $payment->amount,

                'payment_method' => $paymentMethodValue(
                    $payment->payment_method
                ),

                'financial_account_name' =>
                    $payment->financialAccount?->name,

                'bank_or_app_name' => $payment->bank_or_app_name,
                'transaction_reference' =>
                    $payment->transaction_reference,

                'paid_at' => $payment->paid_at?->toIso8601String(),
                'notes' => $payment->notes,
            ];
        })
        ->filter()
        ->values();

    /*
     * دمج الدفعات وترتيبها من الأحدث إلى الأقدم.
     */
    $allPayments = collect()
        ->concat($salesPayments)
        ->concat($purchasePayments)
        ->concat($repairPayments)
        ->sortByDesc('paid_at')
        ->values();

    /*
     * Pagination حقيقية متوافقة مع مكون Pagination.
     */
    $perPage = 20;

    $currentPage = LengthAwarePaginator::resolveCurrentPage(
        'page'
    );

    $currentPageItems = $allPayments
        ->forPage($currentPage, $perPage)
        ->values();

    $payments = new LengthAwarePaginator(
        $currentPageItems,
        $allPayments->count(),
        $perPage,
        $currentPage,
        [
            'path' => $request->url(),
            'pageName' => 'page',
        ]
    );

    $payments->appends(
        $request->except('page')
    );

    return Inertia::render('Payments/AllPayments', [
        'payments' => $payments,

        'paymentMethods' => PaymentMethod::labels(),

        'filters' => [
            'search' => $search,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ],
    ]);
}

/**
 * إنشاء روابط Pagination
 */
protected function getPaginationLinks($currentPage, $lastPage)
{
    $links = [];
    for ($i = 1; $i <= $lastPage; $i++) {
        $links[] = [
            'url' => route('payments.all-payments', ['page' => $i]),
            'label' => $i,
            'active' => $i == $currentPage,
        ];
    }
    return $links;
}

    /**
     * تسجيل دفعة من المركز الموحد
     */
    public function storePayment(
        Request $request
    ) {
        $validated =
            $request->validate([
                'type' => [
                    'required',
                    'string',
                    Rule::in([
                        'sales',
                        'purchase',
                        'repair',
                    ]),
                ],

                'invoice_id' => [
                    'required',
                    'integer',
                ],

                'amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
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
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

        try {
            $account =
                FinancialAccount::query()
                    ->active()
                    ->findOrFail(
                        $validated[
                            'financial_account_id'
                        ]
                    );

            if (
                in_array(
                    $validated['payment_method'],
                    [
                        'bank_transfer',
                        'banking_app',
                    ],
                    true
                )
                && trim(
                    (string) (
                        $validated['bank_or_app_name']
                        ?? ''
                    )
                ) === ''
            ) {
                $validated['bank_or_app_name'] =
                    $account->name;
            }

            if ($validated['type'] === 'sales') {
                $source =
                    SalesInvoice::query()
                        ->findOrFail(
                            $validated['invoice_id']
                        );

                $this->assertPaymentDate(
                    $validated['paid_at'],
                    $source->sale_date,
                    'تاريخ فاتورة البيع'
                );

                $this->paymentService
                    ->addSalesPayment(
                        $source,
                        $validated
                    );
            } elseif (
                $validated['type'] === 'purchase'
            ) {
                $source =
                    PurchaseInvoice::query()
                        ->findOrFail(
                            $validated['invoice_id']
                        );

                $this->assertPaymentDate(
                    $validated['paid_at'],
                    $source->purchase_date,
                    'تاريخ فاتورة الشراء'
                );

                $this->paymentService
                    ->addPurchasePayment(
                        $source,
                        $validated
                    );
            } else {
                $source =
                    RepairOrder::query()
                        ->findOrFail(
                            $validated['invoice_id']
                        );

                $this->assertPaymentDate(
                    $validated['paid_at'],
                    $source->received_at,
                    'تاريخ استلام جهاز الصيانة'
                );

                $this->paymentService
                    ->addRepairPayment(
                        $source,
                        $validated
                    );
            }

            return redirect()
                ->back()
                ->with(
                    'success',
                    'تم تسجيل الدفعة وربطها بالحساب المالي بنجاح.'
                );
        } catch (ValidationException $e) {
            throw $e;
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
     * كشف حساب العميل
     */
    public function customerStatement(
        Customer $customer
    ) {
        $salesInvoices =
            $customer
                ->salesInvoices()
                ->where('status', 'approved')
                ->with('payments')
                ->get();

        $repairOrders =
            $customer
                ->repairOrders()
                ->where('status', '!=', 'cancelled')
                ->with('payments')
                ->get();

        $sales =
            $salesInvoices->map(
                fn ($invoice) => [
                    'date' =>
                        $invoice->sale_date,

                    'type' =>
                        'sale',

                    'reference' =>
                        $invoice->invoice_number,

                    'description' =>
                        'فاتورة بيع',

                    'debit' =>
                        (float) $invoice->total_amount,

                    'credit' =>
                        0,

                    'sort_order' =>
                        10,

                    'sort_id' =>
                        $invoice->id,
                ]
            );

        $salesPayments =
            $salesInvoices
                ->flatMap(
                    fn ($invoice) =>
                        $invoice
                            ->payments
                            ->reject(
                                fn ($payment) =>
                                    $payment->payment_method
                                    === PaymentMethod::EXCHANGE_CREDIT
                            )
                            ->map(
                                fn ($payment) => [
                                    'date' =>
                                        $payment->paid_at,

                                    'type' =>
                                        'sale_payment',

                                    'reference' =>
                                        $invoice->invoice_number,

                                    'description' =>
                                        'دفعة بيع',

                                    'debit' =>
                                        0,

                                    'credit' =>
                                        (float) $payment->amount,

                                    'sort_order' =>
                                        20,

                                    'sort_id' =>
                                        $payment->id,
                                ]
                            )
                );

        $salesReturns =
            SalesReturn::query()
                ->where(
                    'customer_id',
                    $customer->id
                )
                ->where(
                    'status',
                    'approved'
                )
                ->get();

        $returnCredits =
            $salesReturns->map(
                fn ($return) => [
                    'date' =>
                        $return->return_date,

                    'type' =>
                        'sales_return',

                    'reference' =>
                        $return->return_number,

                    'description' =>
                        'مرتجع مبيعات',

                    'debit' =>
                        0,

                    'credit' =>
                        (float) $return->total_amount,

                    'sort_order' =>
                        30,

                    'sort_id' =>
                        $return->id,
                ]
            );

        $refundDebits =
            $salesReturns
                ->where(
                    'amount_refunded',
                    '>',
                    0
                )
                ->map(
                    fn ($return) => [
                        'date' =>
                            $return->approved_at
                            ?? $return->return_date,

                        'type' =>
                            'sales_refund',

                        'reference' =>
                            $return->return_number,

                        'description' =>
                            'مبلغ مسترد للعميل',

                        'debit' =>
                            (float) $return->amount_refunded,

                        'credit' =>
                            0,

                        'sort_order' =>
                            40,

                        'sort_id' =>
                            $return->id,
                    ]
                );

        $repairs =
            $repairOrders->map(
                fn ($order) => [
                    'date' =>
                        $order->received_at,

                    'type' =>
                        'repair',

                    'reference' =>
                        $order->order_number,

                    'description' =>
                        'طلب صيانة',

                    'debit' =>
                        (float) $order->total_amount,

                    'credit' =>
                        0,

                    'sort_order' =>
                        10,

                    'sort_id' =>
                        $order->id,
                ]
            );

        $repairPayments =
            $repairOrders
                ->flatMap(
                    fn ($order) =>
                        $order
                            ->payments
                            ->map(
                                fn ($payment) => [
                                    'date' =>
                                        $payment->paid_at,

                                    'type' =>
                                        'repair_payment',

                                    'reference' =>
                                        $order->order_number,

                                    'description' =>
                                        'دفعة صيانة',

                                    'debit' =>
                                        0,

                                    'credit' =>
                                        (float) $payment->amount,

                                    'sort_order' =>
                                        20,

                                    'sort_id' =>
                                        $payment->id,
                                ]
                            )
                );

        $transactions =
            $sales
                ->merge($salesPayments)
                ->merge($returnCredits)
                ->merge($refundDebits)
                ->merge($repairs)
                ->merge($repairPayments)
                ->sortBy(
                    fn ($item) =>
                        sprintf(
                            '%s-%02d-%010d',
                            Carbon::parse(
                                $item['date']
                            )->format(
                                'Y-m-d H:i:s'
                            ),
                            $item['sort_order'],
                            $item['sort_id']
                        )
                )
                ->values();

        $runningBalance = 0.0;

        $transactions =
            $transactions->map(
                function (
                    array $item
                ) use (
                    &$runningBalance
                ): array {
                    $runningBalance +=
                        (float) (
                            $item['debit']
                            ?? 0
                        )
                        - (float) (
                            $item['credit']
                            ?? 0
                        );

                    $item['balance'] =
                        round(
                            $runningBalance,
                            2
                        );

                    unset(
                        $item['sort_order'],
                        $item['sort_id']
                    );

                    return $item;
                }
            );

        /*
         * الرصيد الرسمي يأتي من remaining_amount الحالي،
         * وهو نفس المصدر المستخدم في شاشة المستحقات.
         */
        $salesDue =
            (float) $salesInvoices
                ->sum('remaining_amount');

        $repairDue =
            (float) $repairOrders
                ->sum('remaining_amount');

        $officialBalance =
            round(
                $salesDue + $repairDue,
                2
            );

        $ledgerBalance =
            round(
                $runningBalance,
                2
            );

        return Inertia::render(
            'Payments/CustomerStatement',
            [
                'customer' =>
                    $customer,

                'transactions' =>
                    $transactions,

                'summary' => [
                    'total_sales' =>
                        round(
                            (float) $sales
                                ->sum('debit')
                            - (float) $returnCredits
                                ->sum('credit'),
                            2
                        ),

                    'total_repairs' =>
                        round(
                            (float) $repairs
                                ->sum('debit'),
                            2
                        ),

                    'total_paid' =>
                        round(
                            (float) $salesPayments
                                ->sum('credit')
                            + (float) $repairPayments
                                ->sum('credit')
                            - (float) $refundDebits
                                ->sum('debit'),
                            2
                        ),

                    'sales_due' =>
                        round($salesDue, 2),

                    'repair_due' =>
                        round($repairDue, 2),

                    'balance' =>
                        $officialBalance,

                    'ledger_balance' =>
                        $ledgerBalance,

                    'balance_difference' =>
                        round(
                            $ledgerBalance
                            - $officialBalance,
                            2
                        ),
                ],
            ]
        );
    }

    /**
     * كشف حساب المورد
     */
    public function supplierStatement(
        Supplier $supplier
    ) {
        $purchaseInvoices =
            $supplier
                ->purchaseInvoices()
                ->where('status', 'approved')
                ->with('payments')
                ->get();

        $purchases =
            $purchaseInvoices->map(
                fn ($invoice) => [
                    'date' =>
                        $invoice->purchase_date,

                    'type' =>
                        'purchase',

                    'reference' =>
                        $invoice->invoice_number,

                    'description' =>
                        'فاتورة شراء',

                    'debit' =>
                        (float) $invoice->total_amount,

                    'credit' =>
                        0,

                    'sort_order' =>
                        10,

                    'sort_id' =>
                        $invoice->id,
                ]
            );

        $purchasePayments =
            $purchaseInvoices
                ->flatMap(
                    fn ($invoice) =>
                        $invoice
                            ->payments
                            ->map(
                                fn ($payment) => [
                                    'date' =>
                                        $payment->paid_at,

                                    'type' =>
                                        'purchase_payment',

                                    'reference' =>
                                        $invoice->invoice_number,

                                    'description' =>
                                        'دفعة مورد',

                                    'debit' =>
                                        0,

                                    'credit' =>
                                        (float) $payment->amount,

                                    'sort_order' =>
                                        20,

                                    'sort_id' =>
                                        $payment->id,
                                ]
                            )
                );

        $purchaseReturns =
            PurchaseReturn::query()
                ->where(
                    'supplier_id',
                    $supplier->id
                )
                ->where(
                    'status',
                    'approved'
                )
                ->get();

        $returnCredits =
            $purchaseReturns->map(
                fn ($return) => [
                    'date' =>
                        $return->return_date,

                    'type' =>
                        'purchase_return',

                    'reference' =>
                        $return->return_number,

                    'description' =>
                        'مرتجع مشتريات',

                    'debit' =>
                        0,

                    'credit' =>
                        (float) $return->total_amount,

                    'sort_order' =>
                        30,

                    'sort_id' =>
                        $return->id,
                ]
            );

        $supplierRefunds =
            $purchaseReturns
                ->where(
                    'amount_refunded',
                    '>',
                    0
                )
                ->map(
                    fn ($return) => [
                        'date' =>
                            $return->approved_at
                            ?? $return->return_date,

                        'type' =>
                            'supplier_refund',

                        'reference' =>
                            $return->return_number,

                        'description' =>
                            'مبلغ مسترد من المورد',

                        'debit' =>
                            (float) $return->amount_refunded,

                        'credit' =>
                            0,

                        'sort_order' =>
                            40,

                        'sort_id' =>
                            $return->id,
                    ]
                );

        $transactions =
            $purchases
                ->merge($purchasePayments)
                ->merge($returnCredits)
                ->merge($supplierRefunds)
                ->sortBy(
                    fn ($item) =>
                        sprintf(
                            '%s-%02d-%010d',
                            Carbon::parse(
                                $item['date']
                            )->format(
                                'Y-m-d H:i:s'
                            ),
                            $item['sort_order'],
                            $item['sort_id']
                        )
                )
                ->values();

        $runningBalance = 0.0;

        $transactions =
            $transactions->map(
                function (
                    array $item
                ) use (
                    &$runningBalance
                ): array {
                    $runningBalance +=
                        (float) (
                            $item['debit']
                            ?? 0
                        )
                        - (float) (
                            $item['credit']
                            ?? 0
                        );

                    $item['balance'] =
                        round(
                            $runningBalance,
                            2
                        );

                    unset(
                        $item['sort_order'],
                        $item['sort_id']
                    );

                    return $item;
                }
            );

        $officialBalance =
            round(
                (float) $purchaseInvoices
                    ->sum('remaining_amount'),
                2
            );

        $ledgerBalance =
            round(
                $runningBalance,
                2
            );

        return Inertia::render(
            'Payments/SupplierStatement',
            [
                'supplier' =>
                    $supplier,

                'transactions' =>
                    $transactions,

                'summary' => [
                    'total_purchases' =>
                        round(
                            (float) $purchases
                                ->sum('debit')
                            - (float) $returnCredits
                                ->sum('credit'),
                            2
                        ),

                    'total_paid' =>
                        round(
                            (float) $purchasePayments
                                ->sum('credit')
                            - (float) $supplierRefunds
                                ->sum('debit'),
                            2
                        ),

                    'balance' =>
                        $officialBalance,

                    'ledger_balance' =>
                        $ledgerBalance,

                    'balance_difference' =>
                        round(
                            $ledgerBalance
                            - $officialBalance,
                            2
                        ),
                ],
            ]
        );
    }

    /**
     * الحسابات المالية المتاحة للدفعات الجديدة.
     */
    private function activeFinancialAccounts(): array
    {
        return FinancialAccount::query()
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(
                fn (
                    FinancialAccount $account
                ): array => [
                    'id' => $account->id,
                    'name' => $account->name,

                    'type' =>
                        $account->type?->value
                        ?? (string) $account->type,

                    'type_label' =>
                        $account->type_label,

                    'current_balance' =>
                        (float) $account->current_balance,
                ]
            )
            ->values()
            ->all();
    }

    /**
     * لا نسمح بدفعة تسبق تاريخ المستند الأصلي.
     */
    private function assertPaymentDate(
        mixed $paidAt,
        mixed $sourceDate,
        string $sourceLabel
    ): void {
        if (! $sourceDate) {
            return;
        }

        $paymentDate =
            Carbon::parse($paidAt)
                ->startOfDay();

        $documentDate =
            Carbon::parse($sourceDate)
                ->startOfDay();

        if (
            $paymentDate->lt(
                $documentDate
            )
        ) {
            throw ValidationException::withMessages([
                'paid_at' =>
                    "تاريخ الدفعة لا يمكن أن يسبق {$sourceLabel}.",
            ]);
        }
    }

}
