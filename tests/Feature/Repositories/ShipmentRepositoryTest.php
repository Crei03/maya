<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\Shipment;
use App\Models\Tenant;
use App\Repositories\ShipmentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ShipmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ShipmentRepository();
    }

    public function test_find_by_id_for_tenant_returns_null_for_other_tenant_shipment(): void
    {
        // Arrange: two tenants with one shipment each
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $shipmentInA = Shipment::factory()->create(['tenant_id' => $tenantA->id]);
        Shipment::factory()->create(['tenant_id' => $tenantB->id]);

        // Make tenant A current
        $tenantA->makeCurrent();

        // Act: try to find tenant A's shipment
        $found = $this->repository->findByIdForTenant($shipmentInA->id);

        // Assert: shipment in tenant A is found
        $this->assertNotNull($found);
        $this->assertSame($shipmentInA->id, $found->id);

        // Act: try to find a shipment that belongs to tenant B (cross-tenant)
        $crossTenant = $this->repository->findByIdForTenant(
            // Create a shipment in tenant B and try to find it from tenant A context
            Shipment::factory()->create(['tenant_id' => $tenantB->id])->id
        );

        // Assert: cross-tenant returns null (indistinguishable from not-found)
        $this->assertNull($crossTenant);
    }

    public function test_all_for_tenant_only_returns_current_tenant_shipments(): void
    {
        // Arrange: two tenants with shipments
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Shipment::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
        Shipment::factory()->count(2)->create(['tenant_id' => $tenantB->id]);

        // Make tenant A current
        $tenantA->makeCurrent();

        // Act
        $shipments = $this->repository->allForTenant();

        // Assert: only 3 shipments (tenant A)
        $this->assertCount(3, $shipments);

        // All returned shipments belong to tenant A
        foreach ($shipments as $shipment) {
            $this->assertSame($tenantA->id, $shipment->tenant_id);
        }
    }

    public function test_find_by_id_for_tenant_returns_null_for_nonexistent_id(): void
    {
        // Arrange: create a tenant
        $tenant = Tenant::factory()->create();
        $tenant->makeCurrent();

        // Act: try to find a non-existent shipment
        $found = $this->repository->findByIdForTenant('non-existent-id-999');

        // Assert: null returned (not-found and cross-tenant are indistinguishable)
        $this->assertNull($found);
    }

    public function test_paginate_for_tenant_returns_paginated_results(): void
    {
        // Arrange: one tenant with 5 shipments
        $tenant = Tenant::factory()->create();
        Shipment::factory()->count(5)->create(['tenant_id' => $tenant->id]);
        $tenant->makeCurrent();

        // Act: paginate with perPage=3
        $paginator = $this->repository->paginateForTenant(3, []);

        // Assert: paginator has correct structure
        $this->assertSame(5, $paginator->total());
        $this->assertCount(3, $paginator->items());
        $this->assertSame(2, $paginator->lastPage());
    }

    public function test_paginate_for_tenant_with_status_filter(): void
    {
        // Arrange: tenant with mixed status shipments
        $tenant = Tenant::factory()->create();
        Shipment::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'status'    => Shipment::STATUS_PENDING,
        ]);
        Shipment::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'status'    => Shipment::STATUS_DELIVERED,
        ]);
        $tenant->makeCurrent();

        // Act: paginate with status filter
        $paginator = $this->repository->paginateForTenant(10, [
            'status' => Shipment::STATUS_PENDING,
        ]);

        // Assert: only pending shipments returned
        $this->assertCount(2, $paginator->items());
        foreach ($paginator->items() as $shipment) {
            $this->assertSame(Shipment::STATUS_PENDING, $shipment->status);
        }
    }
}
