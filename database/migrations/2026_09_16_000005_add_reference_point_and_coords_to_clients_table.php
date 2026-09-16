<?php

declare(strict_types=1);

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
        Schema::table('clients', function (Blueprint $table): void {
            if (! Schema::hasColumn('clients', 'reference_point')) {
                $table->string('reference_point', 255)->nullable()->after('street_number');
            }
            if (! Schema::hasColumn('clients', 'destination_coords')) {
                $table->json('destination_coords')->nullable()->after('reference_point');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            if (Schema::hasColumn('clients', 'destination_coords')) {
                $table->dropColumn('destination_coords');
            }
            if (Schema::hasColumn('clients', 'reference_point')) {
                $table->dropColumn('reference_point');
            }
        });
    }
};
