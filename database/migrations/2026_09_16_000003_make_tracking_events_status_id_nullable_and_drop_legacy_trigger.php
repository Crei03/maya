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
        // 1. Drop obsolete legacy trigger that refers to dropped column current_status_id
        DB::unprepared('DROP TRIGGER IF EXISTS after_tracking_event_insert');

        // 2. Make status_id nullable on tracking_events
        if (Schema::hasTable('tracking_events') && Schema::hasColumn('tracking_events', 'status_id')) {
            try {
                Schema::table('tracking_events', function (Blueprint $table): void {
                    $table->dropForeign('fk_tracking_status');
                });
            } catch (\Throwable) {
                // Constraint may not exist
            }

            Schema::table('tracking_events', function (Blueprint $table): void {
                $table->unsignedBigInteger('status_id')->nullable()->change();
            });

            try {
                Schema::table('tracking_events', function (Blueprint $table): void {
                    $table->foreign('status_id', 'fk_tracking_status')
                        ->references('id')
                        ->on('catalogo_valores')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
                // Constraint could not be re-added or not supported
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not restoring obsolete trigger
    }
};
