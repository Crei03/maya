<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenant;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentCrudTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $gestorA;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip the EnsureTenant middleware so our manual makeCurrent()
        // persists through the HTTP request lifecycle.
        $this->withoutMiddleware(EnsureTenant::class);

        // Create two tenants
        $this->tenantA = Tenant::factory()->create();
        $this->tenantB = Tenant::factory()->create();

        // Make tenant A current for test queries
        $this->tenantA->makeCurrent();

        $this->gestorA = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'status'    => true,
            'tenant_id' => $this->tenantA->id,
        ]);
    }

    // ==========================================================================
    // Cross-Tenant Isolation
    // ==========================================================================

    public function test_gestor_cannot_list_other_tenant_shipments(): void
    {
        // Create 2 shipments in tenant A (current)
        Shipment::factory()->count(2)->create(['tenant_id' => $this->tenantA->id]);

        // Create 3 shipments in tenant B (other tenant)
        Shipment::factory()->count(3)->create(['tenant_id' => $this->tenantB->id]);

        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.list'));

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Should only see tenant A shipments (2), not B (3)
        $data = $response->json('data.data');
        $this->assertCount(2, $data);

        // Verify all returned shipments belong to tenant A
        foreach ($data as $item) {
            $this->assertSame($this->tenantA->id, $item['tenant_id']);
        }
    }

    public function test_gestor_cannot_show_other_tenant_shipment(): void
    {
        // Create shipment in tenant B
        $shipmentB = Shipment::factory()->create(['tenant_id' => $this->tenantB->id]);

        // Gestor A tries to view it → 404 (no info leak)
        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.show', $shipmentB->id));

        $response->assertNotFound();
    }

    public function test_gestor_cannot_update_other_tenant_shipment(): void
    {
        $shipmentB = Shipment::factory()->create(['tenant_id' => $this->tenantB->id]);

        $response = $this->actingAs($this->gestorA)
            ->patchJson(route('admin.shipments.update', $shipmentB->id), [
                'status' => Shipment::STATUS_IN_WAREHOUSE,
            ]);

        $response->assertNotFound();
    }

    public function test_gestor_cannot_delete_other_tenant_shipment(): void
    {
        $shipmentB = Shipment::factory()->create(['tenant_id' => $this->tenantB->id]);

        $response = $this->actingAs($this->gestorA)
            ->deleteJson(route('admin.shipments.destroy', $shipmentB->id));

        $response->assertNotFound();
    }

    // ==========================================================================
    // Scoped Queries (ShipmentRepository)
    // ==========================================================================

    public function test_gestor_only_sees_own_tenant_shipments_in_list(): void
    {
        // Create 2 shipments in tenant A
        $shipmentA1 = Shipment::factory()->create([
            'tenant_id'      => $this->tenantA->id,
            'recipient_name' => 'Cliente A1',
        ]);
        $shipmentA2 = Shipment::factory()->create([
            'tenant_id'      => $this->tenantA->id,
            'recipient_name' => 'Cliente A2',
        ]);

        // Create 3 shipments in tenant B
        Shipment::factory()->count(3)->create(['tenant_id' => $this->tenantB->id]);

        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.list'));

        $response->assertOk();

        $data = $response->json('data.data');
        $this->assertCount(2, $data);

        // Verify the exact shipment IDs are the tenant A ones
        $ids = array_column($data, 'id');
        $this->assertContains($shipmentA1->id, $ids);
        $this->assertContains($shipmentA2->id, $ids);
    }

    // ==========================================================================
    // Filter Edge Cases
    // ==========================================================================

    public function test_filter_by_warehouse(): void
    {
        $warehouse1 = Warehouse::factory()->create(['tenant_id' => $this->tenantA->id]);
        $warehouse2 = Warehouse::factory()->create(['tenant_id' => $this->tenantA->id]);

        // 3 shipments in warehouse 1
        Shipment::factory()->count(3)->create([
            'tenant_id'    => $this->tenantA->id,
            'warehouse_id' => $warehouse1->id,
        ]);

        // 2 shipments in warehouse 2
        Shipment::factory()->count(2)->create([
            'tenant_id'    => $this->tenantA->id,
            'warehouse_id' => $warehouse2->id,
        ]);

        // Filter by warehouse 1
        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.list', ['warehouse_id' => $warehouse1->id]));

        $response->assertOk()
            ->assertJsonCount(3, 'data.data');

        $data = $response->json('data.data');
        foreach ($data as $item) {
            $this->assertSame($warehouse1->id, $item['warehouse_id']);
        }
    }

    public function test_filter_by_date_range(): void
    {
        // Create shipment with specific created_at dates
        $recent = Shipment::factory()->create([
            'tenant_id'  => $this->tenantA->id,
            'created_at' => '2026-03-15 10:00:00',
        ]);
        Shipment::factory()->create([
            'tenant_id'  => $this->tenantA->id,
            'created_at' => '2026-03-20 10:00:00',
        ]);
        $old = Shipment::factory()->create([
            'tenant_id'  => $this->tenantA->id,
            'created_at' => '2026-02-01 10:00:00',
        ]);

        // Filter date range: 2026-03-10 to 2026-03-25
        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.list', [
                'date_from' => '2026-03-10',
                'date_to'   => '2026-03-25',
            ]));

        $response->assertOk()
            ->assertJsonCount(2, 'data.data');

        $ids = array_column($response->json('data.data'), 'id');
        $this->assertContains($recent->id, $ids);
        $this->assertNotContains($old->id, $ids);
    }

    public function test_filter_by_package_type(): void
    {
        Shipment::factory()->count(2)->create([
            'tenant_id'    => $this->tenantA->id,
            'package_type' => 'sobre',
        ]);
        Shipment::factory()->count(3)->create([
            'tenant_id'    => $this->tenantA->id,
            'package_type' => 'caja',
        ]);

        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.list', ['package_type' => 'sobre']));

        $response->assertOk()
            ->assertJsonCount(2, 'data.data');

        $data = $response->json('data.data');
        foreach ($data as $item) {
            $this->assertSame('sobre', $item['package_type']);
        }
    }

    public function test_search_by_tracking_number(): void
    {
        $shipment = Shipment::factory()->create([
            'tenant_id'       => $this->tenantA->id,
            'tracking_number' => 'MAYA-SEARCH-001',
        ]);
        Shipment::factory()->count(3)->create([
            'tenant_id'       => $this->tenantA->id,
        ]);

        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.list', ['search' => 'SEARCH-001']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $shipment->id)
            ->assertJsonPath('data.data.0.tracking_number', 'MAYA-SEARCH-001');
    }

    public function test_search_by_recipient_name(): void
    {
        $shipment = Shipment::factory()->create([
            'tenant_id'      => $this->tenantA->id,
            'recipient_name' => 'María García López',
        ]);
        Shipment::factory()->count(3)->create([
            'tenant_id' => $this->tenantA->id,
        ]);

        $response = $this->actingAs($this->gestorA)
            ->getJson(route('admin.shipments.list', ['search' => 'García']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $shipment->id);
    }

    // ==========================================================================
    // Data Integrity
    // ==========================================================================

    public function test_created_shipment_has_tracking_number_auto_generated(): void
    {
        $payload = [
            'recipient_name'      => 'Auto Track Test',
            'recipient_phone'     => '6000-0001',
            'origin_address'      => 'Origen Test',
            'destination_address' => 'Destino Test',
            'package_type'        => 'caja',
            'weight_kg'           => 1.0,
        ];

        $response = $this->actingAs($this->gestorA)
            ->postJson(route('admin.shipments.store'), $payload);

        $response->assertCreated();

        $trackingNumber = $response->json('data.tracking_number');
        $this->assertNotEmpty($trackingNumber);
        $this->assertStringStartsWith('MAYA', $trackingNumber);
        // Must be at least 14 chars (MAYA + 10 random)
        $this->assertGreaterThanOrEqual(14, strlen($trackingNumber));
    }

    public function test_created_shipment_has_uuid(): void
    {
        $payload = [
            'recipient_name'      => 'UUID Test',
            'recipient_phone'     => '6000-0002',
            'origin_address'      => 'Origen UUID',
            'destination_address' => 'Destino UUID',
            'package_type'        => 'sobre',
            'weight_kg'           => 0.5,
        ];

        $response = $this->actingAs($this->gestorA)
            ->postJson(route('admin.shipments.store'), $payload);

        $response->assertCreated();

        $id = $response->json('data.id');
        // UUID v4 pattern
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id
        );
    }

    public function test_created_shipment_belongs_to_correct_tenant(): void
    {
        $payload = [
            'recipient_name'      => 'Tenant Test',
            'recipient_phone'     => '6000-0003',
            'origin_address'      => 'Origen Tenant',
            'destination_address' => 'Destino Tenant',
            'package_type'        => 'palet',
            'weight_kg'           => 10.0,
        ];

        $response = $this->actingAs($this->gestorA)
            ->postJson(route('admin.shipments.store'), $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('shipments', [
            'recipient_name' => 'Tenant Test',
            'tenant_id'      => $this->tenantA->id,
        ]);
    }

    // ==========================================================================
    // Partial Update Edge Cases
    // ==========================================================================

    public function test_partial_update_only_changes_sent_fields(): void
    {
        $shipment = Shipment::factory()->create([
            'tenant_id'      => $this->tenantA->id,
            'recipient_name' => 'Nombre Original',
            'recipient_phone' => '6000-9999',
            'status'         => Shipment::STATUS_PENDING,
            'weight_kg'      => 5.0,
        ]);

        // Send only status and weight_kg — name and phone should be preserved
        $response = $this->actingAs($this->gestorA)
            ->patchJson(route('admin.shipments.update', $shipment->id), [
                'status'    => Shipment::STATUS_IN_TRANSIT,
                'weight_kg' => 8.5,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', Shipment::STATUS_IN_TRANSIT)
            ->assertJsonPath('data.recipient_name', 'Nombre Original')
            ->assertJsonPath('data.recipient_phone', '6000-9999');

        // weight_kg is cast as decimal:2, so 8.5 is serialized as "8.50"
        $this->assertSame('8.50', $response->json('data.weight_kg'));
    }

    public function test_update_preserves_unchanged_fields(): void
    {
        $shipment = Shipment::factory()->create([
            'tenant_id'           => $this->tenantA->id,
            'recipient_name'      => 'Preserve Test',
            'destination_address' => 'Calle Preservada 123',
            'package_type'        => 'caja',
            'status'              => Shipment::STATUS_PENDING,
            'total_cost'          => 25.50,
        ]);

        // Update only status — all other fields must stay
        $response = $this->actingAs($this->gestorA)
            ->patchJson(route('admin.shipments.update', $shipment->id), [
                'status' => Shipment::STATUS_DELIVERED,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', Shipment::STATUS_DELIVERED)
            ->assertJsonPath('data.recipient_name', 'Preserve Test')
            ->assertJsonPath('data.destination_address', 'Calle Preservada 123')
            ->assertJsonPath('data.package_type', 'caja')
            ->assertJsonPath('data.total_cost', '25.50');

        // Verify in database
        $this->assertDatabaseHas('shipments', [
            'id'                 => $shipment->id,
            'recipient_name'     => 'Preserve Test',
            'destination_address' => 'Calle Preservada 123',
            'package_type'       => 'caja',
            'status'             => Shipment::STATUS_DELIVERED,
            'total_cost'         => 25.50,
        ]);
    }
}
