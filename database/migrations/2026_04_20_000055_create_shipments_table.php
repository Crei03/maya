<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipments')) {
            return;
        }

        Schema::create('shipments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tracking_number')->unique();
            $table->uuid('sender_id')->nullable();
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('origin_address');
            $table->string('destination_address');
            $table->json('destination_coords')->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('total_cost', 8, 2)->nullable();
            $table->unsignedBigInteger('current_status_id')->nullable();
            $table->string('label_url')->nullable();
            $table->dateTime('eta')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('id')->on('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
