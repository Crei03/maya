<?php

namespace Database\Seeders;

use App\Models\Catalogo;
use App\Models\CatalogoValor;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $residencia = Catalogo::query()->updateOrCreate(
            ['slug' => 'residencia'],
            ['nombre' => 'Residencia']
        );

        $provincia = Catalogo::query()->updateOrCreate(
            ['slug' => 'provincia'],
            ['nombre' => 'Provincia']
        );

        $distrito = Catalogo::query()->updateOrCreate(
            ['slug' => 'distrito'],
            ['nombre' => 'Distrito']
        );

        $calle = Catalogo::query()->updateOrCreate(
            ['slug' => 'calle'],
            ['nombre' => 'Calle']
        );

        foreach ([
            ['codigo' => 'RES-001', 'valor' => 'Casa'],
            ['codigo' => 'RES-002', 'valor' => 'Apartamento'],
        ] as $item) {
            CatalogoValor::query()->updateOrCreate(
                ['catalogo_id' => $residencia->id, 'codigo' => $item['codigo']],
                ['valor' => $item['valor'], 'descripcion' => null, 'parent_id' => null]
            );
        }

        $panama = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $provincia->id, 'codigo' => 'PROV-001'],
            ['valor' => 'Panama', 'descripcion' => null, 'parent_id' => null]
        );

        $colon = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $provincia->id, 'codigo' => 'PROV-002'],
            ['valor' => 'Colon', 'descripcion' => null, 'parent_id' => null]
        );

        $chiriqui = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $provincia->id, 'codigo' => 'PROV-003'],
            ['valor' => 'Chiriqui', 'descripcion' => null, 'parent_id' => null]
        );

        $distritoPanama = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-001'],
            ['valor' => 'Panama', 'descripcion' => null, 'parent_id' => $panama->id]
        );

        $distritoChorrera = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-002'],
            ['valor' => 'La Chorrera', 'descripcion' => null, 'parent_id' => $panama->id]
        );

        $distritoColon = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-003'],
            ['valor' => 'Colon', 'descripcion' => null, 'parent_id' => $colon->id]
        );

        $distritoDavid = CatalogoValor::query()->updateOrCreate(
            ['catalogo_id' => $distrito->id, 'codigo' => 'DIST-004'],
            ['valor' => 'David', 'descripcion' => null, 'parent_id' => $chiriqui->id]
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
                ['valor' => $item['valor'], 'descripcion' => null, 'parent_id' => $item['parent_id']]
            );
        }
    }
}
