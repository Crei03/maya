<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Http\Middleware\EnsureTenant;
use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\ShipmentTask;
use App\Models\ShipmentTaskItem;
use App\Models\Tenant;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShipmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip the EnsureTenant middleware so our manual makeCurrent()
        // persists through the HTTP request lifecycle.
        $this->withoutMiddleware(EnsureTenant::class);

        // Create a tenant and make it current for test queries.
        $this->tenant = Tenant::factory()->create();
        $this->tenant->makeCurrent();

        $this->gestor = User::factory()->create([
            'role'      => User::ROLE_GESTOR,
            'status'    => true,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ==========================================================================
    // Authentication / Authorization
    // ==========================================================================

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson(route('admin.shipments.list'));

        $response->assertUnauthorized();
    }

    public function test_messenger_returns_403(): void
    {
        $messenger = User::factory()->create([
            'role'      => User::ROLE_MESSENGER,
            'status'    => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($messenger)
            ->getJson(route('admin.shipments.list'));

        $response->assertForbidden();
    }

    public function test_client_returns_403(): void
    {
        $client = User::factory()->create([
            'role'      => User::ROLE_CLIENT,
            'status'    => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($client)
            ->getJson(route('admin.shipments.list'));

        $response->assertForbidden();
    }

    // ==========================================================================
    // List (index)
    // ==========================================================================

    public function test_list_returns_paginated_results(): void
    {
        Shipment::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list', ['per_page' => 3]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonPath('data.meta.total', 5);

        // Verify items have expected keys
        $item = $response->json('data.data.0');
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('tracking_number', $item);
        $this->assertArrayHasKey('recipient_name', $item);
        $this->assertArrayHasKey('status', $item);
    }

    public function test_list_filters_by_status(): void
    {
        Shipment::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status'    => Shipment::STATUS_PENDING,
        ]);
        Shipment::factory()->count(4)->create([
            'tenant_id' => $this->tenant->id,
            'status'    => Shipment::STATUS_DELIVERED,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list', ['status' => Shipment::STATUS_PENDING]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.data');

        // All returned items must be pending
        $data = $response->json('data.data');
        foreach ($data as $item) {
            $this->assertSame(Shipment::STATUS_PENDING, $item['status']);
        }
    }

    public function test_list_returns_empty_when_no_match(): void
    {
        Shipment::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status'    => Shipment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.list', ['status' => Shipment::STATUS_RETURNED]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.data');
    }

    // ==========================================================================
    // Show
    // ==========================================================================

    public function test_show_returns_shipment_with_relationships(): void
    {
        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $shipment = Shipment::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.show', $shipment->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $shipment->id)
            ->assertJsonPath('data.tracking_number', $shipment->tracking_number)
            ->assertJsonPath('data.status', $shipment->status);

        // Relationships are loaded
        $data = $response->json('data');
        $this->assertArrayHasKey('warehouse', $data);
        $this->assertArrayHasKey('tracking_events', $data);
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.shipments.show', 'non-existent-id'));

        $response->assertNotFound();
    }

    // ==========================================================================
    // Store
    // ==========================================================================

    public function test_store_creates_shipment_and_returns_201(): void
    {
        $payload = [
            'recipient_name'      => 'Juan Pérez',
            'recipient_phone'     => '+507 6000-1234',
            'origin_address'      => 'Bodega Central, Panamá',
            'destination_address' => 'Calle 50, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => 5.5,
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.shipments.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Paquete creado exitosamente')
            ->assertJsonPath('data.recipient_name', 'Juan Pérez')
            ->assertJsonPath('data.status', Shipment::STATUS_PENDING);

        // Tracking number auto-generated, starts with MAYA
        $trackingNumber = $response->json('data.tracking_number');
        $this->assertStringStartsWith('MAYA', $trackingNumber);

        // Verify in database
        $this->assertDatabaseHas('shipments', [
            'recipient_name'  => 'Juan Pérez',
            'tenant_id'       => $this->tenant->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.shipments.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_name', 'destination_address']);
    }

    // ==========================================================================
    // Update
    // ==========================================================================

    public function test_update_partial_changes_only_provided_fields(): void
    {
        $shipment = Shipment::factory()->create([
            'tenant_id'      => $this->tenant->id,
            'recipient_name' => 'Original Name',
            'status'         => Shipment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.shipments.update', $shipment->id), [
                'status' => Shipment::STATUS_IN_WAREHOUSE,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Paquete actualizado exitosamente')
            ->assertJsonPath('data.status', Shipment::STATUS_IN_WAREHOUSE)
            ->assertJsonPath('data.recipient_name', 'Original Name');
    }

    public function test_update_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.shipments.update', 'non-existent-id'), [
                'status' => Shipment::STATUS_PENDING,
            ]);

        $response->assertNotFound();
    }

    // ==========================================================================
    // Delete
    // ==========================================================================

    public function test_delete_succeeds_when_no_relations(): void
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

    public function test_delete_with_tracking_events_returns_409(): void
    {
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        TrackingEvent::create([
            'id'          => (string) Str::uuid(),
            'tenant_id'   => $this->tenant->id,
            'shipment_id' => $shipment->id,
            'status_id'   => 1,
            'timestamp'   => now(),
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.shipments.destroy', $shipment->id));

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_delete_with_manifest_items_returns_409(): void
    {
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        $manifest = Manifest::create([
            'id'             => (string) Str::uuid(),
            'tenant_id'      => $this->tenant->id,
            'scheduled_date' => now(),
        ]);

        ManifestItem::create([
            'tenant_id'   => $this->tenant->id,
            'manifest_id' => $manifest->id,
            'shipment_id' => $shipment->id,
            'stop_order'  => 1,
            'is_delivered' => false,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.shipments.destroy', $shipment->id));

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_delete_with_shipment_task_items_returns_409(): void
    {
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $driver = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $task = ShipmentTask::create([
            'id'                  => (string) Str::uuid(),
            'tenant_id'           => $this->tenant->id,
            'title'               => 'Test Task',
            'status'              => 'pending',
            'driver_id'           => $driver->id,
            'origin_warehouse_id' => $warehouse->id,
            'start_date'          => now(),
        ]);

        ShipmentTaskItem::create([
            'id'               => (string) Str::uuid(),
            'tenant_id'        => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id'      => $shipment->id,
            'status'           => 'pendiente',
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.shipments.destroy', $shipment->id));

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_delete_returns_404_for_nonexistent(): void
    {
        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.shipments.destroy', 'non-existent-id'));

        $response->assertNotFound();
    }
}
