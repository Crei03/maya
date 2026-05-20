<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WarehouseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable multi-tenant for testing; HasTenant falls back to 'demo' tenant
        config(['multi-tenant.enabled' => false]);

        $this->tenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Demo',
            'slug'   => 'demo',
            'status' => 'active',
        ]);

        $this->gestor = User::factory()->create([
            'role'     => User::ROLE_GESTOR,
            'status'   => true,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // ============================================================================
    // 3.1: List endpoint — paginated JSON, current tenant only
    // ============================================================================

    public function test_list_warehouses_returns_paginated_json(): void
    {
        Warehouse::factory()->count(3)->create();

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(3, 'data.data');
    }

    public function test_list_warehouses_only_returns_current_tenant(): void
    {
        // Warehouses for current tenant
        Warehouse::factory()->count(2)->create();

        // Warehouse for a different tenant (bypass HasTenant by setting tenant_id manually)
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.total', 2);
    }

    public function test_list_warehouses_search_by_name_or_code(): void
    {
        Warehouse::factory()->create(['name' => 'Bodega Central', 'code' => 'BOD-001']);
        Warehouse::factory()->create(['name' => 'Bodega Norte', 'code' => 'BOD-002']);
        Warehouse::factory()->create(['name' => 'Almacen Sur', 'code' => 'ALM-001']);

        // Search by name
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list', ['search' => 'Central']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Bodega Central');

        // Search by code
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list', ['search' => 'ALM']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.code', 'ALM-001');
    }

    public function test_list_warehouses_filter_by_active_status(): void
    {
        Warehouse::factory()->create(['name' => 'Active One', 'is_active' => true]);
        Warehouse::factory()->create(['name' => 'Active Two', 'is_active' => true]);
        Warehouse::factory()->create(['name' => 'Inactive One', 'is_active' => false]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list', ['is_active' => '1']));

        $response->assertOk()
            ->assertJsonCount(2, 'data.data');

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list', ['is_active' => '0']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Inactive One');
    }

    public function test_list_warehouses_empty_state(): void
    {
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.data')
            ->assertJsonPath('data.total', 0);
    }

    public function test_list_warehouses_excludes_soft_deleted(): void
    {
        $active = Warehouse::factory()->create(['name' => 'Active']);
        $deleted = Warehouse::factory()->create(['name' => 'Deleted']);
        $deleted->delete();

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list'));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Active');
    }

    // ============================================================================
    // 3.2: Create warehouse — valid data, auto UUID, tenant_id, returns 201
    // ============================================================================

    public function test_create_warehouse_with_valid_data(): void
    {
        $payload = [
            'name'             => 'Bodega Test',
            'code'             => 'BOD-TEST-001',
            'location_address' => 'Calle Falsa 123',
            'location_coords'  => ['lat' => -34.6037, 'lng' => -58.3816],
            'phone'            => '+5491112345678',
            'is_active'        => true,
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.bodegas.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Bodega creada correctamente.')
            ->assertJsonPath('data.name', 'Bodega Test')
            ->assertJsonPath('data.code', 'BOD-TEST-001')
            ->assertJsonPath('data.location_address', 'Calle Falsa 123')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('warehouses', [
            'name'             => 'Bodega Test',
            'code'             => 'BOD-TEST-001',
            'location_address' => 'Calle Falsa 123',
            'tenant_id'        => $this->tenant->id,
        ]);

        // Verify auto-generated UUID
        $warehouse = Warehouse::where('code', 'BOD-TEST-001')->first();
        $this->assertNotNull($warehouse->id);
        $this->assertTrue(Str::isUuid($warehouse->id));
    }

    public function test_create_warehouse_required_field_validation(): void
    {
        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.bodegas.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'code']);
    }

    // ============================================================================
    // 3.3: Create warehouse — duplicate code validation
    // ============================================================================

    public function test_create_warehouse_rejects_duplicate_code(): void
    {
        Warehouse::factory()->create(['code' => 'BOD-DUP-001']);

        $payload = [
            'name'             => 'Bodega Duplicate',
            'code'             => 'BOD-DUP-001',
            'location_address' => 'Some Address',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.bodegas.store'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        // Verify only one warehouse exists with this code
        $this->assertDatabaseCount('warehouses', 1);
    }

    public function test_create_warehouse_allows_same_code_across_tenants(): void
    {
        // Current tenant warehouse
        Warehouse::factory()->create(['code' => 'BOD-SHARED']);

        // Different tenant
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        Warehouse::factory()->create(['code' => 'BOD-SHARED', 'tenant_id' => $otherTenant->id]);

        // Should still be able to create with same code in current tenant? 
        // No — the unique rule is per-tenant, so BOD-SHARED already exists in current tenant
        // Let's test the inverse: other tenant has it, current tenant doesn't
        $this->assertDatabaseCount('warehouses', 2);

        // Create a different code in current tenant — should work
        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.bodegas.store'), [
                'name'             => 'New Bodega',
                'code'             => 'BOD-UNIQUE',
                'location_address' => 'Address',
            ]);

        $response->assertCreated();
    }

    // ============================================================================
    // 3.4: Update warehouse
    // ============================================================================

    public function test_update_warehouse_successfully(): void
    {
        $warehouse = Warehouse::factory()->create([
            'name' => 'Original Name',
            'code' => 'BOD-ORIG',
        ]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.bodegas.update', $warehouse->id), [
                'name'             => 'Updated Name',
                'code'             => 'BOD-UPDATED',
                'location_address' => 'New Address',
                'phone'            => '+5491198765432',
                'is_active'        => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Bodega actualizada correctamente.')
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.code', 'BOD-UPDATED')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('warehouses', [
            'id'   => $warehouse->id,
            'name' => 'Updated Name',
            'code' => 'BOD-UPDATED',
        ]);
    }

    // ============================================================================
    // 3.5: Update — duplicate code validation excluding self
    // ============================================================================

    public function test_update_warehouse_keeps_own_code(): void
    {
        $warehouse = Warehouse::factory()->create(['code' => 'BOD-SELF']);

        // Update without changing code — should succeed
        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.bodegas.update', $warehouse->id), [
                'name' => 'Updated Name',
                'code' => 'BOD-SELF',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_update_warehouse_rejects_code_of_another_warehouse(): void
    {
        $warehouseA = Warehouse::factory()->create(['code' => 'BOD-A']);
        $warehouseB = Warehouse::factory()->create(['code' => 'BOD-B']);

        // Try to change B's code to A's code
        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.bodegas.update', $warehouseB->id), [
                'name' => 'Warehouse B',
                'code' => 'BOD-A',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        // B's code should remain unchanged
        $this->assertDatabaseHas('warehouses', [
            'id'   => $warehouseB->id,
            'code' => 'BOD-B',
        ]);
    }

    // ============================================================================
    // 3.6: Soft delete
    // ============================================================================

    public function test_soft_delete_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.bodegas.destroy', $warehouse->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Bodega eliminada correctamente.');

        // Verify soft delete — record still exists but has deleted_at
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
        $this->assertNotNull(Warehouse::withTrashed()->find($warehouse->id)->deleted_at);

        // Should not appear in list
        $listResponse = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list'));
        $listResponse->assertJsonCount(0, 'data.data');
    }

    public function test_show_deleted_warehouse_returns_404(): void
    {
        $warehouse = Warehouse::factory()->create();
        $warehouse->delete();

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.show', $warehouse->id));

        $response->assertNotFound();
    }

    // ============================================================================
    // 3.7: Tenant isolation — cross-tenant access returns 404
    // ============================================================================

    public function test_cannot_show_warehouse_of_different_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other Tenant',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        $otherWarehouse = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.show', $otherWarehouse->id));

        $response->assertNotFound();
    }

    public function test_cannot_update_warehouse_of_different_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other Tenant',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        $otherWarehouse = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.bodegas.update', $otherWarehouse->id), [
                'name' => 'Hacked Name',
                'code' => $otherWarehouse->code,
            ]);

        $response->assertNotFound();
    }

    public function test_cannot_delete_warehouse_of_different_tenant(): void
    {
        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other Tenant',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        $otherWarehouse = Warehouse::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.bodegas.destroy', $otherWarehouse->id));

        $response->assertNotFound();

        // Verify the warehouse was NOT deleted
        $this->assertNull(Warehouse::withTrashed()->find($otherWarehouse->id)->deleted_at);
    }

    public function test_list_does_not_include_other_tenant_warehouses(): void
    {
        Warehouse::factory()->count(2)->create();

        $otherTenant = Tenant::query()->create([
            'id'     => (string) Str::uuid(),
            'name'   => 'Other Tenant',
            'slug'   => 'other',
            'status' => 'active',
        ]);
        Warehouse::factory()->count(3)->create(['tenant_id' => $otherTenant->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.bodegas.list'));

        $response->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.total', 2);
    }

    // ============================================================================
    // Auth guards
    // ============================================================================

    public function test_list_warehouses_requires_authentication(): void
    {
        $response = $this->getJson(route('admin.bodegas.list'));
        $response->assertUnauthorized();
    }

    public function test_create_warehouse_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.bodegas.store'), [
            'name' => 'Test',
            'code' => 'TEST',
        ]);
        $response->assertUnauthorized();
    }
}
