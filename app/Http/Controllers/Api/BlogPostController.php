<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::with('images')
            ->published()
            ->recent()
            ->paginate(15);

        // Mutate: add absolute URLs for images and featured_image
        $posts->getCollection()->transform(function ($post) {
            return $this->enrichPost($post);
        });

        return response()->json($posts);
    }

    public function show(string $slug)
    {
        $post = BlogPost::with('images')
            ->published()
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            abort(404);
        }

        $related = BlogPost::with('images')
            ->published()
            ->recent()
            ->where('id', '!=', $post->id)
            ->limit(3)
            ->get()
            ->map(fn ($p) => $this->enrichPost($p));

        return response()->json([
            'data' => $this->enrichPost($post),
            'related' => $related,
        ]);
    }

    private function enrichPost($post): array
    {
        $data = $post->toArray();

        // Convert featured_image_url if it's a relative path
        if (!empty($data['featured_image_url']) && !str_starts_with($data['featured_image_url'], 'http')) {
            $data['featured_image_url'] = Storage::disk('public')->url($data['featured_image_url']);
        }

        // Add absolute URLs to each image
        if (isset($data['images'])) {
            foreach ($data['images'] as &$image) {
                if (isset($image['path']) && !str_starts_with($image['path'], 'http')) {
                    $image['url'] = Storage::disk('public')->url($image['path']);
                }
            }
        }

        return $data;
    }
}
