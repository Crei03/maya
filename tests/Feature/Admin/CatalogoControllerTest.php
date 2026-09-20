<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['multi-tenant.enabled' => false]);

        $this->tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Demo',
                'status' => 'active',
            ]
        );

        $this->gestor = User::factory()->create([
            'role' => User::ROLE_GESTOR,
            'status' => true,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_index_returns_visible_catalogos_for_current_tenant(): void
    {
        Catalogo::query()->create([
            'nombre' => 'Global Catalog',
            'slug' => 'global-catalog',
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.configuracion.catalogos.index'));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
    }

    public function test_index_does_not_return_saas_catalogos_to_tenant(): void
    {
        Catalogo::query()->create([
            'nombre' => 'Operational Catalog',
            'slug' => 'operational-cat',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => true,
        ]);

        Catalogo::query()->create([
            'nombre' => 'Tenant Status Catalog',
            'slug' => 'estado-tenant',
            'scope' => Catalogo::SCOPE_SAAS,
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.configuracion.catalogos.index'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.slug', 'operational-cat');
    }

    public function test_show_returns_404_for_saas_catalogos_to_tenant(): void
    {
        Catalogo::query()->create([
            'nombre' => 'Tenant Status Catalog',
            'slug' => 'estado-tenant',
            'scope' => Catalogo::SCOPE_SAAS,
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.configuracion.catalogos.show', ['slug' => 'estado-tenant']));

        $response->assertNotFound();
    }

    public function test_show_returns_valores_by_slug(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Shipment Status',
            'slug' => 'shipment-status',
            'is_global' => true,
        ]);
        CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'PEN',
            'valor' => 'Pendiente',
            'tenant_id' => null,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.configuracion.catalogos.show', ['slug' => 'shipment-status']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.catalogo.slug', 'shipment-status');
        $response->assertJsonCount(1, 'data.valores');
    }

    public function test_store_valor_with_valid_codigo(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $payload = [
            'catalogo_id' => $catalogo->id,
            'codigo' => 'NEW',
            'valor' => 'Nuevo Valor',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.configuracion.catalogos.valores.store'), $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('catalogo_valores', [
            'catalogo_id' => $catalogo->id,
            'codigo' => 'NEW',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_store_valor_rejects_codigo_over_100_chars(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $payload = [
            'catalogo_id' => $catalogo->id,
            'codigo' => str_repeat('A', 101),
            'valor' => 'Nuevo Valor',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.configuracion.catalogos.valores.store'), $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['codigo']);
    }

    public function test_store_valor_rejects_valor_over_255_chars(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $payload = [
            'catalogo_id' => $catalogo->id,
            'codigo' => 'VAL',
            'valor' => str_repeat('a', 256),
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.configuracion.catalogos.valores.store'), $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['valor']);
    }

    public function test_update_valor_owned_by_tenant(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'VAL',
            'valor' => 'Initial Value',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->putJson(route('admin.configuracion.catalogos.valores.update', ['id' => $valor->id]), [
                'valor' => 'Updated Value',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('catalogo_valores', [
            'id' => $valor->id,
            'valor' => 'Updated Value',
        ]);
    }

    public function test_cannot_update_valor_not_owned_by_tenant(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $otherTenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Other Tenant',
            'slug' => 'other-tenant-1',
            'status' => 'active',
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'OTH',
            'valor' => 'Other Tenant',
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->putJson(route('admin.configuracion.catalogos.valores.update', ['id' => $valor->id]), [
                'valor' => 'Hacked',
            ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'No tienes permiso para modificar este valor.');
    }

    public function test_destroy_valor_owned_by_tenant(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'DEL',
            'valor' => 'To Delete',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.configuracion.catalogos.valores.destroy', ['id' => $valor->id]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('catalogo_valores', ['id' => $valor->id]);
    }

    public function test_cannot_destroy_valor_not_owned_by_tenant(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $otherTenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Other Tenant 2',
            'slug' => 'other-tenant-2',
            'status' => 'active',
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'OTH',
            'valor' => 'Other Tenant',
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.configuracion.catalogos.valores.destroy', ['id' => $valor->id]));

        $response->assertForbidden();
        $response->assertJsonPath('message', 'No tienes permiso para eliminar este valor.');
    }

    public function test_store_catalogo_creates_new_operational_catalog(): void
    {
        $payload = [
            'nombre' => 'Prioridad de Entrega',
            'slug' => 'prioridad-entrega',
            'description' => 'Catálogo personalizado de prioridades',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.configuracion.catalogos.store'), $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.slug', 'prioridad-entrega');
        $response->assertJsonPath('data.scope', Catalogo::SCOPE_PAQUETERIA);
        $response->assertJsonPath('data.tenant_id', $this->tenant->id);

        $this->assertDatabaseHas('catalogos', [
            'slug' => 'prioridad-entrega',
            'scope' => 'paqueteria',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_update_catalogo_updates_name_and_description(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Custom Catalog',
            'slug' => 'custom-cat',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->putJson(route('admin.configuracion.catalogos.update', ['id' => $catalogo->id]), [
                'nombre' => 'Updated Custom Catalog',
                'description' => 'Updated description',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.nombre', 'Updated Custom Catalog');

        $this->assertDatabaseHas('catalogos', [
            'id' => $catalogo->id,
            'nombre' => 'Updated Custom Catalog',
        ]);
    }

    public function test_destroy_catalogo_deletes_empty_custom_catalog(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'To Delete',
            'slug' => 'to-delete',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.configuracion.catalogos.destroy', ['id' => $catalogo->id]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('catalogos', ['id' => $catalogo->id]);
    }

    public function test_destroy_catalogo_deletes_catalog_with_unreferenced_values(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Cat With Unused Values',
            'slug' => 'cat-unused-values',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'VAL1',
            'valor' => 'Valor Uno',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.configuracion.catalogos.destroy', ['id' => $catalogo->id]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('catalogos', ['id' => $catalogo->id]);
        $this->assertDatabaseMissing('catalogo_valores', ['id' => $valor->id]);
    }

    public function test_destroy_catalogo_fails_if_values_are_in_use(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Status In Use',
            'slug' => 'status-in-use',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => false,
            'tenant_id' => $this->tenant->id,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'USED',
            'valor' => 'En Uso',
            'tenant_id' => $this->tenant->id,
        ]);

        // Create a shipment referencing this value in package_type_id
        Shipment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'package_type_id' => $valor->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.configuracion.catalogos.destroy', ['id' => $catalogo->id]));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertDatabaseHas('catalogos', ['id' => $catalogo->id]);
        $this->assertDatabaseHas('catalogo_valores', ['id' => $valor->id]);
    }

    public function test_destroy_catalogo_fails_for_saas_catalog(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'SaaS Config Cat',
            'slug' => 'saas-config-cat',
            'scope' => Catalogo::SCOPE_SAAS,
            'is_global' => true,
            'tenant_id' => null,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.configuracion.catalogos.destroy', ['id' => $catalogo->id]));

        $response->assertNotFound();
        $this->assertDatabaseHas('catalogos', ['id' => $catalogo->id]);
    }

    public function test_destroy_valor_global_allowed(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Global Catalog',
            'slug' => 'global-cat',
            'scope' => Catalogo::SCOPE_PAQUETERIA,
            'is_global' => true,
            'tenant_id' => null,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'GLOB_DEL',
            'valor' => 'Global to Delete',
            'tenant_id' => null,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.configuracion.catalogos.valores.destroy', ['id' => $valor->id]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('catalogo_valores', ['id' => $valor->id]);
    }
}
