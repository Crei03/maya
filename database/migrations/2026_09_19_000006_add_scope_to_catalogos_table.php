<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogos', function (Blueprint $table) {
            if (! Schema::hasColumn('catalogos', 'scope')) {
                $table->string('scope', 50)->default('paqueteria')->after('slug')->index();
            }
        });

        // Ensure platform/saas catalogs are isolated
        DB::table('catalogos')
            ->whereIn('slug', ['estado-tenant'])
            ->update(['scope' => 'saas']);
    }

    public function down(): void
    {
        Schema::table('catalogos', function (Blueprint $table) {
            if (Schema::hasColumn('catalogos', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
