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
        Schema::table('shipments', function (Blueprint $table): void {
            if (! Schema::hasColumn('shipments', 'reference_type')) {
                $table->string('reference_type', 50)->nullable()->after('warehouse_id')
                    ->comment('Tipo de documento WMS: pedido, factura, transferencia, recibo, guia, lpn, otro');
            }

            if (! Schema::hasColumn('shipments', 'reference_number')) {
                $table->string('reference_number', 100)->nullable()->index()->after('reference_type')
                    ->comment('Número de documento de referencia WMS/ERP (Pedido, Factura, Transferencia)');
            }

            if (! Schema::hasColumn('shipments', 'lpn_code')) {
                $table->string('lpn_code', 100)->nullable()->index()->after('reference_number')
                    ->comment('Código de LPN o Pallet del WMS');
            }

            if (! Schema::hasColumn('shipments', 'pieces_count')) {
                $table->unsignedSmallInteger('pieces_count')->default(1)->after('lpn_code')
                    ->comment('Cantidad de bultos o piezas físicas amparadas por este envío');
            }

            if (! Schema::hasColumn('shipments', 'recipient_name')) {
                $table->string('recipient_name', 255)->nullable()->after('sender_id')
                    ->comment('Nombre del destinatario directo para última milla');
            }

            if (! Schema::hasColumn('shipments', 'recipient_phone')) {
                $table->string('recipient_phone', 50)->nullable()->after('recipient_name')
                    ->comment('Teléfono del destinatario para contacto en ruta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $columnsToDrop = array_filter(
                ['reference_type', 'reference_number', 'lpn_code', 'pieces_count', 'recipient_name', 'recipient_phone'],
                fn (string $col): bool => Schema::hasColumn('shipments', $col)
            );

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
