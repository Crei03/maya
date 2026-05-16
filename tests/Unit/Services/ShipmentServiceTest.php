<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ShipmentHasRelationsException;
use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\ShipmentTask;
use App\Models\ShipmentTaskItem;
use App\Models\Tenant;
use App\Models\TrackingEvent;
use App\Repositories\ShipmentRepository;
use App\Services\ShipmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShipmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShipmentService $service;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->tenant->makeCurrent();

        $this->service = new ShipmentService(new ShipmentRepository());
    }

    public function test_list_returns_paginated_data_with_mapped_shipments(): void
    {
        // Arrange: create shipments in current tenant
        Shipment::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        // Act
        $result = $this->service->list(['per_page' => 3]);

        // Assert: structure and content
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(3, $result['data']);
        $this->assertArrayHasKey('current_page', $result['meta']);
        $this->assertArrayHasKey('last_page', $result['meta']);
        $this->assertArrayHasKey('total', $result['meta']);
        $this->assertSame(5, $result['meta']['total']);

        // Assert: items are mapped arrays, not Eloquent models
        $this->assertIsArray($result['data'][0]);
    }

    public function test_list_applies_status_filter(): void
    {
        // Arrange
        Shipment::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'status'    => Shipment::STATUS_PENDING,
        ]);
        Shipment::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status'    => Shipment::STATUS_DELIVERED,
        ]);

        // Act
        $result = $this->service->list([
            'status'   => Shipment::STATUS_PENDING,
            'per_page' => 10,
        ]);

        // Assert
        $this->assertCount(2, $result['data']);
        foreach ($result['data'] as $item) {
            $this->assertSame(Shipment::STATUS_PENDING, $item['status']);
        }
    }

    public function test_show_throws_model_not_found_exception_for_nonexistent_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->show('non-existent-id-999');
    }

    public function test_show_returns_shipment_with_relationships(): void
    {
        // Arrange: create a shipment with warehouse relationship
        $warehouse = \App\Models\Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $shipment = Shipment::factory()->create([
            'tenant_id'    => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
        ]);

        // Act
        $result = $this->service->show($shipment->id);

        // Assert: returned as array with relationships
        $this->assertIsArray($result);
        $this->assertSame($shipment->id, $result['id']);
        $this->assertSame($shipment->tracking_number, $result['tracking_number']);

        // Warehouse relationship loaded
        $this->assertArrayHasKey('warehouse', $result);
        $this->assertNotNull($result['warehouse']);
        $this->assertSame($warehouse->id, $result['warehouse']['id']);

        // Tracking events key exists (even if empty)
        $this->assertArrayHasKey('tracking_events', $result);
    }

    public function test_create_returns_shipment_model(): void
    {
        $data = [
            'recipient_name'      => 'Juan Pérez',
            'recipient_phone'     => '+507 6000-1234',
            'origin_address'      => 'Bodega Central, Panamá',
            'destination_address' => 'Calle 50, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => 5.5,
        ];

        $shipment = $this->service->create($data);

        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertSame('Juan Pérez', $shipment->recipient_name);
        $this->assertSame('pending', $shipment->status); // auto-assigned
        $this->assertStringStartsWith('MAYA', $shipment->tracking_number);
        $this->assertSame($this->tenant->id, $shipment->tenant_id);
    }

    public function test_update_updates_only_provided_fields(): void
    {
        // Arrange
        $shipment = Shipment::factory()->create([
            'tenant_id'         => $this->tenant->id,
            'recipient_name'    => 'Original Name',
            'destination_address' => 'Original Address',
            'status'            => Shipment::STATUS_PENDING,
        ]);

        // Act: partial update — only change status
        $updated = $this->service->update($shipment->id, [
            'status' => Shipment::STATUS_IN_WAREHOUSE,
        ]);

        // Assert: only status changed, other fields intact
        $this->assertSame(Shipment::STATUS_IN_WAREHOUSE, $updated->status);
        $this->assertSame('Original Name', $updated->recipient_name);
        $this->assertSame('Original Address', $updated->destination_address);
    }

    public function test_delete_throws_exception_if_has_tracking_events(): void
    {
        // Arrange
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create a tracking event linked to this shipment
        TrackingEvent::create([
            'id'          => (string) Str::uuid(),
            'tenant_id'   => $this->tenant->id,
            'shipment_id' => $shipment->id,
            'status_id'   => 1,
            'timestamp'   => now(),
        ]);

        $this->expectException(ShipmentHasRelationsException::class);

        $this->service->delete($shipment->id);
    }

    public function test_delete_throws_exception_if_has_manifest_items(): void
    {
        // Arrange
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create a manifest first, then a manifest item
        $manifest = Manifest::create([
            'id'             => (string) Str::uuid(),
            'tenant_id'      => $this->tenant->id,
            'messenger_id'   => (string) Str::uuid(),
            'scheduled_date' => now(),
        ]);

        ManifestItem::create([
            'tenant_id'   => $this->tenant->id,
            'manifest_id' => $manifest->id,
            'shipment_id' => $shipment->id,
            'stop_order'  => 1,
            'is_delivered' => false,
        ]);

        $this->expectException(ShipmentHasRelationsException::class);

        $this->service->delete($shipment->id);
    }

    public function test_delete_throws_exception_if_has_shipment_task_items(): void
    {
        // Arrange
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create a shipment task first, then a task item
        $warehouse = \App\Models\Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);
        $driver = \App\Models\User::factory()->create(['tenant_id' => $this->tenant->id]);
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

        $this->expectException(ShipmentHasRelationsException::class);

        $this->service->delete($shipment->id);
    }

    public function test_delete_succeeds_if_no_relations(): void
    {
        // Arrange
        $shipment = Shipment::factory()->create(['tenant_id' => $this->tenant->id]);
        $shipmentId = $shipment->id;

        // Act
        $this->service->delete($shipmentId);

        // Assert: shipment no longer exists
        $this->assertNull(
            Shipment::query()->find($shipmentId)
        );
    }

    public function test_delete_throws_model_not_found_for_nonexistent_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->delete('non-existent-id-999');
    }

    public function test_show_throws_model_not_found_for_cross_tenant_shipment(): void
    {
        // Arrange: create shipment in a different tenant
        $otherTenant = Tenant::factory()->create();
        $shipment = Shipment::factory()->create(['tenant_id' => $otherTenant->id]);

        // Current tenant is still $this->tenant (set in setUp)

        $this->expectException(ModelNotFoundException::class);

        $this->service->show($shipment->id);
    }
}
