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
        Schema::table('service_ratings', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });

        Schema::table('column_preferences', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_ratings', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('column_preferences', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
