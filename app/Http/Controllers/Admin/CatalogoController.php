<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreCatalogoValorRequest;
use App\Http\Requests\Management\UpdateCatalogoValorRequest;
use App\Models\Catalogo;
use App\Models\CatalogoValor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Multitenancy\Models\Tenant;

class CatalogoController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $catalogos = Catalogo::query()
            ->visibleByTenant($tenantId)
            ->withCount('valores')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug', 'is_global', 'tenant_id', 'description']);

        return response()->json([
            'success' => true,
            'data' => $catalogos,
        ]);
    }

    public function storeCatalogo(Request $request): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:catalogos,nombre'],
            'slug' => ['required', 'string', 'max:100', 'unique:catalogos,slug', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'nombre.required' => 'El nombre del catálogo es obligatorio.',
            'nombre.unique' => 'Ya existe un catálogo con este nombre.',
            'slug.required' => 'El slug del catálogo es obligatorio.',
            'slug.unique' => 'Ya existe un catálogo con este slug.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
        ]);

        $catalogo = Catalogo::query()->create([
            'nombre' => $validated['nombre'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => false,
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catálogo creado correctamente.',
            'data' => $catalogo,
        ], 201);
    }

    public function updateCatalogo(Request $request, int $id): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $catalogo = Catalogo::query()
            ->visibleByTenant($tenantId)
            ->findOrFail($id);

        if ($catalogo->tenant_id !== null && $catalogo->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para modificar este catálogo.',
            ], 403);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('catalogos', 'nombre')->ignore($id)],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'nombre.required' => 'El nombre del catálogo es obligatorio.',
            'nombre.unique' => 'Ya existe un catálogo con este nombre.',
        ]);

        $catalogo->update([
            'nombre' => $validated['nombre'],
            'description' => $validated['description'] ?? $catalogo->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catálogo actualizado correctamente.',
            'data' => $catalogo,
        ]);
    }

    public function destroyCatalogo(int $id): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $catalogo = Catalogo::query()
            ->visibleByTenant($tenantId)
            ->findOrFail($id);

        if ($catalogo->scope === Catalogo::SCOPE_SAAS && ! auth()->user()?->isManagement()) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden eliminar catálogos de configuración SaaS.',
            ], 403);
        }

        if ($catalogo->tenant_id !== null && $catalogo->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar este catálogo.',
            ], 403);
        }

        try {
            DB::transaction(function () use ($catalogo) {
                $catalogo->valores()->delete();
                $catalogo->delete();
            });
        } catch (\Illuminate\Database\QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el catálogo porque tiene valores que están siendo utilizados en el sistema.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Catálogo eliminado correctamente.',
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $tenantId = Tenant::current()?->id;

        $catalogo = Catalogo::query()
            ->visibleByTenant($tenantId)
            ->where('slug', $slug)
            ->firstOrFail();

        $valores = CatalogoValor::query()
            ->where('catalogo_id', $catalogo->id)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('sort_order')
            ->orderBy('valor')
            ->get(['id', 'codigo', 'valor', 'descripcion', 'metadata', 'tenant_id', 'is_global', 'sort_order', 'is_active', 'parent_id']);

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

        $valor = CatalogoValor::query()->with('catalogo')->findOrFail($id);

        if ($valor->catalogo && $valor->catalogo->scope === Catalogo::SCOPE_SAAS) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar valores de este catálogo.',
            ], 403);
        }

        if ($valor->tenant_id !== null && $valor->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar este valor.',
            ], 403);
        }

        try {
            $valor->delete();
        } catch (\Illuminate\Database\QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el valor porque está siendo utilizado en el sistema.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Valor eliminado correctamente.',
        ]);
    }
}
