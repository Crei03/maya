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
        // 1. Agregar columnas
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('ownership_type_id')->nullable()->after('license_plate')->constrained('catalogo_valores');
            $table->foreignId('vehicle_class_id')->nullable()->after('ownership_type_id')->constrained('catalogo_valores');
        });

        // 2. Backfill
        $propCat = DB::table('catalogos')->where('slug', 'tipo-vehiculo-propiedad')->first();
        if ($propCat) {
            $propMap = DB::table('catalogo_valores')
                ->where('catalogo_id', $propCat->id)
                ->pluck('id', 'codigo');

            if (isset($propMap['INTERNO'])) {
                DB::table('vehicles')->where('type', 'internal')->update(['ownership_type_id' => $propMap['INTERNO']]);
            }
            if (isset($propMap['EXTERNO'])) {
                DB::table('vehicles')->where('type', 'external')->update(['ownership_type_id' => $propMap['EXTERNO']]);
            }
            if (isset($propMap['INTERNO'])) {
                DB::table('vehicles')->whereNull('ownership_type_id')->update(['ownership_type_id' => $propMap['INTERNO']]);
            }
        }

        // 3. Drop enum type
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('type', ['internal', 'external'])->default('internal')->after('license_plate');
            $table->dropForeign(['ownership_type_id']);
            $table->dropForeign(['vehicle_class_id']);
            $table->dropColumn(['ownership_type_id', 'vehicle_class_id']);
        });
    }
};
