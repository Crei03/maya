<?php

declare(strict_types=1);

namespace Tests\Feature\Management;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ManagementDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['multi-tenant.enabled' => false]);

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => true,
        ]);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/management/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_gestor_cannot_access_super_admin_dashboard(): void
    {
        $gestor = User::factory()->create([
            'role' => User::ROLE_GESTOR,
            'status' => true,
        ]);

        $response = $this->actingAs($gestor)->get('/management/dashboard');

        $response->assertForbidden();
    }

    public function test_super_admin_can_view_dashboard_with_kpis_and_charts(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Demo Courier',
                'status' => 'active',
            ]
        );

        $response = $this->actingAs($this->superAdmin)->get('/management/dashboard');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/Dashboard')
                ->has('stats')
                ->has('stats.total_tenants')
                ->has('stats.active_tenants')
                ->has('stats.total_shipments')
                ->has('stats.month_shipments')
                ->has('stats.potential_revenue')
                ->has('stats.charts')
                ->has('stats.charts.growth')
                ->has('stats.charts.shipments')
                ->has('stats.charts.status_distribution')
                ->has('recentAudits')
            );
    }
}
