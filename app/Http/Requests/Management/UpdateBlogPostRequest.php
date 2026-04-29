<?php

declare(strict_types=1);

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManagement() ?? false;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('blog_posts', 'slug')->ignore($postId),
            ],
            'content' => ['sometimes', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'author' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'structured_data' => ['nullable'],
            'reading_time' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'deleted_image_ids' => ['nullable', 'array'],
            'deleted_image_ids.*' => ['string', 'exists:blog_post_images,id'],
        ];
    }
}
