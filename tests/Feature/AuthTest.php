<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_login()
    {
        $user = User::factory()->create([
            'email' => 'owner@fanana-phone.local',
            'password' => bcrypt('Fanana@123456'),
        ]);

        $response = $this->post('/login', [
            'email' => 'owner@fanana-phone.local',
            'password' => 'Fanana@123456',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected()
    {
        User::factory()->create([
            'email' => 'owner@fanana-phone.local',
            'password' => bcrypt('Fanana@123456'),
        ]);

        $response = $this->post('/login', [
            'email' => 'owner@fanana-phone.local',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_register_page_is_disabled()
    {
        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    public function test_owner_can_logout()
    {
        $user = User::factory()->create([
            'email' => 'owner@fanana-phone.local',
            'password' => bcrypt('Fanana@123456'),
        ]);

        $this->actingAs($user);
        $response = $this->post('/logout');
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_owner_can_access_dashboard_after_login()
    {
        $user = User::factory()->create([
            'email' => 'owner@fanana-phone.local',
            'password' => bcrypt('Fanana@123456'),
        ]);

        $this->actingAs($user);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    }
}
