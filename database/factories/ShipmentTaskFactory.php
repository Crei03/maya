<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShipmentTask;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShipmentTask>
 */
class ShipmentTaskFactory extends Factory
{
    protected $model = ShipmentTask::class;

    public function definition(): array
    {
        return [
            'id'                  => $this->faker->uuid(),
            'title'               => $this->faker->sentence(3),
            'driver_id'           => User::factory(),
            'origin_warehouse_id' => Warehouse::factory(),
            'start_date'          => now(),
            'status'              => 'pending',
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
