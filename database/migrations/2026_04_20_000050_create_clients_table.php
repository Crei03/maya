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
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('full_name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->string('role', 32)->default('client');
                $table->string('phone', 20)->nullable();
                $table->string('business_name')->nullable();

                $table->unsignedBigInteger('residencia_id')->nullable();
                $table->unsignedBigInteger('provincia_id')->nullable();
                $table->unsignedBigInteger('distrito_id')->nullable();
                $table->unsignedBigInteger('corregimiento_id')->nullable();
                $table->unsignedBigInteger('calle_id')->nullable();
                $table->string('street_name', 120)->nullable();
                $table->string('street_number', 40)->nullable();
                $table->string('postal_code', 20)->nullable();

                $table->string('status', 32)->default('active');
                $table->string('avatar_url')->nullable();
                $table->rememberToken();
                $table->timestamps();

                $table->index('role');
                $table->index('status');
                $table->index(['residencia_id', 'provincia_id', 'distrito_id', 'corregimiento_id', 'calle_id'], 'clients_geo_idx');
            });

            return;
        }

        Schema::table('clients', function (Blueprint $table): void {
            if (! Schema::hasColumn('clients', 'corregimiento_id')) {
                $table->unsignedBigInteger('corregimiento_id')->nullable()->after('distrito_id');
            }

            if (! Schema::hasColumn('clients', 'street_name')) {
                $table->string('street_name', 120)->nullable()->after('calle_id');
            }

            if (! Schema::hasColumn('clients', 'updated_at')) {
                $table->timestamps();
            }

            $table->index(['residencia_id', 'provincia_id', 'distrito_id', 'corregimiento_id', 'calle_id'], 'clients_geo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
