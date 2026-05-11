<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DriverProfile;
use App\Models\ShipmentTask;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Multitenancy\Models\Tenant;

class DriverService
{
    /**
     * Lista conductores (usuarios con role=messenger) con su perfil y conteo de tareas activas.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        $query = User::query()
            ->where('role', User::ROLE_MESSENGER)
            ->whereHas('driverProfile') // Only users that actually have a driver profile
            ->where('tenant_id', Tenant::current()?->id)
            ->with('driverProfile')
            ->withCount([
                'shipmentTasks as active_tasks_count' => function ($q) {
                    $q->whereIn('status', ['pending', 'in_progress']);
                },
            ])
            ->when(
                $search !== '',
                function ($builder) use ($search) {
                    $builder->where(function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                isset($filters['is_available']),
                function ($builder) use ($filters) {
                    $available = filter_var($filters['is_available'], FILTER_VALIDATE_BOOLEAN);
                    $builder->whereHas('driverProfile', function ($q) use ($available) {
                        $q->where('is_available', $available);
                    });
                }
            )
            ->when(
                isset($filters['status']),
                function ($builder) use ($filters) {
                    $status = filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN);
                    $builder->where('status', $status);
                }
            )
            ->orderByDesc('created_at');

        return $query->paginate($perPage)->through(fn (User $user) => $this->mapDriver($user));
    }

    /**
     * Encuentra un conductor por ID (solo role=messenger).
     */
    public function find(int|string $id): User
    {
        return User::query()
            ->where('role', User::ROLE_MESSENGER)
            ->with('driverProfile')
            ->withCount([
                'shipmentTasks as active_tasks_count' => function ($q) {
                    $q->whereIn('status', ['pending', 'in_progress']);
                },
            ])
            ->findOrFail($id);
    }

    /**
     * Crea un nuevo conductor (User + DriverProfile).
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->findOrFail($data['user_id']);

            // Set role to messenger
            $user->role = User::ROLE_MESSENGER;
            $user->save();

            // Create or update driver profile
            $profile = $user->driverProfile;
            $profileData = [
                'phone'             => $data['phone'] ?? null,
                'license_number'    => $data['license_number'] ?? null,
                'license_expiry'    => $data['license_expiry'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_phone'   => $data['emergency_phone'] ?? null,
                'is_available'      => $data['is_available'] ?? true,
            ];

            if ($profile) {
                $profile->fill($profileData);
                $profile->save();
            } else {
                DriverProfile::query()->create(array_merge(
                    ['user_id' => $user->id],
                    $profileData
                ));
            }

            return $this->find($user->id);
        });
    }

    /**
     * Actualiza un conductor existente (User + DriverProfile).
     */
    public function update(int|string $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->find($id);

            // Update only status on the user
            if (array_key_exists('status', $data)) {
                $user->status = $data['status'];
                $user->save();
            }

            // Update profile fields
            $profileData = [];
            $profileFields = ['phone', 'license_number', 'license_expiry', 'emergency_contact', 'emergency_phone', 'is_available'];

            foreach ($profileFields as $field) {
                if (array_key_exists($field, $data)) {
                    $profileData[$field] = $data[$field];
                }
            }

            if ($profileData !== []) {
                $profile = $user->driverProfile;

                if ($profile) {
                    $profile->fill($profileData);
                    $profile->save();
                } else {
                    DriverProfile::query()->create(array_merge(
                        ['user_id' => $user->id],
                        $profileData
                    ));
                }
            }

            return $this->find($user->id);
        });
    }

    /**
     * Elimina un conductor (User + DriverProfile en cascada).
     */
    public function delete(int|string $id): bool
    {
        $user = User::query()
            ->where('role', User::ROLE_MESSENGER)
            ->findOrFail($id);

        return (bool) $user->delete();
    }

    /**
     * Mapea un User (messenger) a array para respuesta API.
     */
    public function mapDriver(User $user): array
    {
        $profile = $user->driverProfile;

        return [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'phone'               => $profile?->phone,
            'role'                => $user->role,
            'status'              => $user->status,
            'license_number'      => $profile?->license_number,
            'license_expiry'      => $profile?->license_expiry?->toDateString(),
            'emergency_contact'   => $profile?->emergency_contact,
            'emergency_phone'     => $profile?->emergency_phone,
            'is_available'        => $profile?->is_available ?? true,
            'active_tasks_count'  => (int) ($user->active_tasks_count ?? 0),
            'created_at'          => $user->created_at?->toISOString(),
        ];
    }
}
