<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function guest_cannot_view_customers()
    {
        $response = $this->get(route('customers.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_view_customers()
    {
        $response = $this->actingAs($this->user)
            ->get(route('customers.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_create_customer_with_only_name()
    {
        $data = [
            'name' => 'أحمد محمد',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'أحمد محمد',
        ]);

        $customer = Customer::where('name', 'أحمد محمد')->first();
        $this->assertNotNull($customer->code);
        $this->assertStringStartsWith('CUS-', $customer->code);
    }

    /** @test */
    public function user_can_create_customer_with_full_details()
    {
        $data = [
            'name' => 'سارة أحمد',
            'phone' => '0599123456',
            'whatsapp' => '0599123456',
            'email' => 'sara@example.com',
            'address' => 'رام الله',
            'notes' => 'عميل ممتاز',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'سارة أحمد',
            'phone' => '0599123456',
            'email' => 'sara@example.com',
        ]);
    }

    /** @test */
    public function customer_code_is_generated_automatically()
    {
        $data = [
            'name' => 'محمد خليل',
            'is_active' => true,
        ];

        $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $customer = Customer::where('name', 'محمد خليل')->first();
        $this->assertNotNull($customer->code);
        $this->assertStringStartsWith('CUS-', $customer->code);
    }

    /** @test */
    public function customer_name_is_required()
    {
        $data = [
            'name' => '',
            'phone' => '0599123456',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function customer_phone_can_be_null()
    {
        $data = [
            'name' => 'عميل بدون هاتف',
            'phone' => null,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'عميل بدون هاتف',
            'phone' => null,
        ]);
    }

    /** @test */
    public function customer_phone_must_be_unique()
    {
        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل أول',
            'phone' => '0599123456',
        ]);

        $data = [
            'name' => 'عميل ثاني',
            'phone' => '0599123456',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $response->assertSessionHasErrors('phone');
    }

    /** @test */
    public function customer_email_must_be_valid()
    {
        $data = [
            'name' => 'أحمد محمد',
            'email' => 'invalid-email',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function customer_email_must_be_unique()
    {
        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل أول',
            'phone' => '0599123456',
            'email' => 'test@example.com',
        ]);

        $data = [
            'name' => 'عميل ثاني',
            'phone' => '0599234567',
            'email' => 'test@example.com',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('customers.store'), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function user_can_update_customer()
    {
        $customer = Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل قديم',
            'phone' => '0599123456',
        ]);

        $data = [
            'name' => 'عميل محدث',
            'phone' => '0599123456',
            'address' => 'عنوان جديد',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('customers.update', $customer), $data);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'عميل محدث',
            'address' => 'عنوان جديد',
        ]);
    }

    /** @test */
    public function user_can_toggle_customer_status()
    {
        $customer = Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل نشط',
            'phone' => '0599123456',
            'is_active' => true,
        ]);

        // تعطيل
        $response = $this->actingAs($this->user)
            ->patch(route('customers.toggle-status', $customer));

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => false,
        ]);

        // تفعيل
        $response = $this->actingAs($this->user)
            ->patch(route('customers.toggle-status', $customer));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_soft_delete_customer()
    {
        $customer = Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل للحذف',
            'phone' => '0599123456',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted($customer);
    }

    /** @test */
    public function user_can_search_customers_by_name()
    {
        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'أحمد محمد',
            'phone' => '0599123456',
        ]);

        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'سارة أحمد',
            'phone' => '0599234567',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customers.index', ['search' => 'أحمد']));

        $response->assertStatus(200);
        $response->assertSee('أحمد محمد');
        $response->assertDontSee('سارة أحمد');
    }

    /** @test */
    public function user_can_search_customers_by_phone()
    {
        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'أحمد محمد',
            'phone' => '0599123456',
        ]);

        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'سارة أحمد',
            'phone' => '0599234567',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customers.index', ['search' => '0599123456']));

        $response->assertStatus(200);
        $response->assertSee('أحمد محمد');
        $response->assertDontSee('سارة أحمد');
    }

    /** @test */
    public function user_can_search_customers_by_code()
    {
        $customer1 = Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'أحمد محمد',
            'phone' => '0599123456',
        ]);

        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'سارة أحمد',
            'phone' => '0599234567',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customers.index', ['search' => $customer1->code]));

        $response->assertStatus(200);
        $response->assertSee('أحمد محمد');
        $response->assertDontSee('سارة أحمد');
    }

    /** @test */
    public function user_can_filter_customers_by_status()
    {
        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل نشط',
            'phone' => '0599123456',
            'is_active' => true,
        ]);

        Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل غير نشط',
            'phone' => '0599234567',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customers.index', ['is_active' => true]));

        $response->assertStatus(200);
        $response->assertSee('عميل نشط');
        $response->assertDontSee('عميل غير نشط');
    }

    /** @test */
    public function user_can_view_customer_details()
    {
        $customer = Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل للعرض',
            'phone' => '0599123456',
            'email' => 'show@example.com',
            'address' => 'عنوان العرض',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('customers.show', $customer));

        $response->assertStatus(200);
        $response->assertSee('عميل للعرض');
        $response->assertSee('show@example.com');
        $response->assertSee('عنوان العرض');
    }

    /** @test */
    public function customer_code_is_unique()
    {
        $customer1 = Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل أول',
            'phone' => '0599123456',
        ]);

        $customer2 = Customer::create([
            'code' => Customer::generateCode(),
            'name' => 'عميل ثاني',
            'phone' => '0599234567',
        ]);

        $this->assertNotEquals($customer1->code, $customer2->code);
    }
}
