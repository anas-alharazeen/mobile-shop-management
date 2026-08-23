<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\RepairOrder;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\PurchaseReportService;
use App\Services\Reports\RepairReportService;
use App\Services\Reports\ReportPdfService;
use App\Services\Reports\ReportPresentationService;
use App\Services\Reports\ReturnReportService;
use App\Services\Reports\SalesReportService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        /*
         * =========================
         * المؤشرات العامة
         * =========================
         */

        $totalSales = (float) SalesInvoice::query()
            ->where('status', 'approved')
            ->sum('total_amount');

        $totalPurchases = (float) PurchaseInvoice::query()
            ->where('status', 'approved')
            ->sum('total_amount');

        $inventoryValue = (float) DB::table('product_stocks')
            ->join(
                'products',
                'product_stocks.product_id',
                '=',
                'products.id'
            )
            /*
             * Product يستخدم SoftDeletes.
             * بطاقة التقارير يجب أن تطابق صفحة وتقرير المخزون،
             * لذلك لا نحسب منتجاً محذوفاً منطقياً.
             */
            ->whereNull(
                'products.deleted_at'
            )
            ->selectRaw(
                'COALESCE(SUM(product_stocks.quantity * products.purchase_price), 0) AS total'
            )
            ->value('total');

        /*
         * =========================
         * الديون
         * =========================
         */

        $salesDebts = (float) SalesInvoice::query()
            ->where('status', 'approved')
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');

        $repairDebts = (float) RepairOrder::query()
            ->where('status', '!=', 'cancelled')
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');

        $supplierDebts = (float) PurchaseInvoice::query()
            ->where('status', 'approved')
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');

        /*
         * =========================
         * الصيانة
         * =========================
         */

        $activeRepairs = RepairOrder::query()
            ->whereIn('status', [
                'received',
                'in_progress',
                'ready',
            ])
            ->count();

        $readyRepairs = RepairOrder::query()
            ->where('status', 'ready')
            ->count();

        /*
         * =========================
         * مبيعات اليوم
         * =========================
         *
         * نعتمد sale_date كالتاريخ الأساسي.
         * وإذا كانت فاتورة قديمة لا تحتوي sale_date
         * نستخدم created_at كـ fallback.
         */

        $todaySalesQuery = SalesInvoice::query()
            ->where('status', 'approved')
            ->where(function ($query): void {
                $query
                    ->whereDate(
                        'sale_date',
                        today()
                    )
                    ->orWhere(function ($fallback): void {
                        $fallback
                            ->whereNull('sale_date')
                            ->whereDate(
                                'created_at',
                                today()
                            );
                    });
            });

        $todaySales = (float) (clone $todaySalesQuery)
            ->sum('total_amount');

        $todayProfit = (float) (clone $todaySalesQuery)
            ->sum('gross_profit');

        $todayInvoicesCount = (clone $todaySalesQuery)
            ->count();

        /*
         * =========================
         * أعداد الكيانات
         * =========================
         */

        $productsCount = Product::query()
            ->active()
            ->count();

        $customersCount = Customer::query()
            ->active()
            ->count();

        $suppliersCount = Supplier::query()
            ->active()
            ->count();

        $salesCustomersCount = SalesInvoice::query()
            ->where('status', 'approved')
            ->whereNotNull('customer_id')
            ->distinct()
            ->count('customer_id');

        /*
         * =========================
         * الحسابات المالية
         * =========================
         */

        $financialAccountsCount = FinancialAccount::query()
            ->active()
            ->count();

        $cashBalance = (float) FinancialAccount::query()
            ->active()
            ->where('type', 'cash')
            ->sum('current_balance');

        /*
         * =========================
         * المرتجعات
         * =========================
         */

        $salesReturnsTotal = (float) SalesReturn::query()
            ->where('status', 'approved')
            ->sum('total_amount');

        $purchaseReturnsTotal = (float) PurchaseReturn::query()
            ->where('status', 'approved')
            ->sum('total_amount');

        /*
         * مهم:
         * Index.vue الجديد يستقبل prop اسمه summary
         * وليس overview.
         */
        return Inertia::render('Reports/Index', [
            'summary' => [
                'total_sales' => round(
                    $totalSales,
                    2
                ),

                'total_purchases' => round(
                    $totalPurchases,
                    2
                ),

                'inventory_value' => round(
                    $inventoryValue,
                    2
                ),

                'customer_debts' => round(
                    $salesDebts + $repairDebts,
                    2
                ),

                'supplier_debts' => round(
                    $supplierDebts,
                    2
                ),

                'active_repairs' =>
                $activeRepairs,

                'ready_repairs' =>
                $readyRepairs,

                'today_sales' => round(
                    $todaySales,
                    2
                ),

                'today_profit' => round(
                    $todayProfit,
                    2
                ),

                'today_invoices_count' =>
                $todayInvoicesCount,

                'products_count' =>
                $productsCount,

                'customers_count' =>
                $customersCount,

                'suppliers_count' =>
                $suppliersCount,

                'sales_customers_count' =>
                $salesCustomersCount,

                'financial_accounts_count' =>
                $financialAccountsCount,

                'cash_balance' => round(
                    $cashBalance,
                    2
                ),

                'sales_returns_total' => round(
                    $salesReturnsTotal,
                    2
                ),

                'purchase_returns_total' => round(
                    $purchaseReturnsTotal,
                    2
                ),
            ],
        ]);
    }

    public function sales(
        Request $request,
        SalesReportService $service
    ): Response {
        $filters = $this->filters($request);

        return Inertia::render('Reports/Sales', [
            'data' => $service->getReport($filters),
            'customers' => Customer::active()
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
            'categories' => Category::active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'filters' => $filters,
        ]);
    }

    public function purchases(
        Request $request,
        PurchaseReportService $service
    ): Response {
        $filters = $this->filters($request);

        return Inertia::render('Reports/Purchases', [
            'data' => $service->getReport($filters),
            'suppliers' => Supplier::active()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'company_name',
                ]),
            'categories' => Category::active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'filters' => $filters,
        ]);
    }

    public function inventory(
        Request $request,
        InventoryReportService $service
    ): Response {
        $filters = $this->filters($request);

        return Inertia::render('Reports/Inventory', [
            'data' => $service->getReport($filters),
            'categories' => Category::active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'filters' => $filters,
        ]);
    }

    public function repairs(
        Request $request,
        RepairReportService $service
    ): Response {
        $filters = $this->filters($request);

        return Inertia::render('Reports/Repairs', [
            'data' => $service->getReport($filters),
            'filters' => $filters,
        ]);
    }

    public function finance(
        Request $request,
        FinanceReportService $service
    ): Response {
        $filters = $this->filters($request);

        return Inertia::render('Reports/Finance', [
            'data' => $service->getReport($filters),
            'filters' => $filters,
        ]);
    }

    public function returns(
        Request $request,
        ReturnReportService $service
    ): Response {
        $filters = $this->filters($request);

        return Inertia::render('Reports/Returns', [
            'data' => $service->getReport($filters),
            'filters' => $filters,
        ]);
    }

    public function exportPdf(
        Request $request,
        ReportPresentationService $presenter,
        ReportPdfService $pdfService,
    ): SymfonyResponse {
        $type = $this->reportType($request);
        $filters = $this->filters($request);
        $data = $this->service($type)->getReport($filters);

        $presentation = $presenter->present(
            $type,
            $data,
            $filters
        );

        $orientation = in_array(
            $type,
            ['inventory', 'sales', 'purchases'],
            true
        ) ? 'L' : 'P';

        return $pdfService->download(
            $presentation,
            $this->fileName(
                $presentation['title'],
                'pdf'
            ),
            $orientation,
        );
    }

    public function exportExcel(
        Request $request
    ): BinaryFileResponse {
        $type = $this->reportType($request);
        $filters = $this->filters($request);
        $data = $this->service($type)
            ->getReport($filters);

        $title = app(
            ReportPresentationService::class
        )->title($type);

        return Excel::download(
            new ReportExport(
                $type,
                $data,
                $filters
            ),
            $this->fileName(
                $title,
                'xlsx'
            )
        );
    }

    public function print(
        Request $request,
        SettingsService $settings
    ): Response {
        $type = $this->reportType($request);
        $filters = $this->filters($request);

        $data = $this->service($type)
            ->getReport($filters);

        $title = app(
            ReportPresentationService::class
        )->title($type);

        return Inertia::render('Reports/Print', [
            'type' => $type,
            'data' => $data,
            'filters' => $filters,
            'title' => $title,
            'store' => $settings->getStoreSettings(),
            'generatedAt' => now()->toIso8601String(),
            'reportCode' => strtoupper(
                substr($type, 0, 3)
            ) . '-' . now()->format('Ymd-His'),
        ]);
    }

    private function filters(
        Request $request
    ): array {
        return $request->validate([
            'period' => [
                'nullable',
                'string',
                'in:today,yesterday,last_7_days,this_week,this_month,last_month,this_year,custom',
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],
            'product_id' => [
                'nullable',
                'integer',
                'exists:products,id',
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'warehouse_id' => [
                'nullable',
                'integer',
                'exists:warehouses,id',
            ],
            'status' => [
                'nullable',
                'string',
                'max:50',
            ],
            'payment_status' => [
                'nullable',
                'string',
                'max:50',
            ],
            'payment_method' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);
    }

    private function reportType(
        Request $request
    ): string {
        $type = (string) (
            $request->route('type')
            ?? $request->input('type')
        );

        abort_unless(
            in_array(
                $type,
                [
                    'sales',
                    'purchases',
                    'inventory',
                    'repairs',
                    'finance',
                    'returns',
                ],
                true
            ),
            404
        );

        return $type;
    }

    private function service(
        string $type
    ): object {
        return match ($type) {
            'sales' => app(SalesReportService::class),
            'purchases' => app(PurchaseReportService::class),
            'inventory' => app(InventoryReportService::class),
            'repairs' => app(RepairReportService::class),
            'finance' => app(FinanceReportService::class),
            'returns' => app(ReturnReportService::class),
        };
    }

    private function fileName(
        string $title,
        string $extension
    ): string {
        $safeTitle = preg_replace(
            '/[^\p{Arabic}\p{L}\p{N}_-]+/u',
            '_',
            $title
        ) ?: 'report';

        return $safeTitle
            . '_'
            . now()->format('Y-m-d')
            . '.'
            . $extension;
    }
}
