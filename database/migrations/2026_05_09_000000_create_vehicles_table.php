<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('license_plate');
            $table->enum('type', ['internal', 'external']);
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->decimal('capacity_kg', 8, 2)->nullable();
            $table->string('capacity_volume')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Placa única por tenant
            $table->unique(['tenant_id', 'license_plate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
