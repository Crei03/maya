<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ClientService
{
    private ?array $paCatalog = null;

    /**
     * Paginate clients with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $cliente = trim((string) ($filters['cliente'] ?? $filters['search'] ?? ''));

        $query = Client::query()
            ->with(['residencia:id,valor', 'provincia:id,valor', 'distrito:id,valor', 'corregimiento:id,valor', 'calle:id,valor'])
            ->when(
                $cliente !== '',
                function ($builder) use ($cliente) {
                    $builder->where(function ($subQuery) use ($cliente) {
                        $subQuery->where('first_name', 'like', "%{$cliente}%")
                            ->orWhere('last_name', 'like', "%{$cliente}%")
                            ->orWhere('full_name', 'like', "%{$cliente}%")
                            ->orWhere('email', 'like', "%{$cliente}%");
                    });
                }
            )
            ->when(filled($filters['residencia_id'] ?? null), fn ($builder) => $builder->where('residencia_id', $filters['residencia_id']))
            ->when(filled($filters['provincia_id'] ?? null), fn ($builder) => $builder->where('provincia_id', $filters['provincia_id']))
            ->when(filled($filters['distrito_id'] ?? null), fn ($builder) => $builder->where('distrito_id', $filters['distrito_id']))
            ->when(filled($filters['corregimiento_id'] ?? null), fn ($builder) => $builder->where('corregimiento_id', $filters['corregimiento_id']))
            ->when(filled($filters['calle'] ?? null), fn ($builder) => $builder->where('street_name', 'like', '%'.trim((string) $filters['calle']).'%'))
            ->when(filled($filters['numero'] ?? null), fn ($builder) => $builder->where('street_number', 'like', '%'.trim((string) $filters['numero']).'%'))
            ->when(filled($filters['codigo_postal'] ?? null), fn ($builder) => $builder->where('postal_code', 'like', '%'.trim((string) $filters['codigo_postal']).'%'))
            ->orderByDesc('created_at');

        return $query->paginate($perPage)->through(fn (Client $client) => $this->mapClient($client));
    }

    /**
     * Get one client.
     */
    public function find(string $id): Client
    {
        return Client::query()
            ->with(['residencia:id,valor', 'provincia:id,valor', 'distrito:id,valor', 'corregimiento:id,valor', 'calle:id,valor'])
            ->findOrFail($id);
    }

    /**
     * Create a client record.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Client
    {
        $client = Client::query()->create([
            'first_name' => $data['nombre'],
            'last_name' => $data['apellido'],
            'full_name' => trim($data['nombre'].' '.$data['apellido']),
            'email' => $this->generateClientEmail((string) $data['nombre'], (string) $data['apellido']),
            'password' => null,
            'residencia_id' => $data['residencia_id'] ?? null,
            'provincia_id' => $data['provincia_id'] ?? null,
            'distrito_id' => $data['distrito_id'] ?? null,
            'corregimiento_id' => $data['corregimiento_id'] ?? null,
            'street_name' => $data['calle'] ?? null,
            'street_number' => $data['numero'],
            'postal_code' => $data['codigo_postal'] ?? null,
            'status' => 'active',
        ]);

        return $this->find($client->id);
    }

    /**
     * Update a client record.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Client
    {
        $client = Client::query()->findOrFail($id);

        $client->fill([
            'first_name' => $data['nombre'],
            'last_name' => $data['apellido'],
            'full_name' => trim($data['nombre'].' '.$data['apellido']),
            'residencia_id' => $data['residencia_id'] ?? null,
            'provincia_id' => $data['provincia_id'] ?? null,
            'distrito_id' => $data['distrito_id'] ?? null,
            'corregimiento_id' => $data['corregimiento_id'] ?? null,
            'street_name' => $data['calle'] ?? null,
            'street_number' => $data['numero'],
            'postal_code' => $data['codigo_postal'] ?? null,
        ]);

        $client->save();

        return $this->find($client->id);
    }

    /**
     * Delete a client record.
     */
    public function delete(string $id): bool
    {
        $client = Client::query()->findOrFail($id);

        return (bool) $client->delete();
    }

    /**
     * Return catalog values for selects.
     */
    public function getCatalogValues(string $slug, ?int $parentId = null)
    {
        $catalogo = Catalogo::query()->where('slug', $slug)->firstOrFail();

        return CatalogoValor::query()
            ->where('catalogo_id', $catalogo->id)
            ->when($parentId !== null, fn ($builder) => $builder->where('parent_id', $parentId))
            ->orderBy('valor')
            ->get(['id', 'codigo', 'valor', 'parent_id']);
    }

    /**
     * Return full geography hierarchy used by client form.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaHierarchy(): array
    {
        return $this->loadPaCatalog();
    }

    /**
     * Map a client record to the API shape used by the frontend.
     *
     * @return array<string, mixed>
     */
    public function mapClient(Client $client): array
    {
        $fullName = $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? ''));

        return [
            'id' => $client->id,
            'cliente' => $fullName,
            'full_name' => $fullName,
            'nombre' => $client->first_name,
            'apellido' => $client->last_name,
            'residencia' => $this->residenceLabel((int) ($client->residencia_id ?? 0)),
            'provincia' => $this->provinceLabel((int) ($client->provincia_id ?? 0)),
            'distrito' => $this->districtLabel((int) ($client->provincia_id ?? 0), (int) ($client->distrito_id ?? 0)),
            'corregimiento' => $this->corregimientoLabel(
                (int) ($client->provincia_id ?? 0),
                (int) ($client->distrito_id ?? 0),
                (int) ($client->corregimiento_id ?? 0)
            ),
            'calle' => $client->street_name,
            'numero' => $client->street_number,
            'codigo_postal' => $client->postal_code,
        ];
    }

    private function residenceLabel(int $id): ?string
    {
        return match ($id) {
            1 => 'Casa',
            2 => 'Apartamento',
            default => null,
        };
    }

    private function provinceLabel(int $id): ?string
    {
        foreach ($this->loadPaCatalog() as $province) {
            if ((int) $province['id_provincia'] === $id) {
                return $province['provincia'];
            }
        }

        return null;
    }

    private function districtLabel(int $provinceId, int $districtId): ?string
    {
        foreach ($this->loadPaCatalog() as $province) {
            if ((int) $province['id_provincia'] !== $provinceId) {
                continue;
            }

            foreach ($province['distritos'] ?? [] as $district) {
                if ((int) $district['id_distrito'] === $districtId) {
                    return $district['distrito'];
                }
            }
        }

        return null;
    }

    private function corregimientoLabel(int $provinceId, int $districtId, int $corregimientoId): ?string
    {
        foreach ($this->loadPaCatalog() as $province) {
            if ((int) $province['id_provincia'] !== $provinceId) {
                continue;
            }

            foreach ($province['distritos'] ?? [] as $district) {
                if ((int) $district['id_distrito'] !== $districtId) {
                    continue;
                }

                foreach ($district['corregimientos'] ?? [] as $corregimiento) {
                    if ((int) $corregimiento['id_corregimiento'] === $corregimientoId) {
                        return $corregimiento['corregimiento'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadPaCatalog(): array
    {
        if ($this->paCatalog !== null) {
            return $this->paCatalog;
        }

        $path = storage_path('app/public/PA.json');

        if (! File::exists($path)) {
            return $this->paCatalog = [];
        }

        return $this->paCatalog = json_decode(File::get($path), true) ?: [];
    }

    /**
     * Generate a stable-looking client email.
     */
    private function generateClientEmail(string $firstName, string $lastName): string
    {
        $base = Str::lower(Str::slug($firstName.'.'.$lastName, '.'));

        return sprintf('cliente.%s.%s@maya.local', $base, Str::lower(Str::random(8)));
    }
}
