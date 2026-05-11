<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverApiTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gestor = User::factory()->create([
            'role'   => User::ROLE_GESTOR,
            'status' => true,
        ]);
    }

    // ============================================================================
    // Listado de conductores
    // ============================================================================

    public function test_list_drivers_returns_only_messengers(): void
    {
        // Arrange
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
            'name'   => 'Juan Conductor',
        ]);
        DriverProfile::factory()->create(['user_id' => $messenger->id]);

        User::factory()->create([
            'role'   => User::ROLE_GESTOR,
            'status' => true,
            'name'   => 'Admin User',
        ]);

        // Act
        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.drivers.list'));

        // Assert
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Juan Conductor');
    }

    public function test_list_drivers_requires_gestor_role(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);

        $response = $this->actingAs($messenger)
            ->getJson(route('admin.drivers.list'));

        $response->assertForbidden();
    }

    public function test_list_drivers_requires_authentication(): void
    {
        $response = $this->getJson(route('admin.drivers.list'));

        $response->assertUnauthorized();
    }

    // ============================================================================
    // Mostrar conductor
    // ============================================================================

    public function test_show_driver_returns_driver_with_profile(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);
        DriverProfile::factory()->create([
            'user_id'        => $messenger->id,
            'phone'          => '600123456',
            'license_number' => 'LIC-12345',
            'is_available'   => true,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.drivers.show', $messenger->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', $messenger->name)
            ->assertJsonPath('data.phone', '600123456')
            ->assertJsonPath('data.license_number', 'LIC-12345')
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.active_tasks_count', 0);
    }

    // ============================================================================
    // Crear conductor
    // ============================================================================

    public function test_create_driver_links_existing_user_and_creates_profile(): void
    {
        // Arrange: create an existing user (any role) to link as driver
        $existingUser = User::factory()->create([
            'name'  => 'Nuevo Conductor',
            'email' => 'conductor@maya.com',
            'role'  => User::ROLE_GESTOR,
        ]);

        $payload = [
            'user_id'           => $existingUser->id,
            'phone'             => '600111222',
            'license_number'    => 'LIC-99999',
            'license_expiry'    => '2027-12-31',
            'emergency_contact' => 'Maria Contacto',
            'emergency_phone'   => '600333444',
            'is_available'      => true,
            'status'            => true,
        ];

        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.drivers.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Conductor creado correctamente.')
            ->assertJsonPath('data.name', 'Nuevo Conductor')
            ->assertJsonPath('data.phone', '600111222')
            ->assertJsonPath('data.license_number', 'LIC-99999')
            ->assertJsonPath('data.role', User::ROLE_MESSENGER);

        // Assert the user was promoted to messenger
        $this->assertDatabaseHas('users', [
            'id'   => $existingUser->id,
            'role' => User::ROLE_MESSENGER,
        ]);

        // Assert the driver profile was created with correct data
        $this->assertDatabaseHas('driver_profiles', [
            'user_id'        => $existingUser->id,
            'phone'          => '600111222',
            'license_number' => 'LIC-99999',
            'is_available'   => true,
        ]);
    }

    public function test_create_driver_validates_required_fields(): void
    {
        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.drivers.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_driver_rejects_invalid_user_id(): void
    {
        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.drivers.store'), [
                'user_id' => 'non-existent-id',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_create_driver_updates_profile_if_user_already_has_one(): void
    {
        // Arrange: user already has a driver profile
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'name'   => 'Existing Driver',
        ]);
        DriverProfile::factory()->create([
            'user_id'        => $messenger->id,
            'phone'          => '600000000',
            'license_number' => 'OLD-LIC',
            'is_available'   => false,
        ]);

        // Act: create driver with same user_id (updates existing profile)
        $response = $this->actingAs($this->gestor)
            ->postJson(route('admin.drivers.store'), [
                'user_id'        => $messenger->id,
                'phone'          => '600111222',
                'license_number' => 'NEW-LIC',
                'is_available'   => true,
                'status'         => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.phone', '600111222')
            ->assertJsonPath('data.license_number', 'NEW-LIC')
            ->assertJsonPath('data.is_available', true);

        // Assert profile was updated (not duplicated — unique constraint)
        $this->assertDatabaseCount('driver_profiles', 1);
        $this->assertDatabaseHas('driver_profiles', [
            'user_id'        => $messenger->id,
            'phone'          => '600111222',
            'license_number' => 'NEW-LIC',
            'is_available'   => true,
        ]);
    }

    // ============================================================================
    // Actualizar conductor
    // ============================================================================

    public function test_update_driver_updates_profile_fields(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);
        DriverProfile::factory()->create([
            'user_id'        => $messenger->id,
            'phone'          => '600000000',
            'license_number' => 'OLD-LICENSE',
            'is_available'   => false,
        ]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.drivers.update', $messenger->id), [
                'phone'          => '600999888',
                'license_number' => 'NEW-LICENSE',
                'is_available'   => true,
                'status'         => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '600999888')
            ->assertJsonPath('data.license_number', 'NEW-LICENSE')
            ->assertJsonPath('data.is_available', true);

        $this->assertDatabaseHas('driver_profiles', [
            'user_id'        => $messenger->id,
            'phone'          => '600999888',
            'license_number' => 'NEW-LICENSE',
            'is_available'   => true,
        ]);
    }

    public function test_update_driver_does_not_change_user_name_or_email(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
            'name'   => 'Original Name',
            'email'  => 'original@maya.com',
        ]);
        DriverProfile::factory()->create([
            'user_id' => $messenger->id,
        ]);

        $this->actingAs($this->gestor)
            ->patchJson(route('admin.drivers.update', $messenger->id), [
                'phone'  => '600999888',
                'status' => true,
            ]);

        // Name and email should remain unchanged
        $this->assertDatabaseHas('users', [
            'id'    => $messenger->id,
            'name'  => 'Original Name',
            'email' => 'original@maya.com',
        ]);
    }

    public function test_update_driver_creates_profile_if_missing(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);

        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.drivers.update', $messenger->id), [
                'license_number' => 'LIC-NEW',
                'is_available'   => false,
                'status'         => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.license_number', 'LIC-NEW')
            ->assertJsonPath('data.is_available', false);

        $this->assertDatabaseHas('driver_profiles', [
            'user_id'      => $messenger->id,
            'is_available' => false,
        ]);
    }

    // ============================================================================
    // Eliminar conductor
    // ============================================================================

    public function test_delete_driver_removes_user_and_profile(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);
        DriverProfile::factory()->create(['user_id' => $messenger->id]);

        $response = $this->actingAs($this->gestor)
            ->deleteJson(route('admin.drivers.destroy', $messenger->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Conductor eliminado correctamente.');

        $this->assertDatabaseMissing('users', ['id' => $messenger->id]);
        $this->assertDatabaseMissing('driver_profiles', ['user_id' => $messenger->id]);
    }

    // ============================================================================
    // Filtros
    // ============================================================================

    public function test_filter_drivers_by_availability(): void
    {
        $available = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
            'name'   => 'Disponible',
        ]);
        DriverProfile::factory()->create([
            'user_id'      => $available->id,
            'is_available' => true,
        ]);

        $unavailable = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
            'name'   => 'Ocupado',
        ]);
        DriverProfile::factory()->create([
            'user_id'      => $unavailable->id,
            'is_available' => false,
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.drivers.list', ['is_available' => '1']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Disponible');
    }

    public function test_search_drivers_by_name(): void
    {
        User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
            'name'   => 'Carlos Perez',
        ]);
        $driver = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
            'name'   => 'Maria Gomez',
        ]);
        DriverProfile::factory()->create(['user_id' => $driver->id]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.drivers.list', ['search' => 'Maria']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Maria Gomez');
    }

    // ============================================================================
    // Conteo de tareas activas
    // ============================================================================

    public function test_driver_includes_active_tasks_count(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);
        DriverProfile::factory()->create(['user_id' => $messenger->id]);

        // Create active tasks
        \App\Models\ShipmentTask::factory()->create([
            'driver_id' => $messenger->id,
            'status'    => 'in_progress',
        ]);
        \App\Models\ShipmentTask::factory()->create([
            'driver_id' => $messenger->id,
            'status'    => 'pending',
        ]);
        // Completed task should NOT be counted
        \App\Models\ShipmentTask::factory()->create([
            'driver_id' => $messenger->id,
            'status'    => 'completed',
        ]);

        $response = $this->actingAs($this->gestor)
            ->getJson(route('admin.drivers.show', $messenger->id));

        $response->assertOk()
            ->assertJsonPath('data.active_tasks_count', 2);
    }

    // ============================================================================
    // Verificar request de actualización no requiere campos de usuario
    // ============================================================================

    public function test_update_driver_accepts_only_profile_fields(): void
    {
        $messenger = User::factory()->create([
            'role'   => User::ROLE_MESSENGER,
            'status' => true,
        ]);
        DriverProfile::factory()->create(['user_id' => $messenger->id]);

        // Should work with only driver profile fields
        $response = $this->actingAs($this->gestor)
            ->patchJson(route('admin.drivers.update', $messenger->id), [
                'is_available' => false,
            ]);

        $response->assertOk();
    }
}
