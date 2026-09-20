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

    public function test_scope_paqueteria_and_scope_saas_filter_correctly(): void
    {
        Catalogo::query()->create([
            'nombre' => 'Operational Catalog',
            'slug' => 'operational-catalog',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => true,
        ]);

        Catalogo::query()->create([
            'nombre' => 'SaaS Catalog',
            'slug' => 'saas-catalog',
            'scope' => Catalogo::SCOPE_SAAS,
            'is_global' => true,
        ]);

        $paqueteriaResult = Catalogo::query()->paqueteria()->get();
        $saasResult = Catalogo::query()->saas()->get();

        $this->assertCount(1, $paqueteriaResult);
        $this->assertSame('operational-catalog', $paqueteriaResult->first()->slug);

        $this->assertCount(1, $saasResult);
        $this->assertSame('saas-catalog', $saasResult->first()->slug);
    }

    public function test_scope_visible_by_tenant_excludes_saas_catalogos(): void
    {
        $tenant = $this->createTenant('tenant-x');

        Catalogo::query()->create([
            'nombre' => 'Operational Global',
            'slug' => 'op-global',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => true,
        ]);

        Catalogo::query()->create([
            'nombre' => 'Operational Tenant',
            'slug' => 'op-tenant',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => false,
            'tenant_id' => $tenant->id,
        ]);

        Catalogo::query()->create([
            'nombre' => 'SaaS Platform Catalog',
            'slug' => 'saas-platform',
            'scope' => Catalogo::SCOPE_SAAS,
            'is_global' => true,
        ]);

        $result = Catalogo::query()->visibleByTenant($tenant->id)->get();

        $this->assertCount(2, $result);
        $this->assertFalse($result->contains('slug', 'saas-platform'));
    }
}
