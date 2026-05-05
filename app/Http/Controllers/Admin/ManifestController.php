<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manifest;
use App\Models\ManifestItem;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
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
     * Obtiene los paquetes disponibles para asignación a despacho.
     *
     * Retorna envíos en estado 'pending' o 'in_warehouse' que aún
     * no han sido asignados a ninguna tarea de reparto.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPaquetesDisponibles(): array
    {
        return Shipment::availableForDispatch()
            ->whereNull('assigned_task_id')
            ->with(['warehouse:id,name,code'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn (Shipment $shipment) => [
                'id'                  => $shipment->id,
                'tracking_number'     => $shipment->tracking_number,
                'recipient_name'      => $shipment->recipient_name,
                'recipient_phone'     => $shipment->recipient_phone,
                'destination_address' => $shipment->destination_address,
                'weight_kg'           => $shipment->weight_kg,
                'weight_lb'           => $shipment->weight_lb,
                'package_type'        => $shipment->package_type,
                'total_cost'          => $shipment->total_cost,
                'status'              => $shipment->status,
                'status_label'        => $shipment->getStatusLabel(),
                'warehouse'           => $shipment->warehouse
                    ? ['id' => $shipment->warehouse->id, 'name' => $shipment->warehouse->name]
                    : null,
                'created_at'          => $shipment->created_at,
            ])
            ->toArray();
    }

    /**
     * Obtiene los mensajeros activos disponibles.
     *
     * Retorna usuarios con rol 'messenger' que estén activos
     * e incluye el conteo de paquetes asignados hoy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMensajerosActivos(): array
    {
        return User::where('role', 'messenger')
            ->where('status', 'active')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn (User $user) => [
                'id'                     => $user->id,
                'full_name'              => $user->name,
                'email'                  => $user->email,
                'paquetes_asignados_hoy' => Shipment::where('assigned_task_id', '!=', null)
                    ->whereHas('assignedTask', fn ($q) => $q
                        ->where('driver_id', $user->id)
                        ->whereDate('start_date', today())
                    )
                    ->count(),
            ])
            ->toArray();
    }

    /**
     * Obtiene los manifiestos creados hoy con sus relaciones.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getManifiestosHoy(): array
    {
        return Manifest::with(['messenger:id,name', 'status'])
            ->withCount('items')
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Manifest $manifest) => [
                'id'          => $manifest->id,
                'messenger'   => $manifest->messenger
                    ? ['id' => $manifest->messenger->id, 'full_name' => $manifest->messenger->name]
                    : null,
                'total_items' => $manifest->items_count,
                'status'      => $manifest->status?->valor ?? 'Preparando',
                'created_at'  => $manifest->created_at,
            ])
            ->toArray();
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