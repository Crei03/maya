<?php

declare(strict_types=1);

namespace Tests\Feature\Management;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => true,
        ]);
    }

    public function test_index_returns_catalogos_list(): void
    {
        Catalogo::query()->create([
            'nombre' => 'Shipment Status',
            'slug' => 'shipment-status',
            'is_global' => true,
            'is_active' => true,
        ]);
        Catalogo::query()->create([
            'nombre' => 'Package Type',
            'slug' => 'package-type',
            'is_global' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get('/management/catalogos');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Management/Catalogos/Index')
            ->has('catalogos.data', 2)
        );
    }

    public function test_store_creates_catalogo_with_valid_data(): void
    {
        $payload = [
            'nombre' => 'Test Catalog',
            'slug' => 'test-catalog',
            'description' => 'A test catalog',
            'is_global' => true,
            'is_active' => true,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos', $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('catalogos', [
            'nombre' => 'Test Catalog',
            'slug' => 'test-catalog',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Catalogo::query()->create([
            'nombre' => 'Existing',
            'slug' => 'existing-slug',
            'is_global' => true,
        ]);

        $payload = [
            'nombre' => 'Duplicate',
            'slug' => 'existing-slug',
            'is_global' => true,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos', $payload);

        $response->assertSessionHasErrors('slug');
    }

    public function test_update_modifies_nombre_but_not_slug(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Original Name',
            'slug' => 'original-slug',
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put('/management/catalogos/' . $catalogo->id, [
                'nombre' => 'Updated Name',
                'is_global' => true,
            ]);

        $response->assertRedirect();

        $catalogo->refresh();
        $this->assertEquals('Updated Name', $catalogo->nombre);
        $this->assertEquals('original-slug', $catalogo->slug);
    }

    public function test_update_with_slug_change_attempt_ignores_slug(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Original',
            'slug' => 'original-slug',
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put('/management/catalogos/' . $catalogo->id, [
                'nombre' => 'Updated',
                'slug' => 'new-slug',
                'is_global' => true,
            ]);

        $response->assertRedirect();

        $catalogo->refresh();
        $this->assertEquals('original-slug', $catalogo->slug);
        $this->assertDatabaseHas('catalogos', ['slug' => 'original-slug']);
    }

    public function test_destroy_deletes_catalogo_without_valores(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Deletable',
            'slug' => 'deletable',
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson('/management/catalogos/' . $catalogo->id);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('catalogos', ['id' => $catalogo->id]);
    }

    public function test_destroy_blocked_when_catalogo_has_valores(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Protected',
            'slug' => 'protected',
            'is_global' => true,
        ]);

        CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'VAL',
            'valor' => 'Test Value',
            'tenant_id' => null,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson('/management/catalogos/' . $catalogo->id);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'No se puede eliminar el catálogo porque tiene valores asociados.');
        $this->assertDatabaseHas('catalogos', ['id' => $catalogo->id]);
    }
}
