<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #3 - [Refactor] Refactorizar modelo Shipment y limpiar datos mock existentes.
 *
 * Agrega campos logísticos faltantes a la tabla shipments:
 * - Descripción e información física del paquete
 * - Tipo de paquete y dimensiones
 * - Referencia a la tarea de reparto asignada
 * - Campos de entrega (fecha, foto, firma)
 * - Sistema de estados concreto (enum)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            // --- Información del paquete ---
            $table->text('content_description')->nullable()->after('label_url')
                ->comment('Descripción del contenido del paquete');

            $table->decimal('weight_lb', 8, 2)->nullable()->after('weight_kg')
                ->comment('Peso en libras (adicional a kg)');

            $table->string('package_type')->nullable()->after('content_description')
                ->comment('Tipo de paquete: caja, sobre, palet, etc.');

            $table->json('dimensions')->nullable()->after('package_type')
                ->comment('Dimensiones en cm: {largo, ancho, alto}');

            // --- Tarea de reparto asignada (Issue #2 - ShipmentTask) ---
            $table->uuid('assigned_task_id')->nullable()->index()->after('warehouse_id')
                ->comment('FK a shipment_tasks - tarea de conductor asignada');

            $table->foreign('assigned_task_id')
                ->references('id')
                ->on('shipment_tasks')
                ->nullOnDelete();

            // --- Campos de entrega ---
            $table->dateTime('delivered_at')->nullable()->after('eta')
                ->comment('Fecha y hora real de entrega');

            $table->string('delivered_photo_url')->nullable()->after('delivered_at')
                ->comment('URL de la foto de evidencia de entrega');

            $table->string('recipient_signature_url')->nullable()->after('delivered_photo_url')
                ->comment('URL de la firma del destinatario');

            // --- Sistema de estados concreto ---
            $table->string('status', 20)->default('pending')->index()->after('current_status_id')
                ->comment('Estado concreto del envío: pending, in_warehouse, assigned, in_transit, delivered, returned, failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropForeign(['assigned_task_id']);

            $table->dropColumn([
                'content_description',
                'weight_lb',
                'package_type',
                'dimensions',
                'assigned_task_id',
                'delivered_at',
                'delivered_photo_url',
                'recipient_signature_url',
                'status',
            ]);
        });
    }
};
