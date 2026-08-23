<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(
        Request $request
    ): Response {
        $query = Product::query()
            ->with([
                'category',
                'stocks.warehouse',
            ])
            ->search(
                $request->input('search')
            )
            ->category(
                $request->input('category_id')
            )
            ->when(
                $request->has('is_active'),
                fn($query) =>
                $query->where(
                    'is_active',
                    $request->boolean(
                        'is_active'
                    )
                )
            );

        $products = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $products
            ->getCollection()
            ->transform(
                function (
                    Product $product
                ): Product {
                    $product->setAttribute(
                        'total_stock',
                        $product->total_stock
                    );

                    $product->setAttribute(
                        'stock_status',
                        $product->stock_status
                    );

                    $product->setAttribute(
                        'profit',
                        $product->profit
                    );

                    $product->setAttribute(
                        'profit_margin',
                        $product->profit_margin
                    );

                    return $product;
                }
            );

        $allProducts =
            Product::query()
            ->with('stocks')
            ->get();

        $stats = [
            'total' =>
            $allProducts->count(),

            'active' =>
            $allProducts
                ->where(
                    'is_active',
                    true
                )
                ->count(),

            'low_stock' =>
            $allProducts
                ->filter(
                    fn(Product $product): bool => (
                        $product
                            ->stock_status['color']
                        ?? null
                    ) === 'orange'
                )
                ->count(),

            'out_of_stock' =>
            $allProducts
                ->filter(
                    fn(Product $product): bool => (
                        $product
                            ->stock_status['color']
                        ?? null
                    ) === 'red'
                )
                ->count(),

            'inventory_value' =>
            (float) $allProducts
                ->sum(
                    fn(Product $product) =>
                    (float) $product
                        ->purchase_price
                        * $product
                        ->total_stock
                ),
        ];

        return Inertia::render(
            'Products/Index',
            [
                'products' =>
                $products,

                'stats' =>
                $stats,

                'filters' => [
                    'search' =>
                    $request->input(
                        'search'
                    ),

                    'category_id' =>
                    $request->input(
                        'category_id'
                    ),

                    'is_active' =>
                    $request->has(
                        'is_active'
                    )
                        ? $request
                        ->boolean(
                            'is_active'
                        )
                        : null,
                ],

                'categories' =>
                Category::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ]),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Products/Form',
            [
                'product' =>
                null,

                'categories' =>
                Category::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ]),

                /*
                 * الرصيد الافتتاحي - إن وجد - يرتبط بمخزن فعلي
                 * يختاره المستخدم، وليس بأول مخزن من النوع.
                 */
                'warehouses' =>
                $this->activeWarehouses(),

                'pageTitle' =>
                'إضافة منتج جديد',
            ]
        );
    }

    public function store(
        StoreProductRequest $request
    ) {
        try {
            $product =
                $this->productService
                ->create(
                    $request->validated(),
                    $request->file(
                        'image'
                    )
                );

            return redirect()
                ->route(
                    'products.show',
                    $product
                )
                ->with(
                    'success',
                    'تم إنشاء المنتج بنجاح. يمكنك الآن استخدامه في المشتريات والمبيعات والمخزون.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                        ?: 'تعذر إنشاء المنتج.'
                );
        }
    }

    public function show(
        Product $product
    ): Response {
        $product->load([
            'category',
            'stocks.warehouse',
            'movements' =>
            fn($query) =>
            $query
                ->orderByDesc(
                    'created_at'
                )
                ->limit(10),
        ]);

        $product->setAttribute(
            'total_stock',
            $product->total_stock
        );

        $product->setAttribute(
            'stock_status',
            $product->stock_status
        );

        $product->setAttribute(
            'profit',
            $product->profit
        );

        $product->setAttribute(
            'profit_margin',
            $product->profit_margin
        );

        return Inertia::render(
            'Products/Show',
            [
                'product' =>
                $product,

                'warehouses' =>
                $this->activeWarehouses(),
            ]
        );
    }

    public function edit(
        Product $product
    ): Response {
        $product->load([
            'stocks.warehouse',
        ]);

        return Inertia::render(
            'Products/Form',
            [
                'product' =>
                $product,

                'categories' =>
                Category::query()
                    ->active()
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                    ]),

                'warehouses' =>
                $this->activeWarehouses(),

                /*
                 * بعد أول حركة مخزون تصبح تكلفة الشراء
                 * ناتجة عن العمليات المالية/المخزنية ولا يجوز
                 * تعديلها يدوياً من بطاقة المنتج.
                 */
                'purchasePriceLocked' =>
                $product->movements()
                    ->exists(),

                'pageTitle' =>
                'تعديل المنتج',
            ]
        );
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ) {
        try {
            $this->productService
                ->update(
                    $product,
                    $request->validated(),
                    $request->file(
                        'image'
                    )
                );

            return redirect()
                ->route(
                    'products.show',
                    $product
                )
                ->with(
                    'success',
                    'تم تحديث المنتج بنجاح.'
                );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                        ?: 'تعذر تحديث المنتج.'
                );
        }
    }

    public function destroy(
        Product $product
    ) {
        $this->productService
            ->delete(
                $product
            );

        return redirect()
            ->route(
                'products.index'
            )
            ->with(
                'success',
                'تم حذف المنتج بنجاح.'
            );
    }

    public function toggleStatus(
        Product $product
    ) {
        $this->productService
            ->toggleStatus(
                $product
            );

        $product->refresh();

        return redirect()
            ->back()
            ->with(
                'success',
                $product->is_active
                    ? 'تم تفعيل المنتج.'
                    : 'تم إيقاف المنتج.'
            );
    }

    private function activeWarehouses()
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

                        'type_label' =>
                        $warehouse->type?->label()
                            ?? 'مخزن',

                        'is_active' =>
                        (bool) $warehouse
                            ->is_active,
                    ];
                }
            )
            ->values();
    }
}
