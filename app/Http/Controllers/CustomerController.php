<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\SalesInvoice;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $debtStatus =
            $request->string(
                'debt_status'
            )->toString();

        if (
            ! in_array(
                $debtStatus,
                [
                    '',
                    'with_due',
                    'without_due',
                    'overdue',
                ],
                true
            )
        ) {
            $debtStatus = '';
        }

        $query =
            Customer::query()
                ->search(
                    $request->search
                )
                ->when(
                    $request->has(
                        'is_active'
                    ),
                    fn ($builder) =>
                        $builder->where(
                            'is_active',
                            $request->boolean(
                                'is_active'
                            )
                        )
                )
                /*
                 * نجلب المؤشرات المالية كـSubqueries داخل نفس
                 * الاستعلام لتجنب N+1 عند عرض 15 عميلاً.
                 */
                ->withSum(
                    [
                        'salesInvoices as sales_due' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    'approved'
                                ),
                    ],
                    'remaining_amount'
                )
                ->withSum(
                    [
                        'repairOrders as repair_due' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    '!=',
                                    'cancelled'
                                ),
                    ],
                    'remaining_amount'
                )
                ->withSum(
                    [
                        'salesInvoices as sales_total' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    'approved'
                                ),
                    ],
                    'total_amount'
                )
                ->withCount(
                    [
                        'salesInvoices as sales_count' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    'approved'
                                ),

                        'repairOrders as repairs_count' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    '!=',
                                    'cancelled'
                                ),

                        'repairOrders as active_repairs_count' =>
                            fn ($builder) =>
                                $builder
                                    ->where(
                                        'status',
                                        '!=',
                                        'cancelled'
                                    )
                                    ->where(
                                        'status',
                                        '!=',
                                        'delivered'
                                    ),
                    ]
                )
                ->withMax(
                    [
                        'salesInvoices as last_sale_date' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    'approved'
                                ),
                    ],
                    'sale_date'
                )
                ->withMax(
                    [
                        'repairOrders as last_repair_date' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    '!=',
                                    'cancelled'
                                ),
                    ],
                    'received_at'
                )
                ->when(
                    $debtStatus === 'with_due',
                    fn ($builder) =>
                        $builder->where(
                            function ($nested): void {
                                $nested
                                    ->whereHas(
                                        'salesInvoices',
                                        fn ($invoiceQuery) =>
                                            $invoiceQuery
                                                ->where(
                                                    'status',
                                                    'approved'
                                                )
                                                ->where(
                                                    'remaining_amount',
                                                    '>',
                                                    0
                                                )
                                    )
                                    ->orWhereHas(
                                        'repairOrders',
                                        fn ($repairQuery) =>
                                            $repairQuery
                                                ->where(
                                                    'status',
                                                    '!=',
                                                    'cancelled'
                                                )
                                                ->where(
                                                    'remaining_amount',
                                                    '>',
                                                    0
                                                )
                                    );
                            }
                        )
                )
                ->when(
                    $debtStatus === 'without_due',
                    fn ($builder) =>
                        $builder
                            ->whereDoesntHave(
                                'salesInvoices',
                                fn ($invoiceQuery) =>
                                    $invoiceQuery
                                        ->where(
                                            'status',
                                            'approved'
                                        )
                                        ->where(
                                            'remaining_amount',
                                            '>',
                                            0
                                        )
                            )
                            ->whereDoesntHave(
                                'repairOrders',
                                fn ($repairQuery) =>
                                    $repairQuery
                                        ->where(
                                            'status',
                                            '!=',
                                            'cancelled'
                                        )
                                        ->where(
                                            'remaining_amount',
                                            '>',
                                            0
                                        )
                            )
                )
                ->when(
                    $debtStatus === 'overdue',
                    fn ($builder) =>
                        $builder->where(
                            function ($nested): void {
                                $nested
                                    ->whereHas(
                                        'salesInvoices',
                                        fn ($invoiceQuery) =>
                                            $invoiceQuery
                                                ->where(
                                                    'status',
                                                    'approved'
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
                                    )
                                    ->orWhereHas(
                                        'repairOrders',
                                        fn ($repairQuery) =>
                                            $repairQuery
                                                ->where(
                                                    'status',
                                                    '!=',
                                                    'cancelled'
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
                                    );
                            }
                        )
                );

        $sort =
            $request->string(
                'sort',
                'recent'
            )->toString();

        match ($sort) {
            'name' =>
                $query->orderBy(
                    'name'
                ),

            'oldest' =>
                $query->orderBy(
                    'created_at'
                ),

            default =>
                $query->orderByDesc(
                    'created_at'
                ),
        };

        $customers =
            $query
                ->paginate(15)
                ->withQueryString();

        /*
         * نجهز total_due وlast_activity على نتائج الصفحة
         * فقط، بدون Query إضافي لكل عميل.
         */
        $customers->through(
            function (
                Customer $customer
            ): Customer {
                $salesDue =
                    (float) (
                        $customer
                            ->sales_due
                        ?? 0
                    );

                $repairDue =
                    (float) (
                        $customer
                            ->repair_due
                        ?? 0
                    );

                $customer->setAttribute(
                    'total_due',
                    round(
                        $salesDue
                        + $repairDue,
                        2
                    )
                );

                $lastActivity =
                    collect([
                        $customer
                            ->last_sale_date,
                        $customer
                            ->last_repair_date,
                    ])
                        ->filter()
                        ->sortDesc()
                        ->first();

                $customer->setAttribute(
                    'last_activity_at',
                    $lastActivity
                );

                return $customer;
            }
        );

        /*
         * بطاقات القائمة تستخدم نفس قواعد المستحقات
         * المعتمدة في مركز الدفعات.
         */
        $salesDueTotal =
            (float) SalesInvoice::query()
                ->where(
                    'status',
                    'approved'
                )
                ->sum(
                    'remaining_amount'
                );

        $repairDueTotal =
            (float) RepairOrder::query()
                ->where(
                    'status',
                    '!=',
                    'cancelled'
                )
                ->sum(
                    'remaining_amount'
                );

        $customersWithDue =
            Customer::query()
                ->where(
                    function ($query): void {
                        $query
                            ->whereHas(
                                'salesInvoices',
                                fn ($invoiceQuery) =>
                                    $invoiceQuery
                                        ->where(
                                            'status',
                                            'approved'
                                        )
                                        ->where(
                                            'remaining_amount',
                                            '>',
                                            0
                                        )
                            )
                            ->orWhereHas(
                                'repairOrders',
                                fn ($repairQuery) =>
                                    $repairQuery
                                        ->where(
                                            'status',
                                            '!=',
                                            'cancelled'
                                        )
                                        ->where(
                                            'remaining_amount',
                                            '>',
                                            0
                                        )
                            );
                    }
                )
                ->count();

        $overdueCustomers =
            Customer::query()
                ->where(
                    function ($query): void {
                        $query
                            ->whereHas(
                                'salesInvoices',
                                fn ($invoiceQuery) =>
                                    $invoiceQuery
                                        ->where(
                                            'status',
                                            'approved'
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
                            )
                            ->orWhereHas(
                                'repairOrders',
                                fn ($repairQuery) =>
                                    $repairQuery
                                        ->where(
                                            'status',
                                            '!=',
                                            'cancelled'
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
                            );
                    }
                )
                ->count();

        $stats = [
            'total' =>
                Customer::count(),

            'active' =>
                Customer::active()
                    ->count(),

            'with_due' =>
                $customersWithDue,

            'overdue' =>
                $overdueCustomers,

            'total_due' =>
                round(
                    $salesDueTotal
                    + $repairDueTotal,
                    2
                ),
        ];

        return Inertia::render(
            'Customers/Index',
            [
                'customers' =>
                    $customers,

                'stats' =>
                    $stats,

                'filters' => [
                    'search' =>
                        $request->search,

                    'is_active' =>
                        $request->has(
                            'is_active'
                        )
                            ? $request->boolean(
                                'is_active'
                            )
                            : null,

                    'debt_status' =>
                        $debtStatus,

                    'sort' =>
                        $sort,
                ],
            ]
        );
    }

    public function create()
    {
        return Inertia::render('Customers/Form', [
            'customer' => null,
            'pageTitle' => 'إضافة عميل جديد',
        ]);
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        $customer = Customer::create([
            'code' => Customer::generateCode(),
            'name' => trim($validated['name']),
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'تم إضافة العميل بنجاح');
    }

    public function show(Customer $customer)
    {
        /*
         * صفحة العميل يجب أن تستخدم نفس مصادر الحقيقة
         * المستخدمة في صفحات المستحقات:
         * - المبيعات المعتمدة فقط.
         * - طلبات الصيانة غير الملغاة.
         * - المرتجعات المعتمدة فقط.
         */
        $salesQuery =
            $customer
                ->salesInvoices()
                ->where(
                    'status',
                    'approved'
                );

        $repairsQuery =
            $customer
                ->repairOrders()
                ->where(
                    'status',
                    '!=',
                    'cancelled'
                );

        $returnsQuery =
            $customer
                ->salesReturns()
                ->where(
                    'status',
                    'approved'
                );

        $salesGross =
            (float) (
                clone $salesQuery
            )->sum(
                'total_amount'
            );

        $salesReturns =
            (float) (
                clone $returnsQuery
            )->sum(
                'total_amount'
            );

        $salesDue =
            (float) (
                clone $salesQuery
            )->sum(
                'remaining_amount'
            );

        $repairTotal =
            (float) (
                clone $repairsQuery
            )->sum(
                'total_amount'
            );

        $repairDue =
            (float) (
                clone $repairsQuery
            )->sum(
                'remaining_amount'
            );

        $activeRepairs =
            (int) (
                clone $repairsQuery
            )
                ->whereNotIn(
                    'status',
                    [
                        'delivered',
                        'cancelled',
                    ]
                )
                ->count();

        $recentSales =
            (clone $salesQuery)
                ->orderByDesc(
                    'sale_date'
                )
                ->orderByDesc('id')
                ->limit(8)
                ->get([
                    'id',
                    'invoice_number',
                    'sale_date',
                    'due_date',
                    'total_amount',
                    'paid_amount',
                    'remaining_amount',
                    'payment_status',
                ]);

        $recentRepairs =
            (clone $repairsQuery)
                ->orderByDesc(
                    'received_at'
                )
                ->orderByDesc('id')
                ->limit(8)
                ->get([
                    'id',
                    'order_number',
                    'device_type',
                    'brand',
                    'model',
                    'status',
                    'received_at',
                    'due_date',
                    'total_amount',
                    'paid_amount',
                    'remaining_amount',
                    'payment_status',
                ]);

        $recentReturns =
            (clone $returnsQuery)
                ->with([
                    'invoice:id,invoice_number',
                ])
                ->orderByDesc(
                    'return_date'
                )
                ->orderByDesc('id')
                ->limit(6)
                ->get([
                    'id',
                    'return_number',
                    'sales_invoice_id',
                    'return_date',
                    'total_amount',
                    'amount_used_for_debt',
                    'amount_refunded',
                    'reason',
                ]);

        return Inertia::render(
            'Customers/Show',
            [
                'customer' =>
                    $customer,

                'summary' => [
                    'sales_count' =>
                        (int) (
                            clone $salesQuery
                        )->count(),

                    'sales_gross' =>
                        round(
                            $salesGross,
                            2
                        ),

                    'sales_returns' =>
                        round(
                            $salesReturns,
                            2
                        ),

                    'net_sales' =>
                        round(
                            $salesGross
                            - $salesReturns,
                            2
                        ),

                    'sales_due' =>
                        round(
                            $salesDue,
                            2
                        ),

                    'repair_count' =>
                        (int) (
                            clone $repairsQuery
                        )->count(),

                    'active_repairs' =>
                        $activeRepairs,

                    'repair_total' =>
                        round(
                            $repairTotal,
                            2
                        ),

                    'repair_due' =>
                        round(
                            $repairDue,
                            2
                        ),

                    'total_due' =>
                        round(
                            $salesDue
                            + $repairDue,
                            2
                        ),
                ],

                'recentSales' =>
                    $recentSales,

                'recentRepairs' =>
                    $recentRepairs,

                'recentReturns' =>
                    $recentReturns,
            ]
        );
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Customers/Form', [
            'customer' => $customer,
            'pageTitle' => 'تعديل العميل',
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $customer->update([
            'name' => trim($validated['name']),
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'تم تحديث العميل بنجاح');
    }

public function destroy(Customer $customer)
{
    if ($customer->salesInvoices()->count() > 0 || $customer->repairOrders()->count() > 0) {
        return redirect()->route('customers.index')
            ->with('error', 'لا يمكن حذف هذا العميل لأنه مرتبط بفواتير بيع أو طلبات صيانة.');
    }

    $customer->delete();

    return redirect()->route('customers.index')
        ->with('success', 'تم حذف العميل بنجاح');
}

    public function toggleStatus(Customer $customer)
    {
        $customer->update([
            'is_active' => !$customer->is_active,
        ]);

        $status = $customer->is_active ? 'مفعل' : 'غير مفعل';
        return redirect()->route('customers.index')
            ->with('success', "تم {$status} العميل بنجاح");
    }
}
