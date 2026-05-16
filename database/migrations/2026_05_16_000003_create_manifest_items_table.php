<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifest_items', function (Blueprint $table): void {
            $table->uuid('tenant_id');
            $table->uuid('manifest_id');
            $table->uuid('shipment_id');
            $table->integer('stop_order')->default(0);
            $table->boolean('is_delivered')->default(false);
            $table->timestamps();

            $table->primary(['manifest_id', 'shipment_id']);
            $table->foreign('manifest_id')->references('id')->on('manifests')->cascadeOnDelete();
            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifest_items');
    }
};
