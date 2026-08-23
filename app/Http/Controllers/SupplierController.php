<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
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
            Supplier::query()
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
                ->withSum(
                    [
                        'purchaseInvoices as total_due' =>
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
                        'purchaseInvoices as purchase_total' =>
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
                        'purchaseInvoices as invoices_count' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    'approved'
                                ),

                        'purchaseInvoices as overdue_invoices_count' =>
                            fn ($builder) =>
                                $builder
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
                                    ),
                    ]
                )
                ->withMax(
                    [
                        'purchaseInvoices as last_purchase_date' =>
                            fn ($builder) =>
                                $builder->where(
                                    'status',
                                    'approved'
                                ),
                    ],
                    'purchase_date'
                )
                ->when(
                    $debtStatus === 'with_due',
                    fn ($builder) =>
                        $builder->whereHas(
                            'purchaseInvoices',
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
                )
                ->when(
                    $debtStatus === 'without_due',
                    fn ($builder) =>
                        $builder->whereDoesntHave(
                            'purchaseInvoices',
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
                )
                ->when(
                    $debtStatus === 'overdue',
                    fn ($builder) =>
                        $builder->whereHas(
                            'purchaseInvoices',
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

        $suppliers =
            $query
                ->paginate(15)
                ->withQueryString();

        $suppliers->through(
            function (
                Supplier $supplier
            ): Supplier {
                $supplier->setAttribute(
                    'total_due',
                    round(
                        (float) (
                            $supplier
                                ->total_due
                            ?? 0
                        ),
                        2
                    )
                );

                $supplier->setAttribute(
                    'last_activity_at',
                    $supplier
                        ->last_purchase_date
                );

                return $supplier;
            }
        );

        $totalDue =
            (float) PurchaseInvoice::query()
                ->where(
                    'status',
                    'approved'
                )
                ->sum(
                    'remaining_amount'
                );

        $suppliersWithDue =
            Supplier::query()
                ->whereHas(
                    'purchaseInvoices',
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
                ->count();

        $overdueSuppliers =
            Supplier::query()
                ->whereHas(
                    'purchaseInvoices',
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
                ->count();

        $stats = [
            'total' =>
                Supplier::count(),

            'active' =>
                Supplier::active()
                    ->count(),

            'with_due' =>
                $suppliersWithDue,

            'overdue' =>
                $overdueSuppliers,

            'total_due' =>
                round(
                    $totalDue,
                    2
                ),
        ];

        return Inertia::render(
            'Suppliers/Index',
            [
                'suppliers' =>
                    $suppliers,

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
        return Inertia::render('Suppliers/Form', [
            'supplier' => null,
            'pageTitle' => 'إضافة مورد جديد',
        ]);
    }

    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        $supplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => trim($validated['name']),
            'company_name' => $validated['company_name'] ?? null,
            'phone' => $validated['phone'],
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('suppliers.index')
            ->with('success', 'تم إضافة المورد بنجاح');
    }

    public function show(Supplier $supplier)
    {
        /*
         * صفحة المورد تعتمد فقط المستندات المالية المعتمدة،
         * حتى تتطابق الأرقام مع مستحقات المورد وكشف الحساب.
         */
        $purchasesQuery =
            $supplier
                ->purchaseInvoices()
                ->where(
                    'status',
                    'approved'
                );

        $returnsQuery =
            $supplier
                ->purchaseReturns()
                ->where(
                    'status',
                    'approved'
                );

        $purchaseGross =
            (float) (
                clone $purchasesQuery
            )->sum(
                'total_amount'
            );

        $purchaseReturns =
            (float) (
                clone $returnsQuery
            )->sum(
                'total_amount'
            );

        $totalDue =
            (float) (
                clone $purchasesQuery
            )->sum(
                'remaining_amount'
            );

        $recentPurchases =
            (clone $purchasesQuery)
                ->orderByDesc(
                    'purchase_date'
                )
                ->orderByDesc('id')
                ->limit(10)
                ->get([
                    'id',
                    'invoice_number',
                    'supplier_invoice_number',
                    'purchase_date',
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
                ->limit(8)
                ->get([
                    'id',
                    'return_number',
                    'purchase_invoice_id',
                    'return_date',
                    'total_amount',
                    'amount_used_for_debt',
                    'amount_refunded',
                    'reason',
                ]);

        return Inertia::render(
            'Suppliers/Show',
            [
                'supplier' =>
                    $supplier,

                'summary' => [
                    'invoice_count' =>
                        (int) (
                            clone $purchasesQuery
                        )->count(),

                    'purchase_gross' =>
                        round(
                            $purchaseGross,
                            2
                        ),

                    'purchase_returns' =>
                        round(
                            $purchaseReturns,
                            2
                        ),

                    'net_purchases' =>
                        round(
                            $purchaseGross
                            - $purchaseReturns,
                            2
                        ),

                    'total_due' =>
                        round(
                            $totalDue,
                            2
                        ),

                    'overdue_count' =>
                        (int) (
                            clone $purchasesQuery
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
                ],

                'recentPurchases' =>
                    $recentPurchases,

                'recentReturns' =>
                    $recentReturns,
            ]
        );
    }

    public function edit(Supplier $supplier)
    {
        return Inertia::render('Suppliers/Form', [
            'supplier' => $supplier,
            'pageTitle' => 'تعديل المورد',
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();

        $supplier->update([
            'name' => trim($validated['name']),
            'company_name' => $validated['company_name'] ?? null,
            'phone' => $validated['phone'],
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('suppliers.index')
            ->with('success', 'تم تحديث المورد بنجاح');
    }

   public function destroy(Supplier $supplier)
{
    if ($supplier->purchaseInvoices()->count() > 0) {
        return redirect()->route('suppliers.index')
            ->with('error', 'لا يمكن حذف هذا المورد لأنه مرتبط بفواتير شراء.');
    }

    $supplier->delete();

    return redirect()->route('suppliers.index')
        ->with('success', 'تم حذف المورد بنجاح');
}

    public function toggleStatus(Supplier $supplier)
    {
        $supplier->update([
            'is_active' => !$supplier->is_active,
        ]);

        $status = $supplier->is_active ? 'مفعل' : 'غير مفعل';
        return redirect()->route('suppliers.index')
            ->with('success', "تم {$status} المورد بنجاح");
    }
}
