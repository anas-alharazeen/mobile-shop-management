<?php

namespace Tests\Feature;

use App\Enums\WarehouseType;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $salesWarehouse;
    protected $maintenanceWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء مستخدم للمالك
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
    }

    /** @test */
    public function guest_cannot_view_products()
    {
        $response = $this->get(route('products.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_view_products()
    {
        $response = $this->actingAs($this->user)
            ->get(route('products.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_create_product()
    {
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'barcode' => '1234567890',
            'category_id' => $this->category->id,
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'purchase_price' => 3500.00,
            'selling_price' => 4500.00,
            'minimum_selling_price' => 4200.00,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
            'location' => 'الرف A-1',
            'description' => 'هاتف iPhone 15 Pro الجديد',
            'notes' => 'ملاحظات',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'barcode' => '1234567890',
        ]);

        // التحقق من إنشاء الأرصدة
        $product = Product::where('code', 'IP15P-001')->first();
        $this->assertEquals(10, $product->salesStock->quantity);
        $this->assertEquals(5, $product->maintenanceStock->quantity);

        // التحقق من إنشاء حركات المخزون
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 10,
            'type' => 'opening',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 5,
            'type' => 'opening',
        ]);
    }

    /** @test */
    public function product_code_must_be_unique()
    {
        // إنشاء منتج أول
        Product::create([
            'name' => 'iPhone 15',
            'code' => 'IP15-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3000,
            'selling_price' => 4000,
        ]);

        // محاولة إنشاء منتج بنفس الكود
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertSessionHasErrors('code');
    }

    /** @test */
    public function product_barcode_must_be_unique()
    {
        // إنشاء منتج أول
        Product::create([
            'name' => 'iPhone 15',
            'code' => 'IP15-001',
            'barcode' => '1234567890',
            'category_id' => $this->category->id,
            'purchase_price' => 3000,
            'selling_price' => 4000,
        ]);

        // محاولة إنشاء منتج بنفس الباركود
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15-002',
            'barcode' => '1234567890',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertSessionHasErrors('barcode');
    }

    /** @test */
    public function category_must_exist_and_be_active()
    {
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => 999, // فئة غير موجودة
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertSessionHasErrors('category_id');
    }

    /** @test */
    public function purchase_price_cannot_be_negative()
    {
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => -100,
            'selling_price' => 4500,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertSessionHasErrors('purchase_price');
    }

    /** @test */
    public function selling_price_cannot_be_negative()
    {
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => -100,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertSessionHasErrors('selling_price');
    }

    /** @test */
    public function minimum_selling_price_cannot_be_greater_than_selling_price()
    {
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'minimum_selling_price' => 5000,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertSessionHasErrors('minimum_selling_price');
    }

    /** @test */
    public function sales_stock_cannot_be_negative()
    {
        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'sales_stock' => -5,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);
        $response->assertSessionHasErrors('sales_stock');
    }

    /** @test */
    public function user_can_upload_product_image()
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('product.jpg');

        $data = [
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'sales_stock' => 10,
            'maintenance_stock' => 5,
            'low_stock_threshold' => 3,
            'image' => $image,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $data);

        $product = Product::where('code', 'IP15P-001')->first();
        $this->assertNotNull($product->image_path);

        Storage::disk('public')->assertExists($product->image_path);
    }

    /** @test */
    public function user_can_update_product()
    {
        $product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
        ]);

        $data = [
            'name' => 'iPhone 15 Pro Max',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3800,
            'selling_price' => 5000,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('products.update', $product), $data);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'iPhone 15 Pro Max',
            'purchase_price' => 3800,
            'selling_price' => 5000,
        ]);
    }

    /** @test */
    public function user_can_toggle_product_status()
    {
        $product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'is_active' => true,
        ]);

        // تعطيل
        $response = $this->actingAs($this->user)
            ->patch(route('products.toggle-status', $product));
        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);

        // تفعيل
        $response = $this->actingAs($this->user)
            ->patch(route('products.toggle-status', $product));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_soft_delete_product()
    {
        $product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('products.destroy', $product));
        $response->assertRedirect(route('products.index'));

        $this->assertSoftDeleted($product);
    }

    /** @test */
    public function cannot_delete_category_with_products()
    {
        // إنشاء فئة جديدة
        $category = Category::create([
            'name' => 'أجهزة لوحية',
            'type' => 'tablet',
            'is_active' => true,
        ]);

        // إنشاء منتج مرتبط بالفئة
        Product::create([
            'name' => 'iPad Pro',
            'code' => 'IPAD-001',
            'category_id' => $category->id,
            'purchase_price' => 4000,
            'selling_price' => 5000,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('error', 'لا يمكن حذف هذه الفئة لأنها مرتبطة بمنتجات موجودة.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function user_can_search_products_by_name()
    {
        Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
        ]);

        Product::create([
            'name' => 'Samsung Galaxy S24',
            'code' => 'SGS24-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3000,
            'selling_price' => 4000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('products.index', ['search' => 'iPhone']));

        $response->assertStatus(200);
        $response->assertSee('iPhone 15 Pro');
        $response->assertDontSee('Samsung Galaxy S24');
    }

    /** @test */
    public function user_can_filter_by_category()
    {
        $category2 = Category::create([
            'name' => 'أجهزة لوحية',
            'type' => 'tablet',
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
        ]);

        Product::create([
            'name' => 'iPad Pro',
            'code' => 'IPAD-001',
            'category_id' => $category2->id,
            'purchase_price' => 4000,
            'selling_price' => 5000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('products.index', ['category_id' => $this->category->id]));

        $response->assertStatus(200);
        $response->assertSee('iPhone 15 Pro');
        $response->assertDontSee('iPad Pro');
    }

    /** @test */
    public function product_has_correct_stock_calculation()
    {
        $product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
        ]);

        // إضافة رصيد في مخزون المبيعات
        $product->salesStock()->create([
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 10,
        ]);

        // إضافة رصيد في مخزون الصيانة
        $product->maintenanceStock()->create([
            'warehouse_id' => $this->maintenanceWarehouse->id,
            'quantity' => 5,
        ]);

        $this->assertEquals(15, $product->total_stock);
    }

    /** @test */
    public function product_has_correct_profit_calculation()
    {
        $product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
        ]);

        $this->assertEquals(1000, $product->profit);
        $this->assertEquals(28.57, $product->profit_margin);
    }

    /** @test */
    public function product_has_correct_stock_status()
    {
        $product = Product::create([
            'name' => 'iPhone 15 Pro',
            'code' => 'IP15P-001',
            'category_id' => $this->category->id,
            'purchase_price' => 3500,
            'selling_price' => 4500,
            'low_stock_threshold' => 5,
        ]);

        // بدون مخزون - نافد
        $status = $product->stock_status;
        $this->assertEquals('نافد', $status['label']);
        $this->assertEquals('red', $status['color']);

        // إضافة رصيد قليل - منخفض
        $product->salesStock()->create([
            'warehouse_id' => $this->salesWarehouse->id,
            'quantity' => 3,
        ]);
        $product->refresh();
        $status = $product->stock_status;
        $this->assertEquals('منخفض', $status['label']);
        $this->assertEquals('orange', $status['color']);

        // إضافة رصيد كافي - متوفر
        $product->salesStock()->update([
            'quantity' => 10,
        ]);
        $product->refresh();
        $status = $product->stock_status;
        $this->assertEquals('متوفر', $status['label']);
        $this->assertEquals('green', $status['color']);
    }
}
