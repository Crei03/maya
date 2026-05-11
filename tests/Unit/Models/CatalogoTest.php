<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Catalogo;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(string $slug = 'test'): Tenant
    {
        return Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'slug' => $slug,
            'name' => "Tenant {$slug}",
            'status' => 'active',
        ]);
    }

    public function test_scope_global_returns_only_global_catalogos(): void
    {
        Catalogo::query()->create([
            'nombre' => 'Global Catalog',
            'slug' => 'global-catalog',
            'is_global' => true,
            'tenant_id' => null,
        ]);

        $tenant = $this->createTenant('tenant-a');
        Catalogo::query()->create([
            'nombre' => 'Tenant Catalog',
            'slug' => 'tenant-catalog',
            'is_global' => false,
            'tenant_id' => $tenant->id,
        ]);

        $result = Catalogo::query()->global()->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Global Catalog', $result->first()->nombre);
    }

    public function test_scope_visible_by_tenant_returns_global_and_tenant_catalogos(): void
    {
        $global = Catalogo::query()->create([
            'nombre' => 'Global Catalog',
            'slug' => 'global-catalog',
            'is_global' => true,
            'tenant_id' => null,
        ]);

        $tenant = $this->createTenant('tenant-b');
        $tenantOwned = Catalogo::query()->create([
            'nombre' => 'Tenant Catalog',
            'slug' => 'tenant-catalog',
            'is_global' => false,
            'tenant_id' => $tenant->id,
        ]);

        $otherTenant = $this->createTenant('tenant-c');
        Catalogo::query()->create([
            'nombre' => 'Other Tenant Catalog',
            'slug' => 'other-tenant',
            'is_global' => false,
            'tenant_id' => $otherTenant->id,
        ]);

        $result = Catalogo::query()->visibleByTenant($tenant->id)->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $global->id));
        $this->assertTrue($result->contains('id', $tenantOwned->id));
    }
}
