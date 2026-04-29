<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'blog_posts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image_url',
        'author',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'structured_data',
        'reading_time',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'structured_data' => 'array',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(BlogPostImage::class)->orderBy('sort_order');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BlogPost $post) {
            if (empty($post->id)) {
                $post->id = (string) Str::uuid();
            }
        });
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_published', true)
            ->where('published_at', '<=', now());
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('published_at', 'desc');
    }
}
