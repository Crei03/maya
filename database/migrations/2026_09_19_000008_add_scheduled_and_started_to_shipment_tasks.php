<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('shipment_tasks', 'scheduled_date')) {
                $table->dateTime('scheduled_date')->nullable()->after('origin_warehouse_id');
            }
            if (! Schema::hasColumn('shipment_tasks', 'started_at')) {
                $table->dateTime('started_at')->nullable()->after('scheduled_date');
            }
        });

        // Backfill scheduled_date con start_date
        DB::table('shipment_tasks')
            ->whereNull('scheduled_date')
            ->update([
                'scheduled_date' => DB::raw('start_date'),
            ]);

        // Backfill started_at para tareas que ya están en proceso o completadas
        if (Schema::hasColumn('shipment_tasks', 'status_id')) {
            DB::table('shipment_tasks')
                ->join('catalogo_valores', 'shipment_tasks.status_id', '=', 'catalogo_valores.id')
                ->whereIn('catalogo_valores.codigo', ['EN_PROCESO', 'COMPLETADA'])
                ->whereNull('shipment_tasks.started_at')
                ->update([
                    'shipment_tasks.started_at' => DB::raw('shipment_tasks.start_date'),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('shipment_tasks', 'started_at')) {
                $table->dropColumn('started_at');
            }
            if (Schema::hasColumn('shipment_tasks', 'scheduled_date')) {
                $table->dropColumn('scheduled_date');
            }
        });
    }
};
