<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function guest_cannot_view_suppliers()
    {
        $response = $this->get(route('suppliers.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_view_suppliers()
    {
        $response = $this->actingAs($this->user)
            ->get(route('suppliers.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_create_supplier()
    {
        $data = [
            'name' => 'محمد العلي',
            'company_name' => 'شركة العلي',
            'phone' => '0599123456',
            'whatsapp' => '0599123456',
            'email' => 'ali@example.com',
            'address' => 'رام الله',
            'notes' => 'مورد ممتاز',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), $data);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'محمد العلي',
            'phone' => '0599123456',
        ]);

        // التحقق من توليد الكود
        $supplier = Supplier::where('phone', '0599123456')->first();
        $this->assertNotNull($supplier->code);
        $this->assertStringStartsWith('SUP-', $supplier->code);
    }

    /** @test */
    public function supplier_code_is_generated_automatically()
    {
        $data = [
            'name' => 'أحمد سمير',
            'phone' => '0599234567',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), $data);

        $supplier = Supplier::where('phone', '0599234567')->first();
        $this->assertNotNull($supplier->code);
        $this->assertStringStartsWith('SUP-', $supplier->code);
    }

    /** @test */
    public function supplier_name_is_required()
    {
        $data = [
            'name' => '',
            'phone' => '0599123456',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), $data);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function supplier_phone_is_required()
    {
        $data = [
            'name' => 'محمد العلي',
            'phone' => '',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), $data);

        $response->assertSessionHasErrors('phone');
    }

    /** @test */
    public function supplier_phone_must_be_unique()
    {
        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد أول',
            'phone' => '0599123456',
        ]);

        $data = [
            'name' => 'مورد ثاني',
            'phone' => '0599123456',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), $data);

        $response->assertSessionHasErrors('phone');
    }

    /** @test */
    public function supplier_email_must_be_valid()
    {
        $data = [
            'name' => 'محمد العلي',
            'phone' => '0599123456',
            'email' => 'invalid-email',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function supplier_email_must_be_unique()
    {
        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد أول',
            'phone' => '0599123456',
            'email' => 'test@example.com',
        ]);

        $data = [
            'name' => 'مورد ثاني',
            'phone' => '0599234567',
            'email' => 'test@example.com',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('suppliers.store'), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function user_can_update_supplier()
    {
        $supplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد قديم',
            'phone' => '0599123456',
        ]);

        $data = [
            'name' => 'مورد محدث',
            'company_name' => 'شركة محدثة',
            'phone' => '0599123456',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('suppliers.update', $supplier), $data);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'مورد محدث',
            'company_name' => 'شركة محدثة',
        ]);
    }

    /** @test */
    public function user_can_toggle_supplier_status()
    {
        $supplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد نشط',
            'phone' => '0599123456',
            'is_active' => true,
        ]);

        // تعطيل
        $response = $this->actingAs($this->user)
            ->patch(route('suppliers.toggle-status', $supplier));

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);

        // تفعيل
        $response = $this->actingAs($this->user)
            ->patch(route('suppliers.toggle-status', $supplier));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_soft_delete_supplier()
    {
        $supplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد للحذف',
            'phone' => '0599123456',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('suppliers.destroy', $supplier));

        $response->assertRedirect(route('suppliers.index'));

        $this->assertSoftDeleted($supplier);
    }

    /** @test */
    public function user_can_search_suppliers_by_name()
    {
        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'محمد العلي',
            'phone' => '0599123456',
        ]);

        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'أحمد سمير',
            'phone' => '0599234567',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('suppliers.index', ['search' => 'محمد']));

        $response->assertStatus(200);
        $response->assertSee('محمد العلي');
        $response->assertDontSee('أحمد سمير');
    }

    /** @test */
    public function user_can_search_suppliers_by_phone()
    {
        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'محمد العلي',
            'phone' => '0599123456',
        ]);

        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'أحمد سمير',
            'phone' => '0599234567',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('suppliers.index', ['search' => '0599123456']));

        $response->assertStatus(200);
        $response->assertSee('محمد العلي');
        $response->assertDontSee('أحمد سمير');
    }

    /** @test */
    public function user_can_search_suppliers_by_code()
    {
        $supplier1 = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'محمد العلي',
            'phone' => '0599123456',
        ]);

        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'أحمد سمير',
            'phone' => '0599234567',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('suppliers.index', ['search' => $supplier1->code]));

        $response->assertStatus(200);
        $response->assertSee('محمد العلي');
        $response->assertDontSee('أحمد سمير');
    }

    /** @test */
    public function user_can_filter_suppliers_by_status()
    {
        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد نشط',
            'phone' => '0599123456',
            'is_active' => true,
        ]);

        Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد غير نشط',
            'phone' => '0599234567',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('suppliers.index', ['is_active' => true]));

        $response->assertStatus(200);
        $response->assertSee('مورد نشط');
        $response->assertDontSee('مورد غير نشط');
    }

    /** @test */
    public function user_can_view_supplier_details()
    {
        $supplier = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد للعرض',
            'phone' => '0599123456',
            'company_name' => 'شركة العرض',
            'email' => 'show@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('suppliers.show', $supplier));

        $response->assertStatus(200);
        $response->assertSee('مورد للعرض');
        $response->assertSee('شركة العرض');
        $response->assertSee('show@example.com');
    }

    /** @test */
    public function supplier_code_is_unique()
    {
        $supplier1 = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد أول',
            'phone' => '0599123456',
        ]);

        $supplier2 = Supplier::create([
            'code' => Supplier::generateCode(),
            'name' => 'مورد ثاني',
            'phone' => '0599234567',
        ]);

        $this->assertNotEquals($supplier1->code, $supplier2->code);
    }
}
