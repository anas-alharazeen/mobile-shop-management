<?php

namespace Tests\Feature;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_view_categories()
    {
        $response = $this->get(route('categories.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_categories()
    {
        $response = $this->actingAs($this->user)
            ->get(route('categories.index'));
        $response->assertStatus(200);
    }

    public function test_user_can_create_category()
    {
        $data = [
            'name' => 'هواتف ذكية',
            'type' => CategoryType::PHONE->value,
            'description' => 'فئة الهواتف الذكية',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('categories.store'), $data);
        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'هواتف ذكية',
            'type' => CategoryType::PHONE->value,
        ]);
    }

    public function test_category_name_is_required()
    {
        $data = [
            'name' => '',
            'type' => CategoryType::PHONE->value,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('categories.store'), $data);
        $response->assertSessionHasErrors('name');
    }

    public function test_category_name_must_be_unique()
    {
        Category::create([
            'name' => 'هواتف',
            'type' => CategoryType::PHONE->value,
        ]);

        $data = [
            'name' => 'هواتف',
            'type' => CategoryType::PHONE->value,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('categories.store'), $data);
        $response->assertSessionHasErrors('name');
    }

    public function test_category_type_must_be_valid()
    {
        $data = [
            'name' => 'فئة غير صحيحة',
            'type' => 'invalid_type',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('categories.store'), $data);
        $response->assertSessionHasErrors('type');
    }

    public function test_user_can_update_category()
    {
        $category = Category::create([
            'name' => 'هواتف قديمة',
            'type' => CategoryType::PHONE->value,
        ]);

        $data = [
            'name' => 'هواتف حديثة',
            'type' => CategoryType::PHONE->value,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('categories.update', $category), $data);
        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'هواتف حديثة',
        ]);
    }

    public function test_user_can_toggle_category_status()
    {
        $category = Category::create([
            'name' => 'هواتف',
            'type' => CategoryType::PHONE->value,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('categories.toggle-status', $category));
        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_soft_delete_category()
    {
        $category = Category::create([
            'name' => 'هواتف',
            'type' => CategoryType::PHONE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('categories.destroy', $category));
        $response->assertRedirect(route('categories.index'));

        $this->assertSoftDeleted($category);
    }

    public function test_user_can_search_categories()
    {
        Category::create(['name' => 'هواتف ذكية', 'type' => CategoryType::PHONE->value]);
        Category::create(['name' => 'أجهزة لوحية', 'type' => CategoryType::TABLET->value]);

        $response = $this->actingAs($this->user)
            ->get(route('categories.index', ['search' => 'هواتف']));

        $response->assertStatus(200);
        $response->assertSee('هواتف ذكية');
        $response->assertDontSee('أجهزة لوحية');
    }

    public function test_user_can_filter_by_type()
    {
        Category::create(['name' => 'هواتف', 'type' => CategoryType::PHONE->value]);
        Category::create(['name' => 'أجهزة لوحية', 'type' => CategoryType::TABLET->value]);

        $response = $this->actingAs($this->user)
            ->get(route('categories.index', ['type' => CategoryType::PHONE->value]));

        $response->assertStatus(200);
        $response->assertSee('هواتف');
        $response->assertDontSee('أجهزة لوحية');
    }
}
