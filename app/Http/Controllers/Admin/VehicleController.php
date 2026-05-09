<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VehicleStoreRequest;
use App\Http\Requests\Admin\VehicleUpdateRequest;
use App\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService
    ) {
    }

    /**
     * Render the Transportes configuration page.
     */
    public function page(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Admin/Configuracion/Transportes');
    }

    /**
     * List vehicles with optional filters.
     */
    public function list(Request $request): JsonResponse
    {
        $vehicles = $this->vehicleService->paginate(
            $request->only(['search', 'type', 'is_active']),
            (int) ($request->input('per_page', 15))
        );

        return response()->json([
            'success' => true,
            'data'    => $vehicles->through(fn ($v) => $this->vehicleService->mapVehicle($v)),
        ]);
    }

    /**
     * Show a single vehicle.
     */
    public function show(string $id): JsonResponse
    {
        $vehicle = $this->vehicleService->find($id);

        return response()->json([
            'success' => true,
            'data'    => $this->vehicleService->mapVehicle($vehicle),
        ]);
    }

    /**
     * Create a new vehicle.
     */
    public function store(VehicleStoreRequest $request): JsonResponse
    {
        $vehicle = $this->vehicleService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vehículo creado correctamente.',
            'data'    => $this->vehicleService->mapVehicle($vehicle),
        ], 201);
    }

    /**
     * Update an existing vehicle.
     */
    public function update(VehicleUpdateRequest $request, string $vehicle): JsonResponse
    {
        $updated = $this->vehicleService->update($vehicle, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vehículo actualizado correctamente.',
            'data'    => $this->vehicleService->mapVehicle($updated),
        ]);
    }

    /**
     * Soft-delete a vehicle.
     */
    public function destroy(string $vehicle): JsonResponse
    {
        $this->vehicleService->delete($vehicle);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo eliminado correctamente.',
        ]);
    }
}
