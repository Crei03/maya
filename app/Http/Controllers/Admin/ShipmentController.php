<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\ShipmentHasRelationsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterShipmentRequest;
use App\Http\Requests\Admin\StoreShipmentRequest;
use App\Http\Requests\Admin\UpdateShipmentRequest;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ShipmentService $service,
    ) {}

    /**
     * Listar envíos con paginación y filtros.
     */
    public function list(FilterShipmentRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Shipment::class);

        $result = $this->service->list($request->validated());

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Mostrar un envío con relaciones.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $shipmentData = $this->service->show($id);

            $shipment = Shipment::query()->findOrFail($id);
            $this->authorize('view', $shipment);

            return response()->json([
                'success' => true,
                'data'    => $shipmentData,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Paquete no encontrado',
            ], 404);
        }
    }

    /**
     * Crear un nuevo envío.
     */
    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $this->authorize('create', Shipment::class);

        $shipment = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'data'    => $shipment,
            'message' => 'Paquete creado exitosamente',
        ], 201);
    }

    /**
     * Actualizar un envío existente (parcial).
     */
    public function update(UpdateShipmentRequest $request, string $id): JsonResponse
    {
        try {
            $updated = $this->service->update($id, $request->validated());

            $this->authorize('update', $updated);

            return response()->json([
                'success' => true,
                'data'    => $updated,
                'message' => 'Paquete actualizado exitosamente',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Paquete no encontrado',
            ], 404);
        }
    }

    /**
     * Eliminar un envío sin relaciones.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Paquete no encontrado',
            ], 404);
        } catch (ShipmentHasRelationsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Paquete eliminado exitosamente',
        ]);
    }
}
