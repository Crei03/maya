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
            ['nombre' => 'Residencia', 'scope' => Catalogo::SCOPE_PAQUETERIA, 'is_global' => true, 'tenant_id' => null]
        );

        $provincia = Catalogo::query()->updateOrCreate(
            ['slug' => 'provincia'],
            ['nombre' => 'Provincia', 'scope' => Catalogo::SCOPE_PAQUETERIA, 'is_global' => true, 'tenant_id' => null]
        );

        $distrito = Catalogo::query()->updateOrCreate(
            ['slug' => 'distrito'],
            ['nombre' => 'Distrito', 'scope' => Catalogo::SCOPE_PAQUETERIA, 'is_global' => true, 'tenant_id' => null]
        );

        $calle = Catalogo::query()->updateOrCreate(
            ['slug' => 'calle'],
            ['nombre' => 'Calle', 'scope' => Catalogo::SCOPE_PAQUETERIA, 'is_global' => true, 'tenant_id' => null]
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
                'slug' => 'estado-envio',
                'nombre' => 'Estado de Envío',
                'description' => 'Estados del ciclo de vida del paquete/envío',
                'valores' => [
                    ['codigo' => 'PENDIENTE', 'valor' => 'Pendiente', 'metadata' => ['color' => 'yellow', 'badge' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'icon' => 'clock', 'es_final' => false]],
                    ['codigo' => 'EN_BODEGA', 'valor' => 'En bodega', 'metadata' => ['color' => 'blue', 'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', 'icon' => 'warehouse', 'es_final' => false]],
                    ['codigo' => 'ASIGNADO', 'valor' => 'Asignado', 'metadata' => ['color' => 'purple', 'badge' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300', 'icon' => 'user-check', 'es_final' => false]],
                    ['codigo' => 'EN_TRANSITO', 'valor' => 'En tránsito', 'metadata' => ['color' => 'indigo', 'badge' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300', 'icon' => 'truck', 'es_final' => false]],
                    ['codigo' => 'ENTREGADO', 'valor' => 'Entregado', 'metadata' => ['color' => 'green', 'badge' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', 'icon' => 'check-circle', 'es_final' => true]],
                    ['codigo' => 'DEVUELTO', 'valor' => 'Devuelto', 'metadata' => ['color' => 'orange', 'badge' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300', 'icon' => 'rotate-left', 'es_final' => true]],
                    ['codigo' => 'FALLIDO', 'valor' => 'Fallido', 'metadata' => ['color' => 'red', 'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', 'icon' => 'triangle-exclamation', 'es_final' => true]],
                    ['codigo' => 'CANCELADO', 'valor' => 'Cancelado', 'metadata' => ['color' => 'gray', 'badge' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300', 'icon' => 'ban', 'es_final' => true]],
                ],
            ],
            [
                'slug' => 'tipo-referencia',
                'nombre' => 'Tipo de Documento de Envío',
                'description' => 'Tipo de documento o referencia comercial asociada al envío',
                'valores' => [
                    ['codigo' => 'PEDIDO', 'valor' => 'Pedido / Orden', 'metadata' => ['icon' => 'file-invoice']],
                    ['codigo' => 'FACTURA', 'valor' => 'Factura', 'metadata' => ['icon' => 'file-lines']],
                    ['codigo' => 'TRANSFERENCIA', 'valor' => 'Transferencia', 'metadata' => ['icon' => 'arrow-right-arrow-left']],
                    ['codigo' => 'RECIBO', 'valor' => 'Recibo', 'metadata' => ['icon' => 'receipt']],
                    ['codigo' => 'GUIA', 'valor' => 'Guía de Remisión', 'metadata' => ['icon' => 'truck-ramp-box']],
                    ['codigo' => 'LPN', 'valor' => 'LPN / Pallet directo', 'metadata' => ['icon' => 'pallet']],
                    ['codigo' => 'OTRO', 'valor' => 'Otro documento', 'metadata' => ['icon' => 'asterisk']],
                ],
            ],
            [
                'slug' => 'tipo-paquete',
                'nombre' => 'Tipo de Paquete',
                'description' => 'Formato o presentación física del paquete',
                'valores' => [
                    ['codigo' => 'CAJA', 'valor' => 'Caja', 'metadata' => ['icon' => 'box']],
                    ['codigo' => 'PALET', 'valor' => 'Palet / Tarima', 'metadata' => ['icon' => 'pallet']],
                    ['codigo' => 'SOBRE', 'valor' => 'Sobre', 'metadata' => ['icon' => 'envelope']],
                    ['codigo' => 'PAQUETE', 'valor' => 'Paquete / Bulto', 'metadata' => ['icon' => 'boxes-stacked']],
                    ['codigo' => 'DOCUMENTO', 'valor' => 'Documento', 'metadata' => ['icon' => 'file']],
                    ['codigo' => 'TAMBOR', 'valor' => 'Tambor / Barril', 'metadata' => ['icon' => 'drum']],
                ],
            ],
            [
                'slug' => 'estado-tarea',
                'nombre' => 'Estado de Ruta / Tarea',
                'description' => 'Estados de las rutas de entrega asignadas a mensajeros',
                'valores' => [
                    ['codigo' => 'PENDIENTE', 'valor' => 'Pendiente', 'metadata' => ['color' => 'yellow', 'badge' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'es_final' => false]],
                    ['codigo' => 'EN_PROCESO', 'valor' => 'En Proceso', 'metadata' => ['color' => 'blue', 'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', 'es_final' => false]],
                    ['codigo' => 'COMPLETADA', 'valor' => 'Completada', 'metadata' => ['color' => 'green', 'badge' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', 'es_final' => true]],
                    ['codigo' => 'CANCELADA', 'valor' => 'Cancelada', 'metadata' => ['color' => 'gray', 'badge' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300', 'es_final' => true]],
                ],
            ],
            [
                'slug' => 'estado-item-tarea',
                'nombre' => 'Estado de Item en Ruta',
                'description' => 'Estado de entrega de un paquete individual dentro de una ruta',
                'valores' => [
                    ['codigo' => 'PENDIENTE', 'valor' => 'Pendiente', 'metadata' => ['color' => 'yellow', 'es_final' => false]],
                    ['codigo' => 'ENTREGADO', 'valor' => 'Entregado', 'metadata' => ['color' => 'green', 'es_final' => true]],
                    ['codigo' => 'RETORNADO', 'valor' => 'Retornado', 'metadata' => ['color' => 'orange', 'es_final' => true]],
                    ['codigo' => 'DANADO', 'valor' => 'Dañado', 'metadata' => ['color' => 'red', 'es_final' => false]],
                    ['codigo' => 'FALTANTE', 'valor' => 'Faltante', 'metadata' => ['color' => 'red', 'es_final' => false]],
                ],
            ],
            [
                'slug' => 'prioridad-tarea',
                'nombre' => 'Prioridad de Parada / Tarea',
                'description' => 'Nivel de prioridad o urgencia de entrega',
                'valores' => [
                    ['codigo' => 'ALTA', 'valor' => 'Alta', 'metadata' => ['color' => 'red', 'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300']],
                    ['codigo' => 'MEDIA', 'valor' => 'Media', 'metadata' => ['color' => 'blue', 'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300']],
                    ['codigo' => 'BAJA', 'valor' => 'Baja', 'metadata' => ['color' => 'gray', 'badge' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300']],
                ],
            ],
            [
                'slug' => 'tipo-vehiculo-propiedad',
                'nombre' => 'Régimen de Propiedad de Vehículo',
                'description' => 'Indica si el vehículo es propio de la empresa o subcontratado',
                'valores' => [
                    ['codigo' => 'INTERNO', 'valor' => 'Propio / Interno', 'metadata' => null],
                    ['codigo' => 'EXTERNO', 'valor' => 'Subcontratado / Externo', 'metadata' => null],
                ],
            ],
            [
                'slug' => 'clase-vehiculo',
                'nombre' => 'Clase de Vehículo',
                'description' => 'Clasificación física del transporte para cálculo de capacidad',
                'valores' => [
                    ['codigo' => 'MOTO', 'valor' => 'Motocicleta', 'metadata' => ['icon' => 'motorcycle']],
                    ['codigo' => 'AUTO', 'valor' => 'Automóvil / Sedán', 'metadata' => ['icon' => 'car']],
                    ['codigo' => 'VAN', 'valor' => 'Van / Furgoneta', 'metadata' => ['icon' => 'van-shuttle']],
                    ['codigo' => 'CAMION', 'valor' => 'Camión', 'metadata' => ['icon' => 'truck']],
                    ['codigo' => 'PICKUP', 'valor' => 'Pickup', 'metadata' => ['icon' => 'truck-pickup']],
                ],
            ],
            [
                'slug' => 'tipo-incidente',
                'nombre' => 'Tipo de Incidente',
                'description' => 'Categorías de incidencias reportadas en entregas',
                'valores' => [
                    ['codigo' => 'DIRECCION_ERRONEA', 'valor' => 'Dirección errónea o no encontrada', 'metadata' => ['requiere_foto' => false]],
                    ['codigo' => 'CLIENTE_AUSENTE', 'valor' => 'Cliente ausente', 'metadata' => ['requiere_foto' => true]],
                    ['codigo' => 'PAQUETE_DANADO', 'valor' => 'Paquete dañado', 'metadata' => ['requiere_foto' => true]],
                    ['codigo' => 'ZONA_DIFICIL', 'valor' => 'Zona inaccesible o peligrosa', 'metadata' => ['requiere_foto' => false]],
                    ['codigo' => 'RECHAZADO', 'valor' => 'Rechazado por cliente', 'metadata' => ['requiere_foto' => true]],
                    ['codigo' => 'OTRO', 'valor' => 'Otro incidente', 'metadata' => ['requiere_foto' => false]],
                ],
            ],
            [
                'slug' => 'estado-manifiesto',
                'nombre' => 'Estado de Manifiesto',
                'description' => 'Estados del manifiesto de despacho de paquetes',
                'valores' => [
                    ['codigo' => 'BORRADOR', 'valor' => 'Borrador', 'metadata' => ['es_final' => false]],
                    ['codigo' => 'DESPACHADO', 'valor' => 'Despachado', 'metadata' => ['es_final' => false]],
                    ['codigo' => 'CERRADO', 'valor' => 'Cerrado', 'metadata' => ['es_final' => true]],
                    ['codigo' => 'CANCELADO', 'valor' => 'Cancelado', 'metadata' => ['es_final' => true]],
                ],
            ],
            [
                'slug' => 'estado-solicitud-recoleccion',
                'nombre' => 'Estado de Solicitud de Recolección',
                'description' => 'Flujo de las solicitudes de pickup de clientes',
                'valores' => [
                    ['codigo' => 'PENDIENTE', 'valor' => 'Pendiente', 'metadata' => ['es_final' => false]],
                    ['codigo' => 'ASIGNADA', 'valor' => 'Asignada', 'metadata' => ['es_final' => false]],
                    ['codigo' => 'RECOLECTADA', 'valor' => 'Recolectada', 'metadata' => ['es_final' => true]],
                    ['codigo' => 'CANCELADA', 'valor' => 'Cancelada', 'metadata' => ['es_final' => true]],
                ],
            ],
            [
                'slug' => 'estado-tenant',
                'nombre' => 'Estado de Tenant / Paquetería',
                'scope' => Catalogo::SCOPE_SAAS,
                'description' => 'Estado de cuenta de la empresa en el SaaS',
                'valores' => [
                    ['codigo' => 'ACTIVO', 'valor' => 'Activo', 'metadata' => ['color' => 'green']],
                    ['codigo' => 'PAUSADO', 'valor' => 'Pausado', 'metadata' => ['color' => 'yellow']],
                    ['codigo' => 'SUSPENDIDO', 'valor' => 'Suspendido', 'metadata' => ['color' => 'red']],
                    ['codigo' => 'INACTIVO', 'valor' => 'Inactivo', 'metadata' => ['color' => 'gray']],
                ],
            ],
            [
                'slug' => 'estado-liquidacion',
                'nombre' => 'Estado de Liquidación',
                'description' => 'Estados del proceso de pago y liquidación de mensajeros',
                'valores' => [
                    ['codigo' => 'PENDIENTE', 'valor' => 'Pendiente', 'metadata' => ['es_final' => false]],
                    ['codigo' => 'EN_REVISION', 'valor' => 'En Revisión', 'metadata' => ['es_final' => false]],
                    ['codigo' => 'APROBADA', 'valor' => 'Aprobada', 'metadata' => ['es_final' => false]],
                    ['codigo' => 'PAGADA', 'valor' => 'Pagada', 'metadata' => ['es_final' => true]],
                    ['codigo' => 'CANCELADA', 'valor' => 'Cancelada', 'metadata' => ['es_final' => true]],
                ],
            ],
        ];

        foreach ($catalogs as $catalog) {
            $catalogo = Catalogo::query()->updateOrCreate(
                ['slug' => $catalog['slug']],
                [
                    'nombre' => $catalog['nombre'],
                    'scope' => $catalog['scope'] ?? Catalogo::SCOPE_PAQUETERIA,
                    'description' => $catalog['description'] ?? null,
                    'is_global' => true,
                    'tenant_id' => null,
                ]
            );

            foreach ($catalog['valores'] as $valor) {
                CatalogoValor::query()->updateOrCreate(
                    [
                        'catalogo_id' => $catalogo->id,
                        'codigo' => $valor['codigo'],
                    ],
                    [
                        'valor' => $valor['valor'],
                        'metadata' => $valor['metadata'] ?? null,
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
