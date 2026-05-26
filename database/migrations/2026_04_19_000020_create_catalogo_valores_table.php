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
        Schema::create('catalogo_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_id')->constrained('catalogos');
            $table->foreignId('parent_id')->nullable()->constrained('catalogo_valores');
            $table->string('codigo', 100);
            $table->string('valor', 255);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['catalogo_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_valores');
    }
};
