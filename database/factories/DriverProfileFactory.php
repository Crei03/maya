<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DriverProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    protected $model = DriverProfile::class;

    public function definition(): array
    {
        return [
            'id'                => $this->faker->uuid(),
            'user_id'           => null,
            'license_number'    => $this->faker->optional()->numerify('LIC-#####'),
            'license_expiry'    => $this->faker->optional()->dateTimeBetween('+1 month', '+5 years'),
            'emergency_contact' => $this->faker->optional()->name(),
            'emergency_phone'   => $this->faker->optional()->phoneNumber(),
            'is_available'      => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
