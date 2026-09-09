<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $salesWarehouse;
    protected $maintenanceWarehouse;
    protected $inventoryService;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء مستخدم
        $this->user = User::factory()->create();

        // إنشاء فئة
        $this->category = Category::create([
            'name' => 'هواتف ذكية',
            'type' => 'phone',
            'is_active' => true,
        ]);

        // إنشاء المستودعات
        $this->salesWarehouse = Warehouse::create([
            'name' => 'مخزون المبيعات',
            'type' => WarehouseType::SALES,
            'is_active' => true,
        ]);

        $this->maintenanceWarehouse = Warehouse::create([
            'name' => 'مخزون الصيانة',
            'type' => WarehouseType::MAINTENANCE,
            'is_active' => true,
        ]);

        // إنشاء خدمة المخزون
        $this->inventoryService = app(InventoryService::class);

        // إنشاء منتج
        $this->product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);
    }

    // ========== اختبارات المصادقة ==========

    /** @test */
    public function guest_cannot_access_inventory_pages()
    {
        $response = $this->get(route('inventory.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('inventory.movements'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_view_inventory_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('inventory.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_user_can_view_movements_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('inventory.movements'));
        $response->assertStatus(200);
    }

    // ========== اختبارات الإضافة اليدوية ==========

    /** @test */
    public function user_can_add_stock_manually()
    {
        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 10,
            'reason' => 'إضافة أولية للمخزون',
            'notes' => 'اختبار',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.add-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // التحقق من تحديث الرصيد
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 10,
        ]);

        // التحقق من تسجيل الحركة
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'type' => StockMovementType::MANUAL_ADDITION->value,
            'quantity' => 10,
            'quantity_before' => 0,
            'quantity_after' => 10,
        ]);
    }

    /** @test */
    public function add_stock_requires_valid_data()
    {
        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 0, // غير مسموح
            'reason' => '', // مطلوب
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.add-stock'), $data);

        $response->assertSessionHasErrors(['quantity', 'reason']);
    }

    /** @test */
    public function add_stock_requires_quantity_greater_than_zero()
    {
        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => -5,
            'reason' => 'سبب',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.add-stock'), $data);

        $response->assertSessionHasErrors(['quantity']);
    }

    // ========== اختبارات الخصم اليدوي ==========

    /** @test */
    public function user_can_deduct_stock_manually()
    {
        // إضافة رصيد أولاً
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            20,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 5,
            'type' => 'manual_deduction',
            'reason' => 'استخدام للاختبار',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.deduct-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // التحقق من تحديث الرصيد
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 15,
        ]);

        // التحقق من تسجيل الحركة
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'type' => StockMovementType::MANUAL_DEDUCTION->value,
            'quantity' => 5,
            'quantity_before' => 20,
            'quantity_after' => 15,
        ]);
    }

    /** @test */
    public function cannot_deduct_more_than_available_stock()
    {
        // إضافة رصيد قليل
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            3,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 10, // أكثر من المتوفر
            'type' => 'manual_deduction',
            'reason' => 'خصم',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.deduct-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'الكمية المطلوبة (10) أكبر من المتوفرة (3)');
    }

    /** @test */
    public function can_mark_stock_as_damaged()
    {
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 3,
            'type' => 'damaged',
            'reason' => 'تلف في الشحن',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.deduct-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'type' => StockMovementType::DAMAGED->value,
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 7,
        ]);
    }

    /** @test */
    public function can_mark_stock_as_lost()
    {
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 2,
            'type' => 'lost',
            'reason' => 'فقدان أثناء النقل',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.deduct-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'type' => StockMovementType::LOST->value,
            'quantity' => 2,
        ]);
    }

    // ========== اختبارات النقل بين المخازن ==========

    /** @test */
    public function user_can_transfer_stock_between_warehouses()
    {
        // إضافة رصيد في المخزن المصدر
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            20,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->salesWarehouse->id,
            'destination_warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 7,
            'reason' => 'نقل للصيانة',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.transfer-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // التحقق من خصم المصدر
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 13,
        ]);

        // التحقق من إضافة المستلم
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 7,
        ]);

        // التحقق من حركة الخصم (transfer_out)
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'type' => StockMovementType::TRANSFER_OUT->value,
            'quantity' => 7,
            'quantity_before' => 20,
            'quantity_after' => 13,
        ]);

        // التحقق من حركة الإضافة (transfer_in)
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->maintenanceWarehouse->id,
            'type' => StockMovementType::TRANSFER_IN->value,
            'quantity' => 7,
            'quantity_before' => 0,
            'quantity_after' => 7,
        ]);
    }

    /** @test */
    public function cannot_transfer_to_same_warehouse()
    {
        $data = [
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->salesWarehouse->id,
            'destination_warehouse_id' => $this->salesWarehouse->id, // نفس المخزن
            'quantity' => 5,
            'reason' => 'نقل',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.transfer-stock'), $data);

        $response->assertSessionHasErrors(['destination_warehouse_id']);
    }

    /** @test */
    public function cannot_transfer_more_than_available()
    {
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            5,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->salesWarehouse->id,
            'destination_warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 10, // أكثر من المتوفر
            'reason' => 'نقل',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.transfer-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function transfer_creates_two_movements_with_same_reference()
    {
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->salesWarehouse->id,
            'destination_warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 4,
            'reason' => 'نقل',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.transfer-stock'), $data);

        $response->assertRedirect();

        // التحقق من وجود حركتين بنفس المرجع
        $movements = \App\Models\StockMovement::where('reference_type', 'transfer')
            ->where('product_id', $this->product->id)
            ->get();

        $this->assertCount(2, $movements);
        $this->assertEquals($movements[0]->reference_id, $movements[1]->reference_id);
    }

    // ========== اختبارات البحث والفلاتر ==========

    /** @test */
    public function user_can_search_products_in_inventory()
    {
        $product2 = Product::create([
            'name' => 'Samsung Galaxy S24',
            'code' => 'SGS24-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3000,
            'selling_price' => 4000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('inventory.index', ['search' => 'iPhone']));

        $response->assertStatus(200);
        $response->assertSee('iPhone 15 Pro');
        $response->assertDontSee('Samsung Galaxy S24');
    }

    /** @test */
    public function user_can_filter_inventory_by_category()
    {
        $category2 = Category::create([
            'name' => 'أجهزة لوحية',
            'type' => 'tablet',
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'iPad Pro',
            'code' => 'IPAD-001',
            'category_id' => $category2->id,
            'purchase_price' => 4000,
            'selling_price' => 5000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('inventory.index', ['category_id' => $this->category->id]));

        $response->assertStatus(200);
        $response->assertSee('iPhone 15 Pro');
        $response->assertDontSee('iPad Pro');
    }

    /** @test */
    public function user_can_filter_inventory_by_warehouse()
    {
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد'
        );

        $this->inventoryService->addStock(
            $this->product,
            $this->maintenanceWarehouse,
            5,
            'رصيد'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.index', ['warehouse_id' => $this->salesWarehouse->id]));

        $response->assertStatus(200);
        // التحقق من أن المنتج يظهر مع الرصيد الصحيح
        $response->assertSee('iPhone 15 Pro');
    }

    // ========== اختبارات سجل الحركات ==========

    /** @test */
    public function movements_page_shows_stock_movements()
    {
        // إنشاء حركة
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد أولي'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.movements'));

        $response->assertStatus(200);
        $response->assertSee('رصيد أولي');
        $response->assertSee('10');
    }

    /** @test */
    public function user_can_filter_movements_by_product()
    {
        $product2 = Product::create([
            'name' => 'Samsung S24',
            'code' => 'SGS24-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3000,
            'selling_price' => 4000,
        ]);

        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد iPhone'
        );

        $this->inventoryService->addStock(
            $product2,
            $this->salesWarehouse,
            5,
            'رصيد Samsung'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.movements', ['product_id' => $this->product->id]));

        $response->assertStatus(200);
        $response->assertSee('رصيد iPhone');
        $response->assertDontSee('رصيد Samsung');
    }

    /** @test */
    public function user_can_filter_movements_by_type()
    {
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد أولي'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.movements', ['type' => 'opening']));

        $response->assertStatus(200);
        $response->assertSee('رصيد افتتاحي');
    }

    // ========== اختبارات المعاملات (Transactions) ==========

    /** @test */
    public function inventory_operations_are_atomic()
    {
        // محاولة إضافة كمية لمنتج غير موجود
        $data = [
            'product_id' => 9999, // غير موجود
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 10,
            'reason' => 'اختبار',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.add-stock'), $data);

        $response->assertSessionHasErrors(['product_id']);

        // التأكد من عدم وجود حركة
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => 9999,
        ]);
    }

    /** @test */
    public function product_stock_cannot_be_negative()
    {
        // محاولة خصم أكثر من المتوفر
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            5,
            'رصيد أولي'
        );

        $data = [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 10,
            'type' => 'manual_deduction',
            'reason' => 'خصم كبير',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.deduct-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // التأكد من بقاء الرصيد 5
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 5,
        ]);
    }

    /** @test */
    public function transfer_fails_if_source_has_insufficient_stock()
    {
        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            3,
            'رصيد قليل'
        );

        $data = [
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->salesWarehouse->id,
            'destination_warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 5,
            'reason' => 'نقل',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('inventory.transfer-stock'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // التأكد من عدم تغيير الرصيد
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseMissing('product_stocks', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 5,
        ]);
    }

    // ========== اختبارات فصل مخزون المبيعات والصيانة ==========

    /** @test */
    public function inventory_can_filter_products_by_sales_stock_only()
    {
        $maintenanceOnly = Product::create([
            'name' => 'Maintenance Part',
            'code' => 'MAIN-ONLY-001',
            'category_id' => $this->category->id,
            'purchase_price' => 120,
            'selling_price' => 180,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            8,
            'رصيد مبيعات'
        );

        $this->inventoryService->addStock(
            $maintenanceOnly,
            $this->maintenanceWarehouse,
            4,
            'رصيد صيانة'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.index', [
                'warehouse_type' => 'sales',
            ]));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) =>
            $page
                ->component('Inventory/Index')
                ->where('filters.warehouse_type', 'sales')
                ->has('products.data', 1)
                ->where('products.data.0.id', $this->product->id)
                ->where('products.data.0.sales_stock', 8)
                ->where('products.data.0.maintenance_stock', 0)
                ->where('products.data.0.display_stock', 8)
                ->where('warehouseTypeSummary.sales.pieces', 8)
                ->where('warehouseTypeSummary.sales.sku_count', 1)
        );
    }

    /** @test */
    public function inventory_can_filter_products_by_maintenance_stock_only()
    {
        $salesOnly = Product::create([
            'name' => 'Sales Only Product',
            'code' => 'SALES-ONLY-001',
            'category_id' => $this->category->id,
            'purchase_price' => 500,
            'selling_price' => 700,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $this->inventoryService->addStock(
            $salesOnly,
            $this->salesWarehouse,
            7,
            'رصيد مبيعات'
        );

        $this->inventoryService->addStock(
            $this->product,
            $this->maintenanceWarehouse,
            3,
            'رصيد صيانة'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.index', [
                'warehouse_type' => 'maintenance',
            ]));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) =>
            $page
                ->component('Inventory/Index')
                ->where('filters.warehouse_type', 'maintenance')
                ->has('products.data', 1)
                ->where('products.data.0.id', $this->product->id)
                ->where('products.data.0.sales_stock', 0)
                ->where('products.data.0.maintenance_stock', 3)
                ->where('products.data.0.display_stock', 3)
                ->where('warehouseTypeSummary.maintenance.pieces', 3)
                ->where('warehouseTypeSummary.maintenance.sku_count', 1)
        );
    }

    /** @test */
    public function exact_warehouse_filter_does_not_return_products_without_stock_in_that_warehouse()
    {
        $maintenanceOnly = Product::create([
            'name' => 'Repair Battery',
            'code' => 'REP-BAT-001',
            'category_id' => $this->category->id,
            'purchase_price' => 90,
            'selling_price' => 130,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            5,
            'رصيد مبيعات'
        );

        $this->inventoryService->addStock(
            $maintenanceOnly,
            $this->maintenanceWarehouse,
            6,
            'رصيد صيانة'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.index', [
                'warehouse_id' => $this->salesWarehouse->id,
                'warehouse_type' => 'sales',
            ]));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) =>
            $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $this->product->id)
                ->where('products.data.0.filtered_warehouse_stock', 5)
        );
    }

    // ========== اختبارات الإحصائيات ==========

    /** @test */
    public function inventory_page_displays_correct_statistics()
    {
        // إضافة منتجين برصيد
        $product2 = Product::create([
            'name' => 'Samsung S24',
            'code' => 'SGS24-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3000,
            'selling_price' => 4000,
            'low_stock_threshold' => 3,
            'is_active' => true,
        ]);

        $this->inventoryService->addStock(
            $this->product,
            $this->salesWarehouse,
            10,
            'رصيد'
        );

        $this->inventoryService->addStock(
            $product2,
            $this->maintenanceWarehouse,
            2,
            'رصيد'
        );

        $response = $this->actingAs($this->user)
            ->get(route('inventory.index'));

        $response->assertStatus(200);

        // التحقق من الإحصائيات
        $response->assertSee('مخزون المبيعات');
        $response->assertSee('مخزون الصيانة');
        $response->assertSee('إجمالي القطع');
        $response->assertSee('منخفضة المخزون');
        $response->assertSee('نافدة');
    }
}
