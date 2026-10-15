<?php

declare(strict_types=1);

namespace Tests\Feature\Driver;

use App\Models\CatalogoValor;
use App\Models\Shipment;
use App\Models\ShipmentTask;
use App\Models\ShipmentTaskItem;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DriverMobileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $messenger;

    protected ShipmentTask $task;

    protected ShipmentTaskItem $taskItem;

    protected Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['multi-tenant.enabled' => false]);

        Storage::fake('public');

        $this->tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Demo Courier',
                'status' => 'active',
            ]
        );
        $this->tenant->makeCurrent();

        $this->seed(CatalogoSeeder::class);

        // Ensure EN_CURSO, PAUSA, DEVUELTO in SQLite in-memory test DB
        $cat = \App\Models\Catalogo::where('slug', 'estado-item-tarea')->first();
        if ($cat) {
            CatalogoValor::firstOrCreate(
                ['catalogo_id' => $cat->id, 'codigo' => 'EN_CURSO'],
                ['valor' => 'En Curso', 'metadata' => ['color' => 'sky', 'badge' => 'bg-sky-50 text-sky-700', 'es_final' => false], 'is_global' => true, 'is_active' => true]
            );
            CatalogoValor::firstOrCreate(
                ['catalogo_id' => $cat->id, 'codigo' => 'PAUSA'],
                ['valor' => 'En Pausa', 'metadata' => ['color' => 'purple', 'badge' => 'bg-purple-50 text-purple-700', 'es_final' => false], 'is_global' => true, 'is_active' => true]
            );
            CatalogoValor::firstOrCreate(
                ['catalogo_id' => $cat->id, 'codigo' => 'DEVUELTO'],
                ['valor' => 'Devuelto', 'metadata' => ['color' => 'rose', 'badge' => 'bg-rose-50 text-rose-700', 'es_final' => true], 'is_global' => true, 'is_active' => true]
            );
        }

        $this->messenger = User::factory()->create([
            'name' => 'Carlos Conductor',
            'email' => 'driver-test@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_MESSENGER,
            'status' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->shipment = Shipment::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'tracking_number' => 'TEST-TRK-001',
            'recipient_name' => 'Roberto Gómez',
            'recipient_phone' => '6000-1111',
            'destination_address' => 'Costa del Este, Calle 50, Torre A',
            'weight_kg' => 2.5,
            'weight_lb' => 5.51,
        ]);

        $this->task = ShipmentTask::factory()->create([
            'tenant_id' => $this->tenant->id,
            'title' => 'PLE-2026-10-9999',
            'driver_id' => $this->messenger->id,
            'scheduled_date' => now(),
        ]);

        $this->taskItem = ShipmentTaskItem::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'shipment_task_id' => $this->task->id,
            'shipment_id' => $this->shipment->id,
            'stop_order' => 1,
        ]);
    }

    public function test_messenger_is_redirected_to_driver_dashboard_on_login(): void
    {
        $response = $this->post('/login', [
            'email' => $this->messenger->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('driver.dashboard', absolute: false));
    }

    public function test_driver_can_access_dashboard_and_see_assigned_plan(): void
    {
        $response = $this->actingAs($this->messenger)
            ->get(route('driver.dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Driver/Dashboard')
                ->has('kpis')
                ->has('plans', 1)
                ->where('plans.0.id', $this->task->id)
                ->where('plans.0.title', 'PLE-2026-10-9999')
            );
    }

    public function test_driver_can_view_tasks_for_plan(): void
    {
        $response = $this->actingAs($this->messenger)
            ->get(route('driver.tasks', $this->task->id));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Driver/Tasks')
                ->has('items', 1)
                ->where('items.0.id', $this->taskItem->id)
                ->where('items.0.recipient_name', 'Roberto Gómez')
            );
    }

    public function test_driver_can_update_task_status_to_pausa(): void
    {
        $response = $this->actingAs($this->messenger)
            ->post(route('driver.tasks.status', $this->taskItem->id), [
                'status' => 'PAUSA',
            ]);

        $response->assertSessionHas('success');
        $this->taskItem->refresh();
        $this->assertEquals('PAUSA', $this->taskItem->status?->codigo);
    }

    public function test_driver_can_add_comment_to_task(): void
    {
        $response = $this->actingAs($this->messenger)
            ->post(route('driver.tasks.comment', $this->taskItem->id), [
                'comment' => 'Portón cerrado temporalmente',
            ]);

        $response->assertSessionHas('success');
        $this->taskItem->refresh();
        $this->assertStringContainsString('Portón cerrado temporalmente', $this->taskItem->notes);
    }

    public function test_driver_can_complete_delivery_with_photos_and_signature(): void
    {
        // 1x1 transparent PNG base64
        $fakePng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->actingAs($this->messenger)
            ->post(route('driver.tasks.complete.store', $this->taskItem->id), [
                'signed_by' => 'Roberto Gómez',
                'signature' => $fakePng,
                'photos' => [$fakePng],
                'notes' => 'Entregado en recepción',
            ]);

        $response->assertRedirect(route('driver.tasks', ['taskId' => $this->task->id]));

        $this->taskItem->refresh();
        $this->shipment->refresh();

        $this->assertEquals('ENTREGADO', $this->taskItem->status?->codigo);
        $this->assertNotNull($this->taskItem->delivered_at);
        $this->assertNotNull($this->shipment->delivered_at);
        $this->assertNotNull($this->shipment->recipient_signature_url);
        $this->assertNotNull($this->shipment->delivered_photo_url);

        // Verify signature file name contains client full name
        $this->assertStringContainsString('roberto_gomez', $this->shipment->recipient_signature_url);
    }
}
