<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Demo Paqueteria',
                'contact_email' => 'demo@maya.app',
                'status' => 'active',
            ]
        );

        $warehouses = [
            [
                'code' => 'BOD-PAN-001',
                'name' => 'Bodega Panama Centro',
                'location_address' => 'Ave. Central, Panama',
                'location_coords' => ['lat' => 8.9833, 'lng' => -79.5167],
                'phone' => '+507 300-1000',
                'is_active' => true,
            ],
            [
                'code' => 'BOD-CHO-002',
                'name' => 'Bodega La Chorrera',
                'location_address' => 'Via Principal, La Chorrera',
                'location_coords' => ['lat' => 8.8803, 'lng' => -79.7833],
                'phone' => '+507 300-2000',
                'is_active' => true,
            ],
            [
                'code' => 'BOD-COL-003',
                'name' => 'Bodega Colon',
                'location_address' => 'Zona Libre, Colon',
                'location_coords' => ['lat' => 9.3560, 'lng' => -79.9044],
                'phone' => '+507 300-3000',
                'is_active' => false,
            ],
        ];

        foreach ($warehouses as $data) {
            Warehouse::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $data['code'],
                ],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $data['name'],
                    'location_address' => $data['location_address'],
                    'location_coords' => $data['location_coords'],
                    'phone' => $data['phone'],
                    'is_active' => $data['is_active'],
                ]
            );
        }
    }
}
