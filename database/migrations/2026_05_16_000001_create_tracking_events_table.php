<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tracking_events')) {
            return;
        }

        Schema::create('tracking_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('shipment_id');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('location_name')->nullable();
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
