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

    public function getValoresBySlug(string $slug, ?string $tenantId = null): Collection
    {
        $cacheKey = "catalogo_valores_{$slug}_".($tenantId ?? 'global');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($slug, $tenantId) {
            $catalogo = Catalogo::query()->where('slug', $slug)->first();
            if (! $catalogo) {
                return new Collection;
            }

            return $this->getVisibleValores($catalogo->id, $tenantId);
        });
    }

    public function getValorIdByCodigo(string $catalogoSlug, string $codigo, ?string $tenantId = null): ?int
    {
        $valores = $this->getValoresBySlug($catalogoSlug, $tenantId);
        $valor = $valores->firstWhere('codigo', strtoupper($codigo));

        return $valor?->id;
    }

    public function clearCache(?string $slug = null): void
    {
        if ($slug) {
            \Illuminate\Support\Facades\Cache::forget("catalogo_valores_{$slug}_global");
        } else {
            \Illuminate\Support\Facades\Cache::flush();
        }
    }
}
