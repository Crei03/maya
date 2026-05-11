<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterDriverRequest;
use App\Http\Requests\Admin\StoreDriverRequest;
use App\Http\Requests\Admin\UpdateDriverRequest;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService
    ) {
    }

    /**
     * Listar conductores con filtros opcionales.
     */
    public function list(FilterDriverRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $drivers = $this->driverService->paginate($validated, (int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data'    => $drivers,
        ]);
    }

    /**
     * Detalle de un conductor con su perfil.
     */
    public function show(string $id): JsonResponse
    {
        $driver = $this->driverService->find($id);

        return response()->json([
            'success' => true,
            'data'    => $this->driverService->mapDriver($driver),
        ]);
    }

    /**
     * Crear un nuevo conductor (User + DriverProfile).
     */
    public function store(StoreDriverRequest $request): JsonResponse
    {
        $driver = $this->driverService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Conductor creado correctamente.',
            'data'    => $this->driverService->mapDriver($driver),
        ], 201);
    }

    /**
     * Actualizar un conductor existente.
     */
    public function update(UpdateDriverRequest $request, string $driver): JsonResponse
    {
        $driver = $this->driverService->update($driver, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Conductor actualizado correctamente.',
            'data'    => $this->driverService->mapDriver($driver),
        ]);
    }

    /**
     * Eliminar un conductor.
     */
    public function destroy(string $driver): JsonResponse
    {
        $this->driverService->delete($driver);

        return response()->json([
            'success' => true,
            'message' => 'Conductor eliminado correctamente.',
        ]);
    }
}
