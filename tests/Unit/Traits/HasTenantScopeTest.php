<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Models\Shipment;
use App\Models\Tenant;
use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoped_query_applies_tenant_id_filter(): void
    {
        // Arrange: create a tenant and make it current
        $tenant = Tenant::factory()->create(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $tenant->makeCurrent();

        // Create a test wrapper that uses the trait
        $wrapper = new class {
            use HasTenantScope;

            public function applyScope(Builder $query): Builder
            {
                return $this->scopedQuery($query);
            }
        };

        // Act: apply the scope to a Shipment query
        $query = Shipment::query();
        $scoped = $wrapper->applyScope($query);

        // Assert: the where clause was applied with the correct tenant_id
        $wheres = $scoped->getQuery()->wheres;
        $this->assertCount(1, $wheres, 'Expected exactly one where clause');
        $this->assertSame('tenant_id', $wheres[0]['column']);
        $this->assertSame($tenant->id, $wheres[0]['value']);
        $this->assertSame('=', $wheres[0]['operator']);
    }

    public function test_scoped_query_with_no_current_tenant(): void
    {
        // Arrange: ensure no tenant is current
        // (no tenant was created — Tenant::current() returns null)

        $wrapper = new class {
            use HasTenantScope;

            public function applyScope(Builder $query): Builder
            {
                return $this->scopedQuery($query);
            }
        };

        // Act: apply the scope to a Shipment query
        $query = Shipment::query();
        $scoped = $wrapper->applyScope($query);

        // Assert: the where clause was applied with null value (tenant_id IS NULL)
        $wheres = $scoped->getQuery()->wheres;
        $this->assertCount(1, $wheres, 'Expected exactly one where clause');
        $this->assertSame('tenant_id', $wheres[0]['column']);
        $this->assertSame('Null', $wheres[0]['type']);
    }
}
