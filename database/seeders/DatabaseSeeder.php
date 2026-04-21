<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CatalogoSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => User::ROLE_USER,
        ]);

        User::query()->updateOrCreate(
            ['email' => 'carlosreina@gmail.com'],
            [
                'name' => 'Carlos Reina',
                'password' => Hash::make('123456'),
                'email' => 'carlosreina@gmail.com',
                'email_verified_at' => now(),
                'role' => User::ROLE_ADMIN,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
