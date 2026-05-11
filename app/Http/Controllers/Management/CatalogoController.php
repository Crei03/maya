<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreCatalogoRequest;
use App\Http\Requests\Management\StoreCatalogoValorRequest;
use App\Http\Requests\Management\UpdateCatalogoRequest;
use App\Http\Requests\Management\UpdateCatalogoValorRequest;
use App\Models\Catalogo;
use App\Models\CatalogoValor;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoController extends Controller
{
    public function index(Request $request): Response
    {
        $catalogos = Catalogo::query()
            ->withCount('valores')
            ->with('creador:id,name')
            ->when($request->search, fn ($q, $search) => $q->where('nombre', 'like', "%{$search}%"))
            ->when($request->is_global !== null, fn ($q) => $q->where('is_global', $request->boolean('is_global')))
            ->when($request->tenant_id, fn ($q, $tenantId) => $q->where('tenant_id', $tenantId))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Management/Catalogos/Index', [
            'catalogos' => $catalogos,
            'filters' => $request->only(['search', 'is_global', 'tenant_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Management/Catalogos/Create', [
            'tenants' => Tenant::query()->select('id', 'name', 'slug')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCatalogoRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $catalogo = Catalogo::query()->create($data);

        return redirect()->route('Management.catalogos.index')
            ->with('success', "Catálogo '{$catalogo->nombre}' creado exitosamente.");
    }

    public function show(Catalogo $catalogo): Response
    {
        $catalogo->load('creador:id,name');

        $valores = CatalogoValor::query()
            ->where('catalogo_id', $catalogo->id)
            ->with('tenant:id,name')
            ->orderBy('sort_order')
            ->orderBy('valor')
            ->paginate(20);

        return Inertia::render('Management/Catalogos/Show', [
            'catalogo' => $catalogo,
            'valores' => $valores,
        ]);
    }

    public function edit(Catalogo $catalogo): Response
    {
        $catalogo->load('valores');

        return Inertia::render('Management/Catalogos/Edit', [
            'catalogo' => $catalogo,
            'tenants' => Tenant::query()->select('id', 'name', 'slug')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCatalogoRequest $request, Catalogo $catalogo)
    {
        $catalogo->update($request->validated());

        return redirect()->route('Management.catalogos.index')
            ->with('success', "Catálogo '{$catalogo->nombre}' actualizado exitosamente.");
    }

    public function destroy(Catalogo $catalogo): JsonResponse
    {
        if ($catalogo->valores()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el catálogo porque tiene valores asociados.',
            ], 422);
        }

        $catalogo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catálogo eliminado exitosamente.',
        ]);
    }

    public function storeValor(StoreCatalogoValorRequest $request, Catalogo $catalogo)
    {
        $data = $request->validated();

        // Heredar ámbito del catálogo si no se especifica
        if (! isset($data['is_global'])) {
            $data['is_global'] = $catalogo->is_global;
        }
        if (! isset($data['tenant_id'])) {
            $data['tenant_id'] = $catalogo->tenant_id;
        }
        // Si es global, limpiar tenant_id
        if ($data['is_global']) {
            $data['tenant_id'] = null;
        }

        $valor = $catalogo->valores()->create($data);

        return redirect()->back()
            ->with('success', "Valor '{$valor->valor}' creado exitosamente.");
    }

    public function updateValor(UpdateCatalogoValorRequest $request, Catalogo $catalogo, CatalogoValor $valor)
    {
        $valor->update($request->validated());

        return redirect()->back()
            ->with('success', "Valor '{$valor->valor}' actualizado exitosamente.");
    }

    public function destroyValor(Catalogo $catalogo, CatalogoValor $valor)
    {
        $valor->delete();

        return redirect()->back()
            ->with('success', 'Valor eliminado exitosamente.');
    }
}
