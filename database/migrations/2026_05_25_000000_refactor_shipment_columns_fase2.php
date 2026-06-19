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
        Schema::table('shipments', function (Blueprint $table): void {
            // 1. Drop FK on assigned_task_id before renaming
            $table->dropForeign(['assigned_task_id']);
        });

        Schema::table('shipments', function (Blueprint $table): void {
            // 2. Rename column
            $table->renameColumn('assigned_task_id', 'driver_task');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            // 3. Re-add FK referencing shipment_tasks
            $table->foreign('driver_task')
                ->references('id')
                ->on('shipment_tasks')
                ->nullOnDelete();
        });

        // 4. Backfill weight_lb from weight_kg for rows where weight_lb IS NULL
        DB::statement('UPDATE shipments SET weight_lb = ROUND(weight_kg * 2.20462, 2) WHERE weight_lb IS NULL AND weight_kg IS NOT NULL');

        Schema::table('shipments', function (Blueprint $table): void {
            // 5. Modify weight columns: weight_lb → NOT NULL, weight_kg → nullable
            $table->decimal('weight_lb', 8, 2)->nullable(false)->change();
            $table->decimal('weight_kg', 8, 2)->nullable()->change();

            // 6. Drop columns no longer needed
            $table->dropColumn(['recipient_name', 'recipient_phone', 'origin_address', 'current_status_id']);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            // Re-add dropped columns as nullable
            $table->string('recipient_name')->nullable()->after('sender_id');
            $table->string('recipient_phone')->nullable()->after('recipient_name');
            $table->string('origin_address')->nullable()->after('recipient_phone');
            $table->unsignedBigInteger('current_status_id')->nullable()->after('status');

            // Reverse weight changes
            $table->decimal('weight_kg', 8, 2)->nullable(false)->change();
            $table->decimal('weight_lb', 8, 2)->nullable()->change();

            // Drop FK on driver_task before renaming back
            $table->dropForeign(['driver_task']);
        });

        Schema::table('shipments', function (Blueprint $table): void {
            // Rename back
            $table->renameColumn('driver_task', 'assigned_task_id');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            // Re-add original FK
            $table->foreign('assigned_task_id')
                ->references('id')
                ->on('shipment_tasks')
                ->nullOnDelete();
        });
    }
};
