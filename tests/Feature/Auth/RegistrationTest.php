<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_tenant_and_admin_can_register(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Transportes Panamá',
            'slug' => 'transportes-panama',
            'name' => 'Carlos Administrador',
            'email' => 'admin@transportes.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Transportes Panamá',
            'slug' => 'transportes-panama',
            'status' => 'active',
        ]);

        $tenant = Tenant::query()->where('slug', 'transportes-panama')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'name' => 'Carlos Administrador',
            'email' => 'admin@transportes.com',
            'role' => User::ROLE_GESTOR,
            'status' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }
}
