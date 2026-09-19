<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_valores', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_valores', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
