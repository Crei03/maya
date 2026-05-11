<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('catalogo_valores', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable();
            $table->boolean('is_global')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->index('tenant_id');
        });

        DB::table('catalogo_valores')->update([
            'is_global' => true,
            'tenant_id' => null,
        ]);

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('catalogo_valores', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['tenant_id', 'is_global', 'sort_order', 'is_active']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
