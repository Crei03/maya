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
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? $filters['cliente'] ?? ''));
        $phone = trim((string) ($filters['phone'] ?? ''));

        $query = Client::query()
            ->withCount('shipments')
            ->with(['residencia:id,valor', 'provincia:id,valor', 'distrito:id,valor', 'corregimiento:id,valor', 'calle:id,valor'])
            ->when($search !== '', fn ($builder) => $builder->search($search))
            ->when($phone !== '', fn ($builder) => $builder->where('phone', 'like', "%{$phone}%"))
            ->when(filled($filters['status'] ?? null), fn ($builder) => $builder->where('status', $filters['status']))
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
            ->withCount('shipments')
            ->with(['residencia:id,valor', 'provincia:id,valor', 'distrito:id,valor', 'corregimiento:id,valor', 'calle:id,valor'])
            ->findOrFail($id);
    }

    /**
     * Create a client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client
    {
        $firstName = $data['first_name'] ?? $data['nombre'] ?? '';
        $lastName = $data['last_name'] ?? $data['apellido'] ?? '';
        $fullName = ! empty($data['full_name']) ? $data['full_name'] : trim($firstName.' '.$lastName);

        $email = ! empty($data['email'])
            ? trim((string) $data['email'])
            : $this->generateClientEmail((string) $firstName, (string) $lastName);

        $client = Client::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'phone' => $data['phone'] ?? null,
            'email' => $email,
            'password' => null,
            'residencia_id' => $data['residencia_id'] ?? null,
            'provincia_id' => $data['provincia_id'] ?? null,
            'distrito_id' => $data['distrito_id'] ?? null,
            'corregimiento_id' => $data['corregimiento_id'] ?? null,
            'street_name' => $data['street_name'] ?? $data['calle'] ?? null,
            'street_number' => $data['street_number'] ?? $data['numero'] ?? 'S/N',
            'reference_point' => $data['reference_point'] ?? null,
            'destination_coords' => $data['destination_coords'] ?? null,
            'postal_code' => $data['postal_code'] ?? $data['codigo_postal'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->find($client->id);
    }

    /**
     * Update a client record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Client
    {
        $client = Client::query()->findOrFail($id);

        $firstName = $data['first_name'] ?? $data['nombre'] ?? $client->first_name;
        $lastName = $data['last_name'] ?? $data['apellido'] ?? $client->last_name;
        $fullName = $data['full_name'] ?? trim($firstName.' '.$lastName);

        $fields = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'residencia_id' => array_key_exists('residencia_id', $data) ? $data['residencia_id'] : $client->residencia_id,
            'provincia_id' => array_key_exists('provincia_id', $data) ? $data['provincia_id'] : $client->provincia_id,
            'distrito_id' => array_key_exists('distrito_id', $data) ? $data['distrito_id'] : $client->distrito_id,
            'corregimiento_id' => array_key_exists('corregimiento_id', $data) ? $data['corregimiento_id'] : $client->corregimiento_id,
            'street_name' => array_key_exists('street_name', $data) ? $data['street_name'] : (array_key_exists('calle', $data) ? $data['calle'] : $client->street_name),
            'street_number' => array_key_exists('street_number', $data) ? $data['street_number'] : (array_key_exists('numero', $data) ? ($data['numero'] ?? 'S/N') : $client->street_number),
            'reference_point' => array_key_exists('reference_point', $data) ? $data['reference_point'] : $client->reference_point,
            'destination_coords' => array_key_exists('destination_coords', $data) ? $data['destination_coords'] : $client->destination_coords,
            'postal_code' => array_key_exists('postal_code', $data) ? $data['postal_code'] : (array_key_exists('codigo_postal', $data) ? $data['codigo_postal'] : $client->postal_code),
            'status' => array_key_exists('status', $data) ? $data['status'] : $client->status,
        ];

        if (array_key_exists('phone', $data)) {
            $fields['phone'] = $data['phone'];
        }

        if (array_key_exists('email', $data)) {
            $fields['email'] = $data['email'];
        }

        $client->fill($fields);
        $client->save();

        return $this->find($client->id);
    }

    /**
     * Predictive search for clients (autocomplete).
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 20): array
    {
        $term = trim($term);
        if (strlen($term) < 2) {
            return [];
        }

        return Client::query()
            ->active()
            ->search($term)
            ->withCount('shipments')
            ->limit($limit)
            ->get()
            ->map(fn (Client $c) => $this->mapClient($c))
            ->all();
    }

    /**
     * Get delivery history for a client.
     *
     * @return array<string, mixed>
     */
    public function deliveryHistory(string $id): array
    {
        $client = $this->find($id);

        $shipments = $client->shipments()
            ->with(['warehouse:id,name', 'driverTask.driver:id,name'])
            ->latest('created_at')
            ->get();

        return [
            'client' => $this->mapClient($client),
            'deliveries' => $shipments->map(function ($s) {
                return [
                    'id' => $s->id,
                    'tracking_number' => $s->tracking_number,
                    'status' => $s->status,
                    'package_type' => $s->package_type,
                    'weight_lb' => $s->weight_lb,
                    'total_cost' => $s->total_cost,
                    'destination_address' => $s->destination_address,
                    'created_at' => $s->created_at?->toISOString(),
                    'warehouse_name' => $s->warehouse?->name,
                    'driver_name' => $s->driverTask?->driver?->name,
                ];
            })->all(),
        ];
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

        $street = $client->street_name ?: '';
        $number = $client->street_number ?: '';
        $formattedAddress = trim($street.($number ? ' '.$number : ''));

        return [
            'id' => $client->id,
            'cliente' => $fullName,
            'full_name' => $fullName,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'nombre' => $client->first_name,
            'apellido' => $client->last_name,
            'phone' => $client->phone,
            'email' => $client->email,
            'residencia_id' => $client->residencia_id,
            'residencia' => $this->residenceLabel((int) ($client->residencia_id ?? 0)),
            'provincia_id' => $client->provincia_id,
            'provincia' => $this->provinceLabel((int) ($client->provincia_id ?? 0)),
            'distrito_id' => $client->distrito_id,
            'distrito' => $this->districtLabel((int) ($client->provincia_id ?? 0), (int) ($client->distrito_id ?? 0)),
            'corregimiento_id' => $client->corregimiento_id,
            'corregimiento' => $this->corregimientoLabel(
                (int) ($client->provincia_id ?? 0),
                (int) ($client->distrito_id ?? 0),
                (int) ($client->corregimiento_id ?? 0)
            ),
            'calle' => $client->street_name,
            'numero' => $client->street_number,
            'direccion' => $formattedAddress ?: ($client->reference_point ?? '-'),
            'reference_point' => $client->reference_point,
            'destination_coords' => $client->destination_coords,
            'codigo_postal' => $client->postal_code,
            'status' => $client->status ?? 'active',
            'is_active' => ($client->status ?? 'active') === 'active',
            'deliveries_count' => $client->shipments_count ?? $client->shipments()->count(),
            'created_at' => $client->created_at?->toISOString(),
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
