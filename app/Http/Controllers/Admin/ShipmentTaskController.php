<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterShipmentTaskRequest;
use App\Http\Requests\Admin\ReorderShipmentTaskItemsRequest;
use App\Http\Requests\Admin\StoreShipmentTaskWizardRequest;
use App\Services\ShipmentTaskService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentTaskController extends Controller
{
    public function __construct(
        private readonly ShipmentTaskService $service,
    ) {}

    /**
     * Renderiza la pantalla principal de Planes de Entrega.
     */
    public function page(): Response
    {
        return Inertia::render('Admin/PlanesEntrega/Index');
    }

    /**
     * Listado paginado de planes de entrega con filtros.
     */
    public function list(FilterShipmentTaskRequest $request): JsonResponse
    {
        $result = $this->service->list($request->validated());

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Retorna el siguiente código secuencial disponible (PLE-YYYY-MM-XXXX).
     */
    public function nextCode(): JsonResponse
    {
        $code = $this->service->getNextCode();

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $code,
            ],
        ]);
    }

    /**
     * Crea un nuevo plan de entrega desde el Wizard.
     */
    public function store(StoreShipmentTaskWizardRequest $request): JsonResponse
    {
        $task = $this->service->createFromWizard($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Plan de entrega creado exitosamente en estado pendiente.',
            'data' => $this->service->mapTask($task, true),
        ], 201);
    }

    /**
     * Muestra el detalle completo de un plan de entrega.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $taskData = $this->service->show($id);

            return response()->json([
                'success' => true,
                'data' => $taskData,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Plan de entrega no encontrado.',
            ], 404);
        }
    }

    /**
     * Reordena las paradas de un plan de entrega validando prioridades.
     */
    public function reorder(ReorderShipmentTaskItemsRequest $request, string $id): JsonResponse
    {
        try {
            $task = $this->service->reorderItems($id, $request->validated()['items']);

            return response()->json([
                'success' => true,
                'message' => 'Paradas reordenadas exitosamente.',
                'data' => $this->service->mapTask($task, true),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Plan de entrega no encontrado.',
            ], 404);
        }
    }

    /**
     * Inicia el plan de entrega pasando a in_progress y sus envíos a in_transit.
     */
    public function start(string $id): JsonResponse
    {
        try {
            $task = $this->service->startTask($id);

            return response()->json([
                'success' => true,
                'message' => 'Plan de entrega iniciado correctamente.',
                'data' => $this->service->mapTask($task, true),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Plan de entrega no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Finaliza el plan de entrega, calcula horas y retorna paquetes no entregados.
     */
    public function complete(string $id): JsonResponse
    {
        try {
            $task = $this->service->completeTask($id);

            return response()->json([
                'success' => true,
                'message' => 'Plan de entrega finalizado exitosamente.',
                'data' => $this->service->mapTask($task, true),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Plan de entrega no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Cancela el plan de entrega y reingresa envíos a bodega.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $task = $this->service->cancelTask($id, $validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Plan de entrega cancelado.',
                'data' => $this->service->mapTask($task, true),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Plan de entrega no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Actualiza el estado de una parada individual (entregado, retornado).
     */
    public function updateItemStatus(Request $request, string $id, string $itemId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:entregado,retornado,pendiente',
            'return_reason' => 'nullable|string|max:500',
        ]);

        try {
            $item = $this->service->updateItemStatus(
                $id,
                $itemId,
                $validated['status'],
                $validated['return_reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado de parada actualizado exitosamente.',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Parada o plan no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Asigna paquetes de bodega a un plan pendiente existente.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'required|string',
            'priority' => 'nullable|string|in:alta,media,baja',
        ]);

        try {
            $task = $this->service->assignShipments(
                $id,
                $validated['shipment_ids'],
                $validated['priority'] ?? 'media'
            );

            return response()->json([
                'success' => true,
                'message' => 'Paquetes asignados exitosamente.',
                'data' => $this->service->mapTask($task, true),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Plan de entrega no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Desasigna un paquete de un plan pendiente existente.
     */
    public function unassign(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id' => 'required|string',
        ]);

        try {
            $task = $this->service->unassignShipment($id, $validated['shipment_id']);

            return response()->json([
                'success' => true,
                'message' => 'Paquete desasignado exitosamente.',
                'data' => $this->service->mapTask($task, true),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Plan de entrega no encontrado.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
