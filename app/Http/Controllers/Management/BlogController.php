<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Automation\BlogSeoAnalyzer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreBlogPostRequest;
use App\Http\Requests\Management\UpdateBlogPostRequest;
use App\Models\BlogPost;
use App\Services\BlogPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogPostService $blogPostService
    ) {
    }

    public function index(Request $request): Response
    {
        $posts = BlogPost::query()
            ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($request->status, function ($q, $status) {
                if ($status === 'published') {
                    return $q->where('is_published', true);
                }
                if ($status === 'draft') {
                    return $q->where('is_published', false);
                }
                return $q;
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Management/Blog/Index', [
            'posts' => $posts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Management/Blog/Create');
    }

    public function store(StoreBlogPostRequest $request)
    {
        $post = $this->blogPostService->create(
            $request->safe()->except(['images', 'deleted_image_ids']),
            $request->file('images', [])
        );

        return redirect()->route('Management.blog.index')
            ->with('success', "Post '{$post->title}' created successfully.");
    }

    public function edit(BlogPost $post): Response
    {
        return Inertia::render('Management/Blog/Edit', [
            'post' => $post->load('images'),
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post)
    {
        $this->blogPostService->update(
            $post,
            $request->safe()->except(['images', 'deleted_image_ids']),
            $request->file('images', []),
            $request->input('deleted_image_ids', [])
        );

        return redirect()->route('Management.blog.index')
            ->with('success', "Post '{$post->title}' updated successfully.");
    }

    public function destroy(BlogPost $post)
    {
        $this->blogPostService->delete($post);

        return redirect()->route('Management.blog.index')
            ->with('success', 'Post deleted successfully.');
    }

    public function publish(BlogPost $post)
    {
        $this->blogPostService->togglePublish($post);

        $status = $post->is_published ? 'published' : 'unpublished';

        return redirect()->route('Management.blog.index')
            ->with('success', "Post '{$post->title}' {$status}.");
    }

    public function analyzeSeo(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $analyzer = new BlogSeoAnalyzer($request->content, $request->title);

        return response()->json($analyzer->analyze());
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $file = $request->file('image');
        $path = $file->store('blog/uploads', 'public');

        if (!$path) {
            return response()->json(['error' => 'No se pudo guardar la imagen.'], 500);
        }

        $url = Storage::disk('public')->url($path);

        return response()->json([
            'url' => $url,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }
}
