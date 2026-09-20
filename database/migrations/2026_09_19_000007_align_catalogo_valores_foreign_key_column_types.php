<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. manifests.status_id -> unsignedBigInteger nullable
        if (Schema::hasTable('manifests') && Schema::hasColumn('manifests', 'status_id')) {
            try {
                Schema::table('manifests', function (Blueprint $table): void {
                    $table->dropForeign('fk_manifest_status');
                });
            } catch (\Throwable) {
                //
            }

            Schema::table('manifests', function (Blueprint $table): void {
                $table->unsignedBigInteger('status_id')->nullable()->change();
            });

            try {
                Schema::table('manifests', function (Blueprint $table): void {
                    $table->foreign('status_id', 'fk_manifest_status')
                        ->references('id')
                        ->on('catalogo_valores')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
                //
            }
        }

        // 2. pickup_requests.status_id -> unsignedBigInteger nullable
        if (Schema::hasTable('pickup_requests') && Schema::hasColumn('pickup_requests', 'status_id')) {
            try {
                Schema::table('pickup_requests', function (Blueprint $table): void {
                    $table->dropForeign('fk_pickup_status');
                });
            } catch (\Throwable) {
                //
            }

            Schema::table('pickup_requests', function (Blueprint $table): void {
                $table->unsignedBigInteger('status_id')->nullable()->change();
            });

            try {
                Schema::table('pickup_requests', function (Blueprint $table): void {
                    $table->foreign('status_id', 'fk_pickup_status')
                        ->references('id')
                        ->on('catalogo_valores')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
                //
            }
        }

        // 3. incidents.type_id -> unsignedBigInteger
        if (Schema::hasTable('incidents') && Schema::hasColumn('incidents', 'type_id')) {
            try {
                Schema::table('incidents', function (Blueprint $table): void {
                    $table->dropForeign('fk_incident_type');
                });
            } catch (\Throwable) {
                //
            }

            Schema::table('incidents', function (Blueprint $table): void {
                $table->unsignedBigInteger('type_id')->change();
            });

            try {
                Schema::table('incidents', function (Blueprint $table): void {
                    $table->foreign('type_id', 'fk_incident_type')
                        ->references('id')
                        ->on('catalogo_valores')
                        ->restrictOnDelete();
                });
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
