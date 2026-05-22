<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
    }

    public function test_login_screen_can_be_rendered_with_role_query(): void
    {
        $response = $this->get('/login?role=owner');

        $response->assertStatus(200);
        $response->assertSee('Login Owner');
    }

    public function test_invalid_login_role_query_redirects_to_role_selection(): void
    {
        $response = $this->get('/login?role=invalid');

        $response->assertRedirect('/login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'teknisi',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/transactions/create');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'role' => 'teknisi',
        ]);

        $this->assertGuest();
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_teknisi_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_authenticated_admin_user_can_access_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_logout_redirects_to_non_cached_login_screen(): void
    {
        $user = User::factory()->owner()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $response->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $this->assertGuest();

        $this->get('/login?role=owner')->assertStatus(200);
    }
}
