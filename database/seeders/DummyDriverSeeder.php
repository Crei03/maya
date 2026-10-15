<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DriverProfile;
use App\Models\Shipment;
use App\Models\ShipmentTask;
use App\Models\ShipmentTaskItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Services\CatalogoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDriverSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo')->first() ?? Tenant::first();
        if (! $tenant) {
            $this->command->error('No tenant found.');

            return;
        }

        $tenant->makeCurrent();

        // 1. Create or update Driver User
        $driverEmail = 'conductor@maya.app';
        $driverPassword = 'password123';

        $driver = User::updateOrCreate(
            ['email' => $driverEmail],
            [
                'name' => 'Manuel Rivera (Conductor)',
                'password' => Hash::make($driverPassword),
                'role' => User::ROLE_MESSENGER,
                'status' => 1,
                'tenant_id' => $tenant->id,
            ]
        );

        // 2. Driver Profile
        DriverProfile::updateOrCreate(
            ['user_id' => $driver->id],
            [
                'phone' => '6789-1234',
                'license_number' => 'LIC-PA-9982',
                'emergency_contact' => 'Elena Rivera',
                'emergency_phone' => '6890-4321',
                'is_available' => true,
            ]
        );

        // 3. Resolve Warehouse & Vehicle
        $warehouse = Warehouse::where('tenant_id', $tenant->id)->first() ?? Warehouse::first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first() ?? Vehicle::first();

        // 4. Resolve Catalogs
        $catalogoService = app(CatalogoService::class);
        $taskStatusInProcess = $catalogoService->getValorIdByCodigo('estado-tarea', 'EN_PROCESO');
        $itemStatusPendiente = $catalogoService->getValorIdByCodigo('estado-item-tarea', 'PENDIENTE');
        $itemStatusEnCurso = $catalogoService->getValorIdByCodigo('estado-item-tarea', 'EN_CURSO');
        $itemStatusPausa = $catalogoService->getValorIdByCodigo('estado-item-tarea', 'PAUSA');
        $priorityMedia = $catalogoService->getValorIdByCodigo('prioridad-tarea', 'MEDIA');
        $priorityAlta = $catalogoService->getValorIdByCodigo('prioridad-tarea', 'ALTA');

        // 5. Create Realistic Shipments in Panama
        $shipmentsData = [
            [
                'recipient_name' => 'Ana Sofía Morales',
                'recipient_phone' => '6543-2109',
                'destination_address' => 'Calle 50 con Calle 53, Edificio Plaza 2000, Piso 14, Obarrio, Ciudad de Panamá',
                'destination_coords' => ['lat' => 8.9863, 'lng' => -79.5187],
                'tracking_number' => 'MAYAPTY801',
                'weight_kg' => 2.5,
                'weight_lb' => 5.51,
                'content_description' => 'Documentación legal y contratos',
                'status_id' => $itemStatusEnCurso,
                'stop_status' => $itemStatusEnCurso,
                'priority_id' => $priorityAlta,
                'stop_order' => 1,
                'notes' => '[08:30]: Cliente confirmó que está en recepción esperando el mensajero.',
            ],
            [
                'recipient_name' => 'Carlos Eduardo Mendoza',
                'recipient_phone' => '6871-4523',
                'destination_address' => 'Av. La Rotonda, PH Prime Time Tower, Costa del Este, Ciudad de Panamá',
                'destination_coords' => ['lat' => 9.0152, 'lng' => -79.4674],
                'tracking_number' => 'MAYAPTY802',
                'weight_kg' => 4.2,
                'weight_lb' => 9.26,
                'content_description' => 'Equipo tecnológico para sucursal',
                'status_id' => $itemStatusPendiente,
                'stop_status' => $itemStatusPendiente,
                'priority_id' => $priorityMedia,
                'stop_order' => 2,
                'notes' => null,
            ],
            [
                'recipient_name' => 'Farmacias y Droguerías Panamá',
                'recipient_phone' => '6902-8877',
                'destination_address' => 'Vía Argentina, diagonal al Parque Andrés Bello, El Cangrejo, Bella Vista',
                'destination_coords' => ['lat' => 8.9897, 'lng' => -79.5259],
                'tracking_number' => 'MAYAPTY803',
                'weight_kg' => 0.8,
                'weight_lb' => 1.76,
                'content_description' => 'Insumos de farmacia sellados',
                'status_id' => $itemStatusPausa,
                'stop_status' => $itemStatusPausa,
                'priority_id' => $priorityMedia,
                'stop_order' => 3,
                'notes' => '[09:15]: Horario de almuerzo del farmaceuta. Entregar después de las 1:30 PM.',
            ],
            [
                'recipient_name' => 'Boutique Bella Moda',
                'recipient_phone' => '6112-9900',
                'destination_address' => 'Calle 74 Este con Vía Israel, San Francisco, Ciudad de Panamá',
                'destination_coords' => ['lat' => 8.9912, 'lng' => -79.4998],
                'tracking_number' => 'MAYAPTY804',
                'weight_kg' => 6.0,
                'weight_lb' => 13.23,
                'content_description' => 'Caja textil y calzado importado',
                'status_id' => $itemStatusPendiente,
                'stop_status' => $itemStatusPendiente,
                'priority_id' => $priorityMedia,
                'stop_order' => 4,
                'notes' => null,
            ],
        ];

        // 6. Create Delivery Plan (ShipmentTask)
        $taskTitle = 'PLE-2026-10-0042';
        $task = ShipmentTask::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'title' => $taskTitle,
            ],
            [
                'id' => (string) Str::uuid(),
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle?->id,
                'origin_warehouse_id' => $warehouse?->id,
                'scheduled_date' => now(),
                'start_date' => now(),
                'started_at' => now()->subHour(),
                'status_id' => $taskStatusInProcess,
                'notes' => 'Ruta Metropolitana - Centro y Este de Panamá. Prioridad entregas antes de las 4 PM.',
            ]
        );

        // Update driver assignment if existing
        $task->update([
            'driver_id' => $driver->id,
            'scheduled_date' => now(),
            'status_id' => $taskStatusInProcess,
        ]);

        // 7. Associate Stop Items
        foreach ($shipmentsData as $sData) {
            $shipment = Shipment::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'tracking_number' => $sData['tracking_number'],
                ],
                [
                    'recipient_name' => $sData['recipient_name'],
                    'recipient_phone' => $sData['recipient_phone'],
                    'destination_address' => $sData['destination_address'],
                    'destination_coords' => $sData['destination_coords'],
                    'weight_kg' => $sData['weight_kg'],
                    'weight_lb' => $sData['weight_lb'],
                    'content_description' => $sData['content_description'],
                    'status_id' => $sData['status_id'],
                ]
            );

            ShipmentTaskItem::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'shipment_task_id' => $task->id,
                    'shipment_id' => $shipment->id,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'stop_order' => $sData['stop_order'],
                    'status_id' => $sData['stop_status'],
                    'priority_id' => $sData['priority_id'],
                    'notes' => $sData['notes'],
                ]
            );
        }

        $this->command->info("Dummy driver created: {$driverEmail} / {$driverPassword}");
        $this->command->info("Delivery plan created: {$taskTitle} with 4 stops.");
    }
}
