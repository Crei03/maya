<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogPostImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostService
{
    public function create(array $data, array $images = []): BlogPost
    {
        $data['slug'] = $this->generateUniqueSlug($data['title']);

        if (!empty($data['is_published'])) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        $post = BlogPost::create($data);

        $this->saveImages($post, $images);

        return $post;
    }

    public function update(BlogPost $post, array $data, array $images = [], array $deletedImageIds = []): BlogPost
    {
        if (!empty($data['title']) && $data['title'] !== $post->title) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $post->id);
        }

        if (!empty($data['is_published']) && !$post->is_published) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        $post->update($data);

        $this->deleteImages($deletedImageIds);
        $this->saveImages($post, $images);

        return $post;
    }

    private function saveImages(BlogPost $post, array $images): void
    {
        $sortOrder = $post->images()->max('sort_order') ?? 0;

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $path = Storage::disk('public')->putFile(
                    'blog/' . $post->id,
                    $image
                );

                $sortOrder++;

                BlogPostImage::create([
                    'blog_post_id' => $post->id,
                    'path' => $path,
                    'original_name' => $image->getClientOriginalName(),
                    'sort_order' => $sortOrder,
                ]);
            }
        }
    }

    private function deleteImages(array $deletedImageIds): void
    {
        if (empty($deletedImageIds)) {
            return;
        }

        $images = BlogPostImage::whereIn('id', $deletedImageIds)->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
    }

    public function togglePublish(BlogPost $post): BlogPost
    {
        if ($post->is_published) {
            $post->update([
                'is_published' => false,
            ]);
        } else {
            $post->update([
                'is_published' => true,
                'published_at' => now(),
            ]);
        }

        return $post->refresh();
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }

    private function generateUniqueSlug(string $title, ?string $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 2;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?string $excludeId = null): bool
    {
        $query = BlogPost::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
