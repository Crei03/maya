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
        // 1. Agregar columnas si no existen
        if (! Schema::hasColumn('shipment_tasks', 'status_id')) {
            Schema::table('shipment_tasks', function (Blueprint $table) {
                $table->foreignId('status_id')->nullable()->after('total_hours')->constrained('catalogo_valores');
            });
        }

        if (! Schema::hasColumn('shipment_task_items', 'status_id')) {
            Schema::table('shipment_task_items', function (Blueprint $table) {
                $table->foreignId('status_id')->nullable()->after('shipment_id')->constrained('catalogo_valores');
                $table->foreignId('priority_id')->nullable()->after('status_id')->constrained('catalogo_valores');
            });
        }

        // 2. Backfill de shipment_tasks
        if (Schema::hasColumn('shipment_tasks', 'status')) {
            $taskStatusCat = DB::table('catalogos')->where('slug', 'estado-tarea')->first();
            if ($taskStatusCat) {
                $taskStatusMap = DB::table('catalogo_valores')
                    ->where('catalogo_id', $taskStatusCat->id)
                    ->pluck('id', 'codigo');

                $taskMapping = [
                    'pending' => 'PENDIENTE',
                    'in_progress' => 'EN_PROCESO',
                    'completed' => 'COMPLETADA',
                    'cancelled' => 'CANCELADA',
                ];

                foreach ($taskMapping as $old => $code) {
                    if (isset($taskStatusMap[$code])) {
                        DB::table('shipment_tasks')->where('status', $old)->update(['status_id' => $taskStatusMap[$code]]);
                    }
                }

                if (isset($taskStatusMap['PENDIENTE'])) {
                    DB::table('shipment_tasks')->whereNull('status_id')->update(['status_id' => $taskStatusMap['PENDIENTE']]);
                }
            }
        }

        // 3. Backfill de shipment_task_items
        if (Schema::hasColumn('shipment_task_items', 'status')) {
            $itemStatusCat = DB::table('catalogos')->where('slug', 'estado-item-tarea')->first();
            if ($itemStatusCat) {
                $itemStatusMap = DB::table('catalogo_valores')
                    ->where('catalogo_id', $itemStatusCat->id)
                    ->pluck('id', 'codigo');

                $itemMapping = [
                    'pendiente' => 'PENDIENTE',
                    'entregado' => 'ENTREGADO',
                    'retornado' => 'RETORNADO',
                    'danado' => 'DANADO',
                    'faltante' => 'FALTANTE',
                ];

                foreach ($itemMapping as $old => $code) {
                    if (isset($itemStatusMap[$code])) {
                        DB::table('shipment_task_items')->where('status', $old)->update(['status_id' => $itemStatusMap[$code]]);
                    }
                }

                if (isset($itemStatusMap['PENDIENTE'])) {
                    DB::table('shipment_task_items')->whereNull('status_id')->update(['status_id' => $itemStatusMap['PENDIENTE']]);
                }
            }
        }

        if (Schema::hasColumn('shipment_task_items', 'priority')) {
            $priorityCat = DB::table('catalogos')->where('slug', 'prioridad-tarea')->first();
            if ($priorityCat) {
                $priorityMap = DB::table('catalogo_valores')
                    ->where('catalogo_id', $priorityCat->id)
                    ->pluck('id', 'codigo');

                $priorityMapping = [
                    'alta' => 'ALTA',
                    'media' => 'MEDIA',
                    'baja' => 'BAJA',
                ];

                foreach ($priorityMapping as $old => $code) {
                    if (isset($priorityMap[$code])) {
                        DB::table('shipment_task_items')->where('priority', $old)->update(['priority_id' => $priorityMap[$code]]);
                    }
                }

                if (isset($priorityMap['MEDIA'])) {
                    DB::table('shipment_task_items')->whereNull('priority_id')->update(['priority_id' => $priorityMap['MEDIA']]);
                }
            }
        }

        // 4. Crear primero el nuevo índice para que la FK tenant_id tenga cobertura, luego eliminar el viejo
        Schema::table('shipment_tasks', function (Blueprint $table) {
            $table->index(['tenant_id', 'status_id']);
        });

        if (Schema::hasColumn('shipment_tasks', 'status')) {
            Schema::table('shipment_tasks', function (Blueprint $table) {
                $table->dropIndex('shipment_tasks_tenant_id_status_index');
                $table->dropColumn('status');
            });
        }

        Schema::table('shipment_task_items', function (Blueprint $table) {
            $table->index(['tenant_id', 'status_id']);
        });

        if (Schema::hasColumn('shipment_task_items', 'status')) {
            Schema::table('shipment_task_items', function (Blueprint $table) {
                $table->dropIndex('shipment_task_items_tenant_id_status_index');
                $table->dropColumn(['status', 'priority']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('shipment_tasks', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending')->after('total_hours');
            $table->index(['tenant_id', 'status']);
            $table->dropIndex(['tenant_id', 'status_id']);
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });

        Schema::table('shipment_task_items', function (Blueprint $table) {
            $table->enum('status', ['pendiente', 'entregado', 'retornado'])->default('pendiente')->after('shipment_id');
            $table->enum('priority', ['alta', 'media', 'baja'])->default('media')->after('status');
            $table->index(['tenant_id', 'status']);
            $table->dropIndex(['tenant_id', 'status_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['priority_id']);
            $table->dropColumn(['status_id', 'priority_id']);
        });
    }
};
