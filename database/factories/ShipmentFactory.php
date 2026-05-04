<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weightKg = $this->faker->randomFloat(2, 0.1, 30.0);

        return [
            'id'                      => (string) Str::uuid(),
            'tenant_id'               => Tenant::factory(),
            'warehouse_id'            => null,
            'assigned_task_id'        => null,
            'tracking_number'         => 'MAYA' . strtoupper(Str::random(10)),
            'sender_id'               => null,
            'recipient_name'          => $this->faker->name(),
            'recipient_phone'         => $this->faker->numerify('6###-####'),
            'origin_address'          => $this->faker->address(),
            'destination_address'     => $this->faker->address(),
            'destination_coords'      => [
                'lat' => $this->faker->latitude(8.0, 9.5),
                'lng' => $this->faker->longitude(-80.0, -77.0),
            ],
            'weight_kg'               => $weightKg,
            'weight_lb'               => round($weightKg * 2.20462, 2),
            'total_cost'              => $this->faker->randomFloat(2, 5.0, 100.0),
            'content_description'     => $this->faker->sentence(),
            'package_type'            => $this->faker->randomElement(['caja', 'sobre', 'palet', 'bolsa', 'tubo']),
            'dimensions'              => [
                'largo' => $this->faker->numberBetween(5, 100),
                'ancho' => $this->faker->numberBetween(5, 100),
                'alto'  => $this->faker->numberBetween(5, 100),
            ],
            'status'                  => Shipment::STATUS_PENDING,
            'current_status_id'       => null,
            'label_url'               => null,
            'delivered_photo_url'     => null,
            'recipient_signature_url' => null,
            'eta'                     => $this->faker->dateTimeBetween('+1 day', '+7 days'),
            'delivered_at'            => null,
        ];
    }

    /**
     * Estado: en bodega.
     */
    public function inWarehouse(Warehouse $warehouse): static
    {
        return $this->state(fn () => [
            'status'       => Shipment::STATUS_IN_WAREHOUSE,
            'warehouse_id' => $warehouse->id,
        ]);
    }

    /**
     * Estado: asignado a tarea de reparto.
     */
    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => Shipment::STATUS_ASSIGNED,
        ]);
    }

    /**
     * Estado: en tránsito.
     */
    public function inTransit(): static
    {
        return $this->state(fn () => [
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);
    }

    /**
     * Estado: entregado.
     */
    public function delivered(): static
    {
        return $this->state(fn () => [
            'status'       => Shipment::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Estado: fallido.
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => Shipment::STATUS_FAILED,
        ]);
    }
}
