<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');
            $table->string('canonical_url', 500)->nullable()->after('og_image');
            $table->json('structured_data')->nullable()->after('canonical_url');
            $table->integer('reading_time')->nullable()->after('structured_data');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'og_title',
                'og_description',
                'og_image',
                'canonical_url',
                'structured_data',
                'reading_time',
            ]);
        });
    }
};
