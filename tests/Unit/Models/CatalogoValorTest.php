<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogoValorTest extends TestCase
{
    use RefreshDatabase;

    private Catalogo $catalogo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogo = Catalogo::query()->create([
            'nombre' => 'Test Catalog',
            'slug' => 'test-catalog',
            'is_global' => true,
            'tenant_id' => null,
        ]);
    }

    public function test_scope_global_returns_global_valores(): void
    {
        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'GLO',
            'valor' => 'Global',
            'is_global' => true,
            'tenant_id' => null,
        ]);

        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'TEN',
            'valor' => 'Tenant',
            'is_global' => false,
            'tenant_id' => (string) Str::uuid(),
        ]);

        $result = CatalogoValor::query()->global()->get();

        $this->assertCount(1, $result);
        $this->assertEquals('GLO', $result->first()->codigo);
    }

    public function test_scope_visible_by_tenant_returns_global_and_tenant_valores(): void
    {
        $tenantId = (string) Str::uuid();

        $global = CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'GLO',
            'valor' => 'Global',
            'is_global' => true,
            'tenant_id' => null,
        ]);

        $tenant = CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'TEN',
            'valor' => 'Tenant Owned',
            'is_global' => false,
            'tenant_id' => $tenantId,
        ]);

        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'OTH',
            'valor' => 'Other Tenant',
            'is_global' => false,
            'tenant_id' => (string) Str::uuid(),
        ]);

        $result = CatalogoValor::query()->visibleByTenant($tenantId)->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $global->id));
        $this->assertTrue($result->contains('id', $tenant->id));
    }

    public function test_scope_active_returns_only_active_valores(): void
    {
        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'ACT',
            'valor' => 'Active',
            'is_active' => true,
            'tenant_id' => null,
        ]);

        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'INA',
            'valor' => 'Inactive',
            'is_active' => false,
            'tenant_id' => null,
        ]);

        $result = CatalogoValor::query()->active()->get();

        $this->assertCount(1, $result);
        $this->assertEquals('ACT', $result->first()->codigo);
    }

    public function test_scope_ordered_orders_by_sort_order_and_valor(): void
    {
        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'C',
            'valor' => 'Charlie',
            'sort_order' => 2,
            'tenant_id' => null,
        ]);

        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'A',
            'valor' => 'Alpha',
            'sort_order' => 1,
            'tenant_id' => null,
        ]);

        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'B',
            'valor' => 'Beta',
            'sort_order' => 1,
            'tenant_id' => null,
        ]);

        $result = CatalogoValor::query()->ordered()->get();

        $this->assertCount(3, $result);
        $this->assertEquals('Alpha', $result[0]->valor);
        $this->assertEquals('Beta', $result[1]->valor);
        $this->assertEquals('Charlie', $result[2]->valor);
    }

    public function test_parent_children_relationship(): void
    {
        $parent = CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'PAR',
            'valor' => 'Parent',
            'tenant_id' => null,
        ]);

        $child = CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'CHI',
            'valor' => 'Child',
            'parent_id' => $parent->id,
            'tenant_id' => null,
        ]);

        $this->assertCount(1, $parent->children);
        $this->assertEquals($child->id, $parent->children->first()->id);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_codigo_uniqueness_per_catalogo_at_app_level(): void
    {
        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'ABC',
            'valor' => 'First',
            'tenant_id' => null,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CatalogoValor::query()->create([
            'catalogo_id' => $this->catalogo->id,
            'codigo' => 'ABC',
            'valor' => 'Duplicate',
            'tenant_id' => null,
        ]);
    }
}
