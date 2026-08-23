<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesExchange;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use App\Services\PurchaseReturnService;
use App\Services\SalesExchangeService;
use App\Services\SalesReturnService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReturnController extends Controller
{
    public function __construct(
        private readonly SalesReturnService $salesReturnService,
        private readonly PurchaseReturnService $purchaseReturnService,
        private readonly SalesExchangeService $exchangeService,
    ) {}

    /**
     * مركز المرتجعات والاستبدال.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'tab' => [
                'nullable',
                'string',
                'in:sales,purchase,exchange',
            ],
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'nullable',
                'string',
                'in:draft,approved,cancelled',
            ],
        ]);

        $search = trim(
            (string) ($filters['search'] ?? '')
        );

        $status =
            $filters['status'] ?? null;

        $salesReturns = SalesReturn::query()
            ->with([
                'customer',
                'invoice',
                'account',
                'exchange',
            ])
            ->when(
                $status,
                fn(Builder $query) =>
                $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            $searchQuery
                                ->where(
                                    'return_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'invoice',
                                    fn(Builder $invoice) =>
                                    $invoice->where(
                                        'invoice_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                )
                                ->orWhereHas(
                                    'customer',
                                    function (
                                        Builder $customer
                                    ) use ($search): void {
                                        $customer
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'phone',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->paginate(
                10,
                ['*'],
                'sales_page'
            )
            ->withQueryString();

        $purchaseReturns = PurchaseReturn::query()
            ->with([
                'supplier',
                'invoice',
                'account',
            ])
            ->when(
                $status,
                fn(Builder $query) =>
                $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            $searchQuery
                                ->where(
                                    'return_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'invoice',
                                    fn(Builder $invoice) =>
                                    $invoice->where(
                                        'invoice_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                )
                                ->orWhereHas(
                                    'supplier',
                                    function (
                                        Builder $supplier
                                    ) use ($search): void {
                                        $supplier
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'company_name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->orderByDesc('return_date')
            ->orderByDesc('id')
            ->paginate(
                10,
                ['*'],
                'purchase_page'
            )
            ->withQueryString();

        $exchanges = SalesExchange::query()
            ->with([
                'salesReturn.customer',
                'salesReturn.invoice',
                'newInvoice.customer',
                'account',
            ])
            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            $searchQuery
                                ->where(
                                    'exchange_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'salesReturn',
                                    fn(Builder $return) =>
                                    $return->where(
                                        'return_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                )
                                ->orWhereHas(
                                    'newInvoice',
                                    fn(Builder $invoice) =>
                                    $invoice->where(
                                        'invoice_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                );
                        }
                    );
                }
            )
            ->orderByDesc('exchanged_at')
            ->orderByDesc('id')
            ->paginate(
                10,
                ['*'],
                'exchange_page'
            )
            ->withQueryString();

        $stats = [
            'sales_today' =>
            (float) SalesReturn::query()
                ->where(
                    'status',
                    'approved'
                )
                ->whereDate(
                    'return_date',
                    today()
                )
                ->sum('total_amount'),

            'purchase_month' =>
            (float) PurchaseReturn::query()
                ->where(
                    'status',
                    'approved'
                )
                ->whereMonth(
                    'return_date',
                    now()->month
                )
                ->whereYear(
                    'return_date',
                    now()->year
                )
                ->sum('total_amount'),

            'refunded_to_customers' =>
            (float) SalesReturn::query()
                ->where(
                    'status',
                    'approved'
                )
                ->sum('amount_refunded'),

            'refunded_from_suppliers' =>
            (float) PurchaseReturn::query()
                ->where(
                    'status',
                    'approved'
                )
                ->sum('amount_refunded'),

            'sales_returns_count' =>
            SalesReturn::query()->count(),

            'purchase_returns_count' =>
            PurchaseReturn::query()->count(),

            'exchanges_count' =>
            SalesExchange::query()->count(),

            'drafts_count' =>
            SalesReturn::query()
                ->where(
                    'status',
                    'draft'
                )
                ->count()
                + PurchaseReturn::query()
                ->where(
                    'status',
                    'draft'
                )
                ->count(),
        ];

        return Inertia::render(
            'Returns/Index',
            [
                'salesReturns' =>
                $salesReturns,

                'purchaseReturns' =>
                $purchaseReturns,

                'exchanges' =>
                $exchanges,

                'stats' =>
                $stats,

                'filters' => [
                    'tab' =>
                    $filters['tab']
                        ?? 'sales',

                    'search' =>
                    $search,

                    'status' =>
                    $status ?? '',
                ],
            ]
        );
    }

    /**
     * صفحة موحدة لاختيار نوع المرتجع والفاتورة.
     */
    public function createReturn(): Response
    {
        return Inertia::render(
            'Returns/CreateReturn',
            [
                'salesInvoices' =>
                $this->returnableSalesInvoices(),

                'purchaseInvoices' =>
                $this->returnablePurchaseInvoices(),
            ]
        );
    }

    /**
     * إنشاء مرتجع مبيعات.
     */
    public function createSalesReturn(
        int $invoiceId
    ): Response {
        $invoice = SalesInvoice::query()
            ->with([
                'customer',
                'items.product',
                'items.warehouse',
            ])
            ->where(
                'status',
                'approved'
            )
            ->findOrFail($invoiceId);

        $invoice =
            $this->decorateSalesInvoice(
                $invoice
            );

        abort_if(
            $invoice->items
                ->sum('available_quantity')
                <= 0,
            422,
            'لا توجد كميات متبقية قابلة للإرجاع في هذه الفاتورة.'
        );

        return Inertia::render(
            'Returns/SalesReturn',
            [
                'invoice' =>
                $invoice,

                'accounts' =>
                $this->activeAccounts(),

                'warehouses' =>
                $this
                    ->activeSalesWarehouses(),
            ]
        );
    }

    /**
     * تخزين مرتجع مبيعات.
     */
    public function storeSalesReturn(
        Request $request
    ) {
        $validated =
            $request->validate([
                'sales_invoice_id' => [
                    'required',
                    'exists:sales_invoices,id',
                ],

                'return_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                /*
                 * return_type يبقى للتوافق،
                 * لكن Service يعيد حسابه فعلياً.
                 */
                'return_type' => [
                    'nullable',
                    'string',
                    'in:partial,full',
                ],

                'approve_now' => [
                    'nullable',
                    'boolean',
                ],

                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'financial_account_id' => [
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

                'refund_method' => [
                    'nullable',
                    'string',
                    'in:cash,bank_transfer,banking_app,debt',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.sales_invoice_item_id' => [
                    'required',
                    'distinct',
                    'exists:sales_invoice_items,id',
                ],

                'items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'items.*.product_condition' => [
                    'required',
                    'string',
                    'in:sellable,damaged,needs_inspection',
                ],

                'items.*.restock' => [
                    'nullable',
                    'boolean',
                ],

                'items.*.warehouse_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'warehouses',
                        'id'
                    )
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'type',
                            'sales'
                        ),
                ],

                'items.*.notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

        try {
            $return =
                $this->salesReturnService
                ->create(
                    $validated
                );

            if (
                (bool) (
                    $validated['approve_now']
                    ?? false
                )
            ) {
                $return =
                    $this->salesReturnService
                    ->approve(
                        $return
                    );
            }

            return redirect()
                ->route(
                    'returns.index',
                    [
                        'tab' =>
                        'sales',
                    ]
                )
                ->with(
                    'success',
                    $return->status
                        === 'approved'
                        ? 'تم إنشاء واعتماد مرتجع المبيعات بنجاح.'
                        : 'تم حفظ مرتجع المبيعات كمسودة.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function approveSalesReturn(
        SalesReturn $salesReturn
    ) {
        try {
            $this->salesReturnService
                ->approve(
                    $salesReturn
                );

            return redirect()
                ->route(
                    'returns.index',
                    [
                        'tab' =>
                        'sales',
                    ]
                )
                ->with(
                    'success',
                    'تم اعتماد مرتجع المبيعات.'
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

    public function cancelSalesReturn(
        Request $request,
        SalesReturn $salesReturn
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
            $this->salesReturnService
                ->cancel(
                    $salesReturn,
                    $validated['reason']
                );

            return redirect()
                ->route(
                    'returns.index',
                    [
                        'tab' =>
                        'sales',
                    ]
                )
                ->with(
                    'success',
                    'تم إلغاء مرتجع المبيعات وعكس أثره عند الحاجة.'
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

    /**
     * إنشاء مرتجع مشتريات.
     */
    public function createPurchaseReturn(
        int $invoiceId
    ): Response {
        $invoice = PurchaseInvoice::query()
            ->with([
                'supplier',
                'items.product.stocks.warehouse',
                'items.warehouse',
            ])
            ->where(
                'status',
                'approved'
            )
            ->findOrFail($invoiceId);

        $invoice =
            $this->decoratePurchaseInvoice(
                $invoice
            );

        abort_if(
            $invoice->items
                ->sum('available_quantity')
                <= 0,
            422,
            'لا توجد كميات متبقية قابلة للإرجاع في هذه الفاتورة.'
        );

        return Inertia::render(
            'Returns/PurchaseReturn',
            [
                'invoice' =>
                $invoice,

                'accounts' =>
                $this->activeAccounts(),

                'warehouses' =>
                $this->activeWarehouses(),
            ]
        );
    }

    /**
     * تخزين مرتجع مشتريات.
     */
    public function storePurchaseReturn(
        Request $request
    ) {
        $validated =
            $request->validate([
                'purchase_invoice_id' => [
                    'required',
                    'exists:purchase_invoices,id',
                ],

                'return_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                'return_type' => [
                    'nullable',
                    'string',
                    'in:partial,full',
                ],

                'approve_now' => [
                    'nullable',
                    'boolean',
                ],

                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'financial_account_id' => [
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

                'refund_method' => [
                    'nullable',
                    'string',
                    'in:cash,bank_transfer,banking_app,debt',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.purchase_invoice_item_id' => [
                    'required',
                    'distinct',
                    'exists:purchase_invoice_items,id',
                ],

                'items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'items.*.warehouse_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'warehouses',
                        'id'
                    )->where(
                        'is_active',
                        true
                    ),
                ],

                'items.*.reason' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'items.*.notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

        try {
            $return =
                $this->purchaseReturnService
                ->create(
                    $validated
                );

            if (
                (bool) (
                    $validated['approve_now']
                    ?? false
                )
            ) {
                $return =
                    $this->purchaseReturnService
                    ->approve(
                        $return
                    );
            }

            return redirect()
                ->route(
                    'returns.index',
                    [
                        'tab' =>
                        'purchase',
                    ]
                )
                ->with(
                    'success',
                    $return->status
                        === 'approved'
                        ? 'تم إنشاء واعتماد مرتجع المشتريات بنجاح.'
                        : 'تم حفظ مرتجع المشتريات كمسودة.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function approvePurchaseReturn(
        PurchaseReturn $purchaseReturn
    ) {
        try {
            $this->purchaseReturnService
                ->approve(
                    $purchaseReturn
                );

            return redirect()
                ->route(
                    'returns.index',
                    [
                        'tab' =>
                        'purchase',
                    ]
                )
                ->with(
                    'success',
                    'تم اعتماد مرتجع المشتريات.'
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

    public function cancelPurchaseReturn(
        Request $request,
        PurchaseReturn $purchaseReturn
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
            $this->purchaseReturnService
                ->cancel(
                    $purchaseReturn,
                    $validated['reason']
                );

            return redirect()
                ->route(
                    'returns.index',
                    [
                        'tab' =>
                        'purchase',
                    ]
                )
                ->with(
                    'success',
                    'تم إلغاء مرتجع المشتريات وعكس أثره عند الحاجة.'
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

    /**
     * إنشاء استبدال.
     */
    public function createExchange(): Response
    {
        /*
         * نفس مخزن المبيعات التشغيلي المستخدم في الـPOS.
         * هذا يمنع أن تعرض شاشة الاستبدال كمية من مخزن
         * بينما فاتورة البدائل تخصم من مخزن آخر.
         */
        $salesWarehouse =
            $this->primarySalesWarehouse();

        $products =
            Product::query()
            ->active()
            ->with([
                'category:id,name',

                /*
                     * Eager-load callbacks may receive the relation
                     * instance itself (HasMany) in Laravel 13.
                     * Do not force an Eloquent Builder type here.
                     */
                'stocks' =>
                fn($query) =>
                $query->where(
                    'warehouse_id',
                    $salesWarehouse->id
                ),
            ])
            ->whereHas(
                'stocks',
                fn(Builder $query) =>
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
                ): Product {
                    $available =
                        (int) (
                            $product
                            ->stocks
                            ->first()
                            ?->quantity
                            ?? 0
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
                        'unit_selling_price',
                        (float) $product
                            ->selling_price
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
            )
            ->values();

        return Inertia::render(
            'Returns/CreateExchange',
            [
                'invoices' =>
                $this
                    ->returnableSalesInvoices(),

                'products' =>
                $products,

                'accounts' =>
                $this->activeAccounts(),

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
     * تخزين الاستبدال.
     */
    public function storeExchange(
        Request $request
    ) {
        $validated =
            $request->validate([
                'original_invoice_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'sales_invoices',
                        'id'
                    )->where(
                        'status',
                        'approved'
                    ),
                ],

                'exchange_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                'reason' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'financial_account_id' => [
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

                'invoice_discount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                /*
                 * إذا أصبحت المنتجات البديلة تحت أقل سعر،
                 * الاستبدال لا يُمنع نهائياً، لكنه يحتاج
                 * موافقة صاحب المحل وسبباً واضحاً.
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

                'return_items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'return_items.*.sales_invoice_item_id' => [
                    'required',
                    'integer',
                    'distinct',
                    'exists:sales_invoice_items,id',
                ],

                'return_items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'return_items.*.product_condition' => [
                    'required',
                    'string',
                    'in:sellable,damaged,needs_inspection',
                ],

                'return_items.*.restock' => [
                    'nullable',
                    'boolean',
                ],

                'return_items.*.warehouse_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'warehouses',
                        'id'
                    )
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'type',
                            'sales'
                        ),
                ],

                'return_items.*.notes' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'new_items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'new_items.*.product_id' => [
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

                'new_items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'new_items.*.unit_selling_price' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'new_items.*.line_discount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],
            ]);

        try {
            $exchange =
                $this
                ->exchangeService
                ->create(
                    $validated
                );

            return redirect()
                ->route(
                    'returns.index',
                    [
                        'tab' =>
                        'exchange',
                    ]
                )
                ->with(
                    'success',
                    "تم إنشاء الاستبدال {$exchange->exchange_number} بنجاح."
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                        ?: 'تعذر تنفيذ عملية الاستبدال.'
                );
        }
    }

    public function printSalesReturn(
        SalesReturn $salesReturn
    ): Response {
        $salesReturn->load([
            'customer',
            'items.product',
            'items.warehouse',
            'invoice',
            'account',
        ]);

        return Inertia::render(
            'Returns/PrintSalesReturn',
            [
                'returnData' =>
                $salesReturn,
            ]
        );
    }

    public function printPurchaseReturn(
        PurchaseReturn $purchaseReturn
    ): Response {
        $purchaseReturn->load([
            'supplier',
            'items.product',
            'items.warehouse',
            'invoice',
            'account',
        ]);

        return Inertia::render(
            'Returns/PrintPurchaseReturn',
            [
                'returnData' =>
                $purchaseReturn,
            ]
        );
    }

    public function printExchange(
        SalesExchange $exchange
    ): Response {
        $exchange->load([
            'salesReturn.customer',
            'salesReturn.items.product',
            'newInvoice.customer',
            'newInvoice.items.product',
            'account',
        ]);

        return Inertia::render(
            'Returns/PrintExchange',
            [
                'exchange' =>
                $exchange,
            ]
        );
    }

    private function returnableSalesInvoices(
        int $limit = 150
    ): Collection {
        $invoices = SalesInvoice::query()
            ->with([
                'customer',
                'items.product',
                'items.warehouse',
            ])
            ->where(
                'status',
                'approved'
            )
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $invoices
            ->map(
                fn(SalesInvoice $invoice) =>
                $this
                    ->decorateSalesInvoice(
                        $invoice
                    )
            )
            ->filter(
                fn(SalesInvoice $invoice) =>
                (int) $invoice
                    ->returnable_quantity
                    > 0
            )
            ->values();
    }

    private function returnablePurchaseInvoices(
        int $limit = 150
    ): Collection {
        $invoices = PurchaseInvoice::query()
            ->with([
                'supplier',
                'items.product',
                'items.warehouse',
            ])
            ->where(
                'status',
                'approved'
            )
            ->orderByDesc(
                'purchase_date'
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $invoices
            ->map(
                fn(
                    PurchaseInvoice $invoice
                ) =>
                $this
                    ->decoratePurchaseInvoice(
                        $invoice
                    )
            )
            ->filter(
                fn(
                    PurchaseInvoice $invoice
                ) =>
                (int) $invoice
                    ->returnable_quantity
                    > 0
            )
            ->values();
    }

    private function decorateSalesInvoice(
        SalesInvoice $invoice
    ): SalesInvoice {
        $itemIds =
            $invoice->items
            ->pluck('id');

        $returned = $itemIds->isEmpty()
            ? collect()
            : SalesReturnItem::query()
            ->selectRaw(
                'sales_invoice_item_id, '
                    . 'SUM(quantity) as returned_quantity, '
                    . 'SUM(total_amount) as returned_amount'
            )
            ->whereIn(
                'sales_invoice_item_id',
                $itemIds
            )
            ->whereHas(
                'return',
                fn(Builder $query) =>
                $query->where(
                    'status',
                    '!=',
                    'cancelled'
                )
            )
            ->groupBy(
                'sales_invoice_item_id'
            )
            ->get()
            ->keyBy(
                'sales_invoice_item_id'
            );

        $invoice->items->each(
            function ($item) use (
                $returned
            ): void {
                $summary =
                    $returned[$item->id] ?? null;

                /*
                 * عندما نستخدم pluck لاحقاً نحتاج كائناً كاملاً،
                 * لذلك هذا المسار يُملأ أدناه بعد تحويل النتيجة.
                 */
                $alreadyReturned =
                    (int) (
                        is_object(
                            $summary
                        )
                        ? (
                            $summary
                            ->returned_quantity
                            ?? 0
                        )
                        : 0
                    );

                $alreadyReturnedAmount =
                    (float) (
                        is_object(
                            $summary
                        )
                        ? (
                            $summary
                            ->returned_amount
                            ?? 0
                        )
                        : 0
                    );

                $available = max(
                    0,
                    (int) $item->quantity
                        - $alreadyReturned
                );

                $sourceLineTotal =
                    round(
                        (float) $item
                            ->line_total,
                        2
                    );

                $availableReturnAmount =
                    round(
                        max(
                            0,
                            $sourceLineTotal
                                - $alreadyReturnedAmount
                        ),
                        2
                    );

                $unitValue =
                    $available > 0
                    ? (
                        $availableReturnAmount
                        / $available
                    )
                    : 0;

                $item->setAttribute(
                    'returned_quantity',
                    $alreadyReturned
                );

                $item->setAttribute(
                    'returned_amount',
                    round(
                        $alreadyReturnedAmount,
                        2
                    )
                );

                $item->setAttribute(
                    'available_quantity',
                    $available
                );

                $item->setAttribute(
                    'available_return_amount',
                    $availableReturnAmount
                );

                $item->setAttribute(
                    'source_line_total',
                    $sourceLineTotal
                );

                $item->setAttribute(
                    'source_quantity',
                    (int) $item
                        ->quantity
                );

                $item->setAttribute(
                    'net_unit_price',
                    round(
                        $unitValue,
                        2
                    )
                );
            }
        );

        $invoice->setAttribute(
            'returnable_quantity',
            (int) $invoice->items
                ->sum(
                    'available_quantity'
                )
        );

        $invoice->setAttribute(
            'returnable_value',
            round(
                (float) $invoice->items
                    ->sum(
                        fn($item) =>
                        (float) $item
                            ->available_return_amount
                    ),
                2
            )
        );

        return $invoice;
    }

    private function decoratePurchaseInvoice(
        PurchaseInvoice $invoice
    ): PurchaseInvoice {
        $itemIds =
            $invoice->items
            ->pluck('id');

        $returned = $itemIds->isEmpty()
            ? collect()
            : PurchaseReturnItem::query()
            ->selectRaw(
                'purchase_invoice_item_id, '
                    . 'SUM(quantity) as returned_quantity, '
                    . 'SUM(total_amount) as returned_amount'
            )
            ->whereIn(
                'purchase_invoice_item_id',
                $itemIds
            )
            ->whereHas(
                'return',
                fn(Builder $query) =>
                $query->where(
                    'status',
                    '!=',
                    'cancelled'
                )
            )
            ->groupBy(
                'purchase_invoice_item_id'
            )
            ->get()
            ->keyBy(
                'purchase_invoice_item_id'
            );

        $invoice->items->each(
            function ($item) use (
                $returned
            ): void {
                $summary =
                    $returned[$item->id] ?? null;

                $alreadyReturned =
                    (int) (
                        $summary
                        ?->returned_quantity
                        ?? 0
                    );

                $alreadyReturnedAmount =
                    round(
                        (float) (
                            $summary
                            ?->returned_amount
                            ?? 0
                        ),
                        2
                    );

                $available = max(
                    0,
                    (int) $item->quantity
                        - $alreadyReturned
                );

                $sourceLineCost =
                    $item->landed_line_cost
                    !== null
                    ? round(
                        (float) $item
                            ->landed_line_cost,
                        2
                    )
                    : round(
                        (int) $item->quantity
                            * (float) (
                                $item->landed_unit_cost
                                ?: $item->unit_purchase_price
                            ),
                        2
                    );

                $availableReturnAmount =
                    round(
                        max(
                            0,
                            $sourceLineCost
                                - $alreadyReturnedAmount
                        ),
                        2
                    );

                $unitCost =
                    $available > 0
                    ? (
                        $availableReturnAmount
                        / $available
                    )
                    : 0;

                $item->setAttribute(
                    'returned_quantity',
                    $alreadyReturned
                );

                $item->setAttribute(
                    'returned_amount',
                    $alreadyReturnedAmount
                );

                $item->setAttribute(
                    'available_quantity',
                    $available
                );

                $item->setAttribute(
                    'source_landed_line_cost',
                    $sourceLineCost
                );

                $item->setAttribute(
                    'source_quantity',
                    (int) $item->quantity
                );

                $item->setAttribute(
                    'available_return_amount',
                    $availableReturnAmount
                );

                $item->setAttribute(
                    'return_unit_cost',
                    round(
                        $unitCost,
                        2
                    )
                );

                $currentTotalStock =
                    (int) collect(
                        $item->product?->stocks
                            ?? []
                    )->sum(
                        'quantity'
                    );

                $item->setAttribute(
                    'current_total_stock',
                    $currentTotalStock
                );

                $item->setAttribute(
                    'current_average_cost',
                    round(
                        (float) (
                            $item->product
                            ?->purchase_price
                            ?? 0
                        ),
                        2
                    )
                );
            }
        );

        $invoice->setAttribute(
            'returnable_quantity',
            (int) $invoice->items
                ->sum(
                    'available_quantity'
                )
        );

        $invoice->setAttribute(
            'returnable_value',
            round(
                (float) $invoice->items
                    ->sum(
                        fn($item) =>
                        (float) $item
                            ->available_return_amount
                    ),
                2
            )
        );

        return $invoice;
    }

    private function activeAccounts(): Collection
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

    /**
     * مخزن المبيعات الرئيسي المستخدم في العمليات الجديدة.
     * البنود بعد إنشائها تحتفظ بـ warehouse_id تاريخياً.
     */
    private function primarySalesWarehouse(): Warehouse
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
                'لا يوجد مخزن مبيعات نشط. فعّل مخزن المبيعات قبل تنفيذ الاستبدال.'
            );
        }

        return $warehouse;
    }

    private function activeSalesWarehouses(): Collection
    {
        return Warehouse::query()
            ->where(
                'is_active',
                true
            )
            ->where(
                'type',
                'sales'
            )
            ->orderBy('name')
            ->get()
            ->map(
                function (
                    Warehouse $warehouse
                ): array {
                    return [
                        'id' =>
                        $warehouse->id,

                        'name' =>
                        $warehouse->name,

                        'type' =>
                        $warehouse->type?->value
                            ?? $warehouse->type,

                        'is_active' =>
                        (bool) $warehouse
                            ->is_active,
                    ];
                }
            )
            ->values();
    }

    private function activeWarehouses(): Collection
    {
        return Warehouse::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(
                function (
                    Warehouse $warehouse
                ): array {
                    return [
                        'id' =>
                        $warehouse->id,

                        'name' =>
                        $warehouse->name,

                        'type' =>
                        $warehouse->type?->value
                            ?? $warehouse->type,

                        'is_active' =>
                        (bool) $warehouse
                            ->is_active,
                    ];
                }
            )
            ->values();
    }
}
