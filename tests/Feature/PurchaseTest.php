<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $supplier;
    protected $product;
    protected $warehouse;
    protected $category;
    protected $purchaseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->category = Category::create([
            'name' => 'هواتف ذكية',
            'type' => 'phone',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد للاختبار',
            'phone' => '0599123456',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'مخزون المبيعات',
            'type' => 'sales',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        $this->purchaseService = app(PurchaseInvoiceService::class);
    }

    /** @test */
    public function guest_cannot_access_purchase_pages()
    {
        $response = $this->get(route('purchases.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('purchases.create'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_view_purchases()
    {
        $response = $this->actingAs($this->user)
            ->get(route('purchases.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_create_draft_invoice()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                    'line_discount' => 0,
                ],
            ],
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'additional_expenses' => 0,
            'payment_amount' => 0,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.store'), $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('purchase_invoices', [
            'supplier_id' => $this->supplier->id,
            'status' => PurchaseInvoiceStatus::DRAFT->value,
        ]);

        $invoice = PurchaseInvoice::where('supplier_id', $this->supplier->id)->first();
        $this->assertDatabaseHas('purchase_invoice_items', [
            'purchase_invoice_id' => $invoice->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_purchase_price' => 3500,
        ]);
    }

    /** @test */
    public function invoice_requires_at_least_one_item()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.store'), $data);

        $response->assertSessionHasErrors('items');
    }

    /** @test */
    public function invoice_requires_active_supplier()
    {
        $inactiveSupplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد غير نشط',
            'phone' => '0599234567',
            'is_active' => false,
        ]);

        $data = [
            'supplier_id' => $inactiveSupplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.store'), $data);

        $response->assertSessionHasErrors('supplier_id');
    }

    /** @test */
    public function invoice_requires_active_product()
    {
        $inactiveProduct = Product::create([
            'name' => 'منتج غير نشط',
            'code' => 'INACTIVE-001',
            'category_id' => $this->category->id,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'is_active' => false,
        ]);

        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $inactiveProduct->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.store'), $data);

        $response->assertSessionHasErrors('items.*.product_id');
    }

    /** @test */
    public function quantity_must_be_positive()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 0,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.store'), $data);

        $response->assertSessionHasErrors('items.*.quantity');
    }

    /** @test */
    public function price_cannot_be_negative()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => -100,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.store'), $data);

        $response->assertSessionHasErrors('items.*.unit_purchase_price');
    }

    /** @test */
    public function invoice_calculates_totals_correctly()
    {
        // منتج ثاني
        $product2 = Product::create([
            'name' => 'Samsung S24',
            'code' => 'SGS24-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3000,
            'selling_price' => 4000,
            'is_active' => true,
        ]);

        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                    'line_discount' => 500,
                ],
                [
                    'product_id' => $product2->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 3,
                    'unit_purchase_price' => 3000,
                    'line_discount' => 0,
                ],
            ],
            'discount_amount' => 200,
            'shipping_cost' => 100,
            'additional_expenses' => 50,
        ];

        $invoice = $this->purchaseService->create($data);

        // المجموع: (5*3500 - 500) + (3*3000) = 17000 + 9000 = 26000
        // خصم 200 = 25800
        // شحن 100 = 25900
        // مصاريف 50 = 25950

        $this->assertEquals(26000, $invoice->subtotal);
        $this->assertEquals(200, $invoice->discount_amount);
        $this->assertEquals(100, $invoice->shipping_cost);
        $this->assertEquals(50, $invoice->additional_expenses);
        $this->assertEquals(25950, $invoice->total_amount);
    }

    /** @test */
    public function user_can_approve_invoice()
    {
        // إنشاء مسودة
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                    'line_discount' => 0,
                ],
            ],
        ];

        $invoice = $this->purchaseService->create($data);

        // اعتماد
        $response = $this->actingAs($this->user)
            ->post(route('purchases.approve', $invoice));

        $response->assertRedirect();

        // التحقق من تحديث الحالة
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoice->id,
            'status' => PurchaseInvoiceStatus::APPROVED->value,
        ]);

        // التحقق من تحديث المخزون
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ]);

        // التحقق من حركة المخزون
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'purchase',
            'quantity' => 5,
        ]);

        // التحقق من تحديث متوسط التكلفة
        $this->product->refresh();
        $this->assertEquals(3500, $this->product->purchase_price);
    }

    /** @test */
    public function cannot_approve_invoice_without_items()
    {
        $invoice = PurchaseInvoice::create([
            'invoice_number' => PurchaseInvoice::generateNumber(),
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now(),
            'status' => PurchaseInvoiceStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('purchases.approve', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function cannot_approve_invoice_twice()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $invoice = $this->purchaseService->create($data);
        $this->purchaseService->approve($invoice);

        $response = $this->actingAs($this->user)
            ->post(route('purchases.approve', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function user_can_add_payment_to_invoice()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
            'payment_amount' => 0,
        ];

        $invoice = $this->purchaseService->create($data);
        $this->purchaseService->approve($invoice);

        $paymentData = [
            'amount' => 10000,
            'payment_method' => 'cash',
            'paid_at' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.add-payment', $invoice), $paymentData);

        $response->assertRedirect();

        $this->assertDatabaseHas('purchase_payments', [
            'purchase_invoice_id' => $invoice->id,
            'amount' => 10000,
        ]);

        $invoice->refresh();
        $this->assertEquals(10000, $invoice->paid_amount);
        $this->assertEquals(7500, $invoice->remaining_amount);
        $this->assertEquals(PaymentStatus::PARTIALLY_PAID->value, $invoice->payment_status->value);
    }

    /** @test */
    public function cannot_pay_more_than_remaining()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
            'payment_amount' => 0,
        ];

        $invoice = $this->purchaseService->create($data);
        $this->purchaseService->approve($invoice);

        $paymentData = [
            'amount' => 20000, // أكثر من الإجمالي
            'payment_method' => 'cash',
            'paid_at' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.add-payment', $invoice), $paymentData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function user_can_edit_draft_invoice()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $invoice = $this->purchaseService->create($data);

        $updateData = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 10,
                    'unit_purchase_price' => 3400,
                ],
            ],
            'discount_amount' => 100,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('purchases.update', $invoice), $updateData);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(10, $invoice->items->first()->quantity);
        $this->assertEquals(3400, $invoice->items->first()->unit_purchase_price);
        $this->assertEquals(100, $invoice->discount_amount);
    }

    /** @test */
    public function cannot_edit_approved_invoice()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $invoice = $this->purchaseService->create($data);
        $this->purchaseService->approve($invoice);

        $response = $this->actingAs($this->user)
            ->get(route('purchases.edit', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function user_can_cancel_approved_invoice_if_stock_available()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $invoice = $this->purchaseService->create($data);
        $this->purchaseService->approve($invoice);

        // التحقق من وجود المخزون
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ]);

        $cancelData = [
            'reason' => 'إلغاء للاختبار',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.cancel', $invoice), $cancelData);

        $response->assertRedirect();

        // التحقق من تحديث الحالة
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoice->id,
            'status' => PurchaseInvoiceStatus::CANCELLED->value,
            'cancellation_reason' => 'إلغاء للاختبار',
        ]);

        // التحقق من عكس المخزون
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
        ]);

        // التحقق من حركة الإلغاء
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => 'purchase_cancellation',
            'quantity' => 5,
        ]);
    }

    /** @test */
    public function cannot_cancel_approved_invoice_if_stock_used()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $invoice = $this->purchaseService->create($data);
        $this->purchaseService->approve($invoice);

        // خصم جزء من المخزون (محاكاة بيع أو استخدام)
        $stock = \App\Models\ProductStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $stock->update(['quantity' => 2]);

        $cancelData = [
            'reason' => 'إلغاء',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchases.cancel', $invoice), $cancelData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function invoice_generates_unique_number()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $invoice1 = $this->purchaseService->create($data);
        $invoice2 = $this->purchaseService->create($data);

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        $this->assertStringStartsWith('PUR-' . now()->format('Y') . '-', $invoice1->invoice_number);
    }

    /** @test */
    public function user_can_search_invoices()
    {
        $invoice = PurchaseInvoice::create([
            'invoice_number' => PurchaseInvoice::generateNumber(),
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now(),
            'status' => PurchaseInvoiceStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('purchases.index', ['search' => $invoice->invoice_number]));

        $response->assertStatus(200);
        $response->assertSee($invoice->invoice_number);
    }

    /** @test */
    public function user_can_filter_invoices_by_status()
    {
        $invoice1 = PurchaseInvoice::create([
            'invoice_number' => PurchaseInvoice::generateNumber(),
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now(),
            'status' => PurchaseInvoiceStatus::DRAFT,
        ]);

        $invoice2 = PurchaseInvoice::create([
            'invoice_number' => PurchaseInvoice::generateNumber(),
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now(),
            'status' => PurchaseInvoiceStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('purchases.index', ['status' => 'draft']));

        $response->assertStatus(200);
        $response->assertSee($invoice1->invoice_number);
        $response->assertDontSee($invoice2->invoice_number);
    }

    /** @test */
    public function cannot_delete_supplier_with_purchase_invoices()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 3500,
                ],
            ],
        ];

        $this->purchaseService->create($data);

        $response = $this->actingAs($this->user)
            ->delete(route('suppliers.destroy', $this->supplier));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'لا يمكن حذف هذا المورد لأنه مرتبط بفواتير شراء.');
    }

    /** @test */
    public function invoice_calculates_weighted_average_cost()
    {
        // إضافة رصيد أولي
        \App\Models\ProductStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);

        // تحديث متوسط التكلفة الأولي
        $this->product->update(['purchase_price' => 3000]);

        $data = [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_purchase_price' => 4000,
                ],
            ],
        ];

        $invoice = $this->purchaseService->create($data);
        $this->purchaseService->approve($invoice);

        // المتوسط المرجح: (10*3000 + 5*4000) / 15 = 50000 / 15 = 3333.33
        $this->product->refresh();
        $this->assertEquals(3333.33, round($this->product->purchase_price, 2));
    }
}
