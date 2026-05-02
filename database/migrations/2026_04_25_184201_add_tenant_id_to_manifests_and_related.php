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
        if (Schema::hasTable('manifests')) {
            Schema::table('manifests', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->index()->after('id');
            });
        }

        if (Schema::hasTable('manifest_items')) {
            Schema::table('manifest_items', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->index()->after('manifest_id');
            });
        }

        if (Schema::hasTable('tracking_events')) {
            Schema::table('tracking_events', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->index()->after('id');
            });
        }

        if (Schema::hasTable('delivery_proofs')) {
            Schema::table('delivery_proofs', function (Blueprint $table) {
                $table->uuid('tenant_id')->nullable()->index()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('manifests') && Schema::hasColumn('manifests', 'tenant_id')) {
            Schema::table('manifests', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('manifest_items') && Schema::hasColumn('manifest_items', 'tenant_id')) {
            Schema::table('manifest_items', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('tracking_events') && Schema::hasColumn('tracking_events', 'tenant_id')) {
            Schema::table('tracking_events', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('delivery_proofs') && Schema::hasColumn('delivery_proofs', 'tenant_id')) {
            Schema::table('delivery_proofs', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }
};
