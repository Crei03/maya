<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogos', function (Blueprint $table) {
            if (! Schema::hasColumn('catalogos', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('catalogos', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
            if (! Schema::hasColumn('catalogos', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('catalogos', function (Blueprint $table) {
            $cols = array_filter(['description', 'is_active', 'sort_order'], fn ($col) => Schema::hasColumn('catalogos', $col));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
