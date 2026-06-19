<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('manifests')) {
            return;
        }

        Schema::create('manifests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('messenger_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('vehicle_id')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifests');
    }
};
