<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostImage extends Model
{
    protected $table = 'blog_post_images';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'blog_post_id',
        'path',
        'original_name',
        'sort_order',
        'alt_text',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BlogPostImage $image) {
            if (empty($image->id)) {
                $image->id = (string) Str::uuid();
            }
        });
    }

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
