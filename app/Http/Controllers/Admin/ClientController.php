<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterClientRequest;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService
    ) {}

    /**
     * Render the clients directory page.
     */
    public function page(): Response
    {
        return Inertia::render('Admin/Clientes/Index');
    }

    public function index(): Response
    {
        return $this->page();
    }

    /**
     * List clients with optional filters.
     */
    public function list(FilterClientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $clients = $this->clientService->paginate($validated, (int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    /**
     * Create a new client.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado correctamente.',
            'data' => $this->clientService->mapClient($client),
        ], 201);
    }

    /**
     * Show a client.
     */
    public function show(string $id): JsonResponse
    {
        $client = $this->clientService->find($id);

        return response()->json([
            'success' => true,
            'data' => $this->clientService->mapClient($client),
        ]);
    }

    /**
     * Update a client.
     */
    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        $client = $this->clientService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado correctamente.',
            'data' => $this->clientService->mapClient($client),
        ]);
    }

    /**
     * Delete a client.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->clientService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }

    /**
     * Predictive search for clients (autocomplete).
     */
    public function search(Request $request): JsonResponse
    {
        $term = (string) ($request->input('q') ?? $request->input('search') ?? '');
        $limit = $request->integer('limit') ?: 20;

        $results = $this->clientService->search($term, $limit);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get delivery history for a client.
     */
    public function history(string $id): JsonResponse
    {
        $data = $this->clientService->deliveryHistory($id);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Return catalog values.
     */
    public function catalogValues(Request $request, string $slug): JsonResponse
    {
        $values = $this->clientService->getCatalogValues(
            $slug,
            $request->integer('parent_id') ?: null
        );

        return response()->json([
            'success' => true,
            'data' => $values,
        ]);
    }

    /**
     * Return Panama province/district/corregimiento hierarchy.
     */
    public function paHierarchy(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->clientService->getPaHierarchy(),
        ]);
    }
}
