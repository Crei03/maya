<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('shipment_task_items') && ! Schema::hasColumn('shipment_task_items', 'notes')) {
            Schema::table('shipment_task_items', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('return_reason');
            });
        }

        if (! Schema::hasTable('delivery_proofs')) {
            Schema::create('delivery_proofs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('shipment_id');
                $table->string('signature_url')->nullable();
                $table->string('photo_url')->nullable();
                $table->json('photos')->nullable();
                $table->json('gps_coords')->nullable();
                $table->string('signed_by')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('delivery_proofs', 'photos')) {
            Schema::table('delivery_proofs', function (Blueprint $table) {
                $table->json('photos')->nullable()->after('photo_url');
            });
        }

        // Add EN_CURSO and PAUSA to catalogo_valores for catalogo slug 'estado-item-tarea' (id 17)
        $catalogo = DB::table('catalogos')->where('slug', 'estado-item-tarea')->first();
        if ($catalogo) {
            $existing = DB::table('catalogo_valores')
                ->where('catalogo_id', $catalogo->id)
                ->pluck('codigo')
                ->toArray();

            $now = now();

            if (! in_array('EN_CURSO', $existing)) {
                DB::table('catalogo_valores')->insert([
                    'catalogo_id' => $catalogo->id,
                    'codigo' => 'EN_CURSO',
                    'valor' => 'En Curso',
                    'descripcion' => 'Entrega actualmente en camino',
                    'metadata' => json_encode([
                        'color' => 'sky',
                        'badge' => 'bg-sky-50 text-sky-700 border border-sky-200 ring-sky-100',
                        'es_final' => false,
                    ]),
                    'is_global' => true,
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (! in_array('PAUSA', $existing)) {
                DB::table('catalogo_valores')->insert([
                    'catalogo_id' => $catalogo->id,
                    'codigo' => 'PAUSA',
                    'valor' => 'En Pausa',
                    'descripcion' => 'Entrega pausada temporalmente por el transportista',
                    'metadata' => json_encode([
                        'color' => 'purple',
                        'badge' => 'bg-purple-50 text-purple-700 border border-purple-200 ring-purple-100',
                        'es_final' => false,
                    ]),
                    'is_global' => true,
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (! in_array('DEVUELTO', $existing)) {
                DB::table('catalogo_valores')->insert([
                    'catalogo_id' => $catalogo->id,
                    'codigo' => 'DEVUELTO',
                    'valor' => 'Devuelto',
                    'descripcion' => 'Entrega devuelta o no completada',
                    'metadata' => json_encode([
                        'color' => 'rose',
                        'badge' => 'bg-rose-50 text-rose-700 border border-rose-200 ring-rose-100',
                        'es_final' => true,
                    ]),
                    'is_global' => true,
                    'is_active' => true,
                    'sort_order' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Update existing PENDIENTE, ENTREGADO and RETORNADO metadata with pastel styling
            DB::table('catalogo_valores')
                ->where('catalogo_id', $catalogo->id)
                ->where('codigo', 'PENDIENTE')
                ->update([
                    'metadata' => json_encode([
                        'color' => 'amber',
                        'badge' => 'bg-amber-50 text-amber-700 border border-amber-200 ring-amber-100',
                        'es_final' => false,
                    ]),
                ]);

            DB::table('catalogo_valores')
                ->where('catalogo_id', $catalogo->id)
                ->where('codigo', 'ENTREGADO')
                ->update([
                    'metadata' => json_encode([
                        'color' => 'emerald',
                        'badge' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 ring-emerald-100',
                        'es_final' => true,
                    ]),
                ]);

            DB::table('catalogo_valores')
                ->where('catalogo_id', $catalogo->id)
                ->where('codigo', 'RETORNADO')
                ->update([
                    'metadata' => json_encode([
                        'color' => 'rose',
                        'badge' => 'bg-rose-50 text-rose-700 border border-rose-200 ring-rose-100',
                        'es_final' => true,
                    ]),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('shipment_task_items') && Schema::hasColumn('shipment_task_items', 'notes')) {
            Schema::table('shipment_task_items', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }

        if (Schema::hasTable('delivery_proofs') && Schema::hasColumn('delivery_proofs', 'photos')) {
            Schema::table('delivery_proofs', function (Blueprint $table) {
                $table->dropColumn('photos');
            });
        }

        $catalogo = DB::table('catalogos')->where('slug', 'estado-item-tarea')->first();
        if ($catalogo) {
            DB::table('catalogo_valores')
                ->where('catalogo_id', $catalogo->id)
                ->whereIn('codigo', ['EN_CURSO', 'PAUSA', 'DEVUELTO'])
                ->delete();
        }
    }
};
