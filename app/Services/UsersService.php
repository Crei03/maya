<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UsersService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        $query = User::query()
            ->when(
                $search !== '',
                function ($builder) use ($search) {
                    $builder->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            )
            ->orderByDesc('created_at');

        return $query->paginate($perPage)->through(fn (User $user) => $this->mapUser($user));
    }

    public function find(string $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function create(array $data): User
    {
        $role = $data['role'] ?? User::ROLE_GESTOR;

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $role,
            'status' => $data['status'] ?? true,
        ]);

        return $this->find($user->id);
    }

    public function update(string $id, array $data): User
    {
        $user = User::query()->findOrFail($id);

        $fillData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ];

        if (!empty($data['password'])) {
            $fillData['password'] = Hash::make($data['password']);
        }

        $user->fill($fillData);
        $user->save();

        return $this->find($user->id);
    }

    public function delete(string $id): bool
    {
        $user = User::query()->findOrFail($id);

        return (bool) $user->delete();
    }

    public function mapUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at?->toISOString(),
        ];
    }
}
