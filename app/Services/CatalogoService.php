<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
use Illuminate\Database\Eloquent\Collection;

class CatalogoService
{
    public function getVisibleCatalogos(?string $tenantId): Collection
    {
        return Catalogo::query()
            ->where(function ($q) use ($tenantId) {
                $q->where('is_global', true)
                    ->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('sort_order')
            ->orderBy('nombre')
            ->get();
    }

    public function getVisibleValores(int $catalogoId, ?string $tenantId): Collection
    {
        return CatalogoValor::query()
            ->where('catalogo_id', $catalogoId)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->active()
            ->ordered()
            ->get();
    }
}
