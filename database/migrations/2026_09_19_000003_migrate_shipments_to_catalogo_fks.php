<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar columnas nullable para la migración
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('warehouse_id')->constrained('catalogo_valores');
            $table->foreignId('reference_type_id')->nullable()->after('status_id')->constrained('catalogo_valores');
            $table->foreignId('package_type_id')->nullable()->after('content_description')->constrained('catalogo_valores');
        });

        // 2. Data backfill
        $statusCat = DB::table('catalogos')->where('slug', 'estado-envio')->first();
        if ($statusCat) {
            $statusMap = DB::table('catalogo_valores')
                ->where('catalogo_id', $statusCat->id)
                ->pluck('id', 'codigo');

            $mapping = [
                'pending' => 'PENDIENTE',
                'in_warehouse' => 'EN_BODEGA',
                'assigned' => 'ASIGNADO',
                'in_transit' => 'EN_TRANSITO',
                'delivered' => 'ENTREGADO',
                'returned' => 'DEVUELTO',
                'failed' => 'FALLIDO',
                'cancelled' => 'CANCELADO',
            ];

            foreach ($mapping as $old => $code) {
                if (isset($statusMap[$code])) {
                    DB::table('shipments')->where('status', $old)->update(['status_id' => $statusMap[$code]]);
                }
            }

            // Fallback para cualquier shipment sin status_id
            if (isset($statusMap['PENDIENTE'])) {
                DB::table('shipments')->whereNull('status_id')->update(['status_id' => $statusMap['PENDIENTE']]);
            }
        }

        $refCat = DB::table('catalogos')->where('slug', 'tipo-referencia')->first();
        if ($refCat) {
            $refMap = DB::table('catalogo_valores')
                ->where('catalogo_id', $refCat->id)
                ->pluck('id', 'codigo');

            $mappingRef = [
                'pedido' => 'PEDIDO',
                'factura' => 'FACTURA',
                'transferencia' => 'TRANSFERENCIA',
                'recibo' => 'RECIBO',
                'guia' => 'GUIA',
                'lpn' => 'LPN',
                'otro' => 'OTRO',
            ];

            foreach ($mappingRef as $old => $code) {
                if (isset($refMap[$code])) {
                    DB::table('shipments')->where('reference_type', $old)->update(['reference_type_id' => $refMap[$code]]);
                }
            }
        }

        $pkgCat = DB::table('catalogos')->where('slug', 'tipo-paquete')->first();
        if ($pkgCat) {
            $pkgMap = DB::table('catalogo_valores')
                ->where('catalogo_id', $pkgCat->id)
                ->pluck('id', 'codigo');

            $mappingPkg = [
                'caja' => 'CAJA',
                'palet' => 'PALET',
                'sobre' => 'SOBRE',
                'paquete' => 'PAQUETE',
                'documento' => 'DOCUMENTO',
                'tambor' => 'TAMBOR',
                'cajas' => 'CAJA',
            ];

            foreach ($mappingPkg as $old => $code) {
                if (isset($pkgMap[$code])) {
                    DB::table('shipments')->where('package_type', $old)->update(['package_type_id' => $pkgMap[$code]]);
                }
            }
        }

        // 3. Eliminar columnas string
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_status_index');
            $table->dropColumn(['status', 'reference_type', 'package_type']);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('status', 50)->default('pending')->after('warehouse_id');
            $table->string('reference_type', 50)->nullable()->after('status');
            $table->string('package_type', 50)->nullable()->after('content_description');

            $table->dropForeign(['status_id']);
            $table->dropForeign(['reference_type_id']);
            $table->dropForeign(['package_type_id']);
            $table->dropColumn(['status_id', 'reference_type_id', 'package_type_id']);
        });
    }
};
