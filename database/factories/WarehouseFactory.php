<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'BOD-' . strtoupper(Str::random(3)) . '-' . fake()->numberBetween(100, 999);

        return [
            'name' => 'Bodega ' . fake()->city(),
            'code' => $code,
            'location_address' => fake()->address(),
            'location_coords' => [
                'lat' => (float) fake()->latitude(),
                'lng' => (float) fake()->longitude(),
            ],
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
