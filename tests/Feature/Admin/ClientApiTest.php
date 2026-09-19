<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientApiTest extends TestCase
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
            ],
        );
        $this->tenant->makeCurrent();

        $this->seed(\Database\Seeders\CatalogoSeeder::class);

        $this->gestor = User::factory()->create([
            'role' => User::ROLE_GESTOR,
            'status' => true,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_list_clients_returns_paginated_json(): void
    {
        Client::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.clients.list'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_search_clients_predictive(): void
    {
        Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Roberto',
            'last_name' => 'Durán',
            'phone' => '+507 6123-4567',
            'reference_point' => 'Cerca del gimnasio',
            'status' => 'active',
        ]);

        Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Mariano',
            'last_name' => 'Rivera',
            'phone' => '+507 6999-0000',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.clients.search', ['q' => 'Durán']));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Durán', $data[0]['last_name']);
        $this->assertSame('+507 6123-4567', $data[0]['phone']);
        $this->assertSame('Cerca del gimnasio', $data[0]['reference_point']);
    }

    public function test_store_client_with_full_details(): void
    {
        $payload = [
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'email' => 'ana.perez@example.com',
            'phone' => '+507 6555-1234',
            'calle' => 'Calle 50',
            'numero' => 'Torre Global Piso 12',
            'reference_point' => 'Frente a Farmacias Arrocha',
            'destination_coords' => [
                'lat' => 8.9824,
                'lng' => -79.5199,
            ],
            'status' => 'active',
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.clients.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Ana')
            ->assertJsonPath('data.phone', '+507 6555-1234')
            ->assertJsonPath('data.reference_point', 'Frente a Farmacias Arrocha');

        $this->assertDatabaseHas('clients', [
            'first_name' => 'Ana',
            'email' => 'ana.perez@example.com',
            'reference_point' => 'Frente a Farmacias Arrocha',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_client_delivery_history(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Carlos',
            'last_name' => 'Reina',
        ]);

        $warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id]);

        $shipment = Shipment::create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $warehouse->id,
            'sender_id' => $client->id,
            'destination_address' => 'Costa del Este, Edif Dream Plaza',
            'package_type' => 'caja',
            'weight_lb' => 10.5,
            'status' => Shipment::STATUS_DELIVERED,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.clients.history', ['id' => $client->id]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.client.id', $client->id);

        $deliveries = $response->json('data.deliveries');
        $this->assertCount(1, $deliveries);
        $this->assertSame($shipment->id, $deliveries[0]['id']);
        $this->assertSame('Costa del Este, Edif Dream Plaza', $deliveries[0]['destination_address']);
        $this->assertSame(Shipment::STATUS_DELIVERED, $deliveries[0]['status']);
    }

    public function test_update_client(): void
    {
        $client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Juan',
            'reference_point' => 'Viejo punto',
        ]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.clients.update', ['id' => $client->id]), [
                'first_name' => 'Juan Carlos',
                'reference_point' => 'Nuevo punto de referencia',
                'status' => 'inactive',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Juan Carlos')
            ->assertJsonPath('data.reference_point', 'Nuevo punto de referencia')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'first_name' => 'Juan Carlos',
            'reference_point' => 'Nuevo punto de referencia',
            'status' => 'inactive',
        ]);
    }

    public function test_delete_client(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.clients.destroy', ['id' => $client->id]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_tenant_isolation_clients(): void
    {
        $otherTenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Other Tenant',
            'slug' => 'other',
            'status' => 'active',
        ]);

        $otherClient = Client::withoutGlobalScopes()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Alien',
            'last_name' => 'User',
            'email' => 'alien@other.com',
            'role' => Client::ROLE_CLIENT,
        ]);

        // Current tenant shouldn't see other tenant's client
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.clients.list'));

        $this->assertCount(0, $response->json('data.data'));

        // Search shouldn't return other tenant's client
        $searchResponse = $this->actingAs($this->gestor)
            ->getJson(route('admin.clients.search', ['q' => 'Alien']));

        $this->assertCount(0, $searchResponse->json('data'));

        // Cannot show other tenant's client
        $showResponse = $this->actingAs($this->gestor)
            ->getJson(route('admin.clients.show', ['id' => $otherClient->id]));

        $showResponse->assertNotFound();
    }

    public function test_clientes_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->gestor)
            ->get(route('admin.clientes'));

        $response->assertOk();
    }

    public function test_configuracion_clientes_redirects_to_clientes(): void
    {
        $response = $this->actingAs($this->gestor)
            ->get('/configuracion/clientes');

        $response->assertRedirect('/clientes');
    }
}
