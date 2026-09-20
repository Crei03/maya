<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Shipment;
use App\Models\ShipmentTask;
use App\Models\ShipmentTaskItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShipmentTaskWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;

    private User $driver;

    private Tenant $tenant;

    private Warehouse $warehouse;

    private Vehicle $vehicle;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        config(['multi-tenant.enabled' => false]);

        $this->tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Demo',
                'status' => 'active',
            ],
        );
        $this->tenant->makeCurrent();

        $this->seed(\Database\Seeders\CatalogoSeeder::class);

        $this->gestor = User::factory()->create([
            'role' => User::ROLE_GESTOR,
            'status' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->driver = User::factory()->create([
            'role' => User::ROLE_MESSENGER,
            'status' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->warehouse = Warehouse::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'code' => 'BOD-001',
            'name' => 'Bodega Central',
            'location_address' => 'Vía España 123',
            'is_active' => true,
        ]);

        $this->vehicle = Vehicle::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'license_plate' => 'ABC-123',
            'brand' => 'Toyota',
            'model' => 'Hiace',
            'type' => 'internal',
            'year' => 2024,
            'capacity_kg' => 1000,
            'is_active' => true,
        ]);

        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Comercio',
            'last_name' => 'Panamá',
            'full_name' => 'Comercio Panamá',
            'phone' => '6000-1111',
            'email' => 'comercio@panama.com',
        ]);
    }

    public function test_next_code_endpoint_returns_ple_pattern(): void
    {
        $response = $this->actingAs($this->gestor)->getJson('/api/shipment-tasks/next-code');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['code']]);

        $code = $response->json('data.code');
        $this->assertMatchesRegularExpression('/^PLE-\d{4}-\d{2}-\d{4}$/', $code);
    }

    public function test_create_task_from_wizard_with_new_shipments(): void
    {
        $payload = [
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'notes' => 'Ruta matutina',
            'items' => [
                [
                    'priority' => 'alta',
                    'stop_order' => 1,
                    'new_shipment' => [
                        'sender_id' => $this->client->id,
                        'destination_address' => 'Costa del Este, Edif 10',
                        'package_type' => 'caja',
                        'weight_lb' => 12.5,
                        'content_description' => 'Documentos y muestras',
                    ],
                ],
                [
                    'priority' => 'media',
                    'stop_order' => 2,
                    'new_shipment' => [
                        'sender_id' => $this->client->id,
                        'destination_address' => 'San Francisco, Calle 74',
                        'package_type' => 'sobre',
                        'weight_lb' => 2.0,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->gestor)->postJson('/api/shipment-tasks', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ShipmentTask::STATUS_PENDIENTE)
            ->assertJsonPath('data.total_items', 2);

        $cs = app(\App\Services\CatalogoService::class);
        $pendingTaskId = $cs->getValorIdByCodigo('estado-tarea', 'PENDIENTE');

        $this->assertDatabaseHas('shipment_tasks', [
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'status_id' => $pendingTaskId,
        ]);

        $this->assertDatabaseCount('shipment_task_items', 2);
        $this->assertDatabaseHas('shipment_task_items', [
            'priority_id' => $cs->getValorIdByCodigo('prioridad-tarea', 'ALTA'),
            'stop_order' => 1,
        ]);
        $this->assertDatabaseHas('shipment_task_items', [
            'priority_id' => $cs->getValorIdByCodigo('prioridad-tarea', 'MEDIA'),
            'stop_order' => 2,
        ]);

        // Verificar que los envíos se crearon y tienen status 'assigned'
        $shipments = Shipment::all();
        $this->assertCount(2, $shipments);
        foreach ($shipments as $shipment) {
            $this->assertEquals(Shipment::STATUS_ASSIGNED, $shipment->status);
            $this->assertEquals($this->warehouse->id, $shipment->warehouse_id);
            $this->assertNotNull($shipment->driver_task);
        }
    }

    public function test_create_task_with_existing_shipment(): void
    {
        $existingShipment = Shipment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $this->warehouse->id,
            'tracking_number' => 'MAYAEXIST123',
            'sender_id' => $this->client->id,
            'destination_address' => 'Obarrio, Calle 54',
            'package_type' => 'caja',
            'weight_lb' => 5.0,
            'status' => Shipment::STATUS_IN_WAREHOUSE,
        ]);

        $payload = [
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->addHours(1)->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'priority' => 'media',
                    'stop_order' => 1,
                    'shipment_id' => $existingShipment->id,
                ],
            ],
        ];

        $response = $this->actingAs($this->gestor)->postJson('/api/shipment-tasks', $payload);

        $response->assertCreated();

        $existingShipment->refresh();
        $this->assertEquals(Shipment::STATUS_ASSIGNED, $existingShipment->status);
        $this->assertNotNull($existingShipment->driver_task);
    }

    public function test_create_task_enforces_priority_hierarchy_rule(): void
    {
        // Intentar programar parada de prioridad BAJA antes de una de prioridad ALTA
        $payload = [
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'priority' => 'baja',
                    'stop_order' => 1,
                    'new_shipment' => [
                        'sender_id' => $this->client->id,
                        'destination_address' => 'Destino A',
                        'package_type' => 'caja',
                        'weight_lb' => 3.0,
                    ],
                ],
                [
                    'priority' => 'alta',
                    'stop_order' => 2,
                    'new_shipment' => [
                        'sender_id' => $this->client->id,
                        'destination_address' => 'Destino B',
                        'package_type' => 'sobre',
                        'weight_lb' => 1.0,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->gestor)->postJson('/api/shipment-tasks', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_list_tasks_returns_paginated_data(): void
    {
        ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0001',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->addHours(2),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->gestor)->getJson('/api/shipment-tasks');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'driver_name', 'status', 'total_items'],
                    ],
                    'meta' => ['total', 'current_page'],
                ],
            ])
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_show_task_returns_details_and_stops(): void
    {
        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0001',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->addHours(2),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->gestor)->getJson("/api/shipment-tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', 'PLE-2026-09-0001');
    }

    public function test_reorder_task_items_success(): void
    {
        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0001',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->addHours(2),
            'status' => 'pending',
        ]);

        $item1 = ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => (string) Str::uuid(),
            'priority' => 'alta',
            'stop_order' => 1,
            'status' => 'pendiente',
        ]);

        $item2 = ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => (string) Str::uuid(),
            'priority' => 'alta',
            'stop_order' => 2,
            'status' => 'pendiente',
        ]);

        // Intercambiar orden de dos items de misma prioridad alta
        $payload = [
            'items' => [
                ['id' => $item1->id, 'stop_order' => 2, 'priority' => 'alta'],
                ['id' => $item2->id, 'stop_order' => 1, 'priority' => 'alta'],
            ],
        ];

        $response = $this->actingAs($this->gestor)->patchJson("/api/shipment-tasks/{$task->id}/reorder", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(2, $item1->fresh()->stop_order);
        $this->assertEquals(1, $item2->fresh()->stop_order);
    }

    public function test_reorder_task_items_fails_if_priority_violated(): void
    {
        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0001',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->addHours(2),
            'status' => 'pending',
        ]);

        $itemAlta = ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => (string) Str::uuid(),
            'priority' => 'alta',
            'stop_order' => 1,
            'status' => 'pendiente',
        ]);

        $itemBaja = ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => (string) Str::uuid(),
            'priority' => 'baja',
            'stop_order' => 2,
            'status' => 'pendiente',
        ]);

        // Intentar poner la baja como parada 1 y la alta como parada 2
        $payload = [
            'items' => [
                ['id' => $itemBaja->id, 'stop_order' => 1, 'priority' => 'baja'],
                ['id' => $itemAlta->id, 'stop_order' => 2, 'priority' => 'alta'],
            ],
        ];

        $response = $this->actingAs($this->gestor)->patchJson("/api/shipment-tasks/{$task->id}/reorder", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_start_task_transitions_shipments_to_in_transit(): void
    {
        $shipment = Shipment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tracking_number' => 'TRK-START-01',
            'sender_id' => $this->client->id,
            'destination_address' => 'Via Espana',
            'weight_lb' => 10,
            'status' => Shipment::STATUS_ASSIGNED,
        ]);

        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0002',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now(),
            'status' => 'pending',
        ]);

        $item = ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => $shipment->id,
            'priority' => 'alta',
            'stop_order' => 1,
            'status' => 'pendiente',
        ]);

        $response = $this->actingAs($this->gestor)->postJson("/api/shipment-tasks/{$task->id}/start");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ShipmentTask::STATUS_IN_PROGRESS);

        $this->assertEquals(ShipmentTask::STATUS_IN_PROGRESS, $task->fresh()->status);
        $this->assertEquals(Shipment::STATUS_IN_TRANSIT, $shipment->fresh()->status);
        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $shipment->id,
        ]);
    }

    public function test_cannot_start_task_if_driver_already_has_active_task(): void
    {
        // First active task
        ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0003',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now(),
            'status' => 'in_progress',
        ]);

        // Second task pending
        $task2 = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0004',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->gestor)->postJson("/api/shipment-tasks/{$task2->id}/start");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['driver_id']);
    }

    public function test_update_item_status_delivered_and_returned(): void
    {
        $shipment = Shipment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tracking_number' => 'TRK-STOP-01',
            'sender_id' => $this->client->id,
            'destination_address' => 'Costa del Este',
            'weight_lb' => 5,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);

        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0005',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now(),
            'status' => 'in_progress',
        ]);

        $item = ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => $shipment->id,
            'priority' => 'alta',
            'stop_order' => 1,
            'status' => 'pendiente',
        ]);

        // Mark as delivered
        $response = $this->actingAs($this->gestor)->patchJson("/api/shipment-tasks/{$task->id}/items/{$item->id}", [
            'status' => 'entregado',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(ShipmentTaskItem::STATUS_ENTREGADO, $item->fresh()->status);
        $this->assertEquals(Shipment::STATUS_DELIVERED, $shipment->fresh()->status);

        // Mark as returned
        $response2 = $this->actingAs($this->gestor)->patchJson("/api/shipment-tasks/{$task->id}/items/{$item->id}", [
            'status' => 'retornado',
            'return_reason' => 'Cliente ausente',
        ]);

        $response2->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(ShipmentTaskItem::STATUS_RETORNADO, $item->fresh()->status);
        $this->assertEquals(Shipment::STATUS_RETURNED, $shipment->fresh()->status);
        $this->assertEquals('Cliente ausente', $item->fresh()->return_reason);
    }

    public function test_complete_task_calculates_total_hours_and_returns_undelivered_to_warehouse(): void
    {
        $shipmentDelivered = Shipment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tracking_number' => 'TRK-COMP-01',
            'sender_id' => $this->client->id,
            'destination_address' => 'San Francisco',
            'weight_lb' => 5,
            'status' => Shipment::STATUS_DELIVERED,
        ]);

        $shipmentPending = Shipment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tracking_number' => 'TRK-COMP-02',
            'sender_id' => $this->client->id,
            'destination_address' => 'El Cangrejo',
            'weight_lb' => 8,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);

        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0006',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now()->subHours(2),
            'status' => 'in_progress',
        ]);

        ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => $shipmentDelivered->id,
            'priority' => 'alta',
            'stop_order' => 1,
            'status' => ShipmentTaskItem::STATUS_ENTREGADO,
        ]);

        $itemPending = ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => $shipmentPending->id,
            'priority' => 'media',
            'stop_order' => 2,
            'status' => ShipmentTaskItem::STATUS_PENDIENTE,
        ]);

        $response = $this->actingAs($this->gestor)->postJson("/api/shipment-tasks/{$task->id}/complete");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ShipmentTask::STATUS_COMPLETED);

        $this->assertEquals(ShipmentTask::STATUS_COMPLETED, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->total_hours);
        $this->assertEquals(Shipment::STATUS_DELIVERED, $shipmentDelivered->fresh()->status);
        $this->assertEquals(Shipment::STATUS_IN_WAREHOUSE, $shipmentPending->fresh()->status);
        $this->assertEquals(ShipmentTaskItem::STATUS_RETORNADO, $itemPending->fresh()->status);
    }

    public function test_cancel_task_returns_shipments_to_warehouse(): void
    {
        $shipment = Shipment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tracking_number' => 'TRK-CANC-01',
            'sender_id' => $this->client->id,
            'destination_address' => 'Clayton',
            'weight_lb' => 12,
            'status' => Shipment::STATUS_ASSIGNED,
        ]);

        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0007',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now(),
            'status' => 'pending',
        ]);

        ShipmentTaskItem::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $task->id,
            'shipment_id' => $shipment->id,
            'priority' => 'alta',
            'stop_order' => 1,
            'status' => 'pendiente',
        ]);

        $response = $this->actingAs($this->gestor)->postJson("/api/shipment-tasks/{$task->id}/cancel", [
            'reason' => 'Vehículo averiado antes de salir',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ShipmentTask::STATUS_CANCELLED);

        $this->assertEquals(ShipmentTask::STATUS_CANCELLED, $task->fresh()->status);
        $this->assertEquals(Shipment::STATUS_IN_WAREHOUSE, $shipment->fresh()->status);
    }

    public function test_assign_and_unassign_shipments(): void
    {
        $shipment = Shipment::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tracking_number' => 'TRK-ASSIGN-01',
            'sender_id' => $this->client->id,
            'destination_address' => 'Albrook',
            'weight_lb' => 4,
            'status' => Shipment::STATUS_IN_WAREHOUSE,
        ]);

        $task = ShipmentTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-09-0008',
            'driver_id' => $this->driver->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'start_date' => now(),
            'status' => 'pending',
        ]);

        // Assign
        $responseAssign = $this->actingAs($this->gestor)->postJson("/api/shipment-tasks/{$task->id}/assign", [
            'shipment_ids' => [$shipment->id],
            'priority' => 'media',
        ]);

        $responseAssign->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(Shipment::STATUS_ASSIGNED, $shipment->fresh()->status);
        $this->assertDatabaseHas('shipment_task_items', [
            'shipment_task_id' => $task->id,
            'shipment_id' => $shipment->id,
        ]);

        // Unassign
        $responseUnassign = $this->actingAs($this->gestor)->postJson("/api/shipment-tasks/{$task->id}/unassign", [
            'shipment_id' => $shipment->id,
        ]);

        $responseUnassign->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(Shipment::STATUS_IN_WAREHOUSE, $shipment->fresh()->status);
        $this->assertDatabaseMissing('shipment_task_items', [
            'shipment_task_id' => $task->id,
            'shipment_id' => $shipment->id,
        ]);
    }

    public function test_stats_endpoint_returns_kpi_counts(): void
    {
        $cs = app(\App\Services\CatalogoService::class);
        $pendingStatusId = $cs->getValorIdByCodigo('estado-tarea', 'PENDIENTE');
        $inProgressStatusId = $cs->getValorIdByCodigo('estado-tarea', 'EN_PROCESO');

        ShipmentTask::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'PLE-2026-09-0001',
            'title' => 'PLE-2026-09-0001',
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'scheduled_date' => now(),
            'start_date' => now(),
            'status_id' => $pendingStatusId,
        ]);

        ShipmentTask::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'PLE-2026-09-0002',
            'title' => 'PLE-2026-09-0002',
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'origin_warehouse_id' => $this->warehouse->id,
            'scheduled_date' => now(),
            'started_at' => now(),
            'start_date' => now(),
            'status_id' => $inProgressStatusId,
        ]);

        $response = $this->actingAs($this->gestor)->getJson('/api/shipment-tasks/stats');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.summary.pending', 1)
            ->assertJsonPath('data.summary.in_progress', 1)
            ->assertJsonPath('data.summary.completed', 0)
            ->assertJsonPath('data.summary.cancelled', 0);
    }
}
