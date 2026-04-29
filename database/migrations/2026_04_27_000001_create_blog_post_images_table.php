<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->integer('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();
            $table->index('blog_post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_images');
    }
};
