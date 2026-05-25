<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'id'            => (string) Str::uuid(),
            'name'          => $this->faker->company(),
            'slug'          => $slug,
            'contact_email' => $this->faker->safeEmail(),
            'phone'         => $this->faker->phoneNumber(),
            'address'       => $this->faker->address(),
            'status'        => 'active',
        ];
    }
}
