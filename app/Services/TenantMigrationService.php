<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TenantMigrationService
{
    /**
     * Validate that all records in tenant-scoped tables have a tenant_id.
     */
    public function validateBackfill(string $tenantId): bool
    {
        $tables = [
            'users', 'clients', 'shipments', 'manifests', 'manifest_items',
            'tracking_events', 'delivery_proofs', 'service_ratings', 'incidents',
            'pickup_requests', 'audit_logs', 'column_preferences',
        ];

        foreach ($tables as $table) {
            $missing = DB::table($table)->whereNull('tenant_id')->count();
            if ($missing > 0) {
                return false;
            }
        }

        return true;
    }
}
