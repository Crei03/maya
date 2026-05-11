<?php

namespace Database\Seeders;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $residencia = Catalogo::query()->updateOrCreate(
            ['slug' => 'residencia'],
            ['nombre' => 'Residencia', 'is_global' => true, 'tenant_id' => null]
        );

        $provincia = Catalogo::query()->updateOrCreate(
            ['slug' => 'provincia'],
            ['nombre' => 'Provincia', 'is_global' => true, 'tenant_id' => null]
        );

        $distrito = Catalogo::query()->updateOrCreate(
            ['slug' => 'distrito'],
            ['nombre' => 'Distrito', 'is_global' => true, 'tenant_id' => null]
        );

        $calle = Catalogo::query()->updateOrCreate(
            ['slug' => 'calle'],
            ['nombre' => 'Calle', 'is_global' => true, 'tenant_id' => null]
        );

        foreach ([
            ['codigo' => 'RES-001', 'valor' => 'Casa'],
            ['codigo' => 'RES-002', 'valor' => 'Apartamento'],
        ] as $item) {
            CatalogoValor::query()->updateOrCreate(
                ['catalogo_id' => $residencia->id, 'codigo' => $item['codigo']],
                ['valor' => $item['valor'], 'descripcion' => null, 'parent_id' => null, 'tenant_id' => null, 'is_global' => true]
            );
        }

        $panama = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $provincia->id, 'codigo' => 'PROV-001'],
            ['valor' => 'Panama', 'descripcion' => null, 'parent_id' => null, 'tenant_id' => null, 'is_global' => true]
        );

        $colon = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $provincia->id, 'codigo' => 'PROV-002'],
            ['valor' => 'Colon', 'descripcion' => null, 'parent_id' => null, 'tenant_id' => null, 'is_global' => true]
        );

        $chiriqui = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $provincia->id, 'codigo' => 'PROV-003'],
            ['valor' => 'Chiriqui', 'descripcion' => null, 'parent_id' => null, 'tenant_id' => null, 'is_global' => true]
        );

        $distritoPanama = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-001'],
            ['valor' => 'Panama', 'descripcion' => null, 'parent_id' => $panama->id, 'tenant_id' => null, 'is_global' => true]
        );

        $distritoChorrera = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-002'],
            ['valor' => 'La Chorrera', 'descripcion' => null, 'parent_id' => $panama->id, 'tenant_id' => null, 'is_global' => true]
        );

        $distritoColon = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-003'],
            ['valor' => 'Colon', 'descripcion' => null, 'parent_id' => $colon->id, 'tenant_id' => null, 'is_global' => true]
        );

        $distritoDavid = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-004'],
            ['valor' => 'David', 'descripcion' => null, 'parent_id' => $chiriqui->id, 'tenant_id' => null, 'is_global' => true]
        );

        foreach ([
            ['codigo' => 'CALLE-001', 'valor' => 'Calle 50', 'parent_id' => $distritoPanama->id],
            ['codigo' => 'CALLE-002', 'valor' => 'Avenida Balboa', 'parent_id' => $distritoPanama->id],
            ['codigo' => 'CALLE-003', 'valor' => 'Via Espana', 'parent_id' => $distritoPanama->id],
            ['codigo' => 'CALLE-004', 'valor' => 'Calle Principal', 'parent_id' => $distritoChorrera->id],
            ['codigo' => 'CALLE-005', 'valor' => 'Paseo Central', 'parent_id' => $distritoColon->id],
            ['codigo' => 'CALLE-006', 'valor' => 'Avenida 3ra', 'parent_id' => $distritoDavid->id],
        ] as $item) {
            CatalogoValor::query()->updateOrCreate(
                ['catalogo_id' => $calle->id, 'codigo' => $item['codigo']],
                ['valor' => $item['valor'], 'descripcion' => null, 'parent_id' => $item['parent_id'], 'tenant_id' => null, 'is_global' => true]
            );
        }

        $this->seedOperationalCatalogs();
    }

    private function seedOperationalCatalogs(): void
    {
        $catalogs = [
            [
                'slug' => 'shipment-status',
                'nombre' => 'Estado de Envío',
                'valores' => [
                    ['codigo' => 'PEN', 'valor' => 'Pendiente'],
                    ['codigo' => 'REC', 'valor' => 'Recibido'],
                    ['codigo' => 'TRA', 'valor' => 'En Tránsito'],
                    ['codigo' => 'DEL', 'valor' => 'Entregado'],
                    ['codigo' => 'FAL', 'valor' => 'Fallido'],
                    ['codigo' => 'DEV', 'valor' => 'Devuelto'],
                    ['codigo' => 'CAN', 'valor' => 'Cancelado'],
                ],
            ],
            [
                'slug' => 'task-status',
                'nombre' => 'Estado de Tarea',
                'valores' => [
                    ['codigo' => 'PEN', 'valor' => 'Pendiente'],
                    ['codigo' => 'PRO', 'valor' => 'En Proceso'],
                    ['codigo' => 'COM', 'valor' => 'Completada'],
                    ['codigo' => 'CAN', 'valor' => 'Cancelada'],
                ],
            ],
            [
                'slug' => 'task-item-status',
                'nombre' => 'Estado de Item de Tarea',
                'valores' => [
                    ['codigo' => 'PEN', 'valor' => 'Pendiente'],
                    ['codigo' => 'REC', 'valor' => 'Recibido'],
                    ['codigo' => 'DAN', 'valor' => 'Dañado'],
                    ['codigo' => 'FAL', 'valor' => 'Faltante'],
                    ['codigo' => 'DEV', 'valor' => 'Devuelto'],
                ],
            ],
            [
                'slug' => 'package-type',
                'nombre' => 'Tipo de Paquete',
                'valores' => [
                    ['codigo' => 'DOC', 'valor' => 'Documento'],
                    ['codigo' => 'CAJ', 'valor' => 'Caja'],
                    ['codigo' => 'SOB', 'valor' => 'Sobre'],
                    ['codigo' => 'PAL', 'valor' => 'Pallet'],
                    ['codigo' => 'TAM', 'valor' => 'Tambor'],
                    ['codigo' => 'BUL', 'valor' => 'Bulto'],
                ],
            ],
            [
                'slug' => 'vehicle-type',
                'nombre' => 'Tipo de Vehículo',
                'valores' => [
                    ['codigo' => 'MOT', 'valor' => 'Motocicleta'],
                    ['codigo' => 'CAR', 'valor' => 'Automóvil'],
                    ['codigo' => 'VAN', 'valor' => 'Van'],
                    ['codigo' => 'CAM', 'valor' => 'Camión'],
                    ['codigo' => 'PIC', 'valor' => 'Pickup'],
                ],
            ],
            [
                'slug' => 'user-role',
                'nombre' => 'Rol de Usuario',
                'valores' => [
                    ['codigo' => 'GES', 'valor' => 'Gestor'],
                    ['codigo' => 'MEN', 'valor' => 'Mensajero'],
                    ['codigo' => 'ADM', 'valor' => 'Administrador'],
                ],
            ],
            [
                'slug' => 'tenant-status',
                'nombre' => 'Estado de Tenant',
                'valores' => [
                    ['codigo' => 'ACT', 'valor' => 'Activo'],
                    ['codigo' => 'PAU', 'valor' => 'Pausado'],
                    ['codigo' => 'SUS', 'valor' => 'Suspendido'],
                    ['codigo' => 'INA', 'valor' => 'Inactivo'],
                ],
            ],
            [
                'slug' => 'settlement-status',
                'nombre' => 'Estado de Liquidación',
                'valores' => [
                    ['codigo' => 'PEN', 'valor' => 'Pendiente'],
                    ['codigo' => 'REV', 'valor' => 'En Revisión'],
                    ['codigo' => 'APR', 'valor' => 'Aprobada'],
                    ['codigo' => 'PAG', 'valor' => 'Pagada'],
                    ['codigo' => 'CAN', 'valor' => 'Cancelada'],
                ],
            ],
        ];

        foreach ($catalogs as $catalog) {
            $catalogo = Catalogo::query()->firstOrCreate(
                ['slug' => $catalog['slug']],
                [
                    'nombre' => $catalog['nombre'],
                    'is_global' => true,
                    'tenant_id' => null,
                ]
            );

            foreach ($catalog['valores'] as $valor) {
                CatalogoValor::query()->firstOrCreate(
                    ['catalogo_id' => $catalogo->id, 'codigo' => $valor['codigo']],
                    [
                        'valor' => $valor['valor'],
                        'tenant_id' => null,
                        'is_global' => true,
                        'is_active' => true,
                        'sort_order' => 0,
                    ]
                );
            }
        }
    }
}
