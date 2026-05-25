<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\ShipmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ShipmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ShipmentPolicy();
    }

    public function test_gestor_can_view_any(): void
    {
        $gestor = User::factory()->create([
            'role'   => User::ROLE_GESTOR,
            'status' => true,
        ]);

        $this->assertTrue($this->policy->viewAny($gestor));
    }

    public function test_messenger_cannot_view_any(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);

        $this->assertFalse($this->policy->viewAny($messenger));
    }

    public function test_gestor_can_view_same_tenant_shipment(): void
    {
        $tenant = Tenant::factory()->create();
        $gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'tenant_id' => $tenant->id,
            'status'    => true,
        ]);
        $shipment = Shipment::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($this->policy->view($gestor, $shipment));
    }

    public function test_gestor_cannot_view_cross_tenant_shipment(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'tenant_id' => $tenantA->id,
            'status'    => true,
        ]);
        $shipment = Shipment::factory()->create([
            'tenant_id' => $tenantB->id,
        ]);

        $this->assertFalse($this->policy->view($gestor, $shipment));
    }

    public function test_gestor_can_create(): void
    {
        $gestor = User::factory()->create([
            'role'   => User::ROLE_GESTOR,
            'status' => true,
        ]);

        $this->assertTrue($this->policy->create($gestor));
    }

    public function test_messenger_cannot_create(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);

        $this->assertFalse($this->policy->create($messenger));
    }

    public function test_gestor_can_update_same_tenant_shipment(): void
    {
        $tenant = Tenant::factory()->create();
        $gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'tenant_id' => $tenant->id,
            'status'    => true,
        ]);
        $shipment = Shipment::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($this->policy->update($gestor, $shipment));
    }

    public function test_gestor_cannot_update_cross_tenant_shipment(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'tenant_id' => $tenantA->id,
            'status'    => true,
        ]);
        $shipment = Shipment::factory()->create([
            'tenant_id' => $tenantB->id,
        ]);

        $this->assertFalse($this->policy->update($gestor, $shipment));
    }

    public function test_gestor_can_delete_same_tenant_shipment(): void
    {
        $tenant = Tenant::factory()->create();
        $gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'tenant_id' => $tenant->id,
            'status'    => true,
        ]);
        $shipment = Shipment::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($this->policy->delete($gestor, $shipment));
    }

    public function test_gestor_cannot_delete_cross_tenant_shipment(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'tenant_id' => $tenantA->id,
            'status'    => true,
        ]);
        $shipment = Shipment::factory()->create([
            'tenant_id' => $tenantB->id,
        ]);

        $this->assertFalse($this->policy->delete($gestor, $shipment));
    }
}
