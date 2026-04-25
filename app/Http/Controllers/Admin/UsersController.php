<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterUserRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Services\UsersService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function __construct(
        private readonly UsersService $usersService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Configuracion/Users');
    }

    public function list(FilterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $users = $this->usersService->paginate($validated, (int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->usersService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'data' => $this->usersService->mapUser($user),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->usersService->find($id);

        return response()->json([
            'success' => true,
            'data' => $this->usersService->mapUser($user),
        ]);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = $this->usersService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => $this->usersService->mapUser($user),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->usersService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
