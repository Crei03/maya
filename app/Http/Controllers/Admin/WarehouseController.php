<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WarehouseStoreRequest;
use App\Http\Requests\Admin\WarehouseUpdateRequest;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService
    ) {
    }

    /**
     * Render the Bodegas configuration page.
     */
    public function page(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Admin/Configuracion/Bodegas');
    }

    /**
     * List warehouses with optional filters.
     */
    public function list(Request $request): JsonResponse
    {
        $warehouses = $this->warehouseService->paginate(
            $request->only(['search', 'is_active', 'has_shipments']),
            (int) ($request->input('per_page', 15))
        );

        return response()->json([
            'success' => true,
            'data'    => $warehouses->through(fn ($w) => $this->warehouseService->mapWarehouse($w)),
        ]);
    }

    /**
     * Show a single warehouse.
     */
    public function show(string $id): JsonResponse
    {
        $warehouse = $this->warehouseService->find($id);

        return response()->json([
            'success' => true,
            'data'    => $this->warehouseService->mapWarehouse($warehouse),
        ]);
    }

    /**
     * Create a new warehouse.
     */
    public function store(WarehouseStoreRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bodega creada correctamente.',
            'data'    => $this->warehouseService->mapWarehouse($warehouse),
        ], 201);
    }

    /**
     * Update an existing warehouse.
     */
    public function update(WarehouseUpdateRequest $request, string $warehouse): JsonResponse
    {
        $updated = $this->warehouseService->update($warehouse, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Bodega actualizada correctamente.',
            'data'    => $this->warehouseService->mapWarehouse($updated),
        ]);
    }

    /**
     * Soft-delete a warehouse.
     */
    public function destroy(string $warehouse): JsonResponse
    {
        $this->warehouseService->delete($warehouse);

        return response()->json([
            'success' => true,
            'message' => 'Bodega eliminada correctamente.',
        ]);
    }
}
