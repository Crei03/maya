<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogos', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->boolean('is_global')->default(false)->after('tenant_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('slug');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('tenant_id');
        });

        DB::table('catalogos')->update([
            'is_global' => true,
            'tenant_id' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('catalogos', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['tenant_id', 'is_global', 'created_by']);
        });
    }
};
