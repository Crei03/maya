<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['multi-tenant.enabled' => false]);

        // Create or find the demo tenant and make it current
        // so TenantScope filters correctly and HasTenant assigns tenant_id.
        $this->tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo'],
            [
                'id'     => (string) Str::uuid(),
                'name'   => 'Demo',
                'status' => 'active',
            ],
        );
        $this->tenant->makeCurrent();

        $this->gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'status'    => true,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ============================================================================
    // List endpoint
    // ============================================================================

    public function test_list_shipments_returns_paginated_json(): void
    {
        Shipment::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']],
            ])
            ->assertJsonCount(3, 'data.data');
    }

    public function test_list_shipments_only_returns_current_tenant(): void
    {
        Shipment::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        Shipment::factory()->count(3)->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list'));

        $response->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.meta.total', 2);
    }

    public function test_list_shipments_empty_state(): void
    {
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.data')
            ->assertJsonPath('data.meta.total', 0);
    }

    // ============================================================================
    // Create shipment
    // ============================================================================

    public function test_create_shipment_with_valid_data(): void
    {
        $payload = [
            'recipient_name'      => 'Juan Pérez',
            'destination_address' => 'Calle 50, Edificio Plaza, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => 5.5,
            'recipient_phone'     => '+507 6000-1234',
            'origin_address'      => 'Bodega Central',
            'content_description' => 'Documentos importantes',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.shipments.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Paquete creado exitosamente')
            ->assertJsonPath('data.recipient_name', 'Juan Pérez')
            ->assertJsonPath('data.package_type', 'caja');

        $this->assertDatabaseHas('shipments', [
            'recipient_name' => 'Juan Pérez',
            'tenant_id'      => $this->tenant->id,
        ]);
    }

    public function test_create_shipment_auto_generates_tracking_number(): void
    {
        $payload = [
            'recipient_name'      => 'Auto Track',
            'destination_address' => 'Destino Test',
            'package_type'        => 'sobre',
            'weight_kg'           => 1.0,
            'recipient_phone'     => '+507 6000-0001',
            'origin_address'      => 'Origen Test',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.shipments.store'), $payload);

        $response->assertCreated();

        $tracking = $response->json('data.tracking_number');
        $this->assertNotEmpty($tracking);
        $this->assertStringStartsWith('MAYA', $tracking);
        $this->assertGreaterThanOrEqual(14, strlen($tracking));
    }

    public function test_create_shipment_auto_generates_uuid(): void
    {
        $payload = [
            'recipient_name'      => 'UUID Test',
            'destination_address' => 'Destino UUID',
            'package_type'        => 'caja',
            'weight_kg'           => 2.0,
            'recipient_phone'     => '+507 6000-0002',
            'origin_address'      => 'Origen UUID',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.shipments.store'), $payload);

        $response->assertCreated();

        $id = $response->json('data.id');
        $this->assertTrue(Str::isUuid($id));
    }

    public function test_create_shipment_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.shipments.store'), [
            'recipient_name'      => 'Test',
            'destination_address' => 'Test',
            'package_type'        => 'caja',
            'weight_kg'           => 1.0,
            'recipient_phone'     => '+507 6000-0003',
            'origin_address'      => 'Origen Auth',
        ]);

        $response->assertUnauthorized();
    }

    public function test_create_shipment_validates_required_fields(): void
    {
        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.shipments.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_name', 'destination_address', 'package_type', 'weight_kg']);
    }

    // ============================================================================
    // Show shipment
    // ============================================================================

    public function test_show_shipment_returns_shipment_with_relations(): void
    {
        $warehouse = Warehouse::factory()->create();
        $shipment = Shipment::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.show', $shipment->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $shipment->id)
            ->assertJsonPath('data.recipient_name', $shipment->recipient_name);
    }

    public function test_show_shipment_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.show', 'non-existent-id'));

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Paquete no encontrado');
    }

    public function test_show_shipment_requires_authentication(): void
    {
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson(route('admin.shipments.show', $shipment->id));

        $response->assertUnauthorized();
    }

    // ============================================================================
    // Update shipment
    // ============================================================================

    public function test_update_shipment_partial_update(): void
    {
        $shipment = Shipment::factory()->create([
            'tenant_id'      => $this->tenant->id,
            'recipient_name' => 'Original Name',
            'status'         => Shipment::STATUS_PENDING,
            'weight_kg'      => 5.0,
        ]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.shipments.update', $shipment->id), [
                'status'    => Shipment::STATUS_IN_WAREHOUSE,
                'weight_kg' => 8.5,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Shipment::STATUS_IN_WAREHOUSE)
            ->assertJsonPath('data.recipient_name', 'Original Name');
    }

    public function test_update_shipment_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.shipments.update', 'non-existent-id'), [
                'status' => Shipment::STATUS_IN_WAREHOUSE,
            ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Paquete no encontrado');
    }

    // ============================================================================
    // Delete shipment
    // ============================================================================

    public function test_delete_shipment_without_relations(): void
    {
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);
        $shipmentId = $shipment->id;

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.shipments.destroy', $shipmentId));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Paquete eliminado exitosamente');

        $this->assertDatabaseMissing('shipments', ['id' => $shipmentId]);
    }

    public function test_delete_shipment_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.shipments.destroy', 'non-existent-id'));

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Paquete no encontrado');
    }

    // ============================================================================
    // Cross-tenant isolation
    // ============================================================================

    public function test_cannot_show_shipment_of_different_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        $otherShipment = Shipment::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.show', $otherShipment->id));

        $response->assertNotFound();
    }

    public function test_cannot_update_shipment_of_different_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        $otherShipment = Shipment::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.shipments.update', $otherShipment->id), [
                'status' => Shipment::STATUS_IN_WAREHOUSE,
            ]);

        $response->assertNotFound();
    }

    public function test_cannot_delete_shipment_of_different_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        $otherShipment = Shipment::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.shipments.destroy', $otherShipment->id));

        $response->assertNotFound();
    }

    public function test_list_does_not_include_other_tenant_shipments(): void
    {
        Shipment::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        Shipment::factory()->count(3)->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list'));

        $response->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.meta.total', 2);
    }

    // ============================================================================
    // Filtros
    // ============================================================================

    public function test_filter_shipments_by_status(): void
    {
        Shipment::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'status' => Shipment::STATUS_PENDING]);
        Shipment::factory()->count(3)->create(['tenant_id' => $this->tenant->id, 'status' => Shipment::STATUS_DELIVERED]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list', ['status' => Shipment::STATUS_PENDING]));

        $response->assertOk()
            ->assertJsonCount(2, 'data.data');
    }

    public function test_search_shipments_by_tracking_number(): void
    {
        Shipment::factory()->create(['tenant_id' => $this->tenant->id, 'tracking_number' => 'MAYA-SEARCH-001']);
        Shipment::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list', ['search' => 'SEARCH-001']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.tracking_number', 'MAYA-SEARCH-001');
    }

    public function test_search_shipments_by_recipient_name(): void
    {
        Shipment::factory()->create(['tenant_id' => $this->tenant->id, 'recipient_name' => 'María García']);
        Shipment::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list', ['search' => 'García']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.recipient_name', 'María García');
    }

    // ============================================================================
    // Auth guards
    // ============================================================================

    public function test_list_shipments_requires_authentication(): void
    {
        $response = $this->getJson(route('admin.shipments.list'));
        $response->assertUnauthorized();
    }

    public function test_update_shipment_requires_authentication(): void
    {
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->patchJson(route('admin.shipments.update', $shipment->id), [
            'status' => Shipment::STATUS_IN_WAREHOUSE,
        ]);

        $response->assertUnauthorized();
    }

    public function test_delete_shipment_requires_authentication(): void
    {
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson(route('admin.shipments.destroy', $shipment->id));

        $response->assertUnauthorized();
    }
}
