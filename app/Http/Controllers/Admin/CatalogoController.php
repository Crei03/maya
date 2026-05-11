<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreCatalogoValorRequest;
use App\Http\Requests\Management\UpdateCatalogoValorRequest;
use App\Models\Catalogo;
use App\Models\CatalogoValor;
use Illuminate\Http\JsonResponse;
use Spatie\Multitenancy\Models\Tenant;

class CatalogoController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $catalogos = Catalogo::query()
            ->where(function ($q) use ($tenantId) {
                $q->where('is_global', true)
                    ->orWhere('tenant_id', $tenantId);
            })
            ->withCount('valores')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug', 'is_global']);

        return response()->json([
            'success' => true,
            'data' => $catalogos,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $catalogo = Catalogo::query()
            ->where('slug', $slug)
            ->where(function ($q) use ($tenantId) {
                $q->where('is_global', true)
                    ->orWhere('tenant_id', $tenantId);
            })
            ->firstOrFail();

        $valores = CatalogoValor::query()
            ->where('catalogo_id', $catalogo->id)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('sort_order')
            ->orderBy('valor')
            ->get(['id', 'codigo', 'valor', 'descripcion', 'tenant_id', 'is_global', 'sort_order', 'is_active', 'parent_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'catalogo' => $catalogo,
                'valores' => $valores,
            ],
        ]);
    }

    public function store(StoreCatalogoValorRequest $request): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $data = $request->validated();

        // Heredar ámbito del catálogo padre
        $catalogo = Catalogo::query()->findOrFail($data['catalogo_id']);
        $data['is_global'] = $catalogo->is_global;
        $data['tenant_id'] = $catalogo->is_global ? null : $tenantId;

        $exists = CatalogoValor::query()
            ->where('catalogo_id', $data['catalogo_id'])
            ->where('codigo', $data['codigo'])
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un valor con este código en este catálogo.',
            ], 422);
        }

        $valor = CatalogoValor::query()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Valor creado correctamente.',
            'data' => $valor,
        ], 201);
    }

    public function update(UpdateCatalogoValorRequest $request, int $id): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $valor = CatalogoValor::query()->findOrFail($id);

        if ($valor->tenant_id !== null && $valor->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para modificar este valor.',
            ], 403);
        }

        $valor->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Valor actualizado correctamente.',
            'data' => $valor,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $valor = CatalogoValor::query()->findOrFail($id);

        if ($valor->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar este valor.',
            ], 403);
        }

        $valor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Valor eliminado correctamente.',
        ]);
    }
}
