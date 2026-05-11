<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Models\Catalogo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoValidationTest extends TestCase
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

    public function test_store_catalogo_request_missing_nombre(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos', [
                'slug' => 'test-slug',
                'is_global' => true,
            ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_store_catalogo_request_missing_slug(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos', [
                'nombre' => 'Test',
                'is_global' => true,
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_store_catalogo_request_invalid_slug_format(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos', [
                'nombre' => 'Test',
                'slug' => 'Invalid Slug!',
                'is_global' => true,
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_store_catalogo_request_duplicate_slug(): void
    {
        Catalogo::query()->create([
            'nombre' => 'First',
            'slug' => 'same-slug',
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos', [
                'nombre' => 'Second',
                'slug' => 'same-slug',
                'is_global' => true,
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_update_catalogo_request_slug_not_accepted(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Original',
            'slug' => 'original-slug',
            'is_global' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->put('/management/catalogos/' . $catalogo->id, [
                'nombre' => 'Updated',
                'slug' => 'new-slug',
                'is_global' => true,
            ]);

        $this->assertDatabaseHas('catalogos', [
            'id' => $catalogo->id,
            'slug' => 'original-slug',
        ]);
        $this->assertDatabaseMissing('catalogos', [
            'slug' => 'new-slug',
        ]);
    }

    public function test_store_valor_request_codigo_over_3_chars_rejected(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos/' . $catalogo->id . '/valores', [
                'catalogo_id' => $catalogo->id,
                'codigo' => 'TOOLONG',
                'valor' => 'Test',
            ]);

        $response->assertSessionHasErrors('codigo');
    }

    public function test_store_valor_request_missing_valor(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos/' . $catalogo->id . '/valores', [
                'catalogo_id' => $catalogo->id,
                'codigo' => 'ABC',
            ]);

        $response->assertSessionHasErrors('valor');
    }

    public function test_store_valor_request_duplicate_codigo(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->post('/management/catalogos/' . $catalogo->id . '/valores', [
                'catalogo_id' => $catalogo->id,
                'codigo' => 'ABC',
                'valor' => 'First',
            ]);

        $response = $this->actingAs($this->superAdmin)
            ->post('/management/catalogos/' . $catalogo->id . '/valores', [
                'catalogo_id' => $catalogo->id,
                'codigo' => 'ABC',
                'valor' => 'Second',
            ]);

        $response->assertSessionHasErrors('codigo');
    }

    public function test_update_valor_request_codigo_cannot_be_changed(): void
    {
        $catalogo = Catalogo::query()->create([
            'nombre' => 'Test',
            'slug' => 'test',
            'is_global' => true,
        ]);

        $valor = \App\Models\CatalogoValor::query()->create([
            'catalogo_id' => $catalogo->id,
            'codigo' => 'OLD',
            'valor' => 'Original',
            'tenant_id' => null,
        ]);

        $this->actingAs($this->superAdmin)
            ->put('/management/catalogos/' . $catalogo->id . '/valores/' . $valor->id, [
                'valor' => 'Updated',
                'codigo' => 'NEW',
            ]);

        $this->assertDatabaseHas('catalogo_valores', [
            'id' => $valor->id,
            'codigo' => 'OLD',
            'valor' => 'Updated',
        ]);
    }
}
