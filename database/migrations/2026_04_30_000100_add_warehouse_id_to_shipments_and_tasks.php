<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('shipments') && ! Schema::hasColumn('shipments', 'warehouse_id')) {
            Schema::table('shipments', function (Blueprint $table): void {
                $table->uuid('warehouse_id')->nullable()->index()->after('tenant_id');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            });
        }

        if (Schema::hasTable('shipment_tasks') && ! Schema::hasColumn('shipment_tasks', 'origin_warehouse_id')) {
            Schema::table('shipment_tasks', function (Blueprint $table): void {
                $table->uuid('origin_warehouse_id')->nullable()->index()->after('tenant_id');
                $table->foreign('origin_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'warehouse_id')) {
            Schema::table('shipments', function (Blueprint $table): void {
                $table->dropForeign(['warehouse_id']);
                $table->dropColumn('warehouse_id');
            });
        }

        if (Schema::hasTable('shipment_tasks') && Schema::hasColumn('shipment_tasks', 'origin_warehouse_id')) {
            Schema::table('shipment_tasks', function (Blueprint $table): void {
                $table->dropForeign(['origin_warehouse_id']);
                $table->dropColumn('origin_warehouse_id');
            });
        }
    }
};
