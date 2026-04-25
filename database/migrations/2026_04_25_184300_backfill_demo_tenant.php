<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create default Demo tenant
        $demoTenantId = (string) Str::uuid();
        
        DB::table('tenants')->insert([
            'id' => $demoTenantId,
            'name' => 'Demo Paqueteria',
            'slug' => 'demo',
            'contact_email' => 'demo@maya.app',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Backfill all existing data to Demo tenant
        $tables = [
            'users',
            'clients',
            'shipments',
            'manifests',
            'manifest_items',
            'tracking_events',
            'delivery_proofs',
            'service_ratings',
            'incidents',
            'pickup_requests',
            'audit_logs',
            'column_preferences',
        ];

        foreach ($tables as $table) {
            DB::table($table)->update(['tenant_id' => $demoTenantId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset tenant_id to null
        $tables = [
            'users', 'clients', 'shipments', 'manifests', 'manifest_items',
            'tracking_events', 'delivery_proofs', 'service_ratings', 'incidents',
            'pickup_requests', 'audit_logs', 'column_preferences',
        ];

        foreach ($tables as $table) {
            DB::table($table)->update(['tenant_id' => null]);
        }

        // Remove Demo tenant
        DB::table('tenants')->where('slug', 'demo')->delete();
    }
};
