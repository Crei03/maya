<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de Manifiestos de Transporte.
 *
 * Permite a los administradores asignar paquetes a mensajeros,
 * crear manifiestos y formalizar el inicio del despacho.
 */
class ManifestController extends Controller
{
    /**
     * Muestra la vista de asignación de transporte (Web).
     */
    public function index(): Response
    {
        return Inertia::render('Admin/AsignacionTransporte', [
            'paquetesDisponibles' => $this->getPaquetesDisponibles(),
            'mensajeros' => $this->getMensajerosActivos(),
            'manifiestosHoy' => $this->getManifiestosHoy(),
        ]);
    }

    /**
     * Muestra la vista mobile de asignación de transporte.
     */
    public function mobile(): Response
    {
        return Inertia::render('Admin/AsignacionTransporte/Mobile', [
            'paquetesDisponibles' => $this->getPaquetesDisponibles(),
            'mensajeros' => $this->getMensajerosActivos(),
            'manifiestosHoy' => $this->getManifiestosHoy(),
        ]);
    }

    /**
     * Obtiene los paquetes disponibles para asignación.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPaquetesDisponibles(): array
    {
        // Por ahora retornamos datos mock
        // TODO: Implementar lógica real para obtener paquetes sin asignar
        return [
            [
                'id' => 'ship-001',
                'tracking_number' => 'MAYA001234',
                'recipient_name' => 'Juan Martínez',
                'destination_address' => 'Calle 50 #12-34, Ciudad de Panamá',
                'weight_kg' => 2.5,
                'total_cost' => 15.00,
            ],
            [
                'id' => 'ship-002',
                'tracking_number' => 'MAYA001235',
                'recipient_name' => 'María López',
                'destination_address' => 'Av. Balboa #45, Ciudad de Panamá',
                'weight_kg' => 1.8,
                'total_cost' => 12.50,
            ],
            [
                'id' => 'ship-003',
                'tracking_number' => 'MAYA001236',
                'recipient_name' => 'Pedro Sánchez',
                'destination_address' => 'Carrera 7 #89-12, San Miguelito',
                'weight_kg' => 3.2,
                'total_cost' => 18.00,
            ],
            [
                'id' => 'ship-004',
                'tracking_number' => 'MAYA001237',
                'recipient_name' => 'Laura Torres',
                'destination_address' => 'Calle 100 #23-45, Ciudad de Panamá',
                'weight_kg' => 0.9,
                'total_cost' => 8.00,
            ],
            [
                'id' => 'ship-005',
                'tracking_number' => 'MAYA001238',
                'recipient_name' => 'Carlos Ruiz',
                'destination_address' => 'Vía España #567, Ciudad de Panamá',
                'weight_kg' => 4.0,
                'total_cost' => 22.00,
            ],
            [
                'id' => 'ship-006',
                'tracking_number' => 'MAYA001239',
                'recipient_name' => 'Ana Morales',
                'destination_address' => 'Calle 12 #8-90, Colón',
                'weight_kg' => 2.1,
                'total_cost' => 25.00,
            ],
        ];
    }

    /**
     * Obtiene los mensajeros activos disponibles.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMensajerosActivos(): array
    {
        // Por ahora retornamos datos mock
        // TODO: Implementar lógica real para obtener mensajeros de la base de datos
        return [
            [
                'id' => 'msg-001',
                'full_name' => 'Carlos Méndez',
                'phone' => '6123-4567',
                'vehicle_id' => 'MOTO-001',
                'activo' => true,
                'paquetes_asignados_hoy' => 12,
            ],
            [
                'id' => 'msg-002',
                'full_name' => 'María González',
                'phone' => '6234-5678',
                'vehicle_id' => 'MOTO-002',
                'activo' => true,
                'paquetes_asignados_hoy' => 8,
            ],
            [
                'id' => 'msg-003',
                'full_name' => 'Juan Pérez',
                'phone' => '6345-6789',
                'vehicle_id' => 'CAR-001',
                'activo' => true,
                'paquetes_asignados_hoy' => 15,
            ],
            [
                'id' => 'msg-004',
                'full_name' => 'Ana Rodríguez',
                'phone' => '6456-7890',
                'vehicle_id' => 'MOTO-003',
                'activo' => false,
                'paquetes_asignados_hoy' => 0,
            ],
        ];
    }

    /**
     * Obtiene los manifiestos creados hoy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getManifiestosHoy(): array
    {
        // Por ahora retornamos datos mock
        // TODO: Implementar lógica real para obtener manifiestos del día
        return [
            [
                'id' => 'man-001',
                'messenger' => [
                    'full_name' => 'Carlos Méndez',
                ],
                'total_items' => 12,
                'status' => 'En ruta',
                'created_at' => '2026-03-13 08:30:00',
            ],
            [
                'id' => 'man-002',
                'messenger' => [
                    'full_name' => 'María González',
                ],
                'total_items' => 8,
                'status' => 'Preparando',
                'created_at' => '2026-03-13 09:15:00',
            ],
        ];
    }

    /**
     * Crea un nuevo manifiesto con paquetes asignados.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'messenger_id' => 'required|string|exists:users,id',
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'required|string|exists:shipments,id',
            'vehicle_id' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Crear el manifiesto
            $manifest = Manifest::create([
                'messenger_id' => $request->input('messenger_id'),
                'vehicle_id' => $request->input('vehicle_id'),
                'scheduled_date' => $request->input('scheduled_date', now()),
                'status_id' => 1, // Estado: Preparando
            ]);

            // Crear los items del manifiesto
            $shipmentIds = $request->input('shipment_ids');
            foreach ($shipmentIds as $index => $shipmentId) {
                ManifestItem::create([
                    'manifest_id' => $manifest->id,
                    'shipment_id' => $shipmentId,
                    'stop_order' => $index + 1,
                    'is_delivered' => false,
                ]);

                // Registrar evento de tracking
                TrackingEvent::create([
                    'shipment_id' => $shipmentId,
                    'status_code' => 'ASSIGNED',
                    'description' => 'Paquete asignado a mensajero para despacho',
                    'location' => 'Centro de Distribución',
                    'recorded_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Manifiesto creado exitosamente',
                'data' => [
                    'manifest_id' => $manifest->id,
                    'total_items' => count($shipmentIds),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el manifiesto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inicia el despacho de un manifiesto.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function iniciarDespacho(string $id): JsonResponse
    {
        try {
            $manifest = Manifest::findOrFail($id);

            // Actualizar estado del manifiesto
            $manifest->update([
                'status_id' => 2, // Estado: En ruta
            ]);

            // Registrar evento de tracking para cada paquete
            foreach ($manifest->items as $item) {
                TrackingEvent::create([
                    'shipment_id' => $item->shipment_id,
                    'status_code' => 'IN_TRANSIT',
                    'description' => 'Despacho iniciado - En ruta de entrega',
                    'location' => 'En tránsito',
                    'recorded_at' => now(),
                ]);

                // Actualizar estado del envío
                $item->shipment->update([
                    'current_status_id' => 2, // En tránsito
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Despacho iniciado exitosamente',
                'data' => [
                    'manifest_id' => $manifest->id,
                    'status' => 'En ruta',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el despacho',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el detalle de un manifiesto.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $manifest = Manifest::with(['messenger', 'items.shipment'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $manifest->id,
                    'messenger' => [
                        'id' => $manifest->messenger->id,
                        'full_name' => $manifest->messenger->full_name,
                        'phone' => $manifest->messenger->phone,
                    ],
                    'vehicle_id' => $manifest->vehicle_id,
                    'status' => $manifest->status->valor ?? 'Preparando',
                    'scheduled_date' => $manifest->scheduled_date,
                    'created_at' => $manifest->created_at,
                    'items' => $manifest->items->map(function ($item) {
                        return [
                            'shipment_id' => $item->shipment_id,
                            'tracking_number' => $item->shipment->tracking_number,
                            'recipient_name' => $item->shipment->recipient_name,
                            'destination_address' => $item->shipment->destination_address,
                            'weight_kg' => $item->shipment->weight_kg,
                            'total_cost' => $item->shipment->total_cost,
                            'stop_order' => $item->stop_order,
                            'is_delivered' => $item->is_delivered,
                        ];
                    }),
                    'total_items' => $manifest->totalItems(),
                    'delivered_items' => $manifest->deliveredItems(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Manifiesto no encontrado',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Lista los manifiestos con filtros opcionales.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $query = Manifest::with(['messenger', 'status']);

        // Filtros
        if ($request->has('messenger_id')) {
            $query->byMessenger($request->input('messenger_id'));
        }

        if ($request->has('date')) {
            $query->byDate($request->input('date'));
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        $manifests = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $manifests,
        ]);
    }
}