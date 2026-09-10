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
        Schema::table('shipment_task_items', function (Blueprint $table): void {
            $table->enum('priority', ['alta', 'media', 'baja'])->default('media')->after('status');
            $table->unsignedInteger('stop_order')->default(1)->after('priority');
            $table->index(['shipment_task_id', 'stop_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_task_items', function (Blueprint $table): void {
            $table->dropIndex(['shipment_task_id', 'stop_order']);
            $table->dropColumn(['priority', 'stop_order']);
        });
    }
};
