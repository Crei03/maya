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
        if (Schema::hasTable('tracking_events')) {
            Schema::table('tracking_events', function (Blueprint $table): void {
                if (! Schema::hasColumn('tracking_events', 'created_at')) {
                    $table->timestamp('created_at')->nullable()->useCurrent();
                }
                if (! Schema::hasColumn('tracking_events', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                }
            });
        }

        foreach (['delivery_proofs', 'service_ratings', 'incidents', 'manifests'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'updated_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tracking_events')) {
            Schema::table('tracking_events', function (Blueprint $table): void {
                if (Schema::hasColumn('tracking_events', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
                if (Schema::hasColumn('tracking_events', 'created_at')) {
                    $table->dropColumn('created_at');
                }
            });
        }

        foreach (['delivery_proofs', 'service_ratings', 'incidents', 'manifests'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'updated_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('updated_at');
                });
            }
        }
    }
};
