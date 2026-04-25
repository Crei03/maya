<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantService
{
    public function create(array $data): Tenant
    {
        $data['id'] = (string) Str::uuid();
        $data['status'] = $data['status'] ?? 'active';

        return Tenant::create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        // Slug is immutable after creation
        unset($data['slug']);

        $tenant->update($data);

        return $tenant;
    }

    public function updateStatus(Tenant $tenant, string $status): Tenant
    {
        $tenant->update(['status' => $status]);

        return $tenant;
    }
}
