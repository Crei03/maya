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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->index()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
