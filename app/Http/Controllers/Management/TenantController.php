<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreTenantRequest;
use App\Http\Requests\Management\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {
    }

    public function index(Request $request): Response
    {
        $tenants = Tenant::query()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Management/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Management/Tenants/Create');
    }

    public function store(StoreTenantRequest $request)
    {
        $tenant = $this->tenantService->create($request->validated());

        return redirect()->route('Management.tenants.index')
            ->with('success', "Paquetería '{$tenant->name}' creada exitosamente.");
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->loadCount(['users', 'clients', 'shipments']);

        return Inertia::render('Management/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Management/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $this->tenantService->update($tenant, $request->validated());

        return redirect()->route('Management.tenants.index')
            ->with('success', "Paquetería '{$tenant->name}' actualizada exitosamente.");
    }

    public function toggleStatus(Tenant $tenant)
    {
        $newStatus = $tenant->isActive() ? 'paused' : 'active';
        $this->tenantService->updateStatus($tenant, $newStatus);

        $action = $newStatus === 'active' ? 'resumed' : 'paused';

        return redirect()->route('Management.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' has been {$action}.");
    }
}
