<?php

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
        $tables = [
            'users', 'clients', 'shipments', 'manifests', 'manifest_items',
            'tracking_events', 'delivery_proofs', 'service_ratings', 'incidents',
            'pickup_requests', 'audit_logs', 'column_preferences',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->uuid('tenant_id')->nullable(false)->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users', 'clients', 'shipments', 'manifests', 'manifest_items',
            'tracking_events', 'delivery_proofs', 'service_ratings', 'incidents',
            'pickup_requests', 'audit_logs', 'column_preferences',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->uuid('tenant_id')->nullable()->change();
                });
            }
        }
    }
};
