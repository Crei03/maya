<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
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

        $this->tenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Demo',
            'slug' => 'demo',
            'status' => 'active',
        ]);

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
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson('/configuracion/catalogos');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_returns_valores_by_slug(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Shipment Status',
            'slug' => 'shipment-status',
            'is_global' => true,
            'is_active' => true,
        ]);
        CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'PEN',
            'valor' => 'Pendiente',
            'tenant_id' => null,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson('/configuracion/catalogos/shipment-status');

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
            'is_global' => true,
            'is_active' => true,
        ]);

        $payload = [
            'catalogo_id' => $catalogo->id,
            'codigo' => 'NEW',
            'valor' => 'Nuevo Valor',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson('/configuracion/catalogos/valores', $payload);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('catalogo_valores', [
            'catalogo_id' => $catalogo->id,
            'codigo' => 'NEW',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_store_valor_rejects_codigo_over_3_chars(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
            'is_active' => true,
        ]);

        $payload = [
            'catalogo_id' => $catalogo->id,
            'codigo' => 'ABCD',
            'valor' => 'Invalid Code',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson('/configuracion/catalogos/valores', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['codigo']);
    }

    public function test_store_valor_rejects_valor_over_255_chars(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
            'is_active' => true,
        ]);

        $payload = [
            'catalogo_id' => $catalogo->id,
            'codigo' => 'ABC',
            'valor' => str_repeat('a', 256),
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson('/configuracion/catalogos/valores', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['valor']);
    }

    public function test_update_valor_owned_by_tenant(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
            'is_active' => true,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'OLD',
            'valor' => 'Original',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->putJson('/configuracion/catalogo/valores/' . $valor->id, [
                'valor' => 'Updated Value',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('catalogo_valores', [
            'id' => $valor->id,
            'valor' => 'Updated Value',
            'codigo' => 'OLD',
        ]);
    }

    public function test_cannot_update_valor_not_owned_by_tenant(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
            'is_active' => true,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'OTH',
            'valor' => 'Other Tenant',
            'tenant_id' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($this->gestor)
            ->putJson('/configuracion/catalogo/valores/' . $valor->id, [
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
            'is_active' => true,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'DEL',
            'valor' => 'To Delete',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson('/configuracion/catalogos/valores/' . $valor->id);

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
            'is_active' => true,
        ]);

        $valor = CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'OTH',
            'valor' => 'Other Tenant',
            'tenant_id' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson('/configuracion/catalogos/valores/' . $valor->id);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'No tienes permiso para eliminar este valor.');
    }
}
